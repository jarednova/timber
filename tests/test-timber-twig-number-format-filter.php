<?php

/**
 * Replicates Twig tests from twig/twig/tests/Fixtures/filters/number_format*.test
 *
 * @group Timber\Number
 */
class TestTimberTwigNumberFormatFilter extends Timber_UnitTestCase {

	function get_context() {
		return [
			'number1'     => 20,
			'number2'     => 20.25,
			'number3'     => 1020.25,
		];
	}

	function testNumberFormat1() {
		$result = Timber\Timber::compile_string(
			"{{ number1|number_format }}",
			$this->get_context()
		);

		$this->assertEquals( '20', $result );
	}

	function testNumberFormat2() {
		$result = Timber\Timber::compile_string(
			"{{ number2|number_format }}",
			$this->get_context()
		);

		$this->assertEquals( '20', $result );
	}

	function testNumberFormat3() {
		$result = Timber\Timber::compile_string(
			"{{ number2|number_format(2) }}",
			$this->get_context()
		);

		$this->assertEquals( '20.25', $result );
	}

	function testNumberFormat4() {
		$result = Timber\Timber::compile_string(
			"{{ number2|number_format(2, ',') }}",
			$this->get_context()
		);

		$this->assertEquals( '20,25', $result );
	}

	function testNumberFormat5() {
		$result = Timber\Timber::compile_string(
			"{{ number3|number_format(2, ',') }}",
			$this->get_context()
		);

		$this->assertEquals( '1,020,25', $result );
	}

	function testNumberFormat6() {
		$result = Timber\Timber::compile_string(
			"{{ number3|number_format(2, ',', '.') }}",
			$this->get_context()
		);

		$this->assertEquals( '1.020,25', $result );
	}

}
