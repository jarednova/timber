<?php

	class TestTimberImageToWEBP extends Timber_UnitTestCase {

		function setUp() {
			parent::setUp();
			if ( ! function_exists( 'imagewebp' ) ) {
				self::markTestSkipped( 'WEBP conversion tests requires imagewebp function' );
			}
		}

		function testTIFtoWEBP() {
			$filename = TestTimberImage::copyTestAttachment( 'white-castle.tif' );
			$str = Timber::compile_string('{{file|towebp}}', array('file' => $filename));
			$this->assertEquals($filename, $str);
		}

		function testPNGtoWEBP() {
			$filename = TestTimberImage::copyTestAttachment( 'flag.png' );
			$str = Timber::compile_string('{{file|towebp}}', array('file' => $filename));
			$renamed = str_replace('.png', '.webp', $filename);
			$this->assertFileExists($renamed);
			$this->assertGreaterThan(1000, filesize($renamed));
			$this->assertEquals('image/png', mime_content_type($filename));
			$this->assertEquals('image/webp', mime_content_type($renamed));
		}

		function testGIFtoJPG() {
			$filename = TestTimberImage::copyTestAttachment( 'boyer.gif' );
			$str = Timber::compile_string('{{file|towebp}}', array('file' => $filename));
			$renamed = str_replace('.gif', '.webp', $filename);
			$this->assertFileExists($renamed);
			$this->assertGreaterThan(1000, filesize($renamed));
			$this->assertEquals('image/gif', mime_content_type($filename));
			$this->assertEquals('image/webp', mime_content_type($renamed));
		}

		function testJPGtoWEBP() {
			$filename = TestTimberImage::copyTestAttachment( 'stl.jpg' );
			$original_size = filesize($filename);
			$str = Timber::compile_string('{{file|towebp(100)}}', array('file' => $filename));
			$renamed = str_replace('.jpg', '.webp', $filename);
			$this->assertGreaterThan(1000, filesize($renamed));
			$this->assertEquals('image/jpeg', mime_content_type($filename));
			$this->assertEquals('image/webp', mime_content_type($renamed));
		}

		function testJPEGtoJPG() {
			$filename = TestTimberImage::copyTestAttachment( 'jarednova.jpeg' );
			$str = Timber::compile_string('{{file|towebp}}', array('file' => $filename));
			$renamed = str_replace('.jpeg', '.webp', $filename);
			$this->assertFileExists($renamed);
			$this->assertGreaterThan(1000, filesize($renamed));
			$this->assertEquals('image/jpeg', mime_content_type($filename));
			$this->assertEquals('image/webp', mime_content_type($renamed));
		}

		function testWEBPtoWEBP() {
			$filename = TestTimberImage::copyTestAttachment( 'mountains.webp' );
			$original_size = filesize($filename);
			$str = Timber::compile_string('{{file|towebp}}', array('file' => $filename));
			$new_size = filesize($filename);
			$this->assertEquals($original_size, $new_size);
			$this->assertEquals('image/webp', mime_content_type($filename));
		}


		function testSideloadedJPGToWEBP() {
			$url        = 'https://pbs.twimg.com/profile_images/768086933310476288/acGwPDj4_400x400.jpg';
			$sideloaded = Timber::compile_string( '{{ file|towebp }}', [ 'file' => $url ] );

			$base_url   = str_replace( basename( $sideloaded ), '', $sideloaded );
			$expected   = $base_url . md5( $url ) . '.webp';

			$this->assertEquals( $expected, $sideloaded );
		}

		function testSideloadedPNGToWEBP() {
			$url        = 'https://user-images.githubusercontent.com/2084481/31230351-116569a8-a9e4-11e7-8310-48b7f679892b.png';
			$sideloaded = Timber::compile_string( '{{ file|towebp }}', [ 'file' => $url ] );

			$base_url = str_replace( basename( $sideloaded ), '', $sideloaded );
			$expected = $base_url . md5( $url ) . '.webp';

			$this->assertEquals( $expected, $sideloaded );
		}
	}
