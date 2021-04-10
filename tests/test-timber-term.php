<?php

	class TestTimberTerm extends Timber_UnitTestCase {
		
		function testTermFrom() {
			register_taxonomy('baseball', array('post'));
			register_taxonomy('hockey', array('post'));
			$term_id = $this->factory->term->create(array('name' => 'Rangers', 'taxonomy' => 'baseball'));
			$term_id = $this->factory->term->create(array('name' => 'Cardinals', 'taxonomy' => 'baseball'));
			$term_id = $this->factory->term->create(array('name' => 'Rangers', 'taxonomy' => 'hockey'));
			$baseball_teams = Timber\Term::from(get_terms(array('taxonomy' => 'baseball', 'hide_empty' => false)), 'baseball');
			$this->assertEquals(2, count($baseball_teams));
			$this->assertEquals('Cardinals', $baseball_teams[0]->name());
		}

		/**
		 * @ticket #2362
		 */
		function testMultiTermsWithSameSlug() {
			$post_tag_id = $this->factory->term->create(array('name' => 'Security', 'taxonomy' => 'post_tag'));
			$category_id = $this->factory->term->create(array('name' => 'Security', 'taxonomy' => 'category'));
			$post_id     = $this->factory->post->create();
			wp_set_object_terms($post_id, $post_tag_id, 'post_tag', true);
			wp_set_object_terms($post_id, $category_id, 'category', true);

			$term_default  = new Timber\Term('security');
			$this->assertEquals('post_tag', $term_default->taxonomy);
			$this->assertEquals('Security', $term_default->title());

			$term_category = new Timber\Term('security', 'category');
			$this->assertEquals('category', $term_category->taxonomy);
			$this->assertEquals('Security', $term_category->title());
		}

		function testConstructorWithClass() {
			register_taxonomy('arts', array('post'));

			$term_id = $this->factory->term->create(array('name' => 'Zong', 'taxonomy' => 'post_tag'));
			$term = new \Timber\Term($term_id);

			$template = '{% set zp_term = Term("'.$term_id.'", "Arts") %}{{ zp_term.name }} {{ zp_term.taxonomy }}';
			$string = Timber::compile_string($template);
			$this->assertEquals('Zong post_tag', $string);

			$template = '{% set zp_term = TimberTerm('.$term_id.', "Arts") %}{{ zp_term.foobar }}';
			$string = Timber::compile_string($template);
			$this->assertEquals('Zebra', $string);
		}

		function testConstructorWithClassAndTaxonomy() {
			register_taxonomy('arts', array('post'));

			$term_id = $this->factory->term->create(array('name' => 'Zong', 'taxonomy' => 'arts'));
			$term = new \Timber\Term($term_id);

			$template = '{% set zp_term = Term("'.$term_id.'", "arts", "Arts") %}{{ zp_term.name }} {{ zp_term.taxonomy }}';
			$string = Timber::compile_string($template);
			$this->assertEquals('Zong arts', $string);

			$template = '{% set zp_term = TimberTerm('.$term_id.', "Arts") %}{{ zp_term.foobar }}';
			$string = Timber::compile_string($template);
			$this->assertEquals('Zebra', $string);
		}

		function testConstructor() {
			register_taxonomy('arts', array('post'));

			$term_id = $this->factory->term->create(array('name' => 'Zong', 'taxonomy' => 'arts'));
			$term = new TimberTerm($term_id, 'arts');
			$this->assertEquals('Zong', $term->name());
			$template = '{% set zp_term = TimberTerm("'.$term->ID.'", "arts") %}{{ zp_term.name }}';
			$string = Timber::compile_string($template);
			$this->assertEquals('Zong', $string);
		}

		function testTerm() {
			$term_id = $this->factory->term->create();
			$term = new TimberTerm($term_id);
			$this->assertEquals('Timber\Term', get_class($term));
		}

		function testGetTermWithObject() {
			$term_id = $this->factory->term->create(array('name' => 'Famous Commissioners'));
			$term_data = get_term($term_id, 'post_tag');
			$this->assertTrue( in_array( get_class($term_data), array('WP_Term', 'stdClass') ) );
			$term = new TimberTerm($term_id);
			$this->assertEquals('Famous Commissioners', $term->name());
			$this->assertEquals('Timber\Term', get_class($term));
		}

		function testTermConstructWithSlug() {
			$term_id = $this->factory->term->create(array('name' => 'New England Patriots'));
			$term = new TimberTerm('new-england-patriots');
			$this->assertEquals($term->ID, $term_id);
		}

		function testTermToString() {
			$term_id = $this->factory->term->create(array('name' => 'New England Patriots'));
			$term = new TimberTerm('new-england-patriots');
			$str = Timber::compile_string('{{term}}', array('term' => $term));
			$this->assertEquals('New England Patriots', $str);
		}

		function testTermDescription() {
			$desc = 'An honest football team';
			$term_id = $this->factory->term->create(array('name' => 'New England Patriots', 'description' => $desc));
			$term = new TimberTerm($term_id, 'post_tag');
			$this->assertEquals($desc, $term->description());
		}

		function testTermConstructWithName() {
			$term_id = $this->factory->term->create(array('name' => 'St. Louis Cardinals'));
			$term = new TimberTerm('St. Louis Cardinals');
			$this->assertNull($term->ID);
		}

		function testTermInitObject() {
			$term_id = $this->factory->term->create();
			$term = get_term($term_id, 'post_tag');
			$term = new TimberTerm($term);
			$this->assertEquals($term->ID, $term_id);
		}

		function testTermLink() {
			$term_id = $this->factory->term->create();
			$term = new TimberTerm($term_id);
			$this->assertContains('http://', $term->link());
			$this->assertContains('http://', $term->get_link());
		}

		function testTermPath() {
			$term_id = $this->factory->term->create();
			$term = new TimberTerm($term_id);
			$this->assertFalse(strstr($term->path(), 'http://'));
			$this->assertFalse(strstr($term->get_path(), 'http://'));
		}

		function testGetPostsWithPostTypesString() {
			register_post_type('portfolio', array('taxonomies' => array('post_tag'), 'public' => true));
			$term_id = $this->factory->term->create(array('name' => 'Zong'));
			$posts = $this->factory->post->create_many(3, array('post_type' => 'post', 'tags_input' => 'zong') );
			$posts = $this->factory->post->create_many(5, array('post_type' => 'portfolio', 'tags_input' => 'zong') );
			$term = new TimberTerm($term_id);
			$posts_gotten = $term->posts('posts_per_page=4');
			$this->assertEquals(4, count($posts_gotten));

			$posts_gotten = $term->posts(array('posts_per_page' => 7));
			$this->assertEquals(7, count($posts_gotten));
		}

		function testGetPostsWithAnyAndCustomTax() {
			register_post_type('portfolio', array('taxonomies' => array('arts'), 'public' => true));
			register_taxonomy('arts', array('portfolio'));

			$term_id = $this->factory->term->create(array('name' => 'Zong', 'taxonomy' => 'arts'));
			$posts = $this->factory->post->create_many(5, array('post_type' => 'portfolio' ));
			$term = new TimberTerm($term_id);
			foreach($posts as $post_id) {
				wp_set_object_terms($post_id, $term_id, 'arts', true);
			}
			$terms = Timber::get_terms('arts');
			$template = '{% for term in terms %}{% for post in term.posts %}{{post.title}}{% endfor %}{% endfor %}';
			$template = '{% for term in terms %}{{term.posts|length}}{% endfor %}';
			$str = Timber::compile_string($template, array('terms' => $terms));
			$this->assertEquals('5', $str);
		}

		function testGetPostsOld() {
			$term_id = $this->factory->term->create();
			$posts = array();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			foreach($posts as $post_id){
				wp_set_object_terms($post_id, $term_id, 'post_tag', true);
			}
			$term = new TimberTerm($term_id);
			$gotten_posts = $term->get_posts();
			$this->assertEquals(count($posts), count($gotten_posts));
		}

		function testGetPostsAsPageOld() {
			$term_id = $this->factory->term->create();
			$posts = array();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			foreach($posts as $post_id){
				set_post_type($post_id, 'page');
				wp_set_object_terms($post_id, $term_id, 'post_tag', true);
			}
			$term = new TimberTerm($term_id);
			$gotten_posts = $term->posts(count($posts), 'page');
			$this->assertEquals(count($posts), count($gotten_posts));
			$gotten_posts = $term->posts(count($posts), 'any');
			$this->assertEquals(count($posts), count($gotten_posts));
			$gotten_posts = $term->posts(count($posts), 'post');
			$this->assertEquals(0, count($gotten_posts));
		}

		function testGetPostsNew() {
			require_once('php/timber-post-subclass.php');
			$term_id = $this->factory->term->create();
			$posts = array();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			foreach($posts as $post_id){
				set_post_type($post_id, 'page');
				wp_set_object_terms($post_id, $term_id, 'post_tag', true);
			}

			$term = new TimberTerm($term_id);

			$gotten_posts = $term->get_posts('post_type=page');
			$this->assertEquals(count($posts), count($gotten_posts));

			$gotten_posts = $term->get_posts('post_type=page', 'TimberPostSubclass');
			$this->assertEquals(count($posts), count($gotten_posts));
			$this->assertInstanceOf( 'TimberPostSubclass', $gotten_posts[0] );

			$gotten_posts = $term->get_posts(array('post_type' => 'page'), 'TimberPostSubclass');
			$this->assertInstanceOf( 'TimberPostSubclass', $gotten_posts[0] );
			$this->assertEquals(count($posts), count($gotten_posts));
		}

		function testPostsWithCustomPostType() {
			$term_id = $this->factory->term->create();
			$posts   = array();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();

			foreach ( $posts as $post_id ) {
				set_post_type( $post_id, 'page' );
				wp_set_object_terms( $post_id, $term_id, 'post_tag', true );
			}

			$term = new Timber\Term( $term_id );

			$term_posts = $term->posts( [
				'posts_per_page' => 2,
				'orderby'        => 'menu_order',
			], 'page' );

			$this->assertEquals( 'Timber\Post', get_class( $term_posts[0] ) );
			$this->assertEquals( 'page', $term_posts[0]->post_type );
			$this->assertEquals( 2, count( $term_posts ) );
		}

		function testPostsWithCustomPostTypeAndCustomClass() {
			require_once 'php/timber-post-subclass.php';

			$term_id = $this->factory->term->create();
			$posts   = array();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();

			foreach ( $posts as $post_id ) {
				set_post_type( $post_id, 'page' );
				wp_set_object_terms( $post_id, $term_id, 'post_tag', true );
			}

			$term = new Timber\Term( $term_id );

			$term_posts = $term->posts( [
				'posts_per_page' => 2,
				'orderby'        => 'menu_order',
			], 'page', 'TimberPostSubclass' );

			$this->assertInstanceOf( 'TimberPostSubclass', $term_posts[0] );
			$this->assertEquals( 'page', $term_posts[0]->post_type );
			$this->assertEquals( 2, count( $term_posts ) );
		}

		/**
		 * This test uses the logic described in https://github.com/timber/timber/issues/799#issuecomment-192445207.
		 */
		function testPostsWithCustomPostTypePageAndCustomClass() {
			require_once 'php/timber-post-subclass.php';
			require_once 'php/timber-post-subclass-page.php';

			$term_id = $this->factory->term->create();
			$posts   = array();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();
			$posts[] = $this->factory->post->create();

			foreach ( $posts as $post_id ) {
				set_post_type( $post_id, 'page' );
				wp_set_object_terms( $post_id, $term_id, 'post_tag', true );
			}

			$term = new Timber\Term( $term_id );

			$term_posts = $term->posts( [
				'posts_per_page' => 2,
				'orderby'        => 'menu_order',
			], 'page', 'TimberPostSubclass' );

			$this->assertInstanceOf( 'page', $term_posts[0] );
			$this->assertEquals( 'page', $term_posts[0]->post_type );
			$this->assertEquals( 2, count( $term_posts ) );
		}

		function testTermChildren() {
			$parent_id = $this->factory->term->create(array('name' => 'News', 'taxonomy' => 'category'));
			$local = $this->factory->term->create(array('name' => 'Local', 'parent' => $parent_id, 'taxonomy' => 'category'));
			$int = $this->factory->term->create(array('name' => 'International', 'parent' => $parent_id, 'taxonomy' => 'category'));

			$term = new TimberTerm($parent_id);
			$children = $term->children();
			$this->assertEquals(2, count($children));
			$this->assertEquals('Local', $children[0]->name);
		}

		/**
		 @issue #824
		 */
		function testTermWithNativeMeta() {
			$tid = $this->factory->term->create(array('name' => 'News', 'taxonomy' => 'category'));
			add_term_meta($tid, 'foo', 'bar');
			$term = new TimberTerm($tid);
			$template = '{{term.foo}}';
			$compiled = Timber::compile_string($template, array('term' => $term));
			$this->assertEquals('bar', $compiled);
		}

		/**
		 @issue #824
		 */
		function testTermWithNativeMetaFalse() {
			$tid = $this->factory->term->create(array('name' => 'News', 'taxonomy' => 'category'));
			add_term_meta($tid, 'foo', false);
			$term = new TimberTerm($tid);
			$this->assertEquals('', $term->meta('foo'));
		}

		/**
		 @issue #824
		 */
		function testTermWithNativeMetaNotExisting() {
			$tid = $this->factory->term->create(array('name' => 'News', 'taxonomy' => 'category'));
			add_term_meta($tid, 'bar', 'qux');;
			$wp_native_value = get_term_meta($tid, 'foo', true);
			$acf_native_value = get_field('foo', 'category_'.$tid);

			$valid_wp_native_value = get_term_meta($tid, 'bar', true);
			$valid_acf_native_value = get_field('bar', 'category_'.$tid);

			$term = new TimberTerm($tid);

			//test baseline "bar" data
			$this->assertEquals('qux', $valid_wp_native_value);
			$this->assertEquals('qux', $valid_acf_native_value);
			$this->assertEquals('qux', $term->bar);

			//test the one taht doesn't exist
			$this->assertEquals('string', gettype($wp_native_value));
			$this->assertEmpty($wp_native_value);
			$this->assertNull($acf_native_value);
			$this->assertNotTrue($term->meta('foo'));
		}

		function testTermEditLink() {
			wp_set_current_user(1);
			$tid = $this->factory->term->create(array('name' => 'News', 'taxonomy' => 'category'));
			$term = new TimberTerm($tid);
			$links = array();

			$links[] = 'http://example.org/wp-admin/term.php?taxonomy=category&tag_ID='.$tid.'&post_type=post';
			$links[] = 'http://example.org/wp-admin/edit-tags.php?action=edit&taxonomy=category&tag_ID='.$tid.'&post_type=post';
			$links[] = 'http://example.org/wp-admin/edit-tags.php?action=edit&taxonomy=category&tag_ID='.$tid;
			$links[] = 'http://example.org/wp-admin/term.php?taxonomy=category&term_id='.$tid.'&post_type=post';
			$this->assertContains($term->edit_link(), $links);
		}

	}

	class Arts extends Timber\Term {

		function foobar() {
			return 'Zebra';
		}

	}
