<?php

use Timber\Factory\MenuItemFactory;
use Timber\Menu;
use Timber\MenuItem;
use Timber\Timber;

class MyMenuItem extends MenuItem {}

/**
 * @group factory
 * @group menus-api
 */
class TestMenuItemFactory extends Timber_UnitTestCase {
	public function testMenuFromId() {
		// Destructure the result into the menu WP_Term instance
		// and the *first* item_id
		[
			'term' => $menu_term,
			'item_ids' => [$item_id],
		] = $this->create_menu_from_posts([
			[
				'post_title' => 'Home',
				'post_status' => 'publish',
				'post_name' => 'home',
				'post_type' => 'page',
				'menu_order' => 1,
			]
		]);

		$menu = Timber::get_menu($menu_term['term_id']);
		$factory = new MenuItemFactory();

		$this->assertInstanceOf(MenuItem::class, $factory->from($item_id, $menu));
	}

	public function testMenuGarbageInGarbageOut() {
		[
			'term' => $menu_term,
			'item_ids' => [$item_id],
		] = $this->create_menu_from_posts([
			[
				'post_title' => 'Home',
				'post_status' => 'publish',
				'post_name' => 'home',
				'post_type' => 'page',
				'menu_order' => 1,
			]
		]);

		$menu = Timber::get_menu($menu_term['term_id']);
		$factory = new MenuItemFactory();
		$this->assertFalse( $factory->from(null, $menu) );

		$this->assertFalse( $factory->from(23442, $menu) );
	}

	public function testMenuFromPost() {
		// Destructure the result into the menu WP_Term instance
		// and the *first* item_id
		[
			'term' => $menu_term,
			'item_ids' => [$item_id],
		] = $this->create_menu_from_posts([
			[
				'post_title' => 'Home',
				'post_status' => 'publish',
				'post_name' => 'home',
				'post_type' => 'page',
				'menu_order' => 1,
			]
		]);

		$menu = Timber::get_menu($menu_term['term_id']);
		$factory = new MenuItemFactory();

		$post = get_post($item_id);

		$this->assertInstanceOf(MenuItem::class, $factory->from($post, $menu));
	}

	public function testMenuItemClassmap() {
		// Destructure the result into the menu WP_Term instance
		// and the item_ids
		[
			'term' => $menu_term,
			'item_ids' => [$one, $two, $three],
		] = $this->create_menu_from_posts([
			[
				'post_title' => 'Page One',
				'post_status' => 'publish',
				'post_name' => 'page-one',
				'post_type' => 'page',
				'menu_order' => 1,
			],
			[
				'post_title' => 'Page Two',
				'post_status' => 'publish',
				'post_name' => 'page-two',
				'post_type' => 'page',
				'menu_order' => 2,
			],
			[
				'post_title' => 'Page Three',
				'post_status' => 'publish',
				'post_name' => 'page-three',
				'post_type' => 'page',
				'menu_order' => 3,
			],
		]);

		$menu = Timber::get_menu($menu_term['term_id']);
		$factory = new MenuItemFactory();

		$this->add_filter_temporarily('timber/menuitem/classmap', function($class, WP_Post $item, Menu $menu) use ($two) {
			if ($item->ID === $two) {
				return MyMenuItem::class;
			}

			return $class;
		}, 10, 3);

		$this->assertInstanceOf(MenuItem::class,   $factory->from($one, $menu));
		$this->assertInstanceOf(MyMenuItem::class, $factory->from($two, $menu));
		$this->assertInstanceOf(MenuItem::class,   $factory->from($three, $menu));
	}
}