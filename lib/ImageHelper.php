<?php

namespace Timber;

use Timber\Image;
use Timber\Image\Operation\ToJpg;
use Timber\Image\Operation\Resize;
use Timber\Image\Operation\Retina;
use Timber\Image\Operation\Letterbox;

use Timber\URLHelper;

/**
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
 */
class ImageHelper {

	const BASE_UPLOADS = 1;
	const BASE_CONTENT = 2;

	static $home_url;

	public static function init() {
		self::$home_url = get_home_url();
		add_action('delete_attachment', array(__CLASS__, 'delete_attachment'));
		add_filter('wp_generate_attachment_metadata', array(__CLASS__, 'generate_attachment_metadata'), 10, 2);
		add_filter('upload_dir', array(__CLASS__, 'add_relative_upload_dir_key'), 10, 2);
		return true;
	}

	/**
	 * Generates a new image with the specified dimensions.
	 * New dimensions are achieved by cropping to maintain ratio.
	 *
	 * @api
	 * @param string  		$src an URL (absolute or relative) to the original image
	 * @param int|string	$w target width(int) or WordPress image size (WP-set or user-defined).
	 * @param int     		$h target height (ignored if $w is WP image size). If not set, will ignore and resize based on $w only.
	 * @param string  		$crop your choices are 'default', 'center', 'top', 'bottom', 'left', 'right'
	 * @param bool    		$force
	 * @example
	 * ```twig
	 * <img src="{{ image.src | resize(300, 200, 'top') }}" />
	 * ```
	 * ```html
	 * <img src="http://example.org/wp-content/uploads/pic-300x200-c-top.jpg" />
	 * ```
	 * @return string (ex: )
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
		$op = new Image\Operation\Resize($w, $h, $crop);
		return self::_operate($src, $op, $force);
	}

	/**
	 * Find the sizes of an image based on a defined image size
	 * @param  string $size the image size to search for
	 *                      can be WordPress-defined ("medium")
	 *                      or user-defined ("my-awesome-size")
	 * @return false|array {
	 *     @type int w
	 *     @type int h
	 * }
	 */
	private static function find_wp_dimensions( $size ) {
		global $_wp_additional_image_sizes;
		if ( isset($_wp_additional_image_sizes[$size]) ) {
			$w = $_wp_additional_image_sizes[$size]['width'];
			$h = $_wp_additional_image_sizes[$size]['height'];
		} else if ( in_array($size, array('thumbnail', 'medium', 'large')) ) {
			$w = get_option($size.'_size_w');
			$h = get_option($size.'_size_h');
		}
		if ( isset($w) && isset($h) && ($w || $h) ) {
			return array('w' => $w, 'h' => $h);
		}
		return false;
	}

	/**
	 * Generates a new image with increased size, for display on Retina screens.
	 *
	 * @param string  $src
	 * @param float   $multiplier
	 * @param boolean $force
	 *
	 * @return string url to the new image
	 */
	public static function retina_resize( $src, $multiplier = 2, $force = false ) {
		$op = new Image\Operation\Retina($multiplier);
		return self::_operate($src, $op, $force);
	}

	/**
	 * checks to see if the given file is an aimated gif
	 * @param  string  $file local filepath to a file, not a URL
	 * @return boolean true if it's an animated gif, false if not
	 */
	public static function is_animated_gif( $file ) {
		if ( strpos(strtolower($file), '.gif') === false ) {
			//doesn't have .gif, bail
			return false;
		}
		//its a gif so test
		if ( !($fh = @fopen($file, 'rb')) ) {
		  	return false;
		}
		$count = 0;
		//an animated gif contains multiple "frames", with each frame having a
		//header made up of:
		// * a static 4-byte sequence (\x00\x21\xF9\x04)
		// * 4 variable bytes
		// * a static 2-byte sequence (\x00\x2C)

		// We read through the file til we reach the end of the file, or we've found
		// at least 2 frame headers
		while ( !feof($fh) && $count < 2 ) {
			$chunk = fread($fh, 1024 * 100); //read 100kb at a time
			$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
		}

		fclose($fh);
		return $count > 1;
	}

	/**
	 * Generate a new image with the specified dimensions.
	 * New dimensions are achieved by adding colored bands to maintain ratio.
	 *
	 * @param string  $src
	 * @param int     $w
	 * @param int     $h
	 * @param string  $color
	 * @param bool    $force
	 * @return string
	 */
	public static function letterbox( $src, $w, $h, $color = false, $force = false ) {
		$op = new Letterbox($w, $h, $color);
		return self::_operate($src, $op, $force);
	}

	/**
	 * Generates a new image by converting the source GIF or PNG into JPG
	 *
	 * @param string  $src   a url or path to the image (http://example.org/wp-content/uploads/2014/image.jpg) or (/wp-content/uploads/2014/image.jpg)
	 * @param string  $bghex
	 * @return string
	 */
	public static function img_to_jpg( $src, $bghex = '#FFFFFF', $force = false ) {
		$op = new Image\Operation\ToJpg($bghex);
		return self::_operate($src, $op, $force);
	}

	//-- end of public methods --//

	/**
	 * Deletes all resized versions of an image when the source is deleted.
	 *
	 * @since 1.5.0
	 * @param int   $post_id an attachment post id
	 */
	public static function delete_attachment( $post_id ) {
		self::_delete_generated_if_image($post_id);
	}


	/**
	 * Delete all resized version of an image when its meta data is regenerated.
	 *
	 * @since 1.5.0
	 * @param array $metadata
	 * @param int   $post_id an attachment post id
	 * @return array
	 */
	public static function generate_attachment_metadata( $metadata, $post_id ) {
		self::_delete_generated_if_image($post_id);
		return $metadata;
	}

	/**
	 * Adds a 'relative' key to wp_upload_dir() result.
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
	 * Checks if attachment is an image before deleting generated files
	 *
	 * @param  int  $post_id   an attachment post id
	 *
	 */
	public static function _delete_generated_if_image( $post_id ) {
		if ( wp_attachment_is_image($post_id) ) {
			$attachment = new Image($post_id);
			if ( $attachment->file_loc ) {
				ImageHelper::delete_generated_files($attachment->file_loc);
			}
		}
	}


	/**
	 * Deletes the auto-generated files for resize and letterboxing created by Timber
	 * @param string  $local_file   ex: /var/www/wp-content/uploads/2015/my-pic.jpg
	 *	                            or: http://example.org/wp-content/uploads/2015/my-pic.jpg
	 */
	static function delete_generated_files( $local_file ) {
		if ( URLHelper::is_absolute($local_file) ) {
			$local_file = URLHelper::url_to_file_system($local_file);
		}
		$info = pathinfo($local_file);
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
	 * So if passed a value like my-pic.jpg, this function will delete my-pic-500x200-c-left.jpg, my-pic-400x400-c-default.jpg, etc.
	 *
	 * keeping these here so I know what the hell we're matching
	 * $match = preg_match("/\/srv\/www\/wordpress-develop\/src\/wp-content\/uploads\/2014\/05\/$filename-[0-9]*x[0-9]*-c-[a-z]*.jpg/", $found_file);
	 * $match = preg_match("/\/srv\/www\/wordpress-develop\/src\/wp-content\/uploads\/2014\/05\/arch-[0-9]*x[0-9]*-c-[a-z]*.jpg/", $filename);
	 *
	 * @param string 	$filename   ex: my-pic
	 * @param string 	$ext ex: jpg
	 * @param string 	$dir var/www/wp-content/uploads/2015/
	 * @param string 	$search_pattern pattern of files to pluck from
	 * @param string 	$match_pattern pattern of files to go forth and delete
	 */
	protected static function process_delete_generated_files( $filename, $ext, $dir, $search_pattern, $match_pattern = null ) {
		$searcher = '/'.$filename.$search_pattern;
		foreach ( glob($dir.$searcher) as $found_file ) {
			$pattern = '/'.preg_quote($dir, '/').'\/'.preg_quote($filename, '/').$match_pattern.preg_quote($ext, '/').'/';
			$match = preg_match($pattern, $found_file);
			if ( !$match_pattern || $match ) {
				unlink($found_file);
			}
		}
	}

	/**
	 * Determines the filepath corresponding to a given URL
	 *
	 * @param string  $url
	 * @return string
	 */
	public static function get_server_location( $url ) {
		// if we're already an absolute dir, just return
		if ( 0 === strpos($url, URLHelper::get_root_dir()) ) {
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
		$path_parts = pathinfo($file['path']);
		$basename = md5($filename);
		$ext = 'jpg';
		if ( isset($path_parts['extension']) ) {
			$ext = $path_parts['extension'];
		}
		return $dir.'/'.$basename.'.'.$ext;
	}

	/**
	 * downloads an external image to the server and stores it on the server
	 *
	 * @param string  $file the URL to the original file
	 * @return string the URL to the downloaded file
	 */
	public static function sideload_image( $file ) {
		$loc = self::get_sideloaded_file_loc($file);
		if ( file_exists($loc) ) {
			return URLHelper::file_system_to_url($loc);
		}
		// Download file to temp location
		if ( !function_exists('download_url') ) {
			require_once ABSPATH.'/wp-admin/includes/file.php';
		}
		$tmp = download_url($file);
		preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);
		$file_array = array();
		$file_array['name'] = basename($matches[0]);
		$file_array['tmp_name'] = $tmp;
		// If error storing temporarily, unlink
		if ( is_wp_error($tmp) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}
		// do the validation and storage stuff
		$locinfo = pathinfo($loc);
		$file = wp_upload_bits($locinfo['basename'], null, file_get_contents($file_array['tmp_name']));
		return $file['url'];
	}

	/**
	 * Takes in an URL and breaks it into components,
	 * that will then be used in the different steps of image processing.
	 * The image is expected to be either part of a theme, plugin, or an upload.
	 *
	 * @param  string $url an URL (absolute or relative) pointing to an image
	 * @return array       an array (see keys in code below)
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
		if ( TextHelper::starts_with($tmp, URLHelper::get_root_dir()) ) {
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
		$parts = pathinfo($tmp);
		$result['subdir'] = ($parts['dirname'] === '/') ? '' : $parts['dirname'];
		$result['filename'] = $parts['filename'];
		$result['extension'] = strtolower($parts['extension']);
		$result['basename'] = $parts['basename'];
		return $result;
	}

	/**
	 * Converts a URL located in a theme directory into the raw file path
	 * @param string 	$src a URL (http://example.org/wp-content/themes/twentysixteen/images/home.jpg)
	 * @return string full path to the file in question
	 */
	static function theme_url_to_dir( $src ) 	{
		$site_root = trailingslashit(get_theme_root_uri()).get_stylesheet();
		$tmp = str_replace($site_root, '', $src);
		//$tmp = trailingslashit(get_theme_root()).get_stylesheet().$tmp;
		$tmp = get_stylesheet_directory().$tmp;
		if ( realpath($tmp) ) {
			return realpath($tmp);
		}
		return $tmp;
	}

	protected static function is_in_theme_dir( $path ) {
		$root = realpath(get_stylesheet_directory_uri());
		if ( 0 === strpos($path, $root) ) {
			return true;
		}
	}

	/**
	 * Builds the public URL of a file based on its different components
	 *
	 * @param  int    $base     one of self::BASE_UPLOADS, self::BASE_CONTENT to indicate if file is an upload or a content (theme or plugin)
	 * @param  string $subdir   subdirectory in which file is stored, relative to $base root folder
	 * @param  string $filename file name, including extension (but no path)
	 * @param  bool   $absolute should the returned URL be absolute (include protocol+host), or relative
	 * @return string           the URL
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
		$url .= '/'.$filename;
		if ( !$absolute ) {
			$url = str_replace(site_url(), '', $url);
		}
		// $url = TimberURLHelper::remove_double_slashes( $url);
		return $url;
	}

	/**
	 * Runs realpath to resolve symbolic links (../, etc). But only if it's a path and not a URL
	 * @param  string $path
	 * @return string 			the resolved path
	 */
	protected static function maybe_realpath( $path ) {
		if ( strstr($path, '../') !== false ) {
			return realpath($path);
		}
		return $path;
	}


	/**
	 * Builds the absolute file system location of a file based on its different components
	 *
	 * @param  int    $base     one of self::BASE_UPLOADS, self::BASE_CONTENT to indicate if file is an upload or a content (theme or plugin)
	 * @param  string $subdir   subdirectory in which file is stored, relative to $base root folder
	 * @param  string $filename file name, including extension (but no path)
	 * @return string           the file location
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
	 * @param  string  $src   an URL (absolute or relative) to an image
	 * @param  object  $op    object of class TimberImageOperation
	 * @param  boolean $force if true, remove any already existing result file and forces file generation
	 * @return string         URL to the new image - or the source one if error
	 *
	 */
	private static function _operate( $src, $op, $force = false ) {
		if ( empty($src) ) {
			return '';
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

		$new_url = apply_filters('timber/image/new_url', $new_url);
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
	public static function get_letterbox_file_url( $url, $w, $h, $color ) {
		$au = self::analyze_url($url);
		$op = new Image\Operation\Letterbox($w, $h, $color);
		$new_url = self::_get_file_url(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension']),
			$au['absolute']
		);
		return $new_url;
	}

	public static function get_letterbox_file_path( $url, $w, $h, $color ) {
		$au = self::analyze_url($url);
		$op = new Image\Operation\Letterbox($w, $h, $color);
		$new_path = self::_get_file_path(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension'])
		);
		return $new_path;
	}

	public static function get_resize_file_url( $url, $w, $h, $crop ) {
		$au = self::analyze_url($url);
		$op = new Image\Operation\Resize($w, $h, $crop);
		$new_url = self::_get_file_url(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension']),
			$au['absolute']
		);
		return $new_url;
	}

	public static function get_resize_file_path( $url, $w, $h, $crop ) {
		$au = self::analyze_url($url);
		$op = new Image\Operation\Resize($w, $h, $crop);
		$new_path = self::_get_file_path(
			$au['base'],
			$au['subdir'],
			$op->filename($au['filename'], $au['extension'])
		);
		return $new_path;
	}
}
