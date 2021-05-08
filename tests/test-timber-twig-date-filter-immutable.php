<?php

/**
 * Replicates Twig tests from twig/twig/tests/Fixtures/filters/date*.test
 *
 * @group Timber\Date
 */
class TestTimberTwigDateFilterImmutable extends Timber_UnitTestCase {
	function setUp() {
		parent::setUp();

		update_option( 'date_format', 'F j, Y H:i' );
		update_option( 'timezone_string', 'Europe/Paris' );

		/**
		 * We deliberately do not set a different default timezone with date_default_timezone_set()
		 * like they do in the Twig tests, because in a WordPress context, you shouldn’t do that.
		 * Instead, we set the timezone_string in the WordPress options to Europe/Paris. On the date
		 * that is being checked (2010-10-04), the time difference from Europe/Paris to UTC was +2
		 * hours.
		 */
	}

	function tearDown() {
		update_option( 'timezone_string', 'UTC' );

		parent::tearDown();
	}

	function get_context() {
		return [
			'date1'     => new \DateTimeImmutable( '2010-10-04 13:45' ),
			'date2'     => new \DateTimeImmutable( '2010-10-04 13:45', new \DateTimeZone( 'America/New_York' ) ),
			'timezone1' => new \DateTimeZone( 'America/New_York' ),
		];
	}

	function testDateFormat1() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date }}",
			$this->get_context()
		);

		$this->assertEquals( 'October 4, 2010 15:45', $result );
	}

	function testDateFormat2() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('d/m/Y') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010', $result );
	}

	function testDateFormat3() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('d/m/Y H:i:s', 'Asia/Hong_Kong') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 21:45:00', $result );
	}

	function testDateFormat4() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('d/m/Y H:i:s', timezone1) }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 09:45:00', $result );
	}

	function testDateFormat5() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('d/m/Y H:i:s') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 15:45:00', $result );
	}

	function testDateFormat6() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date_modify('+1 hour')|date('d/m/Y H:i:s') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 16:45:00', $result );
	}

	function testDateFormat7() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('d/m/Y H:i:s P', 'Europe/Paris') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 19:45:00 +02:00', $result );
	}

	function testDateFormat8() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('d/m/Y H:i:s P', 'Asia/Hong_Kong') }}",
			$this->get_context()
		);

		$this->assertEquals( '05/10/2010 01:45:00 +08:00', $result );
	}

	function testDateFormat9() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('d/m/Y H:i:s P', false) }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 13:45:00 -04:00', $result );
	}

	function testDateFormat10() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('e', 'Europe/Paris') }}",
			$this->get_context()
		);

		$this->assertEquals( 'Europe/Paris', $result );
	}

	function testDateFormat11() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('e', false) }}",
			$this->get_context()
		);

		$this->assertEquals( 'America/New_York', $result );
	}
}
