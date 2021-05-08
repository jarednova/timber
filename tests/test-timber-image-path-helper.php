<?php

	class TestTimberImagePathHelper extends TimberAttachment_UnitTestCase {

		function testImagePathLetterboxWithHebrew() {
			//path/to/איתין-נוף-נוסף.jpg

			$hebrew = self::copyTestAttachment('hebrew.jpg', 'איתין-נוף-נוסף.jpg');
			$upload_dir = wp_upload_dir();
			$image = $upload_dir['url'].'/איתין-נוף-נוסף.jpg';
			$new_file = Timber\ImageHelper::letterbox( $image, 500, 500, '#CCC', true );
			$location_of_image = Timber\ImageHelper::get_server_location( $new_file );
			$this->addFile( $location_of_image );
			$this->assertTrue (TestTimberImage::checkSize($location_of_image, 500, 500));
			//whats the bg/color of the image
			$this->assertTrue( TestTimberImage::checkPixel($location_of_image, 1, 1, "#CCC") );
		}

		function testImagePathStartsWithSpecialChar() {
			require_once('wp-overrides.php');
			$filename = self::copyTestAttachment('robocop.jpg', '©Robocop.jpg');
			$filesize = filesize($filename);
			$data = array('tmp_name' => $filename, 'name' => '©Robocop.jpg', 'type' => 'image/jpg', 'size' => $filesize, 'error' => 0);
			$this->assertTrue(file_exists($filename));
			$_FILES['tester'] = $data;
			$file_id = WP_Overrides::media_handle_upload('tester', 0, array(), array( 'test_form' => false));
			if (!is_int($file_id)) {
				error_log(print_r($file_id, true));
			}
			$image = Timber::get_post($file_id);
			$str = '<img src="{{image.src(\'medium\')}}" />';
			$result = Timber::compile_string($str, array('image' => $image));
			$upload_dir = wp_upload_dir();
			$this->assertEquals('<img src="'.$upload_dir['url'].'/'.$image->sizes['medium']['file'].'" />', trim($result));
		}

	}