<?php

	class TestTimberTermTwigFilters extends Timber_UnitTestCase {

		function testTimberFitlerSanitize(){
			$data['title'] = "Jared's Big Adventure";
			$str = Timber::compile_string('{{title|sanitize}}', $data);
			$this->assertEquals('jareds-big-adventure', $str);
		}

		function testTimberPreTags() {
			$data = '<pre><h1>thing</h1></pre>';
			$template = '{{foo|pretags}}';
			$str = Timber::compile_string($template, array('foo' => $data));
			$this->assertEquals('<pre>&lt;h1&gt;thing&lt;/h1&gt;</pre>', $str);
		}

		function testTimberFilterString(){
			$data['arr'] = array('foo', 'foo');
			$str = Timber::compile_string('{{arr|join(" ")}}', $data);
			$this->assertEquals('foo foo', trim($str));
			$data['arr'] = array('bar');
			$str = Timber::compile_string('{{arr|join}}', $data);
			$this->assertEquals('bar', trim($str));
			$data['arr'] = array('foo', 'bar');
			$str = Timber::compile_string('{{arr|join(", ")}}', $data);
			$this->assertEquals('foo, bar', trim($str));
			$data['arr'] = 6;
			$str = Timber::compile_string('{{arr}}', $data);
			$this->assertEquals('6', trim($str));
		}

		function testTwigFilterList() {
			$data['authors'] = array('Tom','Rick','Harry','Mike');
			$str = Timber::compile_string("{{authors|list}}", $data);
			$this->assertEquals('Tom, Rick, Harry and Mike', $str);
		}

		function testTwigFilterListOxford() {
			$data['authors'] = array('Tom','Rick','Harry','Mike');
			$str = Timber::compile_string("{{authors|list(',', ', and')}}", $data);
			$this->assertEquals('Tom, Rick, Harry, and Mike', $str);
		}


	}
