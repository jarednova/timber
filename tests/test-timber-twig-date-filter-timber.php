<?php

/**
 * Replicates Twig tests from twig/twig/tests/Fixtures/filters/date*.test
 *
 * @group Timber\Date
 */
class TestTimberTwigDateFilterTimber extends Timber_UnitTestCase {
	function testTwigFilterDate(){
		$date = '1983-09-28';
		$data['bday'] = $date;
		$str = Timber::compile_string("{{bday|date('M j, Y')}}", $data);
		$this->assertEquals('Sep 28, 1983', trim($str));
		$data['bday'] = new DateTime($date);
		$str = Timber::compile_string("{{bday|date('M j, Y')}}", $data);
		$this->assertEquals('Sep 28, 1983', trim($str));
	}

	function testTwigFilterDateWordPressFormat(){
		$data['day'] = '2012-10-15 20:14:48';
		$str = Timber::compile_string("{{day|date('M jS, Y g:ia')}}", $data);
		$this->assertEquals('Oct 15th, 2012 8:14pm', trim($str));
	}

	function testTwigFilterNow(){
		$now = date('M jS, Y');
		$str = Timber::compile_string("{{now|date('M jS, Y')}}");
		$this->assertSame($now, $str);
		$str = Timber::compile_string("{{null|date('M jS, Y')}}");
		$this->assertSame($now, $str);
		$str = Timber::compile_string("{{'now'|date('M jS, Y')}}");
		$this->assertSame($now, $str);
	}

	function testTwigFilterDateI18n(){
		//Set to Spanish in wp-config to test
		//define("WPLANG", "es_ES");
		if (WPLANG == 'es_ES'){
			global $wp_locale;
			$data['day'] = '1983-09-28 20:14:48';
			$str = Timber::compile_string("{{day|date('F jS, Y g:ia')}}", $data);
			$this->assertEquals('septiembre 28th, 1983 8:14pm', $str);
			return;
		}
		$this->markTestSkipped('WPLANG needs to be set to `es_ES` to test');
	}

	function testTwigFilterDateI18nWordPressOption(){
		if (WPLANG == 'es_ES'){
			global $wp_locale;
			$data['day'] = '1983-09-28';
			$str = Timber::compile_string("{{day|date}}", $data);
			$this->assertEquals('28 septiembre, 1983', $str);
			return;
		}
		$this->markTestSkipped('WPLANG needs to be set to `es_ES` to test');
	}

	function testTwigFilterDateWordPressOption(){
		$format = get_option( 'date_format' );
		$str    = Timber::compile_string( "{{ now|date('{$format}') }}" );
		$empty  = Timber::compile_string( "{{ now|date }}" );

		$this->assertSame( $str, $empty );
	}
}
