<?php

namespace Timber;

use Timber\Cache\Cleaner;
use Twig\CacheExtension;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Loader {

	const CACHEGROUP = 'timberloader';

	const TRANS_KEY_LEN = 50;

	const CACHE_NONE = 'none';
	const CACHE_OBJECT = 'cache';
	const CACHE_TRANSIENT = 'transient';
	const CACHE_SITE_TRANSIENT = 'site-transient';
	const CACHE_USE_DEFAULT = 'default';

	/** Identifier of the main namespace. Will likely mirror Twig\Loader\FilesystemLoader::MAIN_NAMESPACE */
	const MAIN_NAMESPACE = '__main__';

	public static $cache_modes = array(
		self::CACHE_NONE,
		self::CACHE_OBJECT,
		self::CACHE_TRANSIENT,
		self::CACHE_SITE_TRANSIENT
	);

	protected $cache_mode = self::CACHE_TRANSIENT;

	protected $locations;

	/**
	 * @param bool|string   $caller the calling directory or false
	 */
	public function __construct( $caller = false ) {
		$this->locations = LocationManager::get_locations($caller);

		/**
		 * Filters the cache mode.
		 *
		 * You can read more about Caching in the
		 * [Performance/Caching]({{<relref "performance.md" >}}) guide.
		 *
		 * @since 0.20.10
		 *
		 * @param string $cache_mode The cache mode. Can be one of the following:
		 *                           `Timber\Loader::CACHE_NONE`,
		 *                           `Timber\Loader::CACHE_OBJECT`,
		 *                           `Timber\Loader::CACHE_TRANSIENT`,
		 *                           `Timber\Loader::CACHE_SITE_TRANSIENT`,
		 *                           `Timber\Loader::CACHE_USE_DEFAULT`.
		 *                           Default `Timber\Loader::CACHE_TRANSIENT`.
		 */
		$this->cache_mode = apply_filters('timber/cache/mode', $this->cache_mode);

		/**
		 * Filters the cache mode.
		 *
		 * @deprecated 2.0.0, use `timber/cache/mode`
		 */
		$this->cache_mode = apply_filters_deprecated(
			'timber_cache_mode',
			array( $this->cache_mode ),
			'2.0.0',
			'timber/cache/mode'
		);
	}

	/**
	 * @param string        	$file
	 * @param array         	$data
	 * @param array|boolean    	$expires (array for options, false for none, integer for # of seconds)
	 * @param string        	$cache_mode
	 * @return bool|string
	 */
	public function render( $file, $data = null, $expires = false, $cache_mode = self::CACHE_USE_DEFAULT ) {
		// Different $expires if user is anonymous or logged in
		if ( is_array($expires) ) {
			/** @var array $expires */
			if ( is_user_logged_in() && isset($expires[1]) ) {
				$expires = $expires[1];
			} else {
				$expires = $expires[0];
			}
		}

		if ( $expires === 0 ) {
			$expires = false;
		}

		$key = null;
		$output = false;
		if ( false !== $expires ) {
			ksort($data);
			$key = md5($file.json_encode($data));
			$output = $this->get_cache($key, self::CACHEGROUP, $cache_mode);
		}

		if ( false === $output || null === $output ) {
			$twig = $this->get_twig();
			if ( strlen($file) ) {
				$loader = $this->get_loader();
				$result = $loader->getCacheKey($file);

				/**
				 * Fires after …
				 *
				 * @todo Add summary, description parameter description
				 *
				 * @param string $result
				 */
				do_action( 'timber/loader/render_file', $result );

				/**
				 * Fires after …
				 *
				 * This action is used by the Timber Debug Bar extension.
				 *
				 * @todo Add summary
				 *
				 * @deprecated 2.0.0, use `timber/loader/render_file`
				 */
				do_action_deprecated(
					'timber_loader_render_file',
					array( $result ),
					'2.0.0',
					'timber/loader/render_file'
				);
			}

			/**
			 * Filters …
			 *
			 * @todo Add summary, description, example, parameter descriptions
			 *
			 * @since 0.20.10
			 *
			 * @param array  $data
			 * @param string $file
			 */
			$data = apply_filters('timber/loader/render_data', $data, $file);

			/**
			 * Filters …
			 *
			 * @todo Add summary
			 *
			 * @deprecated 2.0.0, use `timber/loader/render_data`
			 */
			$data = apply_filters_deprecated(
				'timber_loader_render_data',
				array( $data ),
				'2.0.0',
				'timber/loader/render_data'
			);

			$template = $twig->load($file);
			$output = $template->render($data);
		}

		if ( false !== $output && false !== $expires && null !== $key ) {
			$this->delete_cache();
			$this->set_cache($key, $output, self::CACHEGROUP, $expires, $cache_mode);
		}

		/**
		 * Filters …
		 *
		 * @todo  Add summary, description, example, parameter descriptions
		 *
		 * @since 0.20.10
		 *
		 * @param string $output
		 * @param array  $data
		 * @param string $file
		 */
		$output = apply_filters('timber/output', $output, $data, $file);

		/**
		 * Filters …
		 *
		 * @todo       Add summary
		 *
		 * @deprecated 2.0.0, use `timber/output`
		 */
		$output = apply_filters_deprecated( 'timber_output', array( $output ), '2.0.0', 'timber/output' );

		return $output;
	}

	protected function delete_cache() {
		Cleaner::delete_transients();
	}

	/**
	 * Get first existing template.
	 *
	 * @param array|string $templates  Name(s) of the Twig template(s) to choose from.
	 * @return string|bool             Name of chosen template, otherwise false.
	 */
	public function choose_template( $templates ) {
		// Change $templates into array, if needed
		if ( !is_array($templates) ) {
			$templates = (array) $templates;
		}

		// Get Twig loader
		$loader = $this->get_loader();

		// Run through template array
		foreach ( $templates as $template ) {

			// Remove any whitespace around the template name
			$template = trim( $template );
			// Use the Twig loader to test for existance
			if ( $loader->exists($template) ) {
				// Return name of existing template
				return $template;
			}
		}

		// No existing template was found
		return false;
	}

	/**
	 * @return \Twig\Loader\FilesystemLoader
	 */
	public function get_loader() {
		$paths = $this->locations;

		/**
		 * Filters the template paths used by the Loader.
		 *
		 * @since 0.20.10
		 *
		 * @deprecated 2.0.0, use `timber/locations`
		 *
		 * @param array $paths
		 */
		$paths = apply_filters_deprecated(
			'timber/loader/paths',
			array( $paths ),
			'2.0.0',
			'timber/locations'
		);

		$open_basedir = ini_get('open_basedir');
		$rootPath = '/';
		if ( $open_basedir ) {
			$rootPath = null;
		}

		$fs = new FilesystemLoader( array(), $rootPath );

		foreach ( $paths as $namespace => $path_locations ) {
			if ( is_array( $path_locations ) ) {
				array_map( function ( $path ) use ( $fs, $namespace ) {
					if ( is_string($namespace) ) {
						$fs->addPath( $path, $namespace );
					} else {
						$fs->addPath( $path, Loader::MAIN_NAMESPACE );
					}
				}, $path_locations );
			} else {
				Helper::deprecated(
					'add_filter( \'timber/loader/paths\', [\'path/to/my/templates\'] ) in a non-associative array',
					'add_filter( \'timber/loader/paths\', [ 0 => [ \'path/to/my/templates\' ] ] )',
					'2.0.0'
				);
				$fs->addPath( $path_locations, self::MAIN_NAMESPACE );
			}
		}

		/**
		 * Filters …
		 *
		 * @todo Add summary, description, example, parameter description
		 *
		 * @link https://github.com/timber/timber/pull/1254
		 * @since 1.1.11
		 *
		 * @param array $paths
		 */
		$fs = apply_filters( 'timber/loader/loader', $fs );

		return $fs;
	}

	/**
	 * @return \Twig\Environment
	 */
	public function get_twig() {

		// Default options.
		$environment_options = array(
			'debug'      => WP_DEBUG,
			'autoescape' => false,
			'cache'      => false,
		);

		/**
		 * Filters the environment options that are used when creating a Twig Environment instance.
		 *
		 * By default, Timber sets the following values:
		 *
		 * - `'debug' => WP_DEBUG`
		 * - `'autoescape' => false`
		 * - `'cache' => false`
		 *
		 * @api
		 * @since 1.9.5
		 * @link https://twig.symfony.com/doc/2.x/api.html#environment-options
		 * @example
		 * ```php
		 * add_filter( 'timber/twig/environment/options', 'update_twig_environment_options' );
		 *
		 * /**
		 *  * Updates Twig environment options.
		 *  *
		 *  * @link https://twig.symfony.com/doc/2.x/api.html#environment-options
		 *  *
		 *  * @param array $options An array of environment options.
		 *  *
		 *  * @return array
		 *  *\/
		 * function update_twig_environment_options( $options ) {
		 *     $options['cache']       = true;
		 *     $options['auto_reload'] = true;
		 *
		 *     return $options;
		 * }
		 * ```
		 *
		 * @param array $environment_options An array of Twig environment options.
		 */
		$environment_options = apply_filters(
			'timber/twig/environment/options',
			$environment_options
		);

		/**
		 * @deprecated 2.0.0
		 */
		if ( isset( Timber::$autoescape ) && false !== Timber::$autoescape ) {
			Helper::deprecated(
				'Timber::$autoescape',
				'the \'timber/twig/environment/options filter\'',
				'2.0.0'
			);

			$environment_options['autoescape'] = Timber::$autoescape;
		}

		/**
		 * Backwards compatibility fix.
		 *
		 * The value `true` doesn’t exist anymore for the `autoescape` option. You need to define
		 * an auto-escaping fallback strategy. This fallback uses the `html` strategy.
		 */
		if ( true === $environment_options['autoescape'] ) {
			$environment_options['autoescape'] = 'html';
		}

		/**
		 * Alias Timber::$cache can be used for Timber::$twig_cache.
		 *
		 * @deprecated 2.0.0
		 */
		if ( isset( Timber::$cache ) && true === Timber::$cache ) {
			Timber::$twig_cache = true;
		}

		/**
		 * @deprecated 2.0.0
		 */
		if ( isset( Timber::$twig_cache ) && false !== Timber::$twig_cache ) {
			Helper::deprecated(
				'Timber::$cache and Timber::$twig_cache',
				'the \'timber/twig/environment/options filter\'',
				'2.0.0'
			);

			$environment_options['cache'] = Timber::$twig_cache;
		}

		if ( true === $environment_options['cache'] ) {
			$twig_cache_loc = TIMBER_LOC . '/cache/twig';

			/**
			 * Filters the cache location used for Twig.
			 *
			 * Allows you to set a new cache location for Twig. If the folder doesn’t exist yet, it
			 * will be created automatically.
			 *
			 * @since 0.20.10
			 * @deprecated 2.0.0
			 *
			 * @param string $twig_cache_loc Full path to the cache location. Default `/cache/twig`
			 *                               in the Timber root folder.
			 */
			$twig_cache_loc = apply_filters_deprecated(
				'timber/cache/location',
				array( $twig_cache_loc ),
				'2.0.0',
				'timber/twig/environment/options'
			);

			if ( !file_exists($twig_cache_loc) ) {
				mkdir($twig_cache_loc, 0777, true);
			}

			$environment_options['cache'] = $twig_cache_loc;
		}
		$twig = new \Twig\Environment( $this->get_loader(), $environment_options );

		if ( WP_DEBUG ) {
			$twig->addExtension(new \Twig\Extension\DebugExtension());
		} else {
			$twig->addFunction(new TwigFunction('dump', function() {
				return null;
			}));
		}
		$twig->addExtension($this->_get_cache_extension());

		/**
		 * Filters …
		 *
		 * @todo Add summary, description, example
		 *
		 * @since 0.21.9
		 *
		 * @param \Twig\Environment $twig The Twig environment you can add functionality to.
		 */
		$twig = apply_filters('timber/twig/filters', $twig);

		/**
		 * Filters …
		 *
		 * @todo Add summary, description, example
		 *
		 * @since 1.3.0-rc2
		 *
		 * @param \Twig\Environment $twig The Twig environment you can add functionality to.
		 */
		$twig = apply_filters('timber/twig/functions', $twig);

		/**
		 * Filters …
		 *
		 * @todo Add summary, description, example
		 *
		 * @since 1.1.1
		 *
		 * @param \Twig\Environment $twig The Twig environment you can add functionality to.
		 */
		$twig = apply_filters('timber/twig/escapers', $twig);

		/**
		 * Filters …
		 *
		 * @todo Add summary, description, example
		 *
		 * @since 0.20.10
		 *
		 * @param \Twig\Environment $twig The Twig environment you can add functionality to.
		 */
		$twig = apply_filters('timber/loader/twig', $twig);

		/**
		 * Filters …
		 *
		 * @deprecated 2.0.0, use `timber/twig/filters`
		 */
		$twig = apply_filters_deprecated( 'twig_apply_filters', array( $twig ), '2.0.0', 'timber/twig/filters' );

		/**
		 * Filters the Twig environment used in the global context.
		 *
		 * You can use this filter if you want to add additional functionality to Twig, like global
		 * variables, filters or functions.
		 *
		 * @since 0.21.9
		 * @example
		 * ```php
		 * /**
		 *  * Adds Twig functionality.
		 *  *
		 *  * @param \Twig\Environment $twig The Twig Environment to which you can add additional functionality.
		 *  *\/
		 * add_filter( 'timber/twig', function( $twig ) {
		 *     // Make get_theme_file_uri() usable as {{ theme_file() }} in Twig.
		 *     $twig->addFunction( new Twig\TwigFunction( 'theme_file', 'get_theme_file_uri' ) );
		 *
		 *     return $twig;
		 * } );
		 * ```
		 *
		 * ```twig
		 * <a class="navbar-brand" href="{{ site.url }}">
		 *     <img src="{{ theme_file( 'build/img/logo-example.svg' ) }}" alt="Logo {{ site.title }}">
		 * </a>
		 * ```
		 *
		 * @param \Twig\Environment $twig The Twig environment.
		 */
		$twig = apply_filters('timber/twig', $twig);

		/**
		 * Filters the Twig environment used in the global context.
		 *
		 * @deprecated 2.0.0
		 */
		$twig = apply_filters_deprecated( 'get_twig', array( $twig ), '2.0.0', 'timber/twig' );

		return $twig;
	}

	public function clear_cache_timber( $cache_mode = self::CACHE_USE_DEFAULT ) {
		//_transient_timberloader
		$object_cache = false;
		if ( isset($GLOBALS['wp_object_cache']) && is_object($GLOBALS['wp_object_cache']) ) {
			$object_cache = true;
		}
		$cache_mode = $this->_get_cache_mode($cache_mode);
		if ( self::CACHE_TRANSIENT === $cache_mode || self::CACHE_SITE_TRANSIENT === $cache_mode ) {
			return self::clear_cache_timber_database();
		} else if ( self::CACHE_OBJECT === $cache_mode && $object_cache ) {
			return self::clear_cache_timber_object();
		}
		return false;
	}

	protected static function clear_cache_timber_database() {
		global $wpdb;
		$query = $wpdb->prepare(
			"DELETE FROM $wpdb->options WHERE option_name LIKE '%s'",
			'_transient%timberloader_%'
		);
		return $wpdb->query($query);
	}

	protected static function clear_cache_timber_object() {
		global $wp_object_cache;
		if ( isset($wp_object_cache->cache[self::CACHEGROUP]) ) {
			$items = $wp_object_cache->cache[self::CACHEGROUP];
			foreach ( $items as $key => $value ) {
				if ( is_multisite() ) {
					$key = preg_replace('/^(.*?):/', '', $key);
				}
				wp_cache_delete($key, self::CACHEGROUP);
			}
			return true;
		}
	}

	public function clear_cache_twig() {
		$twig = $this->get_twig();
		if ( method_exists($twig, 'clearCacheFiles') ) {
			$twig->clearCacheFiles();
		}
		$cache = $twig->getCache();
		if ( $cache ) {
			self::rrmdir($twig->getCache());
			return true;
		}
		return false;
	}

	/**
	 * Remove a directory and everything inside
	 *
	 * @param string|false $dirPath
	 */
	public static function rrmdir( $dirPath ) {
		if ( !is_dir($dirPath) ) {
			throw new \InvalidArgumentException("$dirPath must be a directory");
		}
		if ( substr($dirPath, strlen($dirPath) - 1, 1) != '/' ) {
			$dirPath .= '/';
		}
		$files = glob($dirPath.'*', GLOB_MARK);
		foreach ( $files as $file ) {
			if ( is_dir($file) ) {
				self::rrmdir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}

	/**
	 * @return \Twig\CacheExtension\Extension
	 */
	private function _get_cache_extension() {

		$key_generator   = new \Timber\Cache\KeyGenerator();
		$cache_provider  = new \Timber\Cache\WPObjectCacheAdapter($this);
		$cache_lifetime  = apply_filters('timber/cache/extension/lifetime', 0);
		$cache_strategy  = new CacheExtension\CacheStrategy\GenerationalCacheStrategy(
			$cache_provider,
			$key_generator,
			$cache_lifetime
		);
		$cache_extension = new CacheExtension\Extension($cache_strategy);

		return $cache_extension;
	}

	/**
	 * @param string $key
	 * @param string $group
	 * @param string $cache_mode
	 * @return bool
	 */
	public function get_cache( $key, $group = self::CACHEGROUP, $cache_mode = self::CACHE_USE_DEFAULT ) {
		$object_cache = false;

		if ( isset($GLOBALS['wp_object_cache']) && is_object($GLOBALS['wp_object_cache']) ) {
			$object_cache = true;
		}

		$cache_mode = $this->_get_cache_mode($cache_mode);

		$value = false;

		$trans_key = substr($group.'_'.$key, 0, self::TRANS_KEY_LEN);
		if ( self::CACHE_TRANSIENT === $cache_mode ) {
			$value = get_transient($trans_key);
		} elseif ( self::CACHE_SITE_TRANSIENT === $cache_mode ) {
			$value = get_site_transient($trans_key);
		} elseif ( self::CACHE_OBJECT === $cache_mode && $object_cache ) {
			$value = wp_cache_get($key, $group);
		}

		return $value;
	}

	/**
	 * @param string $key
	 * @param string|boolean $value
	 * @param string $group
	 * @param integer $expires
	 * @param string $cache_mode
	 * @return string|boolean
	 */
	public function set_cache( $key, $value, $group = self::CACHEGROUP, $expires = 0, $cache_mode = self::CACHE_USE_DEFAULT ) {
		$object_cache = false;

		if ( isset($GLOBALS['wp_object_cache']) && is_object($GLOBALS['wp_object_cache']) ) {
			$object_cache = true;
		}

		if ( (int) $expires < 1 ) {
			$expires = 0;
		}

		$cache_mode = $this->_get_cache_mode($cache_mode);
		$trans_key = substr($group.'_'.$key, 0, self::TRANS_KEY_LEN);

		if ( self::CACHE_TRANSIENT === $cache_mode ) {
			set_transient($trans_key, $value, $expires);
		} elseif ( self::CACHE_SITE_TRANSIENT === $cache_mode ) {
			set_site_transient($trans_key, $value, $expires);
		} elseif ( self::CACHE_OBJECT === $cache_mode && $object_cache ) {
			wp_cache_set($key, $value, $group, $expires);
		}

		return $value;
	}

	/**
	 * @param string $cache_mode
	 * @return string
	 */
	private function _get_cache_mode( $cache_mode ) {
		if ( empty($cache_mode) || self::CACHE_USE_DEFAULT === $cache_mode ) {
			$cache_mode = $this->cache_mode;
		}

		// Fallback if self::$cache_mode did not get a valid value
		if ( !in_array($cache_mode, self::$cache_modes) ) {
			$cache_mode = self::CACHE_OBJECT;
		}

		return $cache_mode;
	}

}
