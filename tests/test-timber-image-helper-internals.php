<?php

class TestTimberImageHelperInternals extends TimberAttachment_UnitTestCase {

	function testAnalyzeURLUploads() {
		$src = 'http://'.$_SERVER['HTTP_HOST'].'/wp-content/uploads/myimage.jpg';
		$parts = Timber\ImageHelper::analyze_url($src);
		$this->assertEquals('http://'.$_SERVER['HTTP_HOST'].'/wp-content/uploads/myimage.jpg', $parts['url']);
		$this->assertEquals(1, $parts['absolute']);
		$this->assertEquals(1, $parts['base']);
		$this->assertEquals('', $parts['subdir']);
		$this->assertEquals('myimage', $parts['filename']);
		$this->assertEquals('jpg', $parts['extension']);
		$this->assertEquals('myimage.jpg', $parts['basename']);
	}

	function testAnalyzeURLUploadsWithDate() {
		$src = 'http://'.$_SERVER['HTTP_HOST'].'/wp-content/uploads/2017/02/myimage.jpg';
		$parts = Timber\ImageHelper::analyze_url($src);
		$this->assertEquals('http://'.$_SERVER['HTTP_HOST'].'/wp-content/uploads/2017/02/myimage.jpg', $parts['url']);
		$this->assertEquals(1, $parts['absolute']);
		$this->assertEquals(1, $parts['base']);
		$this->assertEquals('/2017/02', $parts['subdir']);
		$this->assertEquals('myimage', $parts['filename']);
		$this->assertEquals('jpg', $parts['extension']);
		$this->assertEquals('myimage.jpg', $parts['basename']);
	}

	function testAnalyzeURLTheme() {
		$this->assertTrue(true);
		// $src = 'http://'.$_SERVER['HTTP_HOST'].'/wp-content/themes/'.get_stylesheet().'/logo.jpg';
		// $parts = Timber\ImageHelper::analyze_url($src);
		// $this->assertEquals('http://'.$_SERVER['HTTP_HOST'].'/wp-content/themes/'.get_stylesheet().'/logo.jpg', $parts['url']);
		// $this->assertEquals(1, $parts['absolute']);
		// $this->assertEquals(2, $parts['base']);
		// $this->assertEquals('/themes/'.get_stylesheet(), $parts['subdir']);
		// $this->assertEquals('logo', $parts['filename']);
		// $this->assertEquals('jpg', $parts['extension']);
		// $this->assertEquals('logo.jpg', $parts['basename']);
	}

}
