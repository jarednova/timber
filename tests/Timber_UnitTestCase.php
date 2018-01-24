<?php

	class Timber_UnitTestCase extends WP_UnitTestCase {

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
			add_filter('upload_dir', [$this, 'setUploadDir']);
			add_filter('option_upload_path', [$this, 'setUploadPath']);
			add_filter('option_upload_url_path', [$this, 'setUploadUrlPath']);
			add_filter('option_siteurl', [$this, 'setSiteUrl']);
			add_filter('option_home_url', [$this, 'setHomeUrl']);
			Timber\Image::wp_upload_dir(true);
		}

		function tearDownCustomWPDirectoryStructure() {
			remove_filter('content_url', [$this, 'setContentUrl']);
			remove_filter('upload_dir', [$this, 'setUploadDir']);
			remove_filter('option_upload_path', [$this, 'setUploadPath']);
			remove_filter('option_upload_url_path', [$this, 'setUploadUrlPath']);
			remove_filter('option_siteurl', [$this, 'setSiteUrl']);
			remove_filter('option_home_url', [$this, 'setHomeUrl']);
			Timber\Image::wp_upload_dir(true);
		}

		function setContentUrl($url) {
			return 'http://' . $_SERVER['HTTP_HOST'] . '/content';
		}

		function setUploadDir($uploads) {
			$uploads['basedir'] = dirname(ABSPATH) . '/content/uploads';
			$uploads['baseurl'] = 'http://' .  $_SERVER['HTTP_HOST'] . '/content/uploads';
			$uploads['path'] = $uploads['basedir'] . $uploads['subdir'];
			$uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];
			return $uploads;
		}

		function setUploadPath($dir) {
			return dirname(ABSPATH) . '/content/uploads';
		}

		function setUploadUrlPath($dir) {
			return 'http://' . $_SERVER['HTTP_HOST'] . '/content/uploads';
		}

		function setSiteUrl($url) {
			return 'http://' . $_SERVER['HTTP_HOST'] . '/src';
		}

		function setupWPMLDirectory() {
			if (!defined('ICL_LANGUAGE_CODE')) {
				define('ICL_LANGUAGE_CODE', 'en');
			}
			add_filter('home_url', array($this, 'addWPMLHomeFilter'), 10, 2);
		}
		function tearDownWPMLDirectory() {
			remove_filter('home_url', array($this, 'addWPMLHomeFilter'), 10, 2);
		}

		function addWPMLHomeFilter($url, $path) {
			$url = 'http://' . $_SERVER['HTTP_HOST'] . '/en';
			$path = ltrim($path, '/');
			if ( $path ) {
				$url .= '/' . $path;
			}
			return $url;
		}

	}
