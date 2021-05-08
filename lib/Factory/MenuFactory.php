<?php

namespace Timber\Factory;

use Timber\CoreInterface;
use Timber\Menu;

use WP_Term;

/**
 * Internal API class for instantiating Menus
 */
class MenuFactory {
	public function from($params, array $options = []) {
		if ($params === 0) {
			// We know no nav_menu term exists with ID 0,
			// so just look at the existing terms in the database.
			return $this->from_nav_menu_terms($options);
		}

		if (is_numeric($params)) {
			return $this->from_id((int) $params, $options);
		}

		if (is_string($params)) {
			return $this->from_string($params, $options);
		}

		if (is_object($params)) {
			return $this->from_term_object($params, $options);
		}

		// Fall back on the first nav_menu term we find.
		return $this->from_nav_menu_terms($options);
	}

	/**
	 * Query the database for existing nav menu terms and return the first one
	 * as a Timber\Menu object.
	 *
	 * @internal
	 */
	protected function from_nav_menu_terms(array $options) {
		$terms = get_terms('nav_menu', [
			'hide_empty' => true,
		]);

		$id = $terms[0]->term_id ?? 0;

		return $id ? $this->from_id($id, $options) : false;
	}

	/**
	 * Get a Menu by its ID
	 *
	 * @internal
	 */
	protected function from_id($id, $options) {
		// WP Menus are WP_Term objects under the hood.
		$term = wp_get_nav_menu_object($id);

		if (!$term) {
			return false;
		}

		return $this->build($term, $options);
	}

	/**
	 * Get a Menu by its slug or name, or by a nav menu location name, e.g. "primary-menu"
	 *
	 * @internal
	 */
	protected function from_string($ident, $options) {
		$term = get_term_by('slug', $ident, 'nav_menu') ?: get_term_by('name', $ident, 'nav_menu');

		if (!$term) {
			$locations = get_nav_menu_locations();
			if (isset($locations[$ident])) {
				$id   = apply_filters('timber/menu/id_from_location', $locations[$ident]);
				$term = wp_get_nav_menu_object($id);
			}
		}

		if (!$term) {
			return false;
		}

		return $this->build($term, $options);
	}

	protected function from_term_object(object $obj, array $options) : CoreInterface {
		if ($obj instanceof CoreInterface) {
			// We already have a Timber Core object of some kind
			return $obj;
		}

		if ($obj instanceof WP_Term) {
			return $this->build($obj, $options);
		}

		throw new \InvalidArgumentException(sprintf(
			'Expected an instance of Timber\CoreInterface or WP_Term, got %s',
			get_class($obj)
		));
	}

	protected function get_menu_class(WP_Term $term) : string {
		// Get the user-configured Class Map, defaulting to the Menu class
		$class = apply_filters('timber/menu/classmap', Menu::class, $term);

		return $class ?? Menu::class;
	}

	protected function build(WP_Term $term, array $options) : CoreInterface {
		$class = $this->get_menu_class($term);

		return $class::build($term, $options);
	}
}

