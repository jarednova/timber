<?php

require_once(__DIR__.'/php/timber-post-subclass.php');

use Timber\PostArrayObject;

	/**
	 * @group posts-api
	 * @group terms-api
	 * @group users-api
	 */
	class TestTimberHelper extends Timber_UnitTestCase {

		function testPluckArray() {
			$arr = array();
			$arr[] = array('name' => 'Bill', 'number' => 42);
			$arr[] = array('name' => 'Barack', 'number' => 44);
			$arr[] = array('name' => 'Hillary', 'number' => 45);
			$names = \Timber\Helper::pluck($arr, 'name');
			$this->assertEquals(array('Bill', 'Barack', 'Hillary'), $names);
		}

		function testPluckArrayMissing() {
			$arr = array();
			$arr[] = array('name' => 'Bill', 'number' => 42);
			$arr[] = array('name' => 'Barack', 'number' => 44);
			$arr[] = array('name' => 'Hillary', 'number' => 45);
			$arr[] = array('name' => 'Donald');
			$names = \Timber\Helper::pluck($arr, 'number');
			$this->assertEquals(array(42, 44, 45), $names);
		}

		function testPluckObject() {
			$billy = new stdClass();
			$billy->name = 'Billy Corgan';
			$billy->instrument = 'guitar';
			$jimmy = new stdClass();
			$jimmy->name = 'Jimmy Chamberlin';
			$jimmy->instrument = 'drums';
			$pumpkins = array($billy, $jimmy);
			$instruments = \Timber\Helper::pluck($pumpkins, 'instrument');
			$this->assertEquals(array('guitar', 'drums'), $instruments);
		}

		function testPluckObjectWithMethod() {
			$this->register_post_classmap_temporarily([
				'post' => TimberPostSubclass::class,
			]);

			$tps = Timber::get_post($this->factory->post->create());
			$jimmy = new stdClass();
			$jimmy->name = 'Jimmy';
			$pumpkins = array($tps, $jimmy);
			$bar = \Timber\Helper::pluck($pumpkins, 'foo');
			$this->assertEquals(array('bar'), $bar);
		}

		function testTrimCharacters() {
			$text    = "Sometimes you need to do such weird things like remove all comments from your project.";
			$trimmed = \Timber\TextHelper::trim_characters( $text, 20 );
			$this->assertEquals( "Sometimes yo&hellip;", $trimmed );
		}

		function testCloseTagsWithSelfClosingTags(){
			$p = '<p>My thing is this <hr>Whatever';
			$html = \Timber\TextHelper::close_tags($p);
			$this->assertEquals('<p>My thing is this <hr />Whatever</p>', $html);
		}

		function testCommentForm() {
			$post_id = $this->factory->post->create();
      global $post;
			$post = get_post($post_id);
			$form = Timber\Helper::ob_function( 'comment_form', array( array(), $post_id ) );
			$form = trim($form);
			$this->assertStringStartsWith('<div id="respond"', $form);
		}

		function testWPTitle(){
        	//since we're testing with twentyfourteen -- need to remove its filters on wp_title
        	remove_all_filters('wp_title');
            remove_theme_support( 'title-tag' );
        	$this->assertEquals('', Timber\Helper::get_wp_title());
        }

        function testWPTitleSingle(){
        	//since we're testing with twentyfourteen -- need to remove its filters on wp_title
        	remove_all_filters('wp_title');
        	$post_id = $this->factory->post->create(array('post_title' => 'My New Post'));
        	$post = get_post($post_id);
            $this->go_to( site_url( '?p='.$post_id ) );
        	$this->assertEquals('My New Post', Timber\Helper::get_wp_title());
        }

		function testCloseTags() {
			$str = '<a href="http://wordpress.org">Hi!';
			$closed = Timber\TextHelper::close_tags($str);
			$this->assertEquals($str.'</a>', $closed);
		}

		function testArrayToObject(){
			$arr = array('jared' => 'super cool');
			$obj = Timber\Helper::array_to_object($arr);
			$this->assertEquals('super cool', $obj->jared);
		}

		function testArrayArrayToObject() {
			$arr = array('jared' => 'super cool', 'prefs' => array('food' => 'spicy', 'women' => 'spicier'));
			$obj = Timber\Helper::array_to_object($arr);
			$this->assertEquals('spicy', $obj->prefs->food);
		}

		function testGetObjectIndexByProperty(){
			$obj1 = new stdClass();
			$obj1->name = 'mark';
			$obj1->skill = 'acro yoga';
			$obj2 = new stdClass();
			$obj2->name = 'austin';
			$obj2->skill = 'cooking';
			$arr = array($obj1, $obj2);
			$index = Timber\Helper::get_object_index_by_property($arr, 'skill', 'cooking');
			$this->assertEquals(1, $index);
			$obj = Timber\Helper::get_object_by_property($arr, 'skill', 'cooking');
			$this->assertEquals('austin', $obj->name);
		}

		function testGetObjectByPropertyButNoMatch() {
			$obj1 = new stdClass();
			$obj1->name = 'mark';
			$obj1->skill = 'acro yoga';
			$arr = array($obj1);
			$result = Timber\Helper::get_object_by_property($arr, 'skill', 'cooking');
			$this->assertFalse($result);
		}

		function testGetArrayIndexByProperty(){
			$obj1 = array();
			$obj1['name'] = 'mark';
			$obj1['skill'] = 'acro yoga';
			$obj2 = array();
			$obj2['name'] = 'austin';
			$obj2['skill'] = 'cooking';
			$arr = array($obj1, $obj2);
			$index = \Timber\Helper::get_object_index_by_property($arr, 'skill', 'cooking');
			$this->assertEquals(1, $index);
			$this->assertFalse(\Timber\Helper::get_object_index_by_property('butts', 'skill', 'cooking'));
		}

		/**
     	 * @expectedException InvalidArgumentException
     	 */
		function testGetObjectByPropertyButNo() {
			$obj1 = new stdClass();
			$obj1->name = 'mark';
			$obj1->skill = 'acro yoga';
			$obj = Timber\Helper::get_object_by_property($obj1, 'skill', 'cooking');
		}

		function testTimers() {
			$start = Timber\Helper::start_timer();
			sleep(1);
			$end = Timber\Helper::stop_timer($start);
			$this->assertContains(' seconds.', $end);
			$time = str_replace(' seconds.', '', $end);
			$this->assertGreaterThan(1, $time);
		}

		function testArrayTruncate() {
			$arr = array('Buster', 'GOB', 'Michael', 'Lindsay');
			$arr = Timber\Helper::array_truncate($arr, 2);
			$this->assertContains('Buster', $arr);
			$this->assertEquals(2, count($arr));
			$this->assertFalse(in_array('Lindsay', $arr));
		}

		function testIsTrue() {
			$true = Timber\Helper::is_true('true');
			$this->assertTrue($true);
			$false = Timber\Helper::is_true('false');
			$this->assertFalse($false);
			$estelleGetty = Timber\Helper::is_true('Estelle Getty');
			$this->assertTrue($estelleGetty);
		}

		function testIsEven() {
			$this->assertTrue(Timber\Helper::iseven(2));
			$this->assertFalse(Timber\Helper::iseven(7));
		}

		function testIsOdd() {
			$this->assertFalse(Timber\Helper::isodd(2));
			$this->assertTrue(Timber\Helper::isodd(7));
		}

		function testErrorLog() {
			ob_start();
			$this->assertTrue(Timber\Helper::error_log('foo'));
			$this->assertTrue(Timber\Helper::error_log(array('Dark Helmet', 'Barf')));
			$data = ob_get_flush();
		}

		function testOSort() {
			$michael = new stdClass();
			$michael->name = 'Michael';
			$michael->year = 1981;
			$lauren = new stdClass();
			$lauren->name = 'Lauren';
			$lauren->year = 1984;
			$boo = new stdClass();
			$boo->name = 'Robbie';
			$boo->year = 1989;
			$people = array($lauren, $michael, $boo);
			Timber\Helper::osort($people, 'year');
			$this->assertEquals('Michael', $people[0]->name);
			$this->assertEquals('Lauren', $people[1]->name);
			$this->assertEquals('Robbie', $people[2]->name);
			$this->assertEquals(1984, $people[1]->year);
		}

		/**
		 * Updated to new syntax
		 * @ticket #2124
		 */
		function testNewArrayFilter() {
			$posts = [];
			$posts[] = $this->factory->post->create(array('post_title' => 'Stringer Bell', 'post_content' => 'Idris Elba'));
			$posts[] = $this->factory->post->create(array('post_title' => 'Snoop', 'post_content' => 'Felicia Pearson'));
			$posts[] = $this->factory->post->create(array('post_title' => 'Cheese', 'post_content' => 'Method Man'));
			$posts = Timber::get_posts($posts);
			$template = '{% for post in posts | wp_list_filter("snoop")%}{{ post.content|striptags }}{% endfor %}';
			$str = Timber::compile_string($template, array('posts' => $posts));
			$this->assertEquals('Felicia Pearson', trim($str));
		}

		function testTwigFilterFilter() {
			$template = "{% set sizes = [34, 36, 38, 40, 42] %}{{ sizes|filter(v => v > 38)|join(', ') }}";
			$str = Timber::compile_string($template);
			$this->assertEquals("40, 42", $str);
		}

		/**
 		 * @expectedException Twig\Error\RuntimeError
		 */
		function testArrayFilterWithBogusArray() {
			$template = '{% for post in posts | filter({slug:"snoop", post_content:"Idris Elba"}, "OR")%}{{ post.title }} {% endfor %}';
			$str = Timber::compile_string($template, array('posts' => 'foobar'));
			$this->assertEquals('', $str);
		}

		function testConvertWPObject() {

			// Test WP_Post -> \Timber\Post
			$post_id = $this->factory->post->create();
			$wp_post = get_post( $post_id );
			$timber_post = \Timber\Helper::convert_wp_object($wp_post);
			$this->assertTrue($timber_post instanceof \Timber\Post);

			// Test WP_Term -> \Timber\Term
			$term_id = $this->factory->term->create();
			$wp_term = get_term( $term_id );
			$timber_term = \Timber\Helper::convert_wp_object($wp_term);
			$this->assertTrue($timber_term instanceof \Timber\Term);

			// Test WP_User -> \Timber\User
			$user_id = $this->factory->user->create();
			$wp_user = get_user_by('id', $user_id);
			$timber_user = \Timber\Helper::convert_wp_object($wp_user);
			$this->assertTrue($timber_user instanceof \Timber\User);

			// Test strange input
			$random_int = 2018;
			$convert_int = \Timber\Helper::convert_wp_object($random_int);
			$this->assertTrue($convert_int === $random_int);

			$array = array();
			$convert_array = \Timber\Helper::convert_wp_object($array);
			$this->assertTrue(is_array($convert_array));
		}

 		function testConvertPostWithClassMap() {
			register_post_type('sport');
			require_once('assets/Sport.php');

			$this->register_post_classmap_temporarily([
				'sport' => Sport::class,
			]);

 			$sport_id = $this->factory->post->create(array('post_type' => 'sport', 'post_title' => 'Basketball Player'));
			$wp_post = get_post($sport_id);
			$sport_post = \Timber\Helper::convert_wp_object($wp_post);
			$this->assertInstanceOf(Sport::class, $sport_post);
			$this->assertEquals('ESPN', $sport_post->channel());
 		}

 		/**
 		 * @expectedIncorrectUsage Accessing the thumbnail ID through {{ post._thumbnail_id }}
 		 */
 		function testDoingItWrong() {
 			$post_id = $this->factory->post->create();
 			$posts = Timber::get_posts();
 			update_post_meta($post_id, '_thumbnail_id', '707');
 			$post = Timber::get_post($post_id);
 			$thumbnail_id = $post->_thumbnail_id;
 		}  

	}
