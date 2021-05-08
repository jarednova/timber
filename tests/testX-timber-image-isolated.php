<?php

class TestTimberImageIsolated extends Timber_UnitTestCase {

/* ----------------
 * Helper functions
 ---------------- */

	static function copyTestImage( $img = 'arch.jpg' ) {
		$upload_dir = wp_upload_dir();
		$destination = $upload_dir['path'].'/'.$img;
		if ( !file_exists( $destination ) ) {
			copy( __DIR__.'/assets/'.$img, $destination );
		}
		return $destination;
	}

/*
 * This test HAS to be in a separate file, otherwise the UPLOADS const bleeds
 * in the other tests.
 */
	function testResizeFileNamingWithUploadsConst() {
		define('UPLOADS', 'my/up');
		$file_loc = self::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$url_src = $upload_dir['url'].'/eastern.jpg';
		$filename = Timber\ImageHelper::get_resize_file_url( $url_src, 300, 500, 'default' );
		$this->assertEquals($upload_dir['url'].'/eastern-300x500-c-default.jpg', $filename );
	}
}
