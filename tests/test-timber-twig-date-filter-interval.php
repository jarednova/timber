<?php

use Twig\Extension\CoreExtension;

/**
 * Replicates Twig tests from twig/twig/tests/Fixtures/filters/date*.test
 *
 * @group Timber\Date
 */
class TestTimberTwigDateFilterInterval extends Timber_UnitTestCase {
	function setUp() {
		parent::setUp();

		update_option( 'date_format', 'F j, Y H:i' );
		update_option( 'timezone_string', 'Europe/Paris' );

		/**
		 * We deliberately do not set a different default timezone with date_default_timezone_set()
		 * like they do in the Twig tests, because in a WordPress context, you shouldn’t do that.
		 * Instead, we set the timezone_string in the WordPress options to Europe/Paris.
		 */
	}

	function tearDown() {
		update_option( 'timezone_string', 'UTC' );

		parent::tearDown();
	}

	function get_context() {
		return [
			'date1'     => new \DateInterval( 'P2D' ),
			'date2'     => new \DateInterval( 'P2D' ),
			// This should have no effect on \DateInterval formatting
			'timezone1' => new \DateTimeZone( 'America/New_York' ),
		];
	}

	function testDateFormat1() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date }}",
			$this->get_context()
		);

		$this->assertEquals( '2 days', $result );
	}

	function testDateFormat2() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('%d days %h hours') }}",
			$this->get_context()
		);

		$this->assertEquals( '2 days 0 hours', $result );
	}

	function testDateFormat3() {
		$result = Timber\Timber::compile_string(
			"{{ date1|date('%d days %h hours', timezone1) }}",
			$this->get_context()
		);

		$this->assertEquals( '2 days 0 hours', $result );
	}

	function testDateFormat4() {
		add_filter( 'timber/loader/twig', function( Twig\Environment $twig ) {
			$twig->getExtension( CoreExtension::class )
		        ->setDateFormat( 'Y-m-d', '%d days %h hours' );

			return $twig;
		} );

		$result = Timber\Timber::compile_string(
			"{{ date2|date }}",
			$this->get_context()
		);

		$this->assertEquals( '2 days 0 hours', $result );
	}

	function testDateFormat5() {
		$result = Timber\Timber::compile_string(
			"{{ date2|date('%d days') }}",
			$this->get_context()
		);

		$this->assertEquals( '2 days', $result );
	}
}
