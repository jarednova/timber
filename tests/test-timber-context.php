<?php

use Timber\Timber;
use Timber\Post;

class TestTimberContext extends Timber_UnitTestCase {
	/**
	 * This throws an infite loop if memorization isn't working
	 */
	function testContextLoop() {
		add_filter( 'timber/context', function( $context ) {
			$context          = Timber::context();
			$context['zebra'] = 'silly horse';

			return $context;

		} );

		$context = Timber::context();

		$this->assertEquals( 'http://example.org', $context['http_host'] );
	}

	function testPostContextSimple() {
		$post_id = $this->factory->post->create();

		$this->go_to( get_permalink( $post_id ) );

		$context = Timber::context();
		$post    = new Post( $post_id );

		$this->assertArrayNotHasKey( 'posts', $context );
		$this->assertEquals( $post, $context['post'] );

		$context = Timber::context();
		$this->assertEquals('http://example.org', $context['http_host']);
	}

	function testPostsContextSimple() {
		update_option( 'show_on_front', 'posts' );
		$this->factory->post->create_many( 3 );
		$this->go_to( '/' );

		$context = Timber::context();

		$this->assertArrayNotHasKey( 'post', $context );
		$this->assertInstanceOf( 'Timber\PostQuery', $context['posts'] );
		$this->assertCount( 3, $context['posts']->get_posts() );
	}

	function testIfSetupFunctionIsRunInSingularTemplates() {
		$post_id = $this->factory->post->create();
		$this->go_to( get_permalink( $post_id ) );

		global $wp_query;

		$this->assertFalse( $wp_query->in_the_loop );

		Timber::context();

		$this->assertTrue( $wp_query->in_the_loop );
	}

	/**
	 * Tests whether 'the_post' action is called when a singular template is displayed.
	 *
	 * @see TestTimberPost::testPostConstructorAndThePostHook()
	 */
	function testIfThePostHookIsRunInSingularTemplates() {
		add_action( 'the_post', function( $post ) {
			add_filter( 'touched_the_post_action', '__return_true' );
		} );

		$post_id = $this->factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		Timber::context();

		$this->assertTrue( apply_filters( 'touched_the_post_action', false ) );
	}

  function testContext() {
		$context = Timber::context();
		$this->assertEquals('http://example.org', $context['http_host']);
	}

}
