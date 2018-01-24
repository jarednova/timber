<?php

use Timber\Image\Operation as ImageOperation;

class TestTimberImage extends TimberImage_UnitTestCase {

/* ----------------
 * Helper functions
 ---------------- */

 	static function replace_image( $old_id, $new_id ) {
		$uploadDir = wp_upload_dir();
		$newFile = $uploadDir['basedir'].'/'.get_post_meta($new_id, '_wp_attached_file', true);
		$oldFile = $uploadDir['basedir'].'/'.get_post_meta($old_id, '_wp_attached_file', true);
		if (!file_exists(dirname($oldFile)))
			mkdir(dirname($oldFile), 0777, true);
		copy($newFile, $oldFile);
		$meta = wp_generate_attachment_metadata($old_id, $oldFile);
		wp_update_attachment_metadata($old_id, $meta);
		wp_delete_post($new_id, true);
 	}

	static function copyTestImage( $img = 'arch.jpg', $dest_name = null ) {
		$upload_dir = wp_upload_dir();
		if ( is_null($dest_name) ) {
			$dest_name = $img;
		}
		$destination = $upload_dir['path'].'/'.$dest_name;
		copy( __DIR__.'/assets/'.$img, $destination );
		return $destination;
	}


	static function getTestImageURL( $img = 'arch.jpg', $relative = false) {
		$upload_dir = wp_upload_dir();
		$result = $upload_dir['url'].'/'.$img;
		if ( $relative ) {
			$result = str_replace(home_url(), '', $result);
		}
		return $result;
	}


	public static function is_connected() {
		$connected = @fsockopen( "www.google.com", 80, $errno, $errstr, 3 );
		if ( $connected ) {
			$is_conn = true; //action when connected
			fclose( $connected );
		} else {
			$is_conn = false; //action in connection failure
		}
		return $is_conn;
	}

	function add_lang_to_home( $url, $path, $orig_scheme, $blog_id ){
		return "$url?lang=en";
	}

	public static function get_image_attachment( $pid = 0, $file = 'arch.jpg' ) {
		$filename = self::copyTestImage( $file );
		$filetype = wp_check_filetype( basename( $filename ), null );
		$attachment = array('post_title' => 'The Arch', 'post_content' => '', 'post_mime_type' => $filetype['type']);
		$iid = wp_insert_attachment( $attachment, $filename, $pid );
		return $iid;
	}

	public function get_post_with_image() {
		$pid = $this->factory->post->create();
		$iid = self::get_image_attachment( $pid );
		add_post_meta( $pid, '_thumbnail_id', $iid, true );
		$post = new TimberPost($pid);
		return $post;
	}

	public static function get_timber_image_object($file = 'cropper.png') {
		$iid = self::get_image_attachment(0, $file);
		return new TimberImage($iid);
	}

/* ----------------
 * Tests
 ---------------- */

 	function testReplacedImage() {
 		$pid = $this->factory->post->create(array('post_type' => 'post'));
 		$attach_id = self::get_image_attachment($pid, 'arch.jpg');
 		$template = '{{Image(img).src|resize(200, 200)}}';
 		$str = Timber::compile_string($template, array('img' => $attach_id));
 		$resized_one = Timber\ImageHelper::get_server_location($str);
 		sleep(1);
 		$filename = self::copyTestImage('cardinals.jpg', 'arch.jpg');

 		$str = Timber::compile_string($template, array('img' => $attach_id));
 		$resized_tester = Timber\ImageHelper::get_server_location($str);

 		$attach_id = self::get_image_attachment($pid, 'cardinals.jpg');
 		$str = Timber::compile_string($template, array('img' => $attach_id));
 		$resized_known = Timber\ImageHelper::get_server_location($str);
 		$pixel = TestTimberImage::getPixel($resized_one, 5, 5);
 		$is_white = TestTimberImage::checkPixel($resized_one, 5, 5, '#FFFFFF');
 		$this->assertTrue($is_white);
 		$is_also_white = TestTimberImage::checkPixel($resized_one, 5,5, '#FFFFFF');
 		$this->assertTrue($is_also_white);
 	}

 	function testResizedReplacedImage() {
 		$pid = $this->factory->post->create(array('post_type' => 'post'));
 		$attach_id = self::get_image_attachment($pid, 'arch.jpg');
 		$template = '{{Image(img).src|resize(200, 200)}}';
 		$str = Timber::compile_string($template, array('img' => $attach_id));
 		$new_id = self::get_image_attachment($pid, 'pizza.jpg');
 		self::replace_image($attach_id, $new_id);
 		$str = Timber::compile_string($template, array('img' => $attach_id));
 		$resized_path = Timber\ImageHelper::get_server_location($str);
 		$test_md5 = md5( file_get_contents($resized_path) );


 		$str_pizza = Timber::compile_string($template, array('img' => $new_id));
 		$resized_pizza = Timber\ImageHelper::get_server_location($str);

 		$pizza_md5 = md5( file_get_contents($resized_pizza) );
 		$this->assertEquals($pizza_md5, $test_md5);
 	}

 	function testImageLink() {
 		self::setPermalinkStructure();
 		$attach = self::get_image_attachment();
 		$image = new TimberImage($attach);
 		$links = array();
 		$links[] = 'http://example.org/'.$image->post_name.'/';
 		$links[] = 'http://example.org/?attachment_id='.$image->ID;
 		$this->assertContains($image->link(), $links);
 	}

	function testImageMeta() {
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		update_post_meta( $image->ID, 'architect', 'Eero Saarinen' );
		$this->assertEquals( 'Eero Saarinen', $image->meta( 'architect' ) );
		$this->assertEquals( 'Eero Saarinen', $image->architect );
	}

	function testImageSizes() {
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		$this->assertEquals( 1500, $image->width() );
		$this->assertEquals( 1000, $image->height() );
		$this->assertEquals( $post->ID, $image->parent()->id );
		$this->assertEquals( 1.5, $image->aspect() );
	}

	function testExternalImageResize() {
		if ( !self::is_connected() ) {
			$this->markTestSkipped('Cannot test external images when not connected to internet');
		}
		$data = array();
		$data['size'] = array( 'width' => 600, 'height' => 400 );
		$data['crop'] = 'default';
		$filename = 'St._Louis_Gateway_Arch.jpg';
		$data['test_image'] = 'http://upload.wikimedia.org/wikipedia/commons/a/aa/'.$filename;
		$md5 = md5( $data['test_image'] );
		Timber::compile( 'assets/image-test.twig', $data );
		$upload_dir = wp_upload_dir();
		$path = $upload_dir['path'].'/'.$md5;
		/* was the external image D/Ld to the location? */
		$this->assertFileExists( $path.'.jpg' );
		/* does resize work on external image? */
		$resized_path = $path.'-'.$data['size']['width'].'x'.$data['size']['height'].'-c-'.$data['crop'].'.jpg';
		$this->assertFileExists( $resized_path );
		$old_time = filemtime( $resized_path );
		sleep( 1 );
		$str = Timber::compile( 'assets/image-test.twig', $data );
		$new_time = filemtime( $resized_path );
		$this->assertEquals( $old_time, $new_time );
	}

	function testUpSizing() {
		$data = array();
		$file_loc = self::copyTestImage( 'stl.jpg' );
		$upload_dir = wp_upload_dir();
		$new_file = TimberImageHelper::resize( $upload_dir['url'].'/stl.jpg', 500, 200, 'default', true );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$size = getimagesize( $location_of_image );
		$this->assertEquals( 500, $size[0] );
	}

	function testUpSizing2Param() {
		$data = array();
		$file_loc = self::copyTestImage( 'stl.jpg' );
		$upload_dir = wp_upload_dir();
		$new_file = TimberImageHelper::resize( $upload_dir['url'].'/stl.jpg', 500, 300, 'default', true );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$size = getimagesize( $location_of_image );
		$this->assertEquals( 500, $size[0] );
		$this->assertEquals( 300, $size[1] );
	}

	function testImageResizeRelative() {
		$upload_dir = wp_upload_dir();
		self::copyTestImage();
		$url = $upload_dir['url'].'/arch.jpg';
		$url = str_replace( 'http://example.org', '', $url );
		$data = array( 'crop' => 'default', 'test_image' => $url );
		$data['size'] = array( 'width' => 300, 'height' => 300 );
		$html = Timber::compile( 'assets/image-test.twig', $data );
		$resized_path = $upload_dir['path'].'/arch-'.$data['size']['width'].'x'.$data['size']['height'].'-c-'.$data['crop'].'.jpg';
		$this->assertFileExists( $resized_path );
		//Now make sure it doesnt regenerage
		$old_time = filemtime( $resized_path );
		sleep( 1 );
		Timber::compile( 'assets/image-test.twig', $data );
		$new_time = filemtime( $resized_path );
		$this->assertEquals( $old_time, $new_time );
	}

	function testImageResize() {
		$data = array();
		$data['size'] = array( 'width' => 600, 'height' => 400 );
		$upload_dir = wp_upload_dir();
		self::copyTestImage();
		$url = $upload_dir['url'].'/arch.jpg';
		$data['test_image'] = $url;
		$data['crop'] = 'default';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_path = $upload_dir['path'].'/arch-'.$data['size']['width'].'x'.$data['size']['height'].'-c-'.$data['crop'].'.jpg';
		$this->assertFileExists( $resized_path );
		//Now make sure it doesnt regenerage
		$old_time = filemtime( $resized_path );
		sleep( 1 );
		Timber::compile( 'assets/image-test.twig', $data );
		$new_time = filemtime( $resized_path );
		$this->assertEquals( $old_time, $new_time );
	}

	function testIsNotAGif() {
		$image = self::copyTestImage('arch.jpg');
		$this->assertFalse( TimberImageHelper::is_animated_gif($image) );
	}

	function testIsNotAGifFile() {
		$this->assertFalse( TimberImageHelper::is_animated_gif('notreal.gif') );
	}

	function testAnimatedGifResize() {
		if ( ! extension_loaded( 'imagick' ) ) {
			self::markTestSkipped( 'Animated GIF resizing test requires Imagick extension' );
		}
		$image = self::copyTestImage('robocop.gif');
		$data = array('crop' => 'default');
		$data['size'] = array('width' => 90, 'height' => 90);
		$upload_dir = wp_upload_dir();
		$url = $upload_dir['url'].'/robocop.gif';
		$data['test_image'] = $url;
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_path = $upload_dir['path'].'/robocop-'.$data['size']['width'].'x'.$data['size']['height'].'-c-'.$data['crop'].'.gif';
		$this->addFile( $resized_path );
		$this->assertFileExists( $resized_path );
		$this->assertTrue(TimberImageHelper::is_animated_gif($resized_path));
	}

	function testImageArray() {
		$post_id = $this->factory->post->create();
		$filename = self::copyTestImage('arch.jpg');
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		$data = array('ID' => $attach_id);
		$image = new Timber\Image($data);
		$filename = explode('/', $image->file);
		$filename = array_pop($filename);
		$this->assertEquals('arch.jpg', $filename);

	}

	function testResizeTallImage() {
		$data = array();
		$data['size'] = array( 'width' => 600 );
		$upload_dir = wp_upload_dir();
		self::copyTestImage( 'tall.jpg' );
		$url = $upload_dir['url'].'/tall.jpg';
		$data['test_image'] = $url;
		$data['crop'] = 'default';
		Timber::compile( 'assets/image-test-one-param.twig', $data );
		$resized_path = $upload_dir['path'].'/tall-'.$data['size']['width'].'x0'.'-c-'.$data['crop'].'.jpg';
		$exists = file_exists( $resized_path );
		$this->assertTrue( $exists );
		//make sure it's the width it's supposed to be
		$image = wp_get_image_editor( $resized_path );
		if ( $image instanceof WP_Error ) {
			self::markTestSkipped( 'Tall image resizing test is skipped because no image editor is provided by WordPress, make sure that either GD or Imagick extension is installed' );
		}
		$current_size = $image->get_size();
		$w = $current_size['width'];
		$this->assertEquals( $w, 600 );
	}

	function testInitFromRelativePath() {
		$filename = self::copyTestImage( 'arch.jpg' );
		$path = str_replace(ABSPATH, '/', $filename);
		$image = new TimberImage( $path );
		$this->assertEquals( 1500, $image->width() );
		$this->assertEquals( $filename, $image->file_loc );
	}

	function testImagePath() {
		$filename = self::copyTestImage( 'arch.jpg' );
		$image = new TimberImage( $filename );
		$this->assertStringStartsWith('/wp-content', $image->path());
		$this->assertStringEndsWith('.jpg', $image->path());
		$this->assertEquals( $filename, $image->file_loc );
	}

	function testInitFromID() {
		$pid = $this->factory->post->create();
		$filename = self::copyTestImage( 'arch.jpg' );
		$attachment = array( 'post_title' => 'The Arch', 'post_content' => '' );
		$iid = wp_insert_attachment( $attachment, $filename, $pid );
		$image = new TimberImage( $iid );
		$this->assertEquals( 1500, $image->width() );
		$this->assertEquals( $filename, $image->file_loc );
	}

	function testInitFromFilePath() {
		$image_file = self::copyTestImage();
		$image = new TimberImage( $image_file );
		$this->assertEquals( 1500, $image->width() );
		$this->assertEquals( $image_file, $image->file_loc );
	}

	function testInitFromURL() {
		$destination_path = self::copyTestImage();
		$destination_rel_path = TimberURLHelper::get_rel_path( $destination_path );
		$destination_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.$destination_rel_path;
		$image = new TimberImage( $destination_url );
		$this->assertEquals( $destination_url, $image->src() );
		$this->assertEquals( $destination_url, (string)$image );
		$this->assertEquals( $destination_path, $image->file_loc );
	}

	function testInitFromURLWithCustomWPDirectoryStructure() {
		$this->setupCustomWPDirectoryStructure();
		$destination_path = self::copyTestImage();
		$destination_rel_path = TimberURLHelper::get_rel_path( $destination_path );
		$destination_url = 'http://'.$_SERVER['HTTP_HOST'].'/'.$destination_rel_path;
		$image = new TimberImage( $destination_url );
		$upload_dir = wp_upload_dir();
		$this->tearDownCustomWPDirectoryStructure();
		$this->assertEquals( $destination_url, $image->src() );
		$this->assertEquals( $destination_url, (string)$image );
		$this->assertEquals( $destination_path, $image->file_loc );
	}

	function testPostThumbnails() {
		$upload_dir = wp_upload_dir();
		$post_id = $this->factory->post->create();
		$filename = self::copyTestImage( 'flag.png' );
		$destination_url = str_replace( ABSPATH, 'http://'.$_SERVER['HTTP_HOST'].'/', $filename );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		add_post_meta( $post_id, '_thumbnail_id', $attach_id, true );
		$data = array();
		$data['post'] = new TimberPost( $post_id );
		$data['size'] = array( 'width' => 100, 'height' => 50 );
		$data['crop'] = 'default';
		Timber::compile( 'assets/thumb-test.twig', $data );
		$exists = file_exists( $filename );
		$this->assertTrue( $exists );
		$resized_path = $upload_dir['path'].'/flag-'.$data['size']['width'].'x'.$data['size']['height'].'-c-'.$data['crop'].'.png';
		$exists = file_exists( $resized_path );
		$this->assertTrue( $exists );
	}

	function testImageAltText() {
		$upload_dir = wp_upload_dir();
		$thumb_alt = 'Thumb alt';
		$filename = self::copyTestImage( 'flag.png' );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$post_id = $this->factory->post->create();
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_excerpt' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		add_post_meta( $post_id, '_thumbnail_id', $attach_id, true );
		add_post_meta( $attach_id, '_wp_attachment_image_alt', $thumb_alt, true );
		$data = array();
		$data['post'] = new TimberPost( $post_id );
		$this->assertEquals( $data['post']->thumbnail()->alt(), $thumb_alt );
	}

	function testResizeFileNaming() {
		$file = 'eastern.jpg';
		$file_loc = self::copyTestImage( $file );
		$upload_dir = wp_upload_dir();
		$filename = TimberImageHelper::get_resize_file_url( self::getTestImageURL($file, true), 300, 500, 'default' );
		$expected = $upload_dir['relative'].$upload_dir['subdir'].'/eastern-300x500-c-default.jpg';
		$this->assertEquals( $expected, $filename );
	}

	function testResizeFileNamingWithAbsoluteURL() {
		$file_loc = self::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$url_src = $upload_dir['url'].'/eastern.jpg';
		$filename = TimberImageHelper::get_resize_file_url( $url_src, 300, 500, 'default' );
		$this->assertEquals( $upload_dir['url'].'/eastern-300x500-c-default.jpg', $filename );
	}

	function testResizeFileNamingWithLangHome() {
		add_filter( 'home_url', array($this, 'add_lang_to_home') , 1, 4 );
		$file_loc = self::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$url_src = $upload_dir['url'].'/eastern.jpg';
		$filename = TimberImageHelper::get_resize_file_url( $url_src, 300, 500, 'default' );
		$this->assertEquals( $upload_dir['url'].'/eastern-300x500-c-default.jpg', $filename );
		remove_filter( 'home_url', array($this, 'add_lang_to_home'), 1 );
	}

	function testLetterboxFileNaming() {
		$file_loc = self::copyTestImage( 'eastern.jpg' );
		$upload_dir = wp_upload_dir();
		$url_src = $upload_dir['url'].'/eastern.jpg';
		$filename = TimberImageHelper::get_letterbox_file_url( $url_src, 300, 500, '#FFFFFF' );
		$this->assertEquals( $upload_dir['url'].'/eastern-lbox-300x500-FFFFFF.jpg', $filename );
	}

	public static function is_png($file) {
		$file = strtolower($file);
		if (strpos($file, '.png') > 0) {
			return true;
		}
		return false;
	}

	public static function is_gif($file) {
		$file = strtolower($file);
		if (strpos($file, '.gif') > 0) {
			return true;
		}
		return false;
	}

	public static function checkSize( $file, $width, $height ) {
		$size = getimagesize( $file );
		if ($width === $size[0] && $height === $size[1]) {
			return true;
		}
		return false;
	}

	public static function checkChannel($channel, $base, $compare, $upper = false) {
		if ($base[$channel] === $base[$channel]) {
			return true;
		}
		if ($upper) {
			if ( ($base[$channel] <= $compare[$channel]) && ($compare[$channel] <= $upper[$channel])) {
				return true;
			}
		}
		return false;
	}

	public static function checkPixel($file, $x, $y, $color = false, $upper_color = false) {
		if ( self::is_png($file) ) {
			$image = imagecreatefrompng( $file );
		} else if ( self::is_gif($file) ) {
			$image = imagecreatefromgif( $file );
		} else {
			$image = imagecreatefromjpeg( $file );
		}
		$pixel_rgba = imagecolorat( $image, $x, $y );
		$colors_of_file = imagecolorsforindex( $image, $pixel_rgba );
		if ($upper_color) {
			$upper_colors = ImageOperation::hexrgb($upper_color);
		}
		$test_colors = ImageOperation::hexrgb($color);
		if( false === $color ) {
			$alpha = ($pixel_rgba & 0x7F000000) >> 24;
			return $alpha === 127;
		}
		if ( isset($upper_colors) && $upper_colors ) {
			if (self::checkChannel('red', $test_colors, $colors_of_file, $upper_colors) &&
				self::checkChannel('green', $test_colors, $colors_of_file, $upper_colors) &&
				self::checkChannel('blue', $test_colors, $colors_of_file, $upper_colors)
				) {
				return true;
			}
			return false;
		}
		if ( $test_colors['red'] === $colors_of_file['red'] &&
			 $test_colors['blue'] === $colors_of_file['blue'] &&
			 $test_colors['green'] === $colors_of_file['green']) {
			return true;
		}
		return false;
	}

	function getPixel($file, $x, $y) {
		if ( self::is_png($file) ) {
			$image = imagecreatefrompng( $file );
		} else if ( self::is_gif($file) ) {
			$image = imagecreatefromgif( $file );
		} else {
			$image = imagecreatefromjpeg( $file );
		}
		$rgb = imagecolorat( $image, $x, $y );
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		return ImageOperation::rgbhex($r, $g, $b);
	}

	function testPNGtoJPG() {
		if ( ! extension_loaded( 'gd' ) ) {
			self::markTestSkipped( 'PNG to JPEG conversion test requires GD extension' );
		}
		$file_loc = self::copyTestImage( 'eastern-trans.png' );
		$upload_dir = wp_upload_dir();
		$new_file = TimberImageHelper::img_to_jpg( $upload_dir['url'].'/eastern-trans.png', '#FFFF00' );
		$location_of_image = TimberImageHelper::get_server_location( $new_file );
		$this->assertFileExists( $location_of_image );
		$image = imagecreatefromjpeg( $location_of_image );
		$pixel_rgb = imagecolorat( $image, 1, 1 );
		$colors = imagecolorsforindex( $image, $pixel_rgb );
		$this->assertEquals( 255, $colors['red'] );
		$this->assertEquals( 255, $colors['green'] );
		$this->assertEquals( 0, $colors['blue'] );
	}

	function testImageDeletionSimilarNames() {
		$data = array();
		$data['size'] = array( 'width' => 500, 'height' => 300 );
		$upload_dir = wp_upload_dir();
		$file = self::copyTestImage( 'arch-2night.jpg' );
		$data['test_image'] = $upload_dir['url'].'/arch-2night.jpg';
		$data['crop'] = 'default';
		$arch_2night = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		Timber::compile( 'assets/image-test.twig', $data );

		$file = self::copyTestImage( 'arch.jpg' );
		$data['test_image'] = $upload_dir['url'].'/arch.jpg';
		$data['size'] = array( 'width' => 520, 'height' => 250 );
		$data['crop'] = 'left';
		$arch_regular = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		Timber::compile( 'assets/image-test.twig', $data );
		$this->assertFileExists( $arch_regular );
		$this->assertFileExists( $arch_2night );
		//Delte the regular arch image
		TimberImageHelper::delete_generated_files( $file );
		//The child of the regular arch image should be like
		//poof-be-gone
		$this->assertFileNotExists( $arch_regular );
		//...but the night image remains!
		$this->assertFileExists( $arch_2night );

	}

	function testImageDeletion() {
		$data = array();
		$data['size'] = array( 'width' => 500, 'height' => 300 );
		$upload_dir = wp_upload_dir();
		$file = self::copyTestImage( 'city-museum.jpg' );
		$data['test_image'] = $upload_dir['url'].'/city-museum.jpg';
		$data['crop'] = 'default';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_500_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		$data['size'] = array( 'width' => 520, 'height' => 250 );
		$data['crop'] = 'left';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_520_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		//make sure it generated the sizes we're expecting
		$this->assertFileExists( $resized_500_file );
		$this->assertFileExists( $resized_520_file );
		//Now delete the "parent" image
		TimberImageHelper::delete_generated_files( $file );
		//Have the children been deleted as well?
		$this->assertFileNotExists( $resized_520_file );
		$this->assertFileNotExists( $resized_500_file );
	}

	function testImageDeletionByURL() {
		$post_id = $this->factory->post->create();
		$filename = self::copyTestImage( 'flag.png' );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		$data = array();
		$data['size'] = array( 'width' => 500, 'height' => 300 );
		$upload_dir = wp_upload_dir();
		$data['test_image'] = $upload_dir['url'].'/flag.png';
		$data['crop'] = 'default';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_500_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		$data['size'] = array( 'width' => 520, 'height' => 250 );
		$data['crop'] = 'left';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_520_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		//make sure it generated the sizes we're expecting
		$this->assertFileExists( $resized_500_file );
		$this->assertFileExists( $resized_520_file );
		//Now delete the "parent" image
		TimberImageHelper::delete_generated_files( $data['test_image'] );
		//Have the children been deleted as well?
		$this->assertFileNotExists( $resized_520_file );
		$this->assertFileNotExists( $resized_500_file );
	}

	function testImageDeletionByDeletingAttachment() {
		$post_id = $this->factory->post->create();
		$filename = self::copyTestImage( 'flag.png' );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		$data = array();
		$data['size'] = array( 'width' => 500, 'height' => 300 );
		$upload_dir = wp_upload_dir();
		$data['test_image'] = $upload_dir['url'].'/flag.png';
		$data['crop'] = 'default';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_500_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		$data['size'] = array( 'width' => 520, 'height' => 250 );
		$data['crop'] = 'left';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_520_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		//make sure it generated the sizes we're expecting
		$this->assertFileExists( $resized_500_file );
		$this->assertFileExists( $resized_520_file );
		//Now delete the "parent" image
		wp_delete_attachment( $attach_id );
		//Have the children been deleted as well?
		$this->assertFileNotExists( $resized_520_file );
		$this->assertFileNotExists( $resized_500_file );
	}

	function testImageDeletionByAttachmentLocation() {
		$post_id = $this->factory->post->create();
		$filename = self::copyTestImage( 'flag.png' );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		$data = array();
		$data['size'] = array( 'width' => 500, 'height' => 300 );
		$upload_dir = wp_upload_dir();
		$data['test_image'] = $upload_dir['url'].'/flag.png';
		$data['crop'] = 'default';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_500_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		$data['size'] = array( 'width' => 520, 'height' => 250 );
		$data['crop'] = 'left';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_520_file = TimberImageHelper::get_resize_file_path( $data['test_image'], $data['size']['width'], $data['size']['height'], $data['crop'] );
		//make sure it generated the sizes we're expecting
		$this->assertFileExists( $resized_500_file );
		$this->assertFileExists( $resized_520_file );
		//Now delete the "parent" image
		$post = new TimberImage( $attach_id );
		TimberImageHelper::delete_generated_files( $post->file_loc );
		//Have the children been deleted as well?
		$this->assertFileNotExists( $resized_520_file );
		$this->assertFileNotExists( $resized_500_file );
	}

	function testLetterboxImageDeletion() {
		if ( ! extension_loaded( 'gd' ) ) {
			self::markTestSkipped( 'Letterbox image test requires GD extension' );
		}
		$data = array();
		$file = self::copyTestImage( 'city-museum.jpg' );
		$upload_dir = wp_upload_dir();
		$data['test_image'] = $upload_dir['url'].'/city-museum.jpg';
		$new_file = TimberImageHelper::letterbox( $data['test_image'], 500, 500, '#00FF00' );
		$letterboxed_file = TimberImageHelper::get_letterbox_file_path( $data['test_image'], 500, 500, '#00FF00' );
		$this->assertFileExists( $letterboxed_file );
		//Now delete the "parent" image
		TimberImageHelper::delete_generated_files( $file );
		//Have the children been deleted as well?
		$this->assertFileNotExists( $letterboxed_file );
	}

	function _makeThemeImageDirectory() {
		$theme_url = get_theme_root_uri().'/'.get_stylesheet();
		$img_dir = realpath(get_stylesheet_directory_uri()).'/images';
		if ( strpos($img_dir, 'http') === 0 ) {
			$img_dir = Timber\URLHelper::url_to_file_system($img_dir);
		}
		if ( !file_exists($img_dir) ) {
			$parent = dirname($img_dir);
			// error_log($parent);
			chmod($parent, 0777);
    		$res = mkdir($img_dir, 0777, true);
		}
	}

	function tearDown() {
		$theme_url = get_theme_root_uri().'/'.get_stylesheet();
		$img_dir = get_stylesheet_directory_uri().'/images';
		if ( file_exists($img_dir) ) {
			exec(sprintf("rm -rf %s", escapeshellarg($img_dir)));
		}
		parent::tearDown();
	}

	function testThemeImageResize() {
		$theme_url = get_theme_root_uri().'/'.get_stylesheet();
		$source = __DIR__.'/assets/cardinals.jpg';
		$dest = get_stylesheet_directory_uri().'/cardinals.jpg';
		if ( strpos($dest, 'http') === 0 ) {
			$dest = Timber\URLHelper::url_to_file_system($dest);
		}
		$dest = self::maybe_realpath($dest);
		copy($source, $dest);
		$this->assertTrue(file_exists($dest));
		$image = $theme_url.'/cardinals.jpg';
		$image = str_replace( 'http://example.org', '', $image );
		$data = array();
		$data['test_image'] = $image;
		$data['size'] = array( 'width' => 120, 'height' => 120 );
		$str = Timber::compile( 'assets/image-test.twig', $data );
		$file_location = get_stylesheet_directory_uri().'/cardinals-120x120-c-default.jpg';
		if ( strpos($file_location, 'http') === 0 ) {
			$file_location = Timber\URLHelper::url_to_file_system($file_location);
		}
		$file_location = self::maybe_realpath($file_location);
		$this->assertFileExists( $file_location );
		$this->addFile( $file_location );
	}

	function maybe_realpath( $path ) {
		if ( realpath($path) ) {
			return realpath($path);
		}
		return $path;
	}

	function testThemeImageLetterbox() {
		$theme_url = get_theme_root_uri().'/'.get_stylesheet();
		if ( ! extension_loaded( 'gd' ) ) {
			self::markTestSkipped( 'Letterbox image test requires GD extension' );
		}
		$source = __DIR__.'/assets/cardinals.jpg';
		$dest = self::maybe_realpath(get_template_directory()).'/cardinals.jpg';
		copy($source, $dest);
		$image = $theme_url.'/cardinals.jpg';
		$image = str_replace( 'http://example.org', '', $image );
		$letterboxed = TimberImageHelper::letterbox( $image, 600, 300, '#FF0000' );
		$this->assertFileExists( realpath(get_template_directory().'/cardinals-lbox-600x300-FF0000.jpg') );
		unlink( realpath(get_template_directory().'/cardinals-lbox-600x300-FF0000.jpg') );
	}

	function testImageWidthWithFilter() {
		$pid = $this->factory->post->create();
		$photo = $this->copyTestImage();
		$photo = TimberURLHelper::get_rel_path($photo);
		update_post_meta($pid, 'custom_photo', '/'.$photo);
		$str = '{{TimberImage(post.custom_photo).width}}';
		$post = new TimberPost($pid);
		$rendered = Timber::compile_string( $str, array('post' => $post) );
		$this->assertEquals( 1500, $rendered );
	}

	function testWithOutputBuffer() {
		ob_start();
		$post = $this->get_post_with_image();
		$str = '<img src="{{ post.thumbnail.src|resize(510, 280) }}" />';
		Timber::render_string($str, array('post' => $post));
		$result = ob_get_contents();
		ob_end_clean();
		$m = date('m');
		$y = date('Y');
		$this->assertEquals('<img src="http://example.org/wp-content/uploads/'.$y.'/'.$m.'/arch-510x280-c-default.jpg" />', $result);
	}

	function testResizeNamed() {
		add_image_size('timber-testResizeNamed', $width = 600, $height = 400, $crop = true);
		$data = array();
		$data['size'] = 'timber-testResizeNamed';
		$upload_dir = wp_upload_dir();
		self::copyTestImage();
		$url = $upload_dir['url'].'/arch.jpg';
		$data['test_image'] = $url;
		Timber::compile('assets/image-resize-named.twig', $data);
		$resized_path = $upload_dir['path'].'/arch-'.$width.'x'.$height.'-c-default.jpg';
		$this->assertFileExists($resized_path);
		//Now make sure it doesn't regenerate
		$old_time = filemtime($resized_path);
		sleep(1);
		Timber::compile('assets/image-resize-named.twig', $data);
		$new_time = filemtime($resized_path);
		$this->assertEquals($old_time, $new_time);
	}

	function testBogusResizeNamed() {
		$data = array();
		$data['size'] = 'timber-foobar';
		$upload_dir = wp_upload_dir();
		self::copyTestImage();
		$url = $upload_dir['url'].'/arch.jpg';
		$data['test_image'] = $url;
		$result = Timber::compile('assets/image-resize-named.twig', $data);
		$this->assertEquals('<img src="'.$url.'" />', trim($result));
	}

	function testPostThumbnailsNamed() {
		add_image_size('timber-testPostThumbnailsNamed', $width = 100, $height = 50, $crop = true);
		$upload_dir = wp_upload_dir();
		$post_id = $this->factory->post->create();
		$filename = self::copyTestImage('flag.png');
		$destination_url = str_replace(ABSPATH, 'http://'.$_SERVER['HTTP_HOST'].'/', $filename);
		$wp_filetype = wp_check_filetype(basename($filename), null);
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content' => '',
			'post_status' => 'inherit',
		);
		$attach_id = wp_insert_attachment($attachment, $filename, $post_id);
		add_post_meta($post_id, '_thumbnail_id', $attach_id, true);
		$data = array();
		$data['post'] = new TimberPost($post_id);
		$data['size'] = 'timber-testPostThumbnailsNamed';
		Timber::compile('assets/image-thumb-named.twig', $data);
		$resized_path = $upload_dir['path'].'/flag-'.$width.'x'.$height.'-c-default.png';
		$this->assertFileExists($resized_path);
	}

	function testPostThumbnailsWithWPName() {
		$upload_dir = wp_upload_dir();
		$post_id = $this->factory->post->create();
		$filename = self::copyTestImage('flag.png');
		$destination_url = str_replace(ABSPATH, 'http://'.$_SERVER['HTTP_HOST'].'/', $filename);
		$wp_filetype = wp_check_filetype(basename($filename), null);
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content' => '',
			'post_status' => 'inherit',
		);
		$attach_id = wp_insert_attachment($attachment, $filename, $post_id);
		add_post_meta($post_id, '_thumbnail_id', $attach_id, true);
		$data = array();
		$data['post'] = new TimberPost($post_id);
		$data['size'] = 'medium';
		$result = Timber::compile('assets/image-thumb-named.twig', $data);
		$filename = 'flag-300x300-c-default.png';
		$resized_path = $upload_dir['path'].'/'.$filename;
		$this->assertFileExists($resized_path);
		$this->assertEquals('<img src="'.$upload_dir['url'].'/'.$filename.'" />', trim($result));
	}

	function testImageSizeWithWPNameUsingNative(){
		require_once('wp-overrides.php');
		$filename = __DIR__.'/assets/tom-brady.jpg';
		$filesize = filesize($filename);
		$data = array('tmp_name' => $filename, 'name' => 'tom-brady.jpg', 'type' => 'image/jpg', 'size' => $filesize, 'error' => 0);
		$this->assertTrue(file_exists($filename));
		$_FILES['tester'] = $data;
		$file_id = WP_Overrides::media_handle_upload('tester', 0, array(), array( 'test_form' => false));
		if (!is_int($file_id)) {
			error_log(print_r($file_id, true));
		}
		$image = new TimberImage($file_id);
		$str = '<img src="{{image.src(\'medium\')}}" />';
		$result = Timber::compile_string($str, array('image' => $image));
		$upload_dir = wp_upload_dir();
		$this->assertEquals('<img src="'.$upload_dir['url'].'/'.$image->sizes['medium']['file'].'" />', trim($result));
	}

	function testImageSizeWithWPNameUsingNativeGif(){
		require_once('wp-overrides.php');
		$filename = __DIR__.'/assets/boyer.gif';
		$filesize = filesize($filename);
		$data = array('tmp_name' => $filename, 'name' => 'boyer.gif', 'type' => 'image/gif', 'size' => $filesize, 'error' => 0);
		$this->assertTrue(file_exists($filename));
		$_FILES['tester'] = $data;
		$file_id = WP_Overrides::media_handle_upload('tester', 0, array(), array( 'test_form' => false));
		if (!is_int($file_id)) {
			error_log(print_r($file_id, true));
		}
		$image = new TimberImage($file_id);
		$str = '<img src="{{image.src(\'medium\')}}" />';
		$result = Timber::compile_string($str, array('image' => $image));
		$upload_dir = wp_upload_dir();
		$this->assertEquals('<img src="'.$upload_dir['url'].'/'.$image->sizes['medium']['file'].'" />', trim($result));
	}

	function testGifToJpg() {
		if ( ! extension_loaded( 'gd' ) ) {
			self::markTestSkipped( 'JPEG conversion test requires GD extension' );
		}
		$filename = self::copyTestImage('loading.gif');
		$gif_url = str_replace(ABSPATH, 'http://'.$_SERVER['HTTP_HOST'].'/', $filename);
		$str = '<img src="{{'."'$gif_url'".'|tojpg}}" />';
		$result = Timber::compile_string($str);
		$jpg_url = str_replace('.gif', '.jpg', $gif_url);
		$this->assertEquals('<img src="'.$jpg_url.'" />', $result);
	}

	function testImageHelperInit() {
		$helper = TimberImageHelper::init();
		$this->assertTrue($helper);
	}

	function testResizeGif() {
		if ( ! extension_loaded( 'imagick' ) ) {
			self::markTestSkipped( 'Animated GIF resizing test requires Imagick extension' );
		}
		$filename = self::copyTestImage('loading.gif');
		$gif_url = str_replace(ABSPATH, 'http://'.$_SERVER['HTTP_HOST'].'/', $filename);
		$str = '<img src="{{'."'$gif_url'".'|resize(200)}}" />';
		$result = Timber::compile_string($str);
		$resized_url = str_replace('loading.gif', 'loading-200x0-c-default.gif', $gif_url);
		$resized_path = str_replace('http://example.org', ABSPATH, $resized_url);
		$resized_path = TimberURLHelper::remove_double_slashes($resized_path);
		$this->assertFileExists($resized_path);
	}

	function testImageNoParent() {
		$filename = self::copyTestImage( 'arch.jpg' );
		$image = new TimberImage( $filename );
		$this->assertFalse($image->parent());
	}

	function testImageParent() {
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		$this->assertEquals($post->ID, $image->parent()->ID);
	}

	function testPathInfo() {
		$filename = self::copyTestImage( 'arch.jpg' );
		$image = new TimberImage( $filename );
		$path_parts = $image->get_pathinfo();
		$this->assertEquals('jpg', $path_parts['extension']);
	}

	function testTimberImageFromPost() {
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		$post = get_post($post->ID);
		$str = '{{ TimberImage(post).src }}';
		$result = Timber::compile_string( $str, array('post' => $post) );
		$this->assertEquals($image->src(), $result);
	}

	function testTimberImageFromTimberImage() {
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		$post = new TimberImage($image);
		$str = '{{ TimberImage(post).src }}';
		$result = Timber::compile_string( $str, array('post' => $post) );
		$this->assertEquals($image->src(), $result);
	}

	function testTimberImageFromTimberImageID() {
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		$post = new TimberImage($image->ID);
		$str = '{{ TimberImage(post).src }}';
		$result = Timber::compile_string( $str, array('post' => $post) );
		$this->assertEquals($image->src(), $result);
	}

	function testTimberImageFromImageID() {
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		$post = $image->ID;
		$str = '{{ TimberImage(post).src }}';
		$result = Timber::compile_string( $str, array('post' => $post) );
		$this->assertEquals($image->src(), $result);
	}

	function testTimberImageFromAttachment() {
		$iid = self::get_image_attachment();
		$image = new TimberImage($iid);
		$post = get_post($iid);
		$str = '{{ TimberImage(post).src }}';
		$result = Timber::compile_string( $str, array('post' => $post) );
		$this->assertEquals($image->src(), $result);
	}

	// Test document like pdf, docx
	function testTimberImageFromDocument() {
		$pid = $this->factory->post->create();
		$iid = self::get_image_attachment($pid, 'dummy-pdf.pdf');
		$attachment = new TimberImage($iid);
		$str = '{{ TimberImage(post).src }}';
		$result = Timber::compile_string( $str, array('post' => $iid) );
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y/m').'/dummy-pdf.pdf', $result);
	}

	function testNoThumbnail() {
		$pid = $this->factory->post->create();
		$post = new TimberPost($pid);
		$str = Timber::compile_string('Image?{{post.thumbnail.src}}', array('post' => $post));
		$this->assertEquals('Image?', $str);
	}

	function testFilteredImageURL() {
		add_filter('wp_get_attachment_image_src', function($image, $id, $size, $icon) {
			$image = str_replace('jpg', 'jpeg', $image);
			return $image;
		}, 10, 4);
		$post = $this->get_post_with_image();
		$image = $post->thumbnail();
		$str = '{{ post.thumbnail.src }}';
		$result = Timber::compile_string( $str, array('post' => $post) );
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpeg', $result);
	}

	function testTimberImageForExtraSlashes() {
		add_filter('upload_dir', array($this, '_filter_upload'), 10, 1);

		$post = $this->get_post_with_image();
		$image = $post->thumbnail();

		$resized_520_file = TimberImageHelper::resize($image->src, 520, 500);

		remove_filter('upload_dir', array($this, '_filter_upload'));

		$this->assertFalse(strpos($resized_520_file, '//arch-520x500-c-default.jpg') > -1);
	}

	function _filter_upload($data) {
		$data['path'] = $data['basedir'];
		$data['url'] = $data['baseurl'];

		return $data;
	}

}
