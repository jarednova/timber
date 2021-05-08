<?php

namespace Timber;

use ArrayAccess;
use Countable;
use Traversable;

/**
 * Interface for dealing with collections of Posts, whether directly wrapping a WP_Query instance,
 * a simple (flat/numeric) array of Posts, or some other kind of custom collection.
 *
 * @api
 */
interface PostCollectionInterface extends Traversable, Countable, ArrayAccess {
  /**
   * Get the Pagination for this collection, if available.
   *
   * @api
   * @param array $options optional config options to pass to the \Timber\Pagination constructor.
   * @return null|\Timber\Pagination a Pagination object if pagination is available for this collection;
   * null otherwise.
   */
  public function pagination(array $options = []);

  /**
   * Get this collection as a numeric array of \Timber\Post objects.
   *
   * @api
   * @return \Timber\Post[]
   */
  public function to_array() : array;
}