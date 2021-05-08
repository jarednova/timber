<?php

/**
 * @group posts-api
 * @group attachments
 */
class TestTimberImageRetina extends Timber_UnitTestCase {

	function testImageRetina() {
		$file       = TestTimberImage::copyTestAttachment();
		$retina_url = Timber\ImageHelper::retina_resize($file);

		$this->assertEquals( 'arch@2x.jpg', basename($retina_url) );
	}

	function testImageBiggerRetina() {
		$file       = TestTimberImage::copyTestAttachment();
		$retina_url = Timber\ImageHelper::retina_resize($file, 3);

		$this->assertEquals( 'arch@3x.jpg', basename($retina_url) );
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

		$retina_url = Timber::compile_string('{{post.thumbnail.src|retina}}', [
			'post' => Timber::get_post($post_id),
		]);

		$this->assertEquals( 'eastern@2x.jpg', basename($retina_url) );
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

		$compiled = Timber::compile_string('{{post.thumbnail.src|retina(1.5)}}', [
			'post' => Timber::get_post( $post_id ),
		]);

		$this->assertEquals( 'eastern@1.5x.jpg', basename($compiled) );
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

		$compiled = Timber::compile_string('{{post.thumbnail.src|resize(100, 50)|retina(3)}}', [
			'post' => Timber::get_post( $post_id ),
		]);

		$this->assertEquals( 'eastern-100x50-c-default@3x.jpg', basename($compiled) );
	}

	function testImageResizeRetinaFilterNotAnImage() {
		self::enable_error_log(false);
		$str = 'Image? {{"/wp-content/uploads/2016/07/stuff.jpg"|retina(3)}}';
		$compiled = Timber::compile_string($str);
		$this->assertEquals('Image? /wp-content/uploads/2016/07/stuff.jpg', $compiled);
		self::enable_error_log(true);
	}
}
