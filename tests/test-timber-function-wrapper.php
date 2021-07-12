<?php

class TestTimberFunctionWrapper extends Timber_UnitTestCase {

	function testToStringWithException() {
		ob_start();
		$wrapper = new Timber\FunctionWrapper('TestTimberFunctionWrapper::isNum', array('hi'));
		echo $wrapper;
		$content = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals('Caught exception: Argument must be of type integer', $content);
	}

	function testToStringWithoutException() {
		ob_start();
		$wrapper = new Timber\FunctionWrapper('TestTimberFunctionWrapper::isNum', array(4));
		echo $wrapper;
		$content = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals(1, $content);
	}

	function testToStringWithClassObject() {
		ob_start();
		$wrapper = new Timber\FunctionWrapper(array($this, 'isNum'), array(4));
		echo $wrapper;
		$content = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals(1, $content);
	}

	function testToStringWithClassString() {
		ob_start();
		$wrapper = new Timber\FunctionWrapper(array(get_class($this), 'isNum'), array(4));
		echo $wrapper;
		$content = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals(1, $content);
	}

	function testWPHead() {
		return $this->markTestSkipped('@todo Twig\Error\RuntimeError: An exception has been thrown during the rendering of a template ("readfile(/srv/www/wordpress-trunk/public_html/src/wp-includes/js/wp-emoji-loader.js): failed to open stream: No such file or directory")');
		$context = Timber::context();
		$str = Timber::compile_string('{{ function("wp_head") }}', $context);
		$this->assertRegexp('/<title>Test Blog/', trim($str));
	}

	function testFunctionInTemplate() {
		$context = Timber::context();
		$str = Timber::compile_string("{{ function('my_boo') }}", $context);
		$this->assertEquals('bar!', trim($str));
	}

	function testSoloFunctionUsingWrapper() {
		if (version_compare(Timber::$version, 2.0, '>=')) {
            return $this->markTestSkipped(
              'This functionality is disabled in Timber 2.0'
            );
        }
		new Timber\FunctionWrapper('my_boo');
		$str = Timber::compile_string("{{ my_boo() }}");
		$this->assertEquals('bar!', trim($str));
	}

	function testNakedSoloFunction() {
		add_filter('timber/twig', function( $twig ) {
			$twig->addFunction(new \Twig\TwigFunction('your_boo', array($this, 'your_boo')) );
			return $twig;
		});
		$context = Timber::context();
		$str = Timber::compile_string("{{ your_boo() }}", $context);
		$this->assertEquals('yourboo', trim($str));
	}

	/* Sample function to test exception handling */

	static function isNum($num) {
		if(!is_int($num)) {
			throw new Exception("Argument must be of type integer");
		} else {
			return true;
		}
	}

	function your_boo() {
		return 'yourboo';
	}

}



function my_boo() {
	return 'bar!';
}
