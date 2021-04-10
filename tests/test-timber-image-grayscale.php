<?php

class TestTimberImageGrayscale extends TimberImage_UnitTestCase {

	function setUp() {
		parent::setUp();
		if ( ! extension_loaded( 'gd' ) ) {
			self::markTestSkipped( 'Grayscale image operation tests requires GD extension' );
		}
	}

	function testGrayscale() {
		$file_loc = TestTimberImage::copyTestImage( 'red-circle.jpg' );
		$upload_dir = wp_upload_dir();
		$image = $upload_dir['url'] . '/red-circle.jpg';
		$new_file = TimberImageHelper::grayscale( $image );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue( TestTimberImage::checkPixel( $location_of_image, 100, 100, '#5D5D5D' ) );
	}

	function testImageGrayscaleFilterNotAnImage() {
		self::enable_error_log(false);
		$str = 'Image? {{"/wp-content/uploads/2016/07/stuff.jpg"|grayscale}}';
		$compiled = Timber::compile_string($str);
		$this->assertEquals('Image? /wp-content/uploads/2016/07/stuff.jpg', $compiled);
		self::enable_error_log(true);
	}
}
