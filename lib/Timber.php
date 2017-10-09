<?php

namespace Timber;

use Timber\Twig;
use Timber\ImageHelper;
use Timber\Admin;
use Timber\Integrations;
use Timber\PostGetter;
use Timber\TermGetter;
use Timber\Site;
use Timber\URLHelper;
use Timber\Helper;
use Timber\Pagination;
use Timber\Request;
use Timber\User;
use Timber\Loader;

/**
 * Timber Class.
 *
 * Main class called Timber for this plugin.
 *
 * @example
 * ```php
 *  $posts = Timber::get_posts();
 *  $posts = Timber::get_posts('post_type = article')
 *  $posts = Timber::get_posts(array('post_type' => 'article', 'category_name' => 'sports')); // uses wp_query format.
 *  $posts = Timber::get_posts(array(23,24,35,67), 'InkwellArticle');
 *
 *  $context = Timber::get_context(); // returns wp favorites!
 *  $context['posts'] = $posts;
 *  Timber::render('index.twig', $context);
 * ```
 */
class Timber {

	public static $version = '1.2.4';
	public static $locations;
	public static $dirname = 'views';
	public static $twig_cache = false;
	public static $cache = false;
	public static $auto_meta = true;
	public static $autoescape = false;

	public static $context_cache = array();

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		if ( !defined('ABSPATH') ) {
			return;
		}
		if ( class_exists('\WP') && !defined('TIMBER_LOADED') ) {
			$this->test_compatibility();
			$this->backwards_compatibility();
			$this->init_constants();
			self::init();
		}
	}

	/**
	 * Tests whether we can use Timber
	 * @codeCoverageIgnore
	 * @return
	 */
	protected function test_compatibility() {
		if ( is_admin() || $_SERVER['PHP_SELF'] == '/wp-login.php' ) {
			return;
		}
		if ( version_compare(phpversion(), '5.3.0', '<') && !is_admin() ) {
			trigger_error('Timber requires PHP 5.3.0 or greater. You have '.phpversion(), E_USER_ERROR);
		}
		if ( !class_exists('Twig_Token') ) {
			trigger_error('You have not run "composer install" to download required dependencies for Timber, you can read more on https://github.com/timber/timber#installation', E_USER_ERROR);
		}
	}

	/**
	 * @codeCoverageIgnore
	 */
	private function backwards_compatibility() {
		if ( class_exists('TimberArchives') ) {
			//already run, so bail
			return;
		}
		$names = array('Archives', 'Comment', 'Core', 'FunctionWrapper', 'Helper', 'Image', 'ImageHelper', 'Integrations', 'Loader', 'Menu', 'MenuItem', 'Post', 'PostGetter', 'PostCollection', 'QueryIterator', 'Request', 'Site', 'Term', 'TermGetter', 'Theme', 'Twig', 'URLHelper', 'User', 'Integrations\Command', 'Integrations\ACF');
		foreach ( $names as $name ) {
			$old_class_name = 'Timber'.str_replace('Integrations\\', '', $name);
			$new_class_name = 'Timber\\'.$name;
			if ( class_exists($new_class_name) ) {
				class_alias($new_class_name, $old_class_name);
			}
		}
		class_alias(get_class($this), 'Timber');
		if ( class_exists('Timber\\'.'Integrations\Timber_WP_CLI_Command') ) {
			class_alias('Timber\\'.'Integrations\Timber_WP_CLI_Command', 'Timber_WP_CLI_Command');
		}
	}

	public function init_constants() {
		defined("TIMBER_LOC") or define("TIMBER_LOC", realpath(dirname(__DIR__)));
	}

	/**
	 * @codeCoverageIgnore
	 */
	protected static function init() {
		if ( class_exists('\WP') && !defined('TIMBER_LOADED') ) {
			Twig::init();
			TwigExtension\CoreExtension::activateWordpressHook();
			ImageHelper::init();
			Admin::init();
			new Integrations();
			define('TIMBER_LOADED', true);
		}
	}

	/* Post Retrieval Routine
	================================ */

	/**
	 * Get a post by post ID or query (as a query string or an array of arguments).
	 * But it's also cool
	 *
	 * @api
	 * @param mixed        $query     Optional. Post ID or query (as query string or an array of arguments for
	 *                                WP_Query). If a query is provided, only the first post of the result will be
	 *                                returned. Default false.
	 * @param string|array $PostClass Optional. Class to use to wrap the returned post object. Default 'Timber\Post'.
	 * @return \Timber\Post|bool Timber\Post object if a post was found, false if no post was found.
	 */
	public static function get_post( $query = false, $PostClass = 'Timber\Post' ) {
		return PostGetter::get_post($query, $PostClass);
	}

	/**
	 * Get posts.
	 * @api
	 * @example
	 * ```php
	 * $posts = Timber::get_posts();
 	 *  $posts = Timber::get_posts('post_type = article')
 	 *  $posts = Timber::get_posts(array('post_type' => 'article', 'category_name' => 'sports')); // uses wp_query format.
 	 *  $posts = Timber::get_posts('post_type=any', array('portfolio' => 'MyPortfolioClass', 'alert' => 'MyAlertClass')); //use a classmap for the $PostClass
	 * ```
	 * @param mixed   $query
	 * @param string|array  $PostClass
	 * @return array|bool|null
	 */
	public static function get_posts( $query = false, $PostClass = 'Timber\Post', $return_collection = false ) {
		return PostGetter::get_posts($query, $PostClass, $return_collection);
	}

	/**
	 * Query post.
	 * @api
	 * @param mixed   $query
	 * @param string  $PostClass
	 * @return array|bool|null
	 */
	public static function query_post( $query = false, $PostClass = 'Timber\Post' ) {
		return PostGetter::query_post($query, $PostClass);
	}

	/**
	 * Query posts.
	 * @api
	 * @param mixed   $query
	 * @param string  $PostClass
	 * @return PostCollection
	 */
	public static function query_posts( $query = false, $PostClass = 'Timber\Post' ) {
		return PostGetter::query_posts($query, $PostClass);
	}

	/* Term Retrieval
	================================ */

	/**
	 * Get terms.
	 * @api
	 * @param string|array $args
	 * @param array   $maybe_args
	 * @param string  $TermClass
	 * @return mixed
	 */
	public static function get_terms( $args = null, $maybe_args = array(), $TermClass = 'Timber\Term' ) {
		return TermGetter::get_terms($args, $maybe_args, $TermClass);
	}

	/**
	 * Get term.
	 * @api
	 * @param int|WP_Term|object $term
	 * @param string     $taxonomy
	 * @return Timber\Term|WP_Error|null
	 */
	public static function get_term( $term, $taxonomy = 'post_tag', $TermClass = 'Timber\Term' ) {
		return TermGetter::get_term($term, $taxonomy, $TermClass);
	}

	/* Site Retrieval
	================================ */

	/**
	 * Get sites.
	 * @api
	 * @param array|bool $blog_ids
	 * @return array
	 */
	public static function get_sites( $blog_ids = false ) {
		if ( !is_array($blog_ids) ) {
			global $wpdb;
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id ASC");
		}
		$return = array();
		foreach ( $blog_ids as $blog_id ) {
			$return[] = new Site($blog_id);
		}
		return $return;
	}


	/*  Template Setup and Display
	================================ */

	/**
	 * Get context.
	 * @api
	 * @return array
	 */
	public static function get_context() {
		if ( empty(self::$context_cache) ) {
			self::$context_cache['http_host'] = URLHelper::get_scheme().'://'.URLHelper::get_host();
			self::$context_cache['wp_title'] = Helper::get_wp_title();
			self::$context_cache['body_class'] = implode(' ', get_body_class());

			self::$context_cache['site'] = new Site();
			self::$context_cache['request'] = new Request();
			$user = new User();
			self::$context_cache['user'] = ($user->ID) ? $user : false;
			self::$context_cache['theme'] = self::$context_cache['site']->theme;

			self::$context_cache['posts'] = new PostQuery();

			/**
			 * @deprecated as of Timber 1.3.0
			 * @todo remove in Timber 1.4.*
			 */
			self::$context_cache['wp_head'] = new FunctionWrapper( 'wp_head' );
			self::$context_cache['wp_footer'] = new FunctionWrapper( 'wp_footer' );

			self::$context_cache = apply_filters('timber_context', self::$context_cache);
			self::$context_cache = apply_filters('timber/context', self::$context_cache);
		}


		return self::$context_cache;
	}

	/**
	 * Compile a Twig file.
	 *
	 * Passes data to a Twig file and returns the output.
	 *
	 * @api
	 * @example
	 * ```php
	 * $data = array(
	 *     'firstname' => 'Jane',
	 *     'lastname' => 'Doe',
	 *     'email' => 'jane.doe@example.org',
	 * );
	 *
	 * $team_member = Timber::compile( 'team-member.twig', $data );
	 * ```
	 * @param array|string $filenames  Name of the Twig file to render. If this is an array of files, Timber will
	 *                                 render the first file that exists.
	 * @param array        $data       Optional. An array of data to use in Twig template.
	 * @param bool|int     $expires    Optional. In seconds. Use false to disable cache altogether. When passed an
	 *                                 array, the first value is used for non-logged in visitors, the second for users.
	 *                                 Default false.
	 * @param string       $cache_mode Optional. Any of the cache mode constants defined in TimberLoader.
	 * @param bool         $via_render Optional. Whether to apply optional render or compile filters. Default false.
	 * @return bool|string The returned output.
	 */
	public static function compile( $filenames, $data = array(), $expires = false, $cache_mode = Loader::CACHE_USE_DEFAULT, $via_render = false ) {
		if ( !defined('TIMBER_LOADED') ) {
			self::init();
		}
		$caller = LocationManager::get_calling_script_dir(1);
		$loader = new Loader($caller);
		$file = $loader->choose_template($filenames);

		$caller_file = LocationManager::get_calling_script_file(1);
		apply_filters('timber/calling_php_file', $caller_file);

		if ( $via_render ) {
			$file = apply_filters('timber_render_file', $file);
		} else {
			$file = apply_filters('timber_compile_file', $file);
		}

		$output = false;

		if ($file !== false) {
			if ( is_null($data) ) {
				$data = array();
			}

			if ( $via_render ) {
				$data = apply_filters('timber_render_data', $data);
			} else {
				$data = apply_filters('timber_compile_data', $data);
			}

			$output = $loader->render($file, $data, $expires, $cache_mode);
		}

		do_action('timber_compile_done');
		return $output;
	}

	/**
	 * Compile a string.
	 *
	 * @api
	 * @example
	 * ```php
	 * $data = array(
	 *     'username' => 'Jane Doe',
	 * );
	 *
	 * $welcome = Timber::compile_string( 'Hi {{ username }}, I’m a string with a custom Twig variable', $data );
	 * ```
	 * @param string $string A string with Twig variables.
	 * @param array  $data   Optional. An array of data to use in Twig template.
	 * @return  bool|string
	 */
	public static function compile_string( $string, $data = array() ) {
		$dummy_loader = new Loader();
		$twig = $dummy_loader->get_twig();
		$template = $twig->createTemplate($string);
		return $template->render($data);
	}

	/**
	 * Fetch function.
	 *
	 * @api
	 * @param array|string $filenames  Name of the Twig file to render. If this is an array of files, Timber will
	 *                                 render the first file that exists.
	 * @param array        $data       Optional. An array of data to use in Twig template.
	 * @param bool|int     $expires    Optional. In seconds. Use false to disable cache altogether. When passed an
	 *                                 array, the first value is used for non-logged in visitors, the second for users.
	 *                                 Default false.
	 * @param string       $cache_mode Optional. Any of the cache mode constants defined in TimberLoader.
	 * @return bool|string The returned output.
	 */
	public static function fetch( $filenames, $data = array(), $expires = false, $cache_mode = Loader::CACHE_USE_DEFAULT ) {
		$output = self::compile($filenames, $data, $expires, $cache_mode, true);
		$output = apply_filters('timber_compile_result', $output);
		return $output;
	}

	/**
	 * Render function.
	 *
	 * Passes data to a Twig file and echoes the output.
	 *
	 * @api
	 * @example
	 * ```php
	 * $context = Timber::get_context();
	 *
	 * Timber::render( 'index.twig', $context );
	 * ```
	 * @param array|string $filenames  Name of the Twig file to render. If this is an array of files, Timber will
	 *                                 render the first file that exists.
	 * @param array        $data       Optional. An array of data to use in Twig template.
	 * @param bool|int     $expires    Optional. In seconds. Use false to disable cache altogether. When passed an
	 *                                 array, the first value is used for non-logged in visitors, the second for users.
	 *                                 Default false.
	 * @param string       $cache_mode Optional. Any of the cache mode constants defined in TimberLoader.
	 * @return bool|string The echoed output.
	 */
	public static function render( $filenames, $data = array(), $expires = false, $cache_mode = Loader::CACHE_USE_DEFAULT ) {
		$output = self::fetch($filenames, $data, $expires, $cache_mode);
		echo $output;
		return $output;
	}

	/**
	 * Render a string with Twig variables.
	 *
	 * @api
	 * @example
	 * ```php
	 * $data = array(
	 *     'username' => 'Jane Doe',
	 * );
	 *
	 * Timber::render_string( 'Hi {{ username }}, I’m a string with a custom Twig variable', $data );
	 * ```
	 * @param string $string A string with Twig variables.
	 * @param array  $data   An array of data to use in Twig template.
	 * @return bool|string
	 */
	public static function render_string( $string, $data = array() ) {
		$compiled = self::compile_string($string, $data);
		echo $compiled;
		return $compiled;
	}


	/*  Sidebar
	================================ */

	/**
	 * Get sidebar.
	 * @api
	 * @param string  $sidebar
	 * @param array   $data
	 * @return bool|string
	 */
	public static function get_sidebar( $sidebar = 'sidebar.php', $data = array() ) {
		if ( strstr(strtolower($sidebar), '.php') ) {
			return self::get_sidebar_from_php($sidebar, $data);
		}
		return self::compile($sidebar, $data);
	}

	/**
	 * Get sidebar from PHP
	 * @api
	 * @param string  $sidebar
	 * @param array   $data
	 * @return string
	 */
	public static function get_sidebar_from_php( $sidebar = '', $data ) {
		$caller = LocationManager::get_calling_script_dir(1);
		$uris = LocationManager::get_locations($caller);
		ob_start();
		$found = false;
		foreach ( $uris as $uri ) {
			if ( file_exists(trailingslashit($uri).$sidebar) ) {
				include trailingslashit($uri).$sidebar;
				$found = true;
				break;
			}
		}
		if ( !$found ) {
			Helper::error_log('error loading your sidebar, check to make sure the file exists');
		}
		$ret = ob_get_contents();
		ob_end_clean();
		return $ret;
	}

	/* Widgets
	================================ */

	/**
	 * Get widgets.
	 *
	 * @api
	 * @param int|string $widget_id Optional. Index, name or ID of dynamic sidebar. Default 1.
	 * @return string
	 */
	public static function get_widgets( $widget_id ) {
		return trim( Helper::ob_function( 'dynamic_sidebar', array( $widget_id ) ) );
	}

	/*  Pagination
	================================ */

	/**
	 * Get pagination.
	 * @api
	 * @param array   $prefs
	 * @return array mixed
	 */
	public static function get_pagination( $prefs = array() ) {
		return Pagination::get_pagination($prefs);
	}

	/*  Utility
	================================ */

	/**
	 * Add route.
	 *
	 * @param string  $route
	 * @param callable $callback
	 * @param array   $args
	 * @deprecated since 0.20.0 and will be removed in 1.1
	 * @codeCoverageIgnore
	 */
	public static function add_route( $route, $callback, $args = array() ) {
		Helper::warn('Timber::add_route (and accompanying methods for load_view, etc. Have been deprecated and will soon be removed. Please update your theme with Route::map. You can read more in the 1.0 Upgrade Guide: https://timber.github.io/docs/upgrade-guides/1.0/');
		\Routes::map($route, $callback, $args);
	}


}
