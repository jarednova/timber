<?php

class TestTimberImageRetina extends Timber_UnitTestCase {

	function testImageRetina() {
		$file = TestTimberImage::copyTestAttachment();
		$ret = Timber\ImageHelper::retina_resize($file, 2);
		$image = new Timber\Image( $ret );
		$this->assertEquals( 3000, $image->width() );
	}

	function testImageBiggerRetina() {
		$file = TestTimberImage::copyTestAttachment();
		$ret = Timber\ImageHelper::retina_resize($file, 3);
		$image = new Timber\Image( $ret );
		$this->assertEquals( 4500, $image->width() );
	}

	function testImageRetinaFilter() {
		$filename = TestTimberImage::copyTestAttachment( 'eastern.jpg' );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$post_id = $this->factory->post->create( array( 'post_title' => 'Thing One' ) );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_excerpt' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		add_post_meta( $post_id, '_thumbnail_id', $attach_id, true );
		$data = array();
		$post = new Timber\Post( $post_id );
		$data['post'] = $post;
		$str = '{{post.thumbnail.src|retina}}';
		$compiled = Timber::compile_string($str, $data);
		$this->assertContains('@2x', $compiled);
		$img = new Timber\Image($compiled);
		$this->assertEquals(500, $img->width());
	}

	function testImageRetinaFloatFilter() {
		$filename = TestTimberImage::copyTestAttachment( 'eastern.jpg' );
		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$post_id = $this->factory->post->create( array( 'post_title' => 'Thing One' ) );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_excerpt' => '',
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
		add_post_meta( $post_id, '_thumbnail_id', $attach_id, true );
		$data = array();
		$post = new Timber\Post( $post_id );
		$data['post'] = $post;
		$str = '{{post.thumbnail.src|retina(1.5)}}';
		$compiled = Timber::compile_string($str, $data);
		$this->assertContains('@1.5x', $compiled);
		$img = new Timber\Image($compiled);
		$this->assertEquals(375, $img->width());
	}

	function testImageResizeRetinaFilter() {
		$filename = TestTimberImage::copyTestAttachment( 'eastern.jpg' );
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
		$data = array();
		$data['post'] = new Timber\Post( $post_id );
		$str = '{{post.thumbnail.src|resize(100, 50)|retina(3)}}';
		$compiled = Timber::compile_string($str, $data);
		$img = new Timber\Image($compiled);
		$this->assertContains('@3x', $compiled);
		$this->assertEquals(300, $img->width());
	}

	function testImageResizeRetinaFilterNotAnImage() {
		self::enable_error_log(false);
		$str = 'Image? {{"/wp-content/uploads/2016/07/stuff.jpg"|retina(3)}}';
		$compiled = Timber::compile_string($str);
		$this->assertEquals('Image? /wp-content/uploads/2016/07/stuff.jpg', $compiled);
		self::enable_error_log(true);
	}
}
