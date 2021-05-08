<?php

/**
 * @group attachments
 */
class TestTimberWPVIP extends TimberAttachment_UnitTestCase {

	function testDisableCache() {
		$filter = function() {
				return 'none';
		};
		add_filter( 'timber/cache/mode', $filter );
		$loader = new Timber\Loader();
		$cache = $loader->set_cache('test', 'foobar');
		$cache = $loader->get_cache('test');
		$this->assertFalse($cache);
		remove_filter( 'timber/cache/mode', $filter );
	}

	function testImageResize() {
		add_filter( 'timber/allow_fs_write', '__return_false' );
		$data = array();
		$data['size'] = array( 'width' => 600, 'height' => 400 );
		$upload_dir = wp_upload_dir();
		self::copyTestAttachment();
		$url = $upload_dir['url'].'/arch.jpg';
		$data['test_image'] = $url;
		$data['crop'] = 'default';
		Timber::compile( 'assets/image-test.twig', $data );
		$resized_path = $upload_dir['path'].'/arch-'.$data['size']['width'].'x'.$data['size']['height'].'-c-'.$data['crop'].'.jpg';
		$this->assertFileNotExists( $resized_path );
		remove_filter( 'timber/allow_fs_write', '__return_false' );
	}

	function testImageResizeInTwig() {
		add_filter( 'timber/allow_fs_write', '__return_false' );
		$pid = $this->factory->post->create(array('post_type' => 'post'));
		$attach_id = self::get_attachment($pid, 'arch.jpg');
		$template = '<img src="{{get_post(img).src|resize(200, 200)}}">';
		$str = Timber::compile_string($template, array('img' => $attach_id));
		$this->assertEquals('<img src="http://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpg">', $str);
		remove_filter( 'timber/allow_fs_write', '__return_false' );
	}

	function testImageSrcThumbnail() {
		add_filter( 'timber/allow_fs_write', '__return_false' );
		require_once('wp-overrides.php');
		$filename = __DIR__.'/assets/arch.jpg';
		$filesize = filesize($filename);
		$data = array('tmp_name' => $filename, 'name' => 'arch.jpg', 'type' => 'image/jpg', 'size' => $filesize, 'error' => 0);
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
		remove_filter( 'timber/allow_fs_write', '__return_false' );
	}

}