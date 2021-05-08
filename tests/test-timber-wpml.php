<?php

/**
 * Mocked function for testing menus in WPML
 * @group menus-api
 */
function wpml_object_id_filter( $element_id, $element_type = 'post', $return_original_if_missing = false, $language_code = null ) {
	$locations = get_nav_menu_locations();
	if (isset($locations['extra-menu'])) {
		return $locations['extra-menu'];
	}
	return $element_id;
}

class TestTimberWPML extends Timber_UnitTestCase {

	function testWPMLMenu() {
		$items = array();
		$items[] = (object) array('type' => 'link', 'link' => '/');
		$items[] = (object) array('type' => 'link', 'link' => '/foo');
		$items[] = (object) array('type' => 'link', 'link' => '/bar/');

		TestTimberMenu::buildMenu('Froggy', $items);

		$built_menu = TestTimberMenu::buildMenu('Ziggy', $items);
		$built_menu_id = $built_menu['term_id'];

		TestTimberMenu::buildMenu('Zappy', $items);
		$theme = new Timber\Theme();
		$data = array('nav_menu_locations' => array('header-menu' => 0, 'extra-menu' => $built_menu_id, 'bonus' => 0));
		update_option('theme_mods_'.$theme->slug, $data);
		register_nav_menus(
		    array(
		    	'header-menu' => 'Header Menu',
				'extra-menu' => 'Extra Menu',
				'bonus' => 'The Bonus'
		    )
		);
		$menu = Timber::get_menu('extra-menu');
		$this->assertEquals('Ziggy', $menu->name);
	}

}
