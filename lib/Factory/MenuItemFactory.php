<?php

namespace Timber\Factory;

use WP_Post;

use Timber\CoreInterface;
use Timber\Menu;
use Timber\MenuItem;
use Timber\Timber;

/**
 * Internal API class for instantiating Menus
 */
class MenuItemFactory {
	public function from($wp_item, Menu $menu) {
		if ($this->is_post($wp_item)) {
			return $this->from_post($wp_item, $menu);
		}

		if (is_numeric($wp_item)) {
			return $this->from_id((int)$wp_item, $menu);
		}

		return false;
	}

	protected function from_post(WP_Post $post, Menu $menu) {
		$item = $this->build(Timber::get_post($post), $menu);
		$item->import_classes($post);

		return $item;
	}

	protected function from_id(int $id, Menu $menu) {
		$post = get_post($id);

		if ($post) {
			$item = $this->build($post, $menu);
			$item->import_classes($post);

			return $item;
		}

		return false;
	}

	protected function is_post($item) : bool {
		return is_object($item) && $item instanceof WP_Post;
	}

	protected function build($item, Menu $menu) : MenuItem {
		$class = apply_filters('timber/menuitem/classmap', MenuItem::class, $item, $menu);

		return $class::build($item, $menu);
	}
}
