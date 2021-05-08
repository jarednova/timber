<?php

/**
 * @group menus-api
 */
class TestTimberPagesMenu extends Timber_UnitTestCase {

	public static function _createTestMenu() {
		$menu_term = wp_insert_term( 'Menu One', 'nav_menu' );
		$menu_id = $menu_term['term_id'];
		$menu_items = array();
		$parent_page = wp_insert_post(
			array(
				'post_title' => 'Home',
				'post_status' => 'publish',
				'post_name' => 'home',
				'post_type' => 'page',
				'menu_order' => 1
			)
		);
		$parent_id = wp_insert_post( array(
				'post_title' => '',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item'
			) );
		update_post_meta( $parent_id, '_menu_item_type', 'post_type' );
		update_post_meta( $parent_id, '_menu_item_object', 'page' );
		update_post_meta( $parent_id, '_menu_item_menu_item_parent', 0 );
		update_post_meta( $parent_id, '_menu_item_object_id', $parent_page );
		update_post_meta( $parent_id, '_menu_item_url', '' );
		$menu_items[] = $parent_id;
		$link_id = wp_insert_post(
			array(
				'post_title' => 'Upstatement',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => 2
			)
		);

		$menu_items[] = $link_id;
		update_post_meta( $link_id, '_menu_item_type', 'custom' );
		update_post_meta( $link_id, '_menu_item_object_id', $link_id );
		update_post_meta( $link_id, '_menu_item_url', 'http://upstatement.com' );
		update_post_meta( $link_id, '_menu_item_xfn', '' );
		update_post_meta( $link_id, '_menu_item_menu_item_parent', 0 );
		update_post_meta( $link_id, '_menu_item_target', '_blank' );

		/* make a child page */
		$child_id = wp_insert_post( array(
				'post_title' => 'Child Page',
				'post_status' => 'publish',
				'post_name' => 'child-page',
				'post_type' => 'page',
				'menu_order' => 3,
			) );
		$child_menu_item = wp_insert_post( array(
				'post_title' => '',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
			) );
		update_post_meta( $child_menu_item, '_menu_item_type', 'post_type' );
		update_post_meta( $child_menu_item, '_menu_item_menu_item_parent', $parent_id );
		update_post_meta( $child_menu_item, '_menu_item_object_id', $child_id );
		update_post_meta( $child_menu_item, '_menu_item_object', 'page' );
		update_post_meta( $child_menu_item, '_menu_item_url', '' );
		$post = Timber::get_post( $child_menu_item );
		$menu_items[] = $child_menu_item;

		/* make a grandchild page */
		$grandchild_id = wp_insert_post( array(
				'post_title' => 'Grandchild Page',
				'post_status' => 'publish',
				'post_name' => 'grandchild-page',
				'post_type' => 'page',
			) );
		$grandchild_menu_item = wp_insert_post( array(
				'post_title' => '',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => 100,
			) );
		update_post_meta( $grandchild_menu_item, '_menu_item_type', 'post_type' );
		update_post_meta( $grandchild_menu_item, '_menu_item_menu_item_parent', $child_menu_item );
		update_post_meta( $grandchild_menu_item, '_menu_item_object_id', $grandchild_id );
		update_post_meta( $grandchild_menu_item, '_menu_item_object', 'page' );
		update_post_meta( $grandchild_menu_item, '_menu_item_url', '' );
		$post = Timber::get_post( $grandchild_menu_item );
		$menu_items[] = $grandchild_menu_item;

		/* make another grandchild page */
		$grandchild_id = wp_insert_post( array(
				'post_title' => 'Other Grandchild Page',
				'post_status' => 'publish',
				'post_name' => 'other grandchild-page',
				'post_type' => 'page',
			) );
		$grandchild_menu_item = wp_insert_post( array(
				'post_title' => '',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => 101,
			) );
		update_post_meta( $grandchild_menu_item, '_menu_item_type', 'post_type' );
		update_post_meta( $grandchild_menu_item, '_menu_item_menu_item_parent', $child_menu_item );
		update_post_meta( $grandchild_menu_item, '_menu_item_object_id', $grandchild_id );
		update_post_meta( $grandchild_menu_item, '_menu_item_object', 'page' );
		update_post_meta( $grandchild_menu_item, '_menu_item_url', '' );
		$post = Timber::get_post( $grandchild_menu_item );
		$menu_items[] = $grandchild_menu_item;

		$root_url_link_id = wp_insert_post(
			array(
				'post_title' => 'Root Home',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => 4
			)
		);

		$menu_items[] = $root_url_link_id;
		update_post_meta( $root_url_link_id, '_menu_item_type', 'custom' );
		update_post_meta( $root_url_link_id, '_menu_item_object_id', $root_url_link_id );
		update_post_meta( $root_url_link_id, '_menu_item_url', '/' );
		update_post_meta( $root_url_link_id, '_menu_item_xfn', '' );
		update_post_meta( $root_url_link_id, '_menu_item_menu_item_parent', 0 );

		$link_id = wp_insert_post(
			array(
				'post_title' => 'People',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => 6
			)
		);

		$menu_items[] = $link_id;
		update_post_meta( $link_id, '_menu_item_type', 'custom' );
		update_post_meta( $link_id, '_menu_item_object_id', $link_id );
		update_post_meta( $link_id, '_menu_item_url', '#people' );
		update_post_meta( $link_id, '_menu_item_xfn', '' );
		update_post_meta( $link_id, '_menu_item_menu_item_parent', 0 );

		$link_id = wp_insert_post(
			array(
				'post_title' => 'More People',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => 7
			)
		);

		$menu_items[] = $link_id;
		update_post_meta( $link_id, '_menu_item_type', 'custom' );
		update_post_meta( $link_id, '_menu_item_object_id', $link_id );
		update_post_meta( $link_id, '_menu_item_url', 'http://example.org/#people' );
		update_post_meta( $link_id, '_menu_item_xfn', '' );
		update_post_meta( $link_id, '_menu_item_menu_item_parent', 0 );

		$link_id = wp_insert_post(
			array(
				'post_title' => 'Manual Home',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => 8
			)
		);

		$menu_items[] = $link_id;
		update_post_meta( $link_id, '_menu_item_type', 'custom' );
		update_post_meta( $link_id, '_menu_item_object_id', $link_id );
		update_post_meta( $link_id, '_menu_item_url', 'http://example.org' );
		update_post_meta( $link_id, '_menu_item_xfn', '' );
		update_post_meta( $link_id, '_menu_item_menu_item_parent', 0 );

		self::insertIntoMenu($menu_id, $menu_items);
		return $menu_term;
	}

	public static function buildMenu($name, $items) {
		$menu_term = wp_insert_term( $name, 'nav_menu' );
		$menu_items = array();
		$i = 0;
		foreach($items as $item) {
			if ($item->type == 'link') {
				$pid = wp_insert_post( array(
					'post_title'  => '',
					'post_status' => 'publish',
					'post_type'   => 'nav_menu_item',
					'menu_order'  => $i,
				) );
				update_post_meta( $pid, '_menu_item_type', 'custom' );
				update_post_meta( $pid, '_menu_item_object_id', $pid );
				update_post_meta( $pid, '_menu_item_url', $item->link );
				update_post_meta( $pid, '_menu_item_xfn', '' );
				update_post_meta( $pid, '_menu_item_menu_item_parent', 0 );
				$menu_items[] = $pid;
			}
			$i++;
		}
		self::insertIntoMenu($menu_term['term_id'], $menu_items);
		return $menu_term;
	}

	public function registerNavMenus( $locations ) {
		$theme = new Timber\Theme();

		update_option( 'theme_mods_' . $theme->slug, array(
			'nav_menu_locations' => $locations,
		) );

		register_nav_menus(
		    array(
		    	'header-menu' => 'Header Menu',
				'extra-menu' => 'Extra Menu',
				'bonus' => 'The Bonus'
		    )
		);
	}

	public static function _createSimpleMenu( $name = 'My Menu' ) {
		$menu_term = wp_insert_term( $name, 'nav_menu' );
		$menu_items = array();
		$parent_page = wp_insert_post(
			array(
				'post_title' => 'Home',
				'post_status' => 'publish',
				'post_name' => 'home',
				'post_type' => 'page',
				'menu_order' => 1
			)
		);
		$parent_id = wp_insert_post( array(
				'post_title' => '',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item'
			) );
		update_post_meta( $parent_id, '_menu_item_type', 'post_type' );
		update_post_meta( $parent_id, '_menu_item_object', 'page' );
		update_post_meta( $parent_id, '_menu_item_menu_item_parent', 0 );
		update_post_meta( $parent_id, '_menu_item_object_id', $parent_page );
		update_post_meta( $parent_id, '_menu_item_url', '' );
		update_post_meta( $parent_id, 'flood', 'molasses' );
		$menu_items[] = $parent_id;
		self::insertIntoMenu($menu_term['term_id'], $menu_items);
		return $menu_term;
	}

	static function insertIntoMenu($menu_id, $menu_items) {
		global $wpdb;
		foreach ( $menu_items as $object_id ) {
			$query = "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES ($object_id, $menu_id, 0);";
			$wpdb->query( $query );
			update_post_meta( $object_id, 'tobias', 'funke' );
		}
		$menu_items_count = count( $menu_items );
		$wpdb->query( "UPDATE $wpdb->term_taxonomy SET count = $menu_items_count WHERE taxonomy = 'nav_menu'; " );
	}

	static function setPermalinkStructure( $struc = '/%postname%/' ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( $struc );
		$wp_rewrite->flush_rules();
		update_option( 'permalink_structure', $struc );
		flush_rewrite_rules( true );
  }


	/**
	 * @group menuThumbnails
	 */
	function testMenuWithImage() {
		add_theme_support('thumbnails');
		self::setPermalinkStructure();
		$pid = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Bar Page', 'menu_order' => 1 ) );
		$iid = TestTimberImage::get_attachment($pid);
		add_post_meta( $pid, '_thumbnail_id', $iid, true );
		$post = Timber::get_post($pid);
		$page_menu = Timber::get_pages_menu();
		$str = '{% for item in menu.items %}{{item.master_object.thumbnail.src}}{% endfor %}';
		$result = Timber::compile_string($str, array('menu' => $page_menu));
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpg', $result);
	}

	function testPagesMenu() {
		$pg_1 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Foo Page', 'menu_order' => 10 ) );
		$pg_2 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Bar Page', 'menu_order' => 1 ) );
		$page_menu = Timber::get_pages_menu();
		$this->assertEquals( 2, count( $page_menu->items ) );
		$this->assertEquals( 'Bar Page', $page_menu->items[0]->title() );
		self::_createTestMenu();
		//make sure other menus are still more powerful
		$menu = Timber::get_menu();
		$this->assertGreaterThanOrEqual( 3, count( $menu->get_items() ) );
	}

	function testJSONEncodedMenu() {
		$pg_1 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Foo Page', 'menu_order' => 10 ) );
		$pg_2 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Bar Page', 'menu_order' => 1 ) );
		$page_menu = Timber::get_pages_menu();
		$text = json_encode($page_menu->get_items());
		$this->assertGreaterThan(1, strlen($text));
	}

	function testMenuItemMenuProperty() {
		$pg_1 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Foo Page', 'menu_order' => 10 ) );
		$pg_2 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Bar Page', 'menu_order' => 1 ) );
		$page_menu = Timber::get_pages_menu();
		$items = $page_menu->get_items();
		$menu = $items[0]->menu;
		$this->assertEquals('Timber\Menu', get_class($menu));
	}


	function testPagesMenuWithFalse() {
		$pg_1 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Foo Page', 'menu_order' => 10 ) );
		$pg_2 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Bar Page', 'menu_order' => 1 ) );
		$page_menu = Timber::get_pages_menu();
		$this->assertEquals( 2, count( $page_menu->items ) );
		$this->assertEquals( 'Bar Page', $page_menu->items[0]->title() );
		self::_createTestMenu();
		//make sure other menus are still more powerful
		$menu = Timber::get_menu(false);
		$this->assertGreaterThanOrEqual( 3, count( $menu->get_items() ) );
	}

  function testGetCurrentItemWithEmptyMenu() {
    $menu = Timber::get_pages_menu();

    // ain't nothin there
    $this->assertFalse($menu->current_item());
  }
}