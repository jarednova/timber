<?php

use Timber\PostArrayObject;
use Timber\PostQuery;

/**
 * @group posts-api
 * @group post-collections
 */
class TestTimberPostIterator extends Timber_UnitTestCase {
	private $collector;

	/**
	 * Checks if the 'loop_end' hook runs after last array iteration.
	 */
	function testLoopEndAfterLastItem() {
		$pids = $this->factory->post->create_many(3, [
			'post_title' => 'My Post',
		]);
		$posts = new Timber\PostArrayObject( $pids );

		$this->collector = [];

		// Later we'll assert that our loop_end hook got called as expected.
		add_action( 'loop_end', function() {
			$this->collector[] = 'ended';
		} );

		foreach ( $posts as $post ) {
			$this->collector[] = $post->title;
		}

		$this->assertEquals( ['My Post', 'My Post', 'My Post', 'ended'], $this->collector );
	}

	function testSetupMethodCalled() {
		$pids = $this->factory->post->create_many(3);
		$posts = new Timber\PostArrayObject( $pids );

		// Make sure $wp_query is set up.
		$this->go_to( get_permalink( get_option( 'page_for_posts' ) ) );

		$in_the_loop = false;

		foreach ($posts as $post) {
			global $wp_query;
			$in_the_loop = $in_the_loop || $wp_query->in_the_loop;
		}

		$this->assertTrue( $in_the_loop );
	}

	/**
	 * Checks if wp_reset_postdata() is run after a query.
	 */
	function testResetPostDataAfterLastItem() {
		$pids = $this->factory->post->create_many(3);
		$posts = new Timber\PostArrayObject( $pids );

		// Make sure $wp_query is set up.
		$this->go_to( get_permalink( get_option( 'page_for_posts' ) ) );

		// Save initial post for later check.
		global $post;
		$initial_post = $post;

		foreach ( $posts as $post ) {
			// Run something
			$post->title;
		}

		$this->assertEquals( $initial_post, $post );
	}

	/**
	 * Checks if $wp_query->in_the_loop is reset after a query.
	 */
	function testInTheLoopAfterLastItem() {
		$pids = $this->factory->post->create_many(3);
		$posts = new Timber\PostArrayObject( $pids );

		// Make sure $wp_query is set up.
		$this->go_to( get_permalink( get_option( 'page_for_posts' ) ) );

		foreach ( $posts as $post ) {
			// Run something
			$post->title;

			global $wp_query;
			$this->assertTrue( $wp_query->in_the_loop );
		}

		global $wp_query;

		$this->assertFalse( $wp_query->in_the_loop );
	}
}
