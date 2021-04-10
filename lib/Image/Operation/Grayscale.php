<?php

namespace Timber\Image\Operation;

use Timber\Helper;
use Timber\ImageHelper;
use Timber\Image\Operation as ImageOperation;

/**
 * Implements converting an image to black and white.
 * Argument:
 * - color to fill transparent zones
 */
class Grayscale extends ImageOperation {

	/**
	 * @param   string    $src_filename     the basename of the file (ex: my-awesome-pic)
	 * @param   string    $src_extension    the extension (ex: .jpg)
	 * @return  string    the final filename to be used (ex: my-awesome-pic-bw.jpg)
	 */
	public function filename( $src_filename, $src_extension ) {
		$new_name = $src_filename . '-bw.' . $src_extension;
		return $new_name;
	}

	/**
	 * Performs the actual image manipulation,
	 * including saving the target file.
	 *
	 * @param  string $load_filename filepath (not URL) to source file (ex: /src/var/www/wp-content/uploads/my-pic.jpg)
	 * @param  string $save_filename filepath (not URL) where result file should be saved
	 *                               (ex: /src/var/www/wp-content/uploads/my-pic.png)
	 * @return bool                  true if everything went fine, false otherwise
	 */
	public function run( $load_filename, $save_filename ) {

		if ( ! file_exists($load_filename) ) {
			return false;
		}

		// Attempt to check if SVG.
		if ( ImageHelper::is_svg( $load_filename ) ) {
			return false;
		}

		$ext = wp_check_filetype( $load_filename );
		if ( isset( $ext['ext'] ) ) {
			$ext = $ext['ext'];
		}
		$ext = strtolower( $ext );
		$ext = str_replace( 'jpg', 'jpeg', $ext );

		$imagecreate_function = 'imagecreatefrom' . $ext;
		if ( !function_exists( $imagecreate_function ) ) {
			return false;
		}

		$input = $imagecreate_function( $load_filename );

		if ( ! imageistruecolor($input ) ) {
			imagepalettetotruecolor( $input );
		}

		if ( ! function_exists( 'imagefilter' ) ) {
			Helper::error_log( 'The function imagefilter does not exist on this server to convert image to ' . $save_filename . '.' );
			return false;
		}

		imagefilter( $input, IMG_FILTER_GRAYSCALE );

		$imageexec_function = 'image' . $ext;

		return $imageexec_function( $input, $save_filename );
	}

}
