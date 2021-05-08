<?php

namespace Timber\Factory;

use Timber\CoreInterface;
use Timber\Term;

use WP_Term;
use WP_Term_Query;

/**
 * Internal API class for instantiating Terms
 */
class TermFactory {
	public function from($params) {
		if (is_int($params) || is_string($params) && is_numeric($params)) {
			return $this->from_id((int) $params);
		}

		if (is_string($params)) {
			return $this->from_taxonomy_names([$params]);
		}

		if ($params instanceof WP_Term_Query) {
			return $this->from_wp_term_query($params);
		}

		if (is_object($params)) {
			return $this->from_term_object($params);
		}

		if ($this->is_numeric_array($params)) {
			if ($this->is_array_of_strings($params)) {
				return $this->from_taxonomy_names($params);
			}

			return array_map([$this, 'from'], $params);
		}

		if (is_array($params)) {
			return $this->from_wp_term_query(new WP_Term_Query(
				$this->filter_query_params($params)
			));
		}

		return false;
	}

	protected function from_id(int $id) {
		$wp_term = get_term($id);

		if (!$wp_term) {
			return false;
		}

		return $this->build($wp_term);
	}

	protected function from_wp_term_query(WP_Term_Query $query) : Iterable {
		return array_map([$this, 'build'], $query->get_terms());
	}

	protected function from_term_object(object $obj) : CoreInterface {
		if ($obj instanceof CoreInterface) {
			// We already have a Timber Core object of some kind
			return $obj;
		}

		if ($obj instanceof WP_Term) {
			return $this->build($obj);
		}

		throw new \InvalidArgumentException(sprintf(
			'Expected an instance of Timber\CoreInterface or WP_Term, got %s',
			get_class($obj)
		));
	}

	protected function from_taxonomy_names(array $names) {
		return $this->from_wp_term_query(new WP_Term_Query(
			$this->filter_query_params([
				'taxonomy' => $names,
			])
		));
	}

	protected function get_term_class(WP_Term $term) : string {
		// Get the user-configured Class Map
		$map = apply_filters( 'timber/term/classmap', [
			'post_tag' => Term::class,
			'category' => Term::class,
		]);

		$class = $map[$term->taxonomy] ?? null;

		if (is_callable($class)) {
			$class = $class($term);
		}

    // If we don't have a term class by now, fallback on the default class
		return $class ?? Term::class;
	}

	protected function build(WP_Term $term) : CoreInterface {
		$class = $this->get_term_class($term);

		return $class::build($term);
	}

	protected function correct_tax_key(array $params) {
		$corrections = [
			'taxonomies' => 'taxonomy',
			'taxs'       => 'taxonomy',
			'tax'        => 'taxonomy',
		];

		foreach ($corrections as $mistake => $correction) {
			if (isset($params[$mistake])) {
				$params[$correction] = $params[$mistake];
			}
		}

		return $params;
	}

	protected function correct_taxonomies($tax) : array {
		$taxonomies = is_array($tax) ? $tax : [$tax];

		$corrections = [
			'categories' => 'category',
			'tags'       => 'post_tag',
			'tag'        => 'post_tag',
		];

		return array_map(function($taxonomy) use($corrections) {
			return $corrections[$taxonomy] ?? $taxonomy;
		}, $taxonomies);
	}

	protected function filter_query_params(array $params) {
		$params = $this->correct_tax_key($params);

		if (isset($params['taxonomy'])) {
			$params['taxonomy'] = $this->correct_taxonomies($params['taxonomy']);
		}

		$include = $params['term_id'] ?? null;
		if ($include) {
			$params['include'] = is_array($include) ? $include : [$include];
		}

		return $params;
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

	protected function is_array_of_strings($arr) {
		if ( ! is_array($arr) ) {
			return false;
		}
		foreach ($arr as $v) {
			if ( ! is_string($v) ) return false;
		}
		return true;
	}
}
