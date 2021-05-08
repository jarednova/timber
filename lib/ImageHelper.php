<?php

namespace Timber;

use Timber\Image\Operation;

/**
 * Class ImageHelper
 *
 * Implements the Twig image filters:
 * https://timber.github.io/docs/guides/cookbook-images/#arbitrary-resizing-of-images
 * - resize
 * - retina
 * - letterbox
 * - tojpg
 *
 * Implementation:
 * - public static functions provide the methods that are called by the filter
 * - most of the work is common to all filters (URL analysis, directory gymnastics, file caching, error management) and done by private static functions
 * - the specific part (actual image processing) is delegated to dedicated subclasses of TimberImageOperation
 *
 * @api
 */
class ImageHelper {

	const BASE_UPLOADS = 1;
	const BASE_CONTENT = 2;

	static $home_url;

	/**
	 * Inits the object.
	 */
	public static function init() {
		self::$home_url = get_home_url();
		add_action('delete_attachment', array(__CLASS__, 'delete_attachment'));
		add_filter('wp_generate_attachment_metadata', array(__CLASS__, 'generate_attachment_metadata'), 10, 2);
		add_filter('upload_dir', array(__CLASS__, 'add_relative_upload_dir_key'), 10, 2);
		return true;
	}

	/**
	 * Generates a new image with the specified dimensions.
	 *
	 * New dimensions are achieved by cropping to maintain ratio.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <img src="{{ image.src | resize(300, 200, 'top') }}" />
	 * ```
	 * ```html
	 * <img src="http://example.org/wp-content/uploads/pic-300x200-c-top.jpg" />
	 * ```
	 *
	 * @param string     $src   A URL (absolute or relative) to the original image.
	 * @param int|string $w     Target width (int) or WordPress image size (WP-set or
	 *                          user-defined).
	 * @param int        $h     Optional. Target height (ignored if `$w` is WP image size). If not
	 *                          set, will ignore and resize based on `$w` only. Default `0`.
	 * @param string     $crop  Optional. Your choices are `default`, `center`, `top`, `bottom`,
	 *                          `left`, `right`. Default `default`.
	 * @param bool       $force Optional. Whether to remove any already existing result file and
	 *                          force file generation. Default `false`.
	 * @return string The URL of the resized image.
	 */
	public static function resize( $src, $w, $h = 0, $crop = 'default', $force = false ) {
		if ( !is_numeric($w) && is_string($w) ) {
			if ( $sizes = self::find_wp_dimensions($w) ) {
				$w = $sizes['w'];
				$h = $sizes['h'];
			} else {
				return $src;
			}
		}
		$op = new Operation\Resize($w, $h, $crop);
		return self::_operate($src, $op, $force);
	}

	/**
	 * Finds the sizes of an image based on a defined image size.
	 *
	 * @internal
	 * @param  string $size The image size to search for can be WordPress-defined ('medium') or
	 *                      user-defined ('my-awesome-size').
	 * @return false|array An array with `w` and `h` height key, corresponding to the width and the
	 *                     height of the image.
	 */
	private static function find_wp_dimensions( $size ) {
		global $_wp_additional_image_sizes;
		if ( isset($_wp_additional_image_sizes[ $size ]) ) {
			$w = $_wp_additional_image_sizes[ $size ]['width'];
			$h = $_wp_additional_image_sizes[ $size ]['height'];
		} elseif ( in_array($size, array('thumbnail', 'medium', 'large')) ) {
			$w = get_option($size . '_size_w');
			$h = get_option($size . '_size_h');
		}
		if ( isset($w) && isset($h) && ($w || $h) ) {
			return array('w' => $w, 'h' => $h);
		}
		return false;
	}

	/**
	 * Generates a new image with increased size, for display on Retina screens.
	 *
	 * @api
	 *
	 * @param string  $src        URL of the file to read from.
	 * @param float   $multiplier Optional. Factor the original dimensions should be multiplied
	 *                            with. Default `2`.
	 * @param boolean $force      Optional. Whether to remove any already existing result file and
	 *                            force file generation. Default `false`.
	 * @return string URL to the new image.
	 */
	public static function retina_resize( $src, $multiplier = 2, $force = false ) {
		$op = new Operation\Retina($multiplier);
		return self::_operate($src, $op, $force);
	}

	/**
	 * Checks to see if the given file is an animated GIF.
	 *
	 * @api
	 *
	 * @param string $file Local filepath to a file, not a URL.
	 * @return boolean True if it’s an animated GIF, false if not.
	 */
	public static function is_animated_gif( $file ) {
		if ( strpos(strtolower($file), '.gif') === false ) {
			//doesn't have .gif, bail
			return false;
		}
		// Its a gif so test
		if ( ! ($fh = @fopen($file, 'rb')) ) {
			return false;
		}
		$count = 0;
		// An animated gif contains multiple "frames", with each frame having a
		// header made up of:
		// * a static 4-byte sequence (\x00\x21\xF9\x04).
		// * 4 variable bytes.
		// * a static 2-byte sequence (\x00\x2C).
		// We read through the file til we reach the end of the file, or we've found.
		// at least 2 frame headers.
		while ( ! feof($fh) && $count < 2 ) {
			$chunk = fread($fh, 1024 * 100); //read 100kb at a time
			$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
		}

		fclose($fh);
		return $count > 1;
	}

	/**
	 * Checks if file is an SVG.
	 *
	 * @param string $file_path File path to check.
	 * @return bool True if SVG, false if not SVG or file doesn't exist.
	 */
	public static function is_svg( $file_path ) {
		if ( '' === $file_path || ! file_exists( $file_path ) ) {
			return false;
		}

		if ( TextHelper::ends_with( strtolower($file_path), '.svg' ) ) {
			return true;
		}

		/**
		 * Try reading mime type.
		 *
		 * SVG images are not allowed by default in WordPress, so we have to pass a default mime
		 * type for SVG images.
		 */
		$mime = wp_check_filetype_and_ext( $file_path, PathHelper::basename( $file_path ), array(
			'svg' => 'image/svg+xml',
		) );

		return in_array( $mime['type'], array(
			'image/svg+xml',
			'text/html',
			'text/plain',
			'image/svg',
		) );
	}

	/**
	 * Generate a new image with the specified dimensions.
	 *
	 * New dimensions are achieved by adding colored bands to maintain ratio.
	 *
	 * @api
	 *
	 * @param string  $src
	 * @param int     $w
	 * @param int     $h
	 * @param string  $color
	 * @param bool    $force
	 * @return string
	 */
	public static function letterbox( $src, $w, $h, $color = false, $force = false ) {
		$op = new Operation\Letterbox($w, $h, $color);
		return self::_operate($src, $op, $force);
	}

	/**
	 * Generates a new image by converting the source GIF or PNG into JPG.
	 *
	 * @api
	 *
	 * @param string $src   A URL or path to the image
	 *                      (http://example.org/wp-content/uploads/2014/image.jpg) or
	 *                      (/wp-content/uploads/2014/image.jpg).
	 * @param string $bghex The hex color to use for transparent zones.
	 * @return string The URL of the processed image.
	 */
	public static function img_to_jpg( $src, $bghex = '#FFFFFF', $force = false ) {
		$op = new Operation\ToJpg($bghex);
		return self::_operate($src, $op, $force);
	}

	/**
	 * Generates a new image by converting the source into WEBP if supported by the server.
	 *
	 * @param string $src     A URL or path to the image
	 *                        (http://example.org/wp-content/uploads/2014/image.webp) or
	 *                        (/wp-content/uploads/2014/image.webp).
	 * @param int    $quality Range from `0` (worst quality, smaller file) to `100` (best quality,
	 *                        biggest file).
	 * @param bool   $force   Optional. Whether to remove any already existing result file and
	 *                        force file generation. Default `false`.
	 * @return string The URL of the processed image. If webp is not supported, a jpeg image will be
	 *                        generated.
	 */
	public static function img_to_webp( $src, $quality = 80, $force = false ) {
		$op = new Operation\ToWebp($quality);
		return self::_operate($src, $op, $force);
	}

	//-- end of public methods --//

	/**
	 * Deletes all resized versions of an image when the source is deleted.
	 *
	 * @since 1.5.0
	 * @param int   $post_id An attachment ID.
	 */
	public static function delete_attachment( $post_id ) {
		self::_delete_generated_if_image($post_id);
	}

	/**
	 * Delete all resized version of an image when its meta data is regenerated.
	 *
	 * @since 1.5.0
	 * @param array $metadata Existing metadata.
	 * @param int   $post_id  An attachment ID.
	 * @return array
	 */
	public static function generate_attachment_metadata( $metadata, $post_id ) {
		self::_delete_generated_if_image($post_id);
		return $metadata;
	}

	/**
	 * Adds a 'relative' key to wp_upload_dir() result.
	 *
	 * It will contain the relative url to upload dir.
	 *
	 * @since 1.5.0
	 * @param array $arr
	 * @return array
	 */
	public static function add_relative_upload_dir_key( $arr ) {
		$arr['relative'] = str_replace(self::$home_url, '', $arr['baseurl']);
		return $arr;
	}

	/**
	 * Checks if attachment is an image before deleting generated files.
	 *
	 * @param int $post_id An attachment ID.
	 */
	public static function _delete_generated_if_image( $post_id ) {
		if ( wp_attachment_is_image($post_id) ) {
			$attachment = Timber::get_post($post_id);
			if ( $attachment->file_loc ) {
				ImageHelper::delete_generated_files($attachment->file_loc);
			}
		}
	}

	/**
	 * Deletes the auto-generated files for resize and letterboxing created by Timber.
	 *
	 * @param string $local_file ex: /var/www/wp-content/uploads/2015/my-pic.jpg
	 *                           or: http://example.org/wp-content/uploads/2015/my-pic.jpg
	 */
	static function delete_generated_files( $local_file ) {
		if ( URLHelper::is_absolute($local_file) ) {
			$local_file = URLHelper::url_to_file_system($local_file);
		}
		$info = PathHelper::pathinfo($local_file);
		$dir = $info['dirname'];
		$ext = $info['extension'];
		$filename = $info['filename'];
		self::process_delete_generated_files($filename, $ext, $dir, '-[0-9999999]*', '-[0-9]*x[0-9]*-c-[a-z]*.');
		self::process_delete_generated_files($filename, $ext, $dir, '-lbox-[0-9999999]*', '-lbox-[0-9]*x[0-9]*-[a-zA-Z0-9]*.');
		self::process_delete_generated_files($filename, 'jpg', $dir, '-tojpg.*');
		self::process_delete_generated_files($filename, 'jpg', $dir, '-tojpg-[0-9999999]*');
	}

	/**
	 * Deletes resized versions of the supplied file name.
	 *
	 * If passed a value like my-pic.jpg, this function will delete my-pic-500x200-c-left.jpg, my-pic-400x400-c-default.jpg, etc.
	 *
	 * Keeping these here so I know what the hell we’re matching
	 * $match = preg_match("/\/srv\/www\/wordpress-develop\/src\/wp-content\/uploads\/2014\/05\/$filename-[0-9]*x[0-9]*-c-[a-z]*.jpg/", $found_file);
	 * $match = preg_match("/\/srv\/www\/wordpress-develop\/src\/wp-content\/uploads\/2014\/05\/arch-[0-9]*x[0-9]*-c-[a-z]*.jpg/", $filename);
	 *
	 * @param string  $filename       ex: my-pic.
	 * @param string  $ext            ex: jpg.
	 * @param string  $dir            var/www/wp-content/uploads/2015/.
	 * @param string  $search_pattern Pattern of files to pluck from.
	 * @param string  $match_pattern  Pattern of files to go forth and delete.
	 */
	protected static function process_delete_generated_files( $filename, $ext, $dir, $search_pattern, $match_pattern = null ) {
		$searcher = '/'.$filename.$search_pattern;
		$files = glob($dir.$searcher);
		if ( $files === false || empty($files) ) {
			return;
		}
		foreach ( $files as $found_file ) {
			$pattern = '/'.preg_quote($dir, '/').'\/'.preg_quote($filename, '/').$match_pattern.preg_quote($ext, '/').'/';
			$match = preg_match($pattern, $found_file);
			if ( !$match_pattern || $match ) {
				unlink($found_file);
			}
		}
	}

	/**
	 * Determines the filepath corresponding to a given URL.
	 *
	 * @param string $url
	 * @return string
	 */
	public static function get_server_location( $url ) {
		// if we're already an absolute dir, just return.
		if ( 0 === strpos($url, ABSPATH) ) {
			return $url;
		}
		// otherwise, analyze URL then build mapping path
		$au = self::analyze_url($url);
		$result = self::_get_file_path($au['base'], $au['subdir'], $au['basename']);
		return $result;
	}

	/**
	 * Determines the filepath where a given external file will be stored.
	 *
	 * @param string  $file
	 * @return string
	 */
	public static function get_sideloaded_file_loc( $file ) {
		$upload = wp_upload_dir();
		$dir = $upload['path'];
		$filename = $file;
		$file = parse_url($file);
		$path_parts = PathHelper::pathinfo($file['path']);
		$basename = md5($filename);
		$ext = 'jpg';
		if ( isset($path_parts['extension']) ) {
			$ext = $path_parts['extension'];
		}
		return $dir.'/'.$basename.'.'.$ext;
	}

	/**
	 * Downloads an external image to the server and stores it on the server.
	 *
	 * External/sideloaded images are saved in a folder named **external** in the uploads folder.
	 * If you want to change the folder that is used for your sideloaded images, you can use the
	 * [`timber/sideload_image/subdir`](https://timber.github.io/docs/v2/hooks/filters/#timber/sideload_image/subdir)
	 * filter. You can disable this behavior using the same filter.
	 *
	 * @param string $file The URL to the original file.
	 *
	 * @return string The URL to the downloaded file.
	 */
	public static function sideload_image( $file ) {
		/**
		 * Adds a filter to change the upload folder temporarily.
		 *
		 * This is necessary so that external images are not downloaded every month in case
		 * year-month-based folders are used. We need to use the `upload_dir` filter, because we use
		 * functions like `wp_upload_bits()` which uses `wp_upload_dir()` under the hood.
		 *
		 * @ticket 1098
		 * @link https://github.com/timber/timber/issues/1098
		 */
		add_filter( 'upload_dir', [ __CLASS__, 'set_sideload_image_upload_dir' ] );

		$loc = self::get_sideloaded_file_loc($file);
		if ( file_exists($loc) ) {
			$url = URLHelper::file_system_to_url($loc);

			remove_filter( 'upload_dir', [ __CLASS__, 'set_sideload_image_upload_dir' ] );

			return $url;
		}
		// Download file to temp location
		if ( !function_exists('download_url') ) {
			require_once ABSPATH.'/wp-admin/includes/file.php';
		}
		$tmp = download_url($file);
		preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);
		$file_array = array();
		$file_array['name'] = PathHelper::basename($matches[0]);
		$file_array['tmp_name'] = $tmp;
		// If error storing temporarily, do not use
		if ( is_wp_error($tmp) ) {
			$file_array['tmp_name'] = '';
		}
		// do the validation and storage stuff
		$locinfo = PathHelper::pathinfo($loc);
		$file = wp_upload_bits($locinfo['basename'], null, file_get_contents($file_array['tmp_name']));
		// delete tmp file
		@unlink($file_array['tmp_name']);

		remove_filter( 'upload_dir', [ __CLASS__, 'set_sideload_image_upload_dir' ] );

		return $file['url'];
	}

	/**
	 * Gets upload folder definition for sideloaded images.
	 *
	 * Used by ImageHelper::sideload_image().
	 *
	 * @internal
	 * @since 2.0.0
	 * @see   \Timber\ImageHelper::sideload_image()
	 *
	 * @param array $upload Array of information about the upload directory.
	 *
	 * @return array 		Array of information about the upload directory, modified by this
	 *						function.
	 */
	public static function set_sideload_image_upload_dir( array $upload ) {
		$subdir = 'external';

		/**
		 * Filters to directory that should be used for sideloaded images.
		 *
		 * @since 2.0.0
		 * @example
		 * ```php
		 * // Change the subdirectory used for sideloaded images.
		 * add_filter( 'timber/sideload_image/subdir', function( $subdir ) {
		 *     return 'sideloaded';
		 * } );
		 *
		 * // Disable subdirectory used for sideloaded images.
		 * add_filter( 'timber/sideload_image/subdir', '__return_false' );
		 * ```
		 *
		 * @param string $subdir The subdir name to use for sideloaded images. Return an empty
		 *                       string or a falsey value in order to not use a subfolder. Default
		 *                       `external`.
		 */
		$subdir = apply_filters( 'timber/sideload_image/subdir', $subdir );

		if ( ! empty( $subdir ) ) {
			// Remove slashes before or after.
			$subdir = trim( $subdir, '/' );

			$upload['subdir'] = '/' . $subdir;
			$upload['path']   = $upload['basedir'] . $upload['subdir'];
			$upload['url']    = $upload['baseurl'] . $upload['subdir'];
		}

		return $upload;
	}

	/**
	 * Takes a URL and breaks it into components.
	 *
	 * The components can then be used in the different steps of image processing.
	 * The image is expected to be either part of a theme, plugin, or an upload.
	 *
	 * @param  string $url A URL (absolute or relative) pointing to an image.
	 * @return array       An array (see keys in code below).
	 */
	public static function analyze_url( $url ) {
		$result = array(
			'url' => $url, // the initial url
			'absolute' => URLHelper::is_absolute($url), // is the url absolute or relative (to home_url)
			'base' => 0, // is the image in uploads dir, or in content dir (theme or plugin)
			'subdir' => '', // the path between base (uploads or content) and file
			'filename' => '', // the filename, without extension
			'extension' => '', // the file extension
			'basename' => '', // full file name
		);
		$upload_dir = wp_upload_dir();
		$tmp = $url;
		if ( TextHelper::starts_with($tmp, ABSPATH) || TextHelper::starts_with($tmp, '/srv/www/') ) {
			// we've been given a dir, not an url
			$result['absolute'] = true;
			if ( TextHelper::starts_with($tmp, $upload_dir['basedir']) ) {
				$result['base'] = self::BASE_UPLOADS; // upload based
				$tmp = URLHelper::remove_url_component($tmp, $upload_dir['basedir']);
			}
			if ( TextHelper::starts_with($tmp, WP_CONTENT_DIR) ) {
				$result['base'] = self::BASE_CONTENT; // content based
				$tmp = URLHelper::remove_url_component($tmp, WP_CONTENT_DIR);
			}
		} else {
			if ( !$result['absolute'] ) {
				$tmp = untrailingslashit(network_home_url()).$tmp;
			}
			if ( URLHelper::starts_with($tmp, $upload_dir['baseurl']) ) {
				$result['base'] = self::BASE_UPLOADS; // upload based
				$tmp = URLHelper::remove_url_component($tmp, $upload_dir['baseurl']);
			} else if ( URLHelper::starts_with($tmp, content_url()) ) {
				$result['base'] = self::BASE_CONTENT; // content-based
				$tmp = self::theme_url_to_dir($tmp);
				$tmp = URLHelper::remove_url_component($tmp, WP_CONTENT_DIR);
			}
		}
		$parts = PathHelper::pathinfo($tmp);
		$result['subdir'] = ($parts['dirname'] === '/') ? '' : $parts['dirname'];
		$result['filename'] = $parts['filename'];
		$result['extension'] = strtolower($parts['extension']);
		$result['basename'] = $parts['basename'];
		return $result;
	}

	/**
	 * Converts a URL located in a theme directory into the raw file path.
	 *
	 * @param string  $src A URL (http://example.org/wp-content/themes/twentysixteen/images/home.jpg).
	 * @return string Full path to the file in question.
	 */
	static function theme_url_to_dir( $src ) {
		$site_root = trailingslashit(get_theme_root_uri()).get_stylesheet();
		$tmp = str_replace($site_root, '', $src);
		//$tmp = trailingslashit(get_theme_root()).get_stylesheet().$tmp;
		$tmp = get_stylesheet_directory().$tmp;
		if ( realpath($tmp) ) {
			return realpath($tmp);
		}
		return $tmp;
	}

	/**
	 * Checks if uploaded image is located in theme.
	 *
	 * @param string $path image path.
	 * @return bool     If the image is located in the theme directory it returns true.
	 *                  If not or $path doesn't exits it returns false.
	 */
	protected static function is_in_theme_dir( $path ) {
		$root = realpath(get_stylesheet_directory());

		if ( false === $root ) {
			return false;
		}

		if ( 0 === strpos($path, (string) $root) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Builds the public URL of a file based on its different components.
	 *
	 * @param  int    $base     One of `self::BASE_UPLOADS`, `self::BASE_CONTENT` to indicate if
	 *                          file is an upload or a content (theme or plugin).
	 * @param  string $subdir   Subdirectory in which file is stored, relative to $base root
	 *                          folder.
	 * @param  string $filename File name, including extension (but no path).
	 * @param  bool   $absolute Should the returned URL be absolute (include protocol+host), or
	 *                          relative.
	 * @return string           The URL.
	 */
	private static function _get_file_url( $base, $subdir, $filename, $absolute ) {
		$url = '';
		if ( self::BASE_UPLOADS == $base ) {
			$upload_dir = wp_upload_dir();
			$url = $upload_dir['baseurl'];
		}
		if ( self::BASE_CONTENT == $base ) {
			$url = content_url();
		}
		if ( !empty($subdir) ) {
			$url .= $subdir;
		}
		$url = untrailingslashit($url).'/'.$filename;
		if ( !$absolute ) {
			$home = home_url();
			$home = apply_filters('timber/image_helper/_get_file_url/home_url', $home);
			$url = str_replace($home, '', $url);
		}
		return $url;
	}

	/**
	 * Runs realpath to resolve symbolic links (../, etc). But only if it’s a path and not a URL.
	 *
	 * @param  string $path
	 * @return string The resolved path.
	 */
	protected static function maybe_realpath( $path ) {
		if ( strstr($path, '../') !== false ) {
			return realpath($path);
		}
		return $path;
	}

	/**
	 * Builds the absolute file system location of a file based on its different components.
	 *
	 * @param  int    $base     One of `self::BASE_UPLOADS`, `self::BASE_CONTENT` to indicate if
	 *                          file is an upload or a content (theme or plugin).
	 * @param  string $subdir   Subdirectory in which file is stored, relative to $base root
	 *                          folder.
	 * @param  string $filename File name, including extension (but no path).
	 * @return string           The file location.
	 */
	private static function _get_file_path( $base, $subdir, $filename ) {
		if ( URLHelper::is_url($subdir) ) {
			$subdir = URLHelper::url_to_file_system($subdir);
		}
		$subdir = self::maybe_realpath($subdir);

		$path = '';
		if ( self::BASE_UPLOADS == $base ) {
			//it is in the Uploads directory
			$upload_dir = wp_upload_dir();
			$path = $upload_dir['basedir'];
		} else if ( self::BASE_CONTENT == $base ) {
			//it is in the content directory, somewhere else ...
			$path = WP_CONTENT_DIR;
		}
		if ( self::is_in_theme_dir(trailingslashit($subdir).$filename) ) {
			//this is for weird installs when the theme folder is outside of /wp-content
			return trailingslashit($subdir).$filename;
		}
		if ( !empty($subdir) ) {
			$path = trailingslashit($path).$subdir;
		}
		$path = trailingslashit($path).$filename;

		return URLHelper::remove_double_slashes($path);
	}

	/**
	 * Main method that applies operation to src image:
	 * 1. break down supplied URL into components
	 * 2. use components to determine result file and URL
	 * 3. check if a result file already exists
	 * 4. otherwise, delegate to supplied TimberImageOperation
	 *
	 * @param  string  $src   A URL (absolute or relative) to an image.
	 * @param  object  $op    Object of class TimberImageOperation.
	 * @param  boolean $force Optional. Whether to remove any already existing result file and
	 *                        force file generation. Default `false`.
	 * @return string URL to the new image - or the source one if error.
	 */
	private static function _operate( $src, $op, $force = false ) {
		if ( empty($src) ) {
			return '';
		}

		$allow_fs_write = apply_filters('timber/allow_fs_write', true);

		if ( $allow_fs_write === false ) {
			return $src;
		}

		$external = false;
		// if external image, load it first
		if ( URLHelper::is_external_content($src) ) {
			$src = self::sideload_image($src);
			$external = true;
		}

		// break down URL into components
		$au = self::analyze_url($src);

		// build URL and filenames
		$new_url = self::_get_file_url(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension']),
			$au['absolute']
		);
		$destination_path = self::_get_file_path(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension'])
		);
		$source_path = self::_get_file_path(
			$au['base'],
			$au['subdir'],
			$au['basename']
		);

		/**
		 * Filters the URL for the resized version of a `Timber\Image`.
		 *
		 * You’ll probably need to use this in combination with `timber/image/new_path`.
		 *
		 * @since 1.0.0
		 *
		 * @param string $new_url The URL to the resized version of an image.
		 */
		$new_url = apply_filters('timber/image/new_url', $new_url);

		/**
		 * Filters the destination path for the resized version of a `Timber\Image`.
		 *
		 * A possible use case for this would be to store all images generated by Timber in a
		 * separate directory. You’ll probably need to use this in combination with
		 * `timber/image/new_url`.
		 *
		 * @since 1.0.0
		 *
		 * @param string $destination_path Full path to the destination of a resized image.
		 */
		$destination_path = apply_filters('timber/image/new_path', $destination_path);

		// if already exists...
		if ( file_exists($source_path) && file_exists($destination_path) ) {
			if ( $force || filemtime($source_path) > filemtime($destination_path) ) {
				// Force operation - warning: will regenerate the image on every pageload, use for testing purposes only!
				unlink($destination_path);
			} else {
				// return existing file (caching)
				return $new_url;
			}
		}
		// otherwise generate result file
		if ( $op->run($source_path, $destination_path) ) {
			if ( get_class($op) === 'Timber\Image\Operation\Resize' && $external ) {
				$new_url = strtolower($new_url);
			}
			return $new_url;
		} else {
			// in case of error, we return source file itself
			return $src;
		}
	}


// -- the below methods are just used for unit testing the URL generation code
//
	/**
	 * @internal
	 */
	public static function get_letterbox_file_url( $url, $w, $h, $color ) {
		$au = self::analyze_url($url);
		$op = new Operation\Letterbox($w, $h, $color);
		$new_url = self::_get_file_url(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension']),
			$au['absolute']
		);
		return $new_url;
	}

	/**
	 * @internal
	 */
	public static function get_letterbox_file_path( $url, $w, $h, $color ) {
		$au = self::analyze_url($url);
		$op = new Operation\Letterbox($w, $h, $color);
		$new_path = self::_get_file_path(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension'])
		);
		return $new_path;
	}

	/**
	 * @internal
	 */
	public static function get_resize_file_url( $url, $w, $h, $crop ) {
		$au = self::analyze_url($url);
		$op = new Operation\Resize($w, $h, $crop);
		$new_url = self::_get_file_url(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension']),
			$au['absolute']
		);
		return $new_url;
	}

	/**
	 * @internal
	 */
	public static function get_resize_file_path( $url, $w, $h, $crop ) {
		$au = self::analyze_url($url);
		$op = new Operation\Resize($w, $h, $crop);
		$new_path = self::_get_file_path(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension'])
		);
		return $new_path;
	}
}
