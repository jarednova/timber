<?php

	/**
	 * @group called-post-constructor
	 */
	class TestTimberParentChild extends Timber_UnitTestCase {

		function testParentChildGeneral(){
			TestTimberLoader::_setupParentTheme();
			TestTimberLoader::_setupChildTheme();
			switch_theme('fake-child-theme');
			register_post_type('course');
			//copy a specific file to the PARENT directory
			$dest_dir = WP_CONTENT_DIR.'/themes/twentyfifteen';
			copy(__DIR__.'/assets/single-course.twig', $dest_dir.'/views/single-course.twig');
			$pid = $this->factory->post->create();
			$post = Timber::get_post($pid);
			$str = Timber::compile(array('single-course.twig', 'single.twig'), array( 'post' => $post ));
			$this->assertEquals('I am single course', $str);
		}
	}
