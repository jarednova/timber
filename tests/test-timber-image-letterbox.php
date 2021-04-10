<?php

class TestTimberImageLetterbox extends TimberImage_UnitTestCase {

	function setUp() {
		parent::setUp();
		if ( ! extension_loaded( 'gd' ) ) {
			self::markTestSkipped( 'Letterbox image operation tests requires GD extension' );
		}
	}

	function testLetterbox() {
		$file_loc = TestTimberImage::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$image = $upload_dir['url'].'/eastern.jpg';
		$new_file = TimberImageHelper::letterbox( $image, 500, 500, '#CCC', true );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
		//whats the bg/color of the image
		$this->assertTrue( TestTimberImage::checkPixel($location_of_image, 1, 1, "#CCC") );
	}

	function testLetterboxArray() {
		$file_loc = TestTimberImage::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$image = $upload_dir['url'].'/eastern.jpg';
		$new_file = TimberImageHelper::letterbox( $image, array( 'w' => 500, 'h' => 500, 'color' => '#CCC' ), true );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
		//whats the bg/color of the image
		$this->assertTrue( TestTimberImage::checkPixel($location_of_image, 1, 1, "#CCC") );
	}

	function testLetterboxColorChange() {
		$file_loc = TestTimberImage::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$new_file_red = TimberImageHelper::letterbox( $upload_dir['url'].'/eastern.jpg', 500, 500, '#FF0000' );
		$new_file = TimberImageHelper::letterbox( $upload_dir['url'].'/eastern.jpg', 500, 500, '#00FF00' );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
		//whats the bg/color of the image
		$image = imagecreatefromjpeg( $location_of_image );
		$pixel_rgb = imagecolorat( $image, 1, 1 );
		$colors = imagecolorsforindex( $image, $pixel_rgb );
		$this->assertEquals( 0, $colors['red'] );
		$this->assertEquals( 255, $colors['green'] );
	}

	function testLetterboxTransparent() {
		$base_file = 'eastern-trans.png';
		$file_loc = TestTimberImage::copyTestImage( $base_file );
		$upload_dir = wp_upload_dir();
		$new_file = TimberImageHelper::letterbox( $upload_dir['url'].'/'.$base_file, 500, 500, '00FF00', true );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
		//whats the bg/color of the image
		$is_green = TestTimberImage::checkPixel($location_of_image, 250, 250, '#00FF00');
		$this->assertTrue( $is_green );
		$this->assertFileExists( $location_of_image );
	}

	function testLetterboxTransparentBackground() {
		$base_file = 'eastern-trans.png';
		$file_loc = TestTimberImage::copyTestImage( $base_file );
		$upload_dir = wp_upload_dir();
		$new_file = TimberImageHelper::letterbox( $upload_dir['url'].'/'.$base_file, 500, 500 );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
		// whats the bg/color of the image?
		$is_trans = TestTimberImage::checkPixel($location_of_image, 250, 250, false);
		$this->assertFileExists( $location_of_image );
		$this->assertTrue( $is_trans );
	}

	function testLetterboxGif() {
		$base_file = 'panam.gif';
		$file_loc = TestTimberImage::copyTestImage( $base_file );
		$upload_dir = wp_upload_dir();
		$new_file = TimberImageHelper::letterbox( $upload_dir['url'].'/'.$base_file, 300, 100, '00FF00', true );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue (TestTimberImage::checkSize($location_of_image, 300, 100));
		//whats the bg/color of the image
		$this->assertTrue( TestTimberImage::checkPixel($location_of_image, 50, 10, "#00FF00", "#00FF10") );
		$this->assertFileExists( $location_of_image );
	}

	function testLetterboxSixCharHex() {
		$data = array();
		$file_loc = TestTimberImage::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$new_file = TimberImageHelper::letterbox( $upload_dir['url'].'/eastern.jpg', 500, 500, '#FFFFFF', true );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->addFile( $location_of_image );
		$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
		//whats the bg/color of the image
		$image = imagecreatefromjpeg( $location_of_image );
		$pixel_rgb = imagecolorat( $image, 1, 1 );
		$colors = imagecolorsforindex( $image, $pixel_rgb );
		$this->assertEquals( 255, $colors['red'] );
		$this->assertEquals( 255, $colors['blue'] );
		$this->assertEquals( 255, $colors['green'] );
	}

	function testImageLetterboxFilterNotAnImage() {
		self::enable_error_log(false);
		$str = 'Image? {{"/wp-content/uploads/2016/07/stuff.jpg"|letterbox(500, 500)}}';
		$compiled = Timber::compile_string($str);
		$this->assertEquals('Image? /wp-content/uploads/2016/07/stuff.jpg', $compiled);
		self::enable_error_log(true);
	}
}
