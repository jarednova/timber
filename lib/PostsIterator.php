<?php

namespace Timber;

use Timber\Factory\PostFactory;

/**
 * Class PostsIterator
 */
class PostsIterator extends \ArrayIterator {
	/**
	 * Prepares the state before working on a post.
	 *
	 * Calls the `setup()` function of the current post to setup post data. Before starting the
	 * loop, it will call the 'loop_start' hook to improve compatibility with WordPress.
	 *
	 * @return mixed
	 */
	public function current() {
		static $factory;
		$factory = $factory ?? new PostFactory();

		// Fire action when the loop has just started.
		if ( 0 === $this->key() ) {
			do_action_ref_array( 'loop_start', array( &$GLOBALS['wp_query'] ) );
		}

		/**
		 * The `loop_start` action is not the only thing we do to improve compatibility. There’s
		 * more going on in the Timber\Post::setup() function. The compabitibility improvements live
		 * there, because they also need to work for singular templates, where there’s no loop.
		 */
		$wp_post = parent::current();
		// Lazily instantiate a Timber\Post instance exactly once.
		// @todo maybe improve performance by caching the instantiated post.
		$post = $factory->from($wp_post);
		$post->setup();

		return $post;
	}

	/**
	 * Cleans up state before advancing to the next post.
	 *
	 * Calls the `teardown()` function of the current post. In the last run of a loop through posts,
	 * it will call the 'loop_end' hook to improve compatibility with WordPress.
	 *
	 * @since 2.0.0
	 */
	public function next() {
		/**
		 * The `loop_end` action is not the only thing we do to improve compatibility. There’s
		 * more going on in the Timber\Post::teardown() function. The compabitibility improvements
		 * live there, because they also need to work for singular templates, where there’s no loop.
		 */
		$post = $this->current();
		$post->teardown();

		// Fire action when the loop has ended.
		if ( $this->key() === $this->count() - 1 ) {
			do_action_ref_array( 'loop_end', array( &$GLOBALS['wp_query'] ) );
			wp_reset_postdata();
		}

		parent::next();
	}
}
