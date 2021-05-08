<?php

/**
 * Replicates Twig tests from twig/twig/tests/Fixtures/filters/date*.test
 *
 * @group Timber\Date
 */
class TestTimberTwigDateFilter extends Timber_UnitTestCase {
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
			'date1'     => mktime( 13, 45, 0, 10, 4, 2010 ),
			'date2'     => new \DateTime( '2010-10-04 13:45' ),
			'date3'     => '2010-10-04 13:45',
			'date4'     => \DateTime::createFromFormat(
				'Y-m-d H:i',
				'2010-10-04 13:45',
				new \DateTimeZone( 'UTC' )
			)->getTimestamp(), // A unix timestamp is always GMT
			'date5'     => - 189291360,
			// \DateTime::createFromFormat('Y-m-d H:i', '1964-01-02 03:04', new \DateTimeZone('UTC'))->getTimestamp(),
			'date6'     => new \DateTime( '2010-10-04 13:45', new \DateTimeZone( 'America/New_York' ) ),
			'date7'     => '2010-01-28T15:00:00+04:00',
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
			"{{ date1|date('d/m/Y H:i:s P', 'Asia/Hong_Kong') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 21:45:00 +08:00', $result );
	}

	function testDateFormat5() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('d/m/Y H:i:s P', 'America/Chicago') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 08:45:00 -05:00', $result );
	}

	function testDateFormat6() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('e') }}",
			$this->get_context()
		);

		$this->assertEquals( 'Europe/Paris', $result );
	}

	function testDateFormat7() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('d/m/Y H:i:s') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 15:45:00', $result );
	}

	function testDateFormat8() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date }}",
			$this->get_context()
		);

		$this->assertEquals( 'October 4, 2010 15:45', $result );
	}

	function testDateFormat9() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('d/m/Y') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010', $result );
	}

	function testDateFormat10() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('d/m/Y H:i:s', 'Asia/Hong_Kong') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 21:45:00', $result );
	}

	function testDateFormat11() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('d/m/Y H:i:s', timezone1) }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 09:45:00', $result );
	}

	function testDateFormat12() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('d/m/Y H:i:s') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 15:45:00', $result );
	}

	function testDateFormat13() {
		$result = Timber\Timber::compile_string(
			"{{ date3|date }}",
			$this->get_context()
		);

		$this->assertEquals( 'October 4, 2010 15:45', $result );
	}

	function testDateFormat14() {
		$result = Timber\Timber::compile_string(
			"{{ date3|date('d/m/Y') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010', $result );
	}

	function testDateFormat15() {
		$result = Timber\Timber::compile_string(
			"{{ date4|date }}",
			$this->get_context()
		);

		$this->assertEquals( 'October 4, 2010 15:45', $result );
	}

	function testDateFormat16() {
		$result = Timber\Timber::compile_string(
			"{{ date4|date('d/m/Y') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010', $result );
	}

	function testDateFormat17() {
		$result = Timber\Timber::compile_string(
			"{{ date5|date }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 2, 1964 04:04', $result );
	}

	function testDateFormat18() {
		$result = Timber\Timber::compile_string(
			"{{ date5|date('d/m/Y') }}",
			$this->get_context()
		);

		$this->assertEquals( '02/01/1964', $result );
	}

	function testDateFormat19() {
		$result = Timber\Timber::compile_string(
			"{{ date6|date('d/m/Y H:i:s P', 'Europe/Paris') }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 19:45:00 +02:00', $result );
	}

	function testDateFormat20() {
		$result = Timber\Timber::compile_string(
			"{{ date6|date('d/m/Y H:i:s P', 'Asia/Hong_Kong') }}",
			$this->get_context()
		);

		$this->assertEquals( '05/10/2010 01:45:00 +08:00', $result );
	}

	function testDateFormat21() {
		$result = Timber\Timber::compile_string(
			"{{ date6|date('d/m/Y H:i:s P', false) }}",
			$this->get_context()
		);

		$this->assertEquals( '04/10/2010 13:45:00 -04:00', $result );
	}

	function testDateFormat22() {
		$result = Timber\Timber::compile_string(
			"{{ date6|date('e', 'Europe/Paris') }}",
			$this->get_context()
		);

		$this->assertEquals( 'Europe/Paris', $result );
	}

	function testDateFormat23() {
		$result = Timber\Timber::compile_string(
			"{{ date6|date('e', false) }}",
			$this->get_context()
		);

		$this->assertEquals( 'America/New_York', $result );
	}

	function testDateFormat24() {
		$result = Timber\Timber::compile_string(
			"{{ date7|date }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 28, 2010 12:00', $result );
	}

	function testDateFormat25() {
		$result = Timber\Timber::compile_string(
			"{{ date7|date(timezone='Europe/Paris') }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 28, 2010 12:00', $result );
	}

	function testDateFormat26() {
		$result = Timber\Timber::compile_string(
			"{{ date7|date(timezone='Asia/Hong_Kong') }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 28, 2010 19:00', $result );
	}

	function testDateFormat27() {
		$result = Timber\Timber::compile_string(
			"{{ date7|date(timezone=false) }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 28, 2010 15:00', $result );
	}

	function testDateFormat28() {
		$result = Timber\Timber::compile_string(
			"{{ date7|date(timezone='Indian/Mauritius') }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 28, 2010 15:00', $result );
	}

	function testDateFormat29() {
		$result = Timber\Timber::compile_string(
			"{{ '2010-01-28 15:00:00'|date(timezone='Europe/Paris') }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 28, 2010 16:00', $result );
	}

	function testDateFormat30() {
		$result = Timber\Timber::compile_string(
			"{{ '2010-01-28 15:00:00'|date(timezone='Asia/Hong_Kong') }}",
			$this->get_context()
		);

		$this->assertEquals( 'January 28, 2010 23:00', $result );
	}
}
