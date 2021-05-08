<?php

	/**
	 * @group called-post-constructor
	 */
	class TestTimberSidebar extends Timber_UnitTestCase {

		function testTwigSidebar(){
			$context = Timber::context();
			$sidebar_post = $this->factory->post->create(array('post_title' => 'Sidebar post content'));
			$sidebar_context = array();
			$sidebar_context['post'] = Timber::get_post($sidebar_post);
			$context['sidebar'] = Timber::get_sidebar('assets/sidebar.twig', $sidebar_context);
			$result = Timber::compile('assets/main-w-sidebar.twig', $context);
			$this->assertEquals('I am the main stuff <h4>Sidebar post content</h4>', trim($result));
		}

		function testPHPSidebar() {
			add_filter('timber/context', function($context){
				$context['sidebar'] = Timber::get_sidebar('assets/my-sidebar.php');
				return $context;
			});
			$context = Timber::context();
			$result = Timber::compile('assets/main-w-sidebar-php.twig', $context);
			$this->assertEquals("A Fever You Can't Sweat Out by Panic! at the Disco from 2005", trim($result));

		}
	}
