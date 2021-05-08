<?php

class TestTimberFilters extends Timber_UnitTestCase {

	function testLoaderRenderDataFilter() {
		add_filter('timber/loader/render_data', array($this, 'filter_timber_render_data'), 10, 2);
		$output = Timber::compile('assets/output.twig', array('output' => 14) );
		$this->assertEquals('output.twig assets/output.twig', $output);
	}

	function testRenderDataFilter() {
		add_filter('timber/render/data', function( $data, $file ){
			$data['post'] = array('title' => 'daaa');
			return $data;
		}, 10, 2);
		ob_start();
		Timber::render('assets/single-post.twig', array('fop' => 'wag'));
		$str = ob_get_clean();
		$this->assertEquals('<h1>daaa</h1>', $str);
	}

	function filter_timber_render_data($data, $file) {
		$data['output'] = $file;
		return $data;
	}

	function testOutputFilter() {
		add_filter('timber/output', array($this, 'filter_timber_output'), 10, 3);
		$output = Timber::compile('assets/single.twig', array('number' => 14) );
		$this->assertEquals('assets/single.twig14', $output);
	}

	function filter_timber_output( $output, $data, $file ) {
		return $file . $data['number'];
	}
}
