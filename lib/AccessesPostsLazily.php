<?php

namespace Timber;

use WP_Post;

use Timber\Factory\PostFactory;
use Timber\Helper;

/**
 * Trait implementing ArrayAccess::getOffset() using lazy instantiation.
 *
 * @see https://timber.github.io/docs/v2/guides/posts.md#laziness-and-caching
 * @internal
 */
trait AccessesPostsLazily {
	/**
	 * Whether Timber\Post instances have been lazily instantiated.
	 *
	 * @var bool
	 */
	private $realized = false;

	/**
	 * PostFactory instance used internally to instantiate Posts.
	 *
	 * @var \Timber\Factory\PostFactory
	 */
	private $factory;

	/**
	 * For better performance, Post Collections do not instantiate `Timber\Post` objects
	 * at query time. They instantiate each `Timber\Post` only as needed, i.e. while
	 * iterating or for direct array access (`$coll[$i]`). Since specific `Timber\Post`
	 * implementations may have expensive `::setup()` operations, this is usually
	 * what you want, but not always. For example, you may want to force eager
	 * instantiation to front-load a collection to be cached. To eagerly instantiate
	 * a lazy collection of objects is to "realize" that collection.
	 *
	 * @api
	 * @example
	 * ```php
	 * $lazy_posts = \Timber\Helper::transient('my_posts', function() {
	 *   return \Timber\Timber::get_posts([
	 * 		 'post_type' => 'some_post_type',
	 *   ]);
	 * }, HOUR_IN_SECONDS);
	 *
	 * foreach ($lazy_posts as $post) {
	 *   // This will incur the performance cost of Post::setup()
	 * }
	 *
	 * // Contrast with:
	 *
	 * $eager_posts = \Timber\Helper::transient('my_posts', function() {
	 *   $query = \Timber\Timber::get_posts([
	 * 		 'post_type' => 'some_post_type',
	 *   ]);
	 *   // Incur Post::setup() cost up front.
	 *   return $query->realize();
	 * }, HOUR_IN_SECONDS);
	 *
	 * foreach ($eager_posts as $post) {
	 *   // No additional overhead here.
	 * }
	 * ```
	 * @return \Timber\PostCollectionInterface the realized PostQuery
	 */
	public function realize() : self {
		if (!$this->realized) {
			// offsetGet() is where lazy instantiation actually happens.
			// Since arbitrary array index access may have happened previously,
			// leverage that to ensure each Post is instantiated exactly once.
			// We call parent::getArrayCopy() to avoid infinite mutual recursion.
			foreach (array_keys(parent::getArrayCopy()) as $k) {
				$this->offsetGet($k);
			}
			$this->realized = true;
		}

		return $this;
	}

	/**
	 * @internal
	 */
	public function getArrayCopy() {
		// Force eager instantiation of Timber\Posts before returning them all in an array.
		$this->realize();
		return parent::getArrayCopy();
	}

	/**
	 * @api
	 * @return array
	 */
	public function to_array() : array {
		return $this->getArrayCopy();
	}

	/**
	 * @deprecated 2.0.0 use PostCollectionInterface::to_array() instead
	 * @api
	 * @return array
	 */
	public function get_posts() : array {
		Helper::deprecated(sprintf('%s::get_posts()', static::class), sprintf('%s::to_array()', static::class), '2.0.0');
		return $this->getArrayCopy();
	}

	/**
	 * Lazily instantiates Timber\Post instances from WP_Post objects.
	 *
	 * @internal
	 */
	public function offsetGet($offset) {
		$post = parent::offsetGet($offset);
		if ($post instanceof WP_Post) {
			$post = $this->factory()->from($post);
			$this->offsetSet($offset, $post);
		}

		return $post;
	}

	/**
	 * @internal
	 */
	private function factory() : PostFactory {
		if (!$this->factory) {
			$this->factory = new PostFactory();
		}

		return $this->factory;
  }

}