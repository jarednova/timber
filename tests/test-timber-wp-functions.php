<?php

use Timber\FunctionWrapper;
use Timber\Helper;

class TestTimberWPFunctions extends Timber_UnitTestCase {

		function testFunctionFire(){
			$str = '{{function("my_test_function")}}';
			$output = Timber::compile_string($str);
			$this->assertEquals('jared sez hi', $output);
		}

		function testFooterOnFooterFW(){
			global $wp_scripts;
			$wp_scripts = null;
			wp_enqueue_script( 'jquery', false, array(), false, true );
			$fw1 = new FunctionWrapper('wp_footer', array(), true);
			$fw2 = new FunctionWrapper('wp_footer', array(), true);
			$this->assertGreaterThan(50, strlen($fw1->call()));
			//this is bunk because footer scripts will only print once
			$this->assertEquals(0, strlen($fw2->call()));
			wp_dequeue_script('jquery');
			$wp_footer_output1 = new FunctionWrapper('wp_footer', array(), true);
			$this->assertEquals(0, strlen($wp_footer_output1));
		}

		function testFooterAlone(){
			global $wp_scripts;
			$wp_scripts = null;
			wp_enqueue_script( 'jquery', false, array(), false, true );
			$fw1 = new FunctionWrapper('wp_footer', array(), true);
			$this->assertGreaterThan(50, strlen($fw1->call()));
		}

		function testDoubleAction(){
			add_action('jared_action', function(){
				echo 'bar';
			});
			$fw1 = new FunctionWrapper('do_jared_action', array(), true);
			$fw2 = new FunctionWrapper('do_jared_action', array(), true);
			$this->assertEquals($fw1->call(), $fw2->call());
			$this->assertEquals('bar', $fw1->call());
		}

		function testDoubleActionWPFooter(){
			global $wp_scripts;
			$wp_scripts = null;
			add_action('wp_footer', 'echo_junk');
			$fw1 = new FunctionWrapper('wp_footer', array(), true);
			$fw2 = new FunctionWrapper('wp_footer', array(), true);
			$this->assertEquals($fw1->call(), $fw2->call());
			$pos = strpos($fw2->call(), 'foo');
			$this->assertGreaterThan(-1, $pos);
			remove_action('wp_footer', 'echo_junk');
		}

		function testInTwig(){
			global $wp_scripts;
			$wp_scripts = null;
			wp_enqueue_script( 'jquery', false, array(), false, true );
			$str = Timber::compile('assets/wp-footer.twig', array());
			$pos = strpos($str, 'wp-includes/js/jquery/jquery');
			$this->assertGreaterThan(-1, $pos);
		}

		function testInTwigString(){
			global $wp_scripts;
			$wp_scripts = null;
			wp_enqueue_script( 'jquery', false, array(), false, true );
			$str = Timber::compile_string('{{function("wp_footer")}}', array());
			$pos = strpos($str, 'wp-includes/js/jquery/jquery');
			$this->assertGreaterThan(-1, $pos);
		}

		function testAgainstFooterFunctionOutput(){
			global $wp_scripts;
			$wp_scripts = null;
			wp_enqueue_script( 'colorpicker', false, array(), false, true);
			wp_enqueue_script( 'fake-js', 'http://example.org/fake-js.js', array(), false, true );
			$wp_footer = Helper::ob_function('wp_footer');
			global $wp_scripts;
			$wp_scripts = null;
			wp_enqueue_script( 'colorpicker', false, array(), false, true);
			wp_enqueue_script( 'fake-js', 'http://example.org/fake-js.js', array(), false, true );
			$str = Timber::compile_string('{{function("wp_footer")}}');
			$this->assertEquals($wp_footer, $str);
			$this->assertGreaterThan(50, strlen($str));

		}

		function testInTwigStringHeadAndFooter(){
			return $this->markTestSkipped('@todo Twig\Error\RuntimeError: An exception has been thrown during the rendering of a template ("readfile(/srv/www/wordpress-trunk/public_html/src/wp-includes/js/wp-emoji-loader.js): failed to open stream: No such file or directory")');
			global $wp_scripts;
			$wp_scripts = null;
			//send colorpicker to the header
			wp_enqueue_script( 'colorpicker', false, array(), false, false);
			//send fake-js to the footer
			wp_enqueue_script( 'fake-js', 'http://example.org/fake-js.js', array(), false, true );
			$str = Timber::compile_string('<head>{{function("wp_head")}}</head><footer>{{function("wp_footer")}}</footer>');
			$footer_tag = strpos($str, '<footer>');
			$colorpicker = strpos($str, 'colorpicker');
			$this->assertGreaterThan(1, $colorpicker);
			//make sure that footer appears after colorpicker
			$this->assertGreaterThan($colorpicker, $footer_tag);

		}






	}

	function do_jared_action(){
		do_action('jared_action');
	}

	function echo_junk(){
		echo 'foo';
	}

	function my_test_function(){
		return 'jared sez hi';
	}
