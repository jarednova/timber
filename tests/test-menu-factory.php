<?php

use Timber\Menu;
use Timber\Factory\MenuFactory;

class MyMenu extends Menu {}

/**
 * @group factory
 * @group menus-api
 */
class TestMenuFactory extends Timber_UnitTestCase {
	public function testMenuFromTermId() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);

		$factory = new MenuFactory();

		$this->assertInstanceOf(Menu::class, $factory->from($id));
	}

	public function testGetMenuFromInvalidId() {
		$factory = new MenuFactory();

		$this->assertFalse( $factory->from(9999999) );
	}

	public function testGetMenuFromNavMenuTerms() {
		$this->create_menu_from_posts([
			[
				'post_title' => 'Home',
				'post_status' => 'publish',
				'post_name' => 'home',
				'post_type' => 'page',
				'menu_order' => 1,
			],
		]);

		$factory = new MenuFactory();

		$this->assertInstanceOf(Menu::class, $factory->from(0));
	}

	public function testGetMenuFromIdString() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);

		$factory = new MenuFactory();

		$this->assertInstanceOf(Menu::class, $factory->from("$id"));
	}

	public function testGetMenuFromName() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);

		$factory = new MenuFactory();

		$this->assertInstanceOf(Menu::class, $factory->from('Main Menu'));
	}

	public function testGetMenuFromSlug() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);

		$factory = new MenuFactory();

		$this->assertInstanceOf(Menu::class, $factory->from('main-menu'));
	}

	public function testGetMenuFromLocation() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);

		// Set up our new custom menu location.
		register_nav_menu('custom', 'Custom nav location');
		$locations = get_theme_mod('nav_menu_locations');
		$locations['custom'] = $id;
		set_theme_mod('nav_menu_locations', $locations);

		$factory = new MenuFactory();

		$this->assertInstanceOf(Menu::class, $factory->from('custom'));
	}

	public function testFromTimberMenuObject() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);

		$factory = new MenuFactory();
		$term    = $factory->from($id);

		$this->assertInstanceOf(Menu::class, $factory->from($term));
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testFromTimberMenuObjectGarbageInGarbageOut() {
		$factory = new MenuFactory();
		$this->assertFalse($factory->from(new stdClass()));
	}

	public function testFromTimberMenuGarbageInGarbageOut() {
		$factory = new MenuFactory();
		$this->assertFalse($factory->from(null));
	}

	public function testFromWpTermObject() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);


		$factory = new MenuFactory();
		$term    = get_term($id);

		$this->assertInstanceOf(Menu::class, $factory->from($term));
	}

	public function testFromWithOverride() {
		$id = $this->factory->term->create([
			'name'     => 'Main Menu',
			'taxonomy' => 'nav_menu',
		]);

		$factory = new MenuFactory();

		$this->add_filter_temporarily('timber/menu/classmap', function() {
			return MyMenu::class;
		});

		$this->assertInstanceOf(MyMenu::class, $factory->from($id));
	}
}
