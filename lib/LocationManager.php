<?php

namespace Timber;

class LocationManager {

	
	/**
	 * @param bool|string   $caller the calling directory (or false)
	 * @return array
	 */
	public static function get_locations( $caller = false ) {
		//prioirty: user locations, caller (but not theme), child theme, parent theme, caller
		$locs = array();
		$locs = array_merge_recursive( $locs, self::get_locations_user() );
		$locs = array_merge_recursive( $locs, self::get_locations_caller( $caller ) );
		//remove themes from caller
		$locs = array_merge_recursive( $locs, self::get_locations_theme() );
		$locs = array_merge_recursive( $locs, self::get_locations_caller( $caller ) );
		$locs = array_map( 'array_unique', $locs );

		//now make sure theres a trailing slash on everything
		$locs = array_map( function ( $loc ) {
			return array_map( 'trailingslashit', $loc );
		}, $locs );

		$locs = array_map( function ( $loc ) {
			return apply_filters( 'timber_locations', $loc );
		}, $locs );

		$locs = array_map( function ( $loc ) {
			return apply_filters( 'timber/locations', $loc );
		}, $locs );

		return $locs;
	}


	/**
	 * @return array
	 */
	protected static function get_locations_theme() {
		$theme_locs = array();
		$theme_dirs = LocationManager::get_locations_theme_dir();
		$roots      = array( get_stylesheet_directory(), get_template_directory() );
		$roots      = array_map( 'realpath', $roots );
		$roots      = array_unique( $roots );
		foreach ( $roots as $root ) {
			if ( ! is_dir( $root ) ) {
				continue;
			}
			$theme_locs[ Loader::MAIN_NAMESPACE ][] = $root;
			$root                                   = trailingslashit( $root );
			foreach ( $theme_dirs as $dirname ) {
				$tloc = realpath( $root . $dirname );
				if ( is_dir( $tloc ) ) {
					$theme_locs[ Loader::MAIN_NAMESPACE ][] = $tloc;
				}
			}
		}

		return $theme_locs;
	}


	/**
	 * Get calling script file.
	 * @api
	 * @param int     $offset
	 * @return string|null
	 */
	public static function get_calling_script_file( $offset = 0 ) {
		$callers = array();
		$backtrace = debug_backtrace();
		foreach ( $backtrace as $trace ) {
			if ( array_key_exists('file', $trace) && $trace['file'] != __FILE__ ) {
				$callers[] = $trace['file'];
			}
		}		
		$callers = array_unique($callers);
		$callers = array_values($callers);
		return $callers[$offset];
	}

	/**
	 * Get calling script dir.
	 * @api
	 * @return string
	 */
	public static function get_calling_script_dir( $offset = 0 ) {
		$caller = self::get_calling_script_file($offset);
		if ( !is_null($caller) ) {
			$pathinfo = pathinfo($caller);
			$dir = $pathinfo['dirname'];
			return $dir;
		}
	}

	/**
	 * returns an array of the directory inside themes that holds twig files
	 * @return string[] the names of directores, ie: array('templats', 'views');
	 */
	public static function get_locations_theme_dir() {
		if ( is_string(Timber::$dirname) ) {
			return array(Timber::$dirname);
		}
		return Timber::$dirname;
	}


	/**
	 *
	 * @return array
	 */
	protected static function get_locations_user() {
		$locs = array();
		if ( isset( Timber::$locations ) ) {
			if ( is_string( Timber::$locations ) ) {
				Timber::$locations = array( Timber::$locations );
			}
			foreach ( Timber::$locations as $tloc => $namespace_or_tloc ) {
				if ( is_string( $tloc ) ) {
					$namespace = $namespace_or_tloc;
				} else {
					$tloc      = $namespace_or_tloc;
					$namespace = null;
				}

				$tloc = realpath( $tloc );
				if ( is_dir( $tloc ) ) {
					if ( ! is_string( $namespace ) ) {
						$locs[ Loader::MAIN_NAMESPACE ][] = $tloc;
					} else {
						$locs[ $namespace ][] = $tloc;
					}
				}
			}
		}

		return $locs;
	}

	/**
	 * @param bool|string   $caller the calling directory
	 * @return array
	 */
	protected static function get_locations_caller( $caller = false ) {
		$locs = array();
		if ( $caller && is_string( $caller ) ) {
			$caller = realpath( $caller );
			if ( is_dir( $caller ) ) {
				$locs[ Loader::MAIN_NAMESPACE ][] = $caller;
			}
			$caller = trailingslashit( $caller );
			foreach ( LocationManager::get_locations_theme_dir() as $dirname ) {
				$caller_sub = realpath( $caller . $dirname );
				if ( is_dir( $caller_sub ) ) {
					$locs[ Loader::MAIN_NAMESPACE ][] = $caller_sub;
				}
			}
		}

		return $locs;
	}


}
