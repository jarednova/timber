<?php

	class Timber_UnitTestCase extends WP_UnitTestCase {
		/**
		 * Maintain a list of action/filter hook removals to perform at the end of each test.
		 */
		private $temporary_hook_removals = [];

		/**
		 * Overload WP_UnitTestcase to ignore deprecated notices
		 * thrown by use of wp_title() in Timber
		 */
		public function expectedDeprecated() {
		    if ( false !== ( $key = array_search( 'wp_title', $this->caught_deprecated ) ) ) {
		        unset( $this->caught_deprecated[ $key ] );
		    }
		    parent::expectedDeprecated();
		}

		public static function enable_error_log( $opt = true ) {
			global $timber_disable_error_log;
			$timber_disable_error_log = !$opt;
		}

		public static function setPermalinkStructure( $struc = '/%postname%/' ) {
			global $wp_rewrite;
			$wp_rewrite->set_permalink_structure( $struc );
			$wp_rewrite->flush_rules();
			update_option( 'permalink_structure', $struc );
			flush_rewrite_rules( true );
		}

		function tearDown() {
			self::resetPermalinks();
			parent::tearDown();
			Timber::$context_cache = array();

			// remove any hooks added during this test run
			foreach ($this->temporary_hook_removals as $callback) {
				$callback();
			}
			// reset hooks
			$this->temporary_hook_removals = [];
		}

		function resetPermalinks() {
			delete_option( 'permalink_structure' );
			global $wp_rewrite;
			$wp_rewrite->set_permalink_structure( false );
			$wp_rewrite->init();
			$wp_rewrite->flush_rules();
			flush_rewrite_rules( true );
		}

		function setupCustomWPDirectoryStructure() {
			add_filter('content_url', [$this, 'setContentUrl']);
			add_filter('option_upload_path', [$this, 'setUploadPath']);
			add_filter('option_upload_url_path', [$this, 'setUploadUrlPath']);
			add_filter('option_siteurl', [$this, 'setSiteUrl']);
			add_filter('option_home_url', [$this, 'setHomeUrl']);
		}

		function tearDownCustomWPDirectoryStructure() {
			remove_filter('content_url', [$this, 'setContentUrl']);
			remove_filter('option_upload_path', [$this, 'setUploadPath']);
			remove_filter('option_upload_url_path', [$this, 'setUploadUrlPath']);
			remove_filter('option_siteurl', [$this, 'setSiteUrl']);
			remove_filter('option_home_url', [$this, 'setHomeUrl']);
		}

		function setContentUrl($url) {
			return 'http://' . $_SERVER['HTTP_HOST'] . '/content';
		}

		function setUploadPath($dir) {
			return $_SERVER['DOCUMENT_ROOT'] .'content/uploads';
		}

		function setUploadUrlPath($dir) {
			return 'http://' . $_SERVER['HTTP_HOST'] . '/content/uploads';
		}

		function setSiteUrl($url) {
			return 'http://' . $_SERVER['HTTP_HOST'] . '/wp';
		}

		function clearPosts() {
			global $wpdb;
			$wpdb->query("TRUNCATE TABLE $wpdb->posts;");
		}

		/**
		 * Installs a translation.
		 *
		 * Deletes already installed translation first.
		 *
		 * @param string $locale The locale to install.
		 */
		public static function install_translation( $locale ) {
			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

			self::uninstall_translation( $locale );
			wp_download_language_pack( $locale );
		}

		/**
		 * Deletes translation files for a locale.
		 *
		 * Logic borrowed from WP CLI Language Command
		 *
		 * @link https://github.com/wp-cli/language-command/blob/master/src/Core_Language_Command.php
		 *
		 * @param string $locale The locale to delete.
		 *
		 * @return bool Whether the locale was deleted.
		 */
		public static function uninstall_translation( $locale ) {
			global $wp_filesystem;

			$available    = wp_get_installed_translations( 'core' );
			$translations = array_keys( $available['default'] );

			if ( ! in_array( $locale, $translations, true ) ) {
				return false;
			}

			$files = scandir( WP_LANG_DIR );

			if ( ! $files ) {
				return false;
			}

			$current_locale = get_locale();
			if ( $locale === $current_locale ) {
				// Language is active.
				return true;
			}

			// As of WP 4.0, no API for deleting a language pack
			WP_Filesystem();
			$deleted = false;
			foreach ( $files as $file ) {

				if ( '.' === $file[0] || is_dir( $file ) ) {
					continue;
				}

				$extension_length = strlen( $locale ) + 4;
				$ending           = substr( $file, -$extension_length );
				$starting = substr( $file, 0, strlen( $locale ) );

				if ( ! in_array( $file, [ $locale . '.po', $locale . '.mo' ], true )
					&& ! in_array( $ending, [ '-' . $locale . '.po', '-' . $locale . '.mo' ], true )
					&& $locale !== $starting
				) {
					continue;
				}

				/** @var WP_Filesystem_Base $wp_filesystem */
				$deleted = $wp_filesystem->delete( trailingslashit( WP_LANG_DIR ) . $file );
			}

			if ( $deleted ) {
				return true;
			}

			return false;
		}

		/**
		 * Changes to a different locale.
		 *
		 * The translations for the locale you might want to use maybe don’t exist yet. You will
		 * have to download it first through wp_download_language_pack(). Check the bootstrap.php
		 * file to see how it works.
		 *
		 * After you used this function in a test, don’t forget to restore the current locale using
		 * $this->restore_locale().
		 *
		 * @see \Timber_UnitTestCase::restore_locale()
		 *
		 * @param string $locale The locale to switch to.
		 */
		function change_locale( $locale ) {
			// Check if the translation is already installed.
			if ( ! in_array( $locale, get_available_languages() ) ) {
				self::install_translation( $locale );
			}

			switch_to_locale( $locale );
		}

		/**
		 * Restores the locale after it was changed by $this->change_locale().
		 *
		 * @see \Timber_UnitTestCase::change_locale()
		 */
		function restore_locale() {
			restore_current_locale();
		}

		function truncate(string $table) {
			global $wpdb;
			$wpdb->query("TRUNCATE TABLE {$wpdb->$table}");
		}

		/**
		 * Exactly the same as add_filter, but automatically calls remove_filter with the same
		 * arguments during tearDown().
		 */
		protected function add_filter_temporarily(string $filter, callable $callback, int $pri = 10, int $count = 1) {
			add_filter($filter, $callback, $pri, $count);
			$this->temporary_hook_removals[] = function() use ($filter, $callback, $pri, $count) {
				remove_filter($filter, $callback, $pri, $count);
			};
		}
    
    /**
		 * Exactly the same as add_action, but automatically calls remove_action with the same
		 * arguments during tearDown().
		 */
		protected function add_action_temporarily(string $action, callable $callback, int $pri = 10, int $count = 1) {
			add_action($action, $callback, $pri, $count);
			$this->temporary_hook_removals[] = function() use ($action, $callback, $pri, $count) {
				remove_action($action, $callback, $pri, $count);
			};
		}

		protected function register_post_classmap_temporarily(array $classmap) {
			$this->add_filter_temporarily('timber/post/classmap', function(array $current) use ($classmap) {
				return array_merge($current, $classmap);
			});
		}

		/**
		 * Add the given nav_menu_item post IDs to the given menu.
		 * @param int $menu_id the term_id of the menu to add to.
		 * @param int[] $item_ids the list of nav_menu_item post IDs to add.
		 */
		protected function add_menu_items(int $menu_id, array $item_ids) {
			global $wpdb;
			foreach ($item_ids as $id) {
				// $query = "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES ($id, $menu_id, 0);";
				$wpdb->query(sprintf(
					'INSERT INTO %s (object_id, term_taxonomy_id, term_order)'
					. ' VALUES (%d, %d, 0);',
					$wpdb->term_relationships,
					$id,
					$menu_id
				));
			}
			$menu_items_count = count($item_ids);
			$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = $menu_items_count WHERE taxonomy = 'nav_menu'; ");
		}

		/**
		 * Test helper for creating posts and corresponding menu/items from raw post data.
		 * @param array $posts_data an array of raw post data arrays. Each post array is passed
		 * separately to wp_insert_post().
		 * @return array an array of the form:
		 * [
		 *   "term" => (WP_Term menu instance),
		 *   "item_ids" => [(...nav_menu_item post IDs...)],
		 * ]
		 */
		protected function create_menu_from_posts(array $posts_data) {
			$item_ids = array_map(function(array $post_data) {
				$post_id = wp_insert_post($post_data);
				$item_id = wp_insert_post([
					'post_title'  => '',
					'post_status' => 'publish',
					'post_type'   => 'nav_menu_item',
				]);

				update_post_meta( $item_id, '_menu_item_object_id', $post_id );
				update_post_meta( $item_id, '_menu_item_type', 'post_type' );
				update_post_meta( $item_id, '_menu_item_object', 'page' );
				update_post_meta( $item_id, '_menu_item_menu_item_parent', 0 );
				update_post_meta( $item_id, '_menu_item_url', '' );

				return $item_id;
			}, $posts_data);

			$menu_term = wp_insert_term( 'Main Menu', 'nav_menu' );
			$this->add_menu_items($menu_term['term_id'], $item_ids);

			return [
				'term'     => $menu_term,
				'item_ids' => $item_ids,
			];
		}
		

	}
