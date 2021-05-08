<?php

namespace Timber\Factory;

use Timber\CoreInterface;
use Timber\User;

use WP_User_Query;
use WP_User;

/**
 * Class UserFactory
 *
 * Internal class for instantiating User objects/collections. Responsible for applying
 * the `timber/user/classmap` filter.
 *
 * @internal
 */
class UserFactory {
	/**
	 * Internal method that does the heavy lifting for converting some kind of user
	 * object or ID to a Timber\User object.
	 *
	 * Do not call this directly. Use Timber::get_user() or Timber::get_users() instead.
	 *
	 * @internal
	 * @param mixed $params One of:
	 * * a user ID (string or int)
	 * * a WP_User_Query object
	 * * a WP_User object
	 * * a Timber\Core object (presumably a User)
	 * * an array of IDs
	 * * an associative array (interpreted as arguments for a WP_User_Query)
	 * @return \Timber\User|array|false
	 */
	public function from($params) {
		if (is_int($params) || is_string($params) && is_numeric($params)) {
			return $this->from_id($params);
		}

		if ($params instanceof WP_User_Query) {
			return $this->from_wp_user_query($params);
		}

		if (is_object($params)) {
			// assume we have some kind of WP user object, Timber or otherwise
			return $this->from_user_object($params);
		}

		if ($this->is_numeric_array($params)) {
			// we have a numeric array of objects and/or IDs
			return array_map([$this, 'from'], $params);
		}

		if (is_array($params)) {
			// we have a query array to be passed to WP_User_Query::__construct()
			return $this->from_wp_user_query(new WP_User_Query($params));
		}

		return null;
	}

	protected function from_id(int $id) {
		$wp_user = get_user_by('id', $id);

		return $wp_user ? $this->build($wp_user) : false;
	}

	protected function from_user_object($obj) : CoreInterface {
		if ($obj instanceof CoreInterface) {
			// we already have some kind of Timber Core object
			return $obj;
		}

		if ($obj instanceof WP_User) {
			return $this->build($obj);
		}

		throw new \InvalidArgumentException(sprintf(
			'Expected an instance of Timber\CoreInterface or WP_User, got %s',
			get_class($obj)
		));
	}

	// @todo return a UserCollection instance?
	protected function from_wp_user_query(WP_User_Query $query) : Iterable {
		return array_map([$this, 'build'], $query->get_results());
	}

	protected function build(WP_User $user) : CoreInterface {
		/**
		 * Filters the name of the PHP class used to instantiate `Timber\User` objects.
		 *
		 * The User Class Map receives the default `Timber\User` class and a `WP_User` object. You
		 * should be able to decide which class to use based on that user object.
		 *
		 * @api
		 * @since 2.0.0
		 * @example
		 * ```php
		 * use Administrator;
		 * use Editor;
		 *
		 * add_filter( 'timber/user/classmap', function( $class, \WP_User $user ) {
		 *     if ( in_array( 'editor', $user->roles, true ) ) {
		 *         return Editor::class;
		 *     } elseif ( in_array( 'author', $user->roles, true ) ) {
		 *         return Author::class;
		 *     }
		 *
		 *     return $class;
		 * }, 10, 2 );
		 * ```
		 *
		 * @param string   $class The name of the class. Default `Timber\User`.
		 * @param \WP_User $user  The `WP_User` object that is used as the base for the
		 *                        `Timber\User` object.
		 */
		$class = apply_filters( 'timber/user/classmap', User::class, $user );

		return $class::build($user);
	}

	protected function is_numeric_array($arr) {
		if ( ! is_array($arr) ) {
			return false;
		}
		foreach (array_keys($arr) as $k) {
			if ( ! is_int($k) ) return false;
		}
		return true;
	}
}
