<?php

/**
 * Replicates Twig tests from twig/twig/tests/Fixtures/functions/date.test
 *
 * @group Timber\Date
 */
class TestTimberTwigDateFunction extends Timber_UnitTestCase {
	/**
	 * This test also checks whether date() returns a Datetime object.
	 *
	 * NOTE: we do the rounding because otherwise this can randomly fail when some fraction of ~.5 
	 * seconds has elapsed between when compile_string is called and the date().getTimestamp()
	 * method is executed.
	 */
	function testDateNowRounded() {
		$result = Timber\Timber::compile_string(
			"{{ (date().getTimestamp()/10)|round == date ? 'OK' : 'KO' }}",
			[ 'date' => round(time()/10) ]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testDateNowExpression() {
		$result = Timber\Timber::compile_string(
			"{{ date().format('r') == date('now').format('r') ? 'OK' : 'KO' }}",
			[]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testMkTime() {
		$result = Timber\Timber::compile_string(
			"{{ date(date) == date('2010-10-04 13:45') ? 'OK' : 'KO' }}",
			[ 'date' => mktime( 13, 45, 0, 10, 4, 2010 ) ]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testDateTimeYmdHi() {
		$result = Timber\Timber::compile_string(
			"{{ date(date) == date('2010-10-04 13:45') ? 'OK' : 'KO' }}",
			[ 'date' => new \DateTime( '2010-10-04 13:45' ) ]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testDateStringYmdHi() {
		$result = Timber\Timber::compile_string(
			"{{ date(date) == date('2010-10-04 13:45') ? 'OK' : 'KO' }}",
			[ 'date' => '2010-10-04 13:45' ]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testDateTimeCreateFormFormat() {
		$result = Timber\Timber::compile_string(
			"{{ date(date) == date('2010-10-04 13:45') ? 'OK' : 'KO' }}",
			[
				'date' => \DateTime::createFromFormat(
					'Y-m-d H:i', '2010-10-04 13:45',
					new \DateTimeZone( 'UTC' )
				)->getTimestamp(), // A Unix Timestamp is always GMT.
			]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testDateTimeCreateFormFormatBefore1970() {
		$result = Timber\Timber::compile_string(
			"{{ date(date) == date('1964-01-02 03:04') ? 'OK' : 'KO' }}",
			[
				'date' => \DateTime::createFromFormat(
					'Y-m-d H:i', '1964-01-02 03:04',
					new \DateTimeZone( 'UTC' )
				)->getTimestamp(),
			]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testDateDifferenceExpression() {
		$result = Timber\Timber::compile_string(
			"{{ date() > date('-1day') ? 'OK' : 'KO' }}",
			[]
		);

		$this->assertEquals( 'OK', $result );
	}

	function testDateNamedArgsBase() {
		$result = Timber\Timber::compile_string(
			"{{ date(date, 'America/New_York')|date('d/m/Y H:i:s P', false) }}",
			[ 'date' => mktime( 13, 45, 0, 10, 4, 2010 ) ]
		);

		$this->assertEquals( '04/10/2010 09:45:00 -04:00', $result );
	}

	function testDateNamedArgs() {
		$result = Timber\Timber::compile_string(
			"{{ date(timezone='America/New_York', date=date)|date('d/m/Y H:i:s P', false) }}",
			[ 'date' => mktime( 13, 45, 0, 10, 4, 2010 ) ]
		);

		$this->assertEquals( '04/10/2010 09:45:00 -04:00', $result );
	}
}
