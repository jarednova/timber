<?php

class CustomMenuItemClass extends Timber\MenuItem {}

/**
 * @group menus-api
 */
class TestTimberMenu extends Timber_UnitTestCase {

	const MENU_NAME = 'Menu One';

	public static function _createTestMenu() {
		$menu_term = wp_insert_term( self::MENU_NAME, 'nav_menu' );
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

	function testBlankMenu() {
		self::setPermalinkStructure();
		self::_createTestMenu();
		$menu = Timber::get_menu();
		$nav_menu = wp_nav_menu( array( 'echo' => false ) );
		$this->assertGreaterThanOrEqual( 3, count( $menu->get_items() ) );
		$items = $menu->get_items();
		$item = $items[0];
		$this->assertEquals( 'home', $item->slug() );
		$this->assertFalse( $item->is_external() );
		$struc = get_option( 'permalink_structure' );
		$this->assertEquals( 'http://example.org/home/', $item->link() );
		$this->assertEquals( '/home/', $item->path() );
	}


	function testTrailingSlashesOrNot() {
		self::setPermalinkStructure();
		$items = array();
		$items[] = (object) array('type' => 'link', 'link' => '/');
		$items[] = (object) array('type' => 'link', 'link' => '/foo');
		$items[] = (object) array('type' => 'link', 'link' => '/bar/');
		$menu = $this->buildMenu('Blanky', $items);
		$menu = Timber::get_menu($menu['term_id']);
		$items = $menu->get_items();
		$this->assertEquals('/', $items[0]->path());
		$this->assertEquals('/foo', $items[1]->path());
		$this->assertEquals('/bar/', $items[2]->path());
	}

	/**
	 * @group menuThumbnails
	 */
	function testNavMenuThumbnailsWithInitializedMenu() {
		add_theme_support( 'thumbnails' );
		self::setPermalinkStructure();

		$menu_term = self::_createTestMenu();
		$menu = Timber::get_menu( $menu_term['term_id'] );
		$menu_items = $menu->items;

		// Add attachment to post
		$pid = $menu->items[0]->object_id;
		$iid = TestTimberImage::get_attachment( $pid );
		add_post_meta( $pid, '_thumbnail_id', $iid, true );

		// Lets confirm this post has a thumbnail on it!
		$post = Timber::get_post($pid);
		$this->assertEquals('http://example.org/wp-content/uploads/' . date( 'Y/m' ) . '/arch.jpg', $post->thumbnail());

		$nav_menu = Timber::get_menu( $menu_term['term_id'] );

		$str    = '{{ menu.items[0].ID }} - {{ menu.items[0].master_object.thumbnail.src }}';
		$result = Timber::compile_string( $str, array( 'menu' => $nav_menu ) );
		$this->assertEquals( $menu_items[0]->ID . ' - http://example.org/wp-content/uploads/' . date( 'Y/m' ) . '/arch.jpg', $result );
	}

	function testImportClasses() {
		$menu = self::_createSimpleMenu('Main Tester');
		$menu = Timber::get_menu('Main Tester');
		$items = $menu->get_items();
		$item = $items[0];
		$array = array('classes' => array('menu-test-class'));
		$item->import_classes($array);
		$this->assertContains('menu-test-class', $item->classes);
	}

	/**
	 * Test the menu with applied filter in wp_nav_menu_objects
	 */
	function testNavMenuFilters() {
		self::_createTestMenu();

		add_filter( 'wp_nav_menu_objects', function( $menu_items ) {
			$menu_items[4]->current = true;
			$menu_items[4]->classes = array_merge( (array)$menu_items[4]->classes, array( 'current-menu-item' ) );
			return $menu_items;
		}, 2 );

		$arguments = array(
			'depth' => 1,
		);
		$menu = Timber::get_menu('Menu One', $arguments);
		$menu_items = $menu->get_items();
		$this->assertTrue( $menu_items[3]->current );
		$this->assertContains( 'current-menu-item', $menu_items[3]->classes );
	}

	function testMenuOptions () {
		$menu_arr = self::_createTestMenu();

		// With no options set.
		$menu = Timber::get_menu($menu_arr['term_id']);
		$this->assertInternalType("int", $menu->depth);
		$this->assertEquals( 0, $menu->depth );
		$this->assertInternalType("array", $menu->raw_options);
		$this->assertInternalType('array', $menu->options);
		$this->assertEquals(array( 'depth' => 0 ), $menu->options);

		// With Valid options set.
		$arguments = array(
			'depth' => 1,
		);
		$menu = Timber::get_menu('Menu One', $arguments);
		$this->assertInternalType("int", $menu->depth);
		$this->assertEquals( 1, $menu->depth );
		$this->assertInternalType("array", $menu->raw_options);
		$this->assertEquals( $arguments, $menu->raw_options );
		$this->assertInternalType('array', $menu->options);
		$this->assertEquals(array( 'depth' => 1 ), $menu->options);

		// With invalid option set.
		$arguments = array(
			'depth' => 'boogie',
		);
		$menu = Timber::get_menu('Menu One', $arguments);
		$this->assertInternalType("int", $menu->depth);
		$this->assertEquals( 0, $menu->depth );
	}

	function testMenuOptions_Depth() {
		self::_createTestMenu();
		$arguments = array(
			'depth' => 1,
		);
		$menu = Timber::get_menu('Menu One', $arguments);

		// Confirm that none of them have "children" set.
		$items = $menu->get_items();
		foreach ($items as $item) {
			$this->assertEquals(null, $item->children);
		}

		// Confirm two levels deep
		$arguments = array(
			'depth' => 2,
		);
		$menu = Timber::get_menu('Menu One', $arguments);
		foreach ($items as $item) {
			if ($item->children) {
				foreach ($item->children as $child) {
					$this->assertEquals(null, $child->children);
				}
			}
		}
	}

	function testMenuItemLink() {
		self::setPermalinkStructure();
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$nav_menu = wp_nav_menu( array( 'echo' => false ) );
		$this->assertGreaterThanOrEqual( 3, count( $menu->get_items() ) );
		$items = $menu->get_items();
		$item = $items[1];
		$struc = get_option( 'permalink_structure' );
		$this->assertEquals( 'http://upstatement.com', $item->link() );
		$this->assertEquals( 'http://upstatement.com', $item->url );
		$this->assertTrue( $item->is_external() );
	}

	function testWPMLMenu() {
		self::setPermalinkStructure();
		self::_createTestMenu();
		$menu = Timber::get_menu();
		$nav_menu = wp_nav_menu( array( 'echo' => false ) );
		$this->assertGreaterThanOrEqual( 3, count( $menu->get_items() ) );
		$items = $menu->get_items();
		$item = $items[0];
		$this->assertEquals( 'home', $item->slug() );
		$this->assertFalse( $item->is_external() );
		$this->assertEquals( 'http://example.org/home/', $item->link() );
		$this->assertEquals( '/home/', $item->path() );
	}

	function testMenuOptionsInNavMenuCssClassFilter() {
		$term    = self::_createTestMenu();
		$menu_id = $term['term_id'];

		$this->registerNavMenus( array(
			'secondary' => $menu_id,
		) );

		$filter = function( $classes, $item, $args ) {
			$this->assertEquals( 3, $item->menu->options['depth'] );
			$this->assertEquals( 3, $args->depth );

		    return $classes;
		};

		add_filter( 'nav_menu_css_class', $filter, 10, 3 );

		Timber::get_menu( $menu_id, [
			'depth' => 3,
		] );

		remove_filter( 'nav_menu_css_class', $filter );
	}

	function testMenuItemIsTargetBlank() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$items = $menu->get_items();

		// Menu item without _menu_item_target set
		$item = $items[0];
		$this->assertFalse( $item->is_target_blank() );

		// Menu item with _menu_item_target set to '_blank'
		$item = $items[1];
		$this->assertTrue( $item->is_target_blank() );

		// Menu item with _menu_item_target set to ''
		$item = $items[2];
		$this->assertFalse( $item->is_target_blank() );
	}

	function testMenuTwigWithClasses() {
		self::setPermalinkStructure();
		self::_createTestMenu();
		$this->go_to( home_url( '/home' ) );
		$context = Timber::context();
		$context['menu'] = Timber::get_menu();
		$str = Timber::compile( 'assets/menu-classes.twig', $context );
		$str = trim( $str );
		$this->assertContains( 'current_page_item', $str );
		$this->assertContains( 'current-menu-item', $str );
		$this->assertContains( 'menu-item-object-page', $str );
		$this->assertNotContains( 'foobar', $str );
  }

	function testCustomArchivePage() {
		self::setPermalinkStructure();
		add_filter( 'nav_menu_css_class', function( $classes, $menu_item ) {
				if ( trailingslashit( $menu_item->link() ) == trailingslashit( 'http://example.org/gallery' ) ) {
					$classes[] = 'current-page-item';
				}
				return $classes;
			}, 10, 2 );
		global $wpdb;
		register_post_type( 'gallery',
			array(
				'labels' => array(
					'name' => __( 'Gallery' ),
					'singular_name' => __( 'Gallery' )
				),
				'taxonomies' => array( 'post_tag' ),
				'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array( 'slug' => 'gallery' ),
			)
		);
		$menu = self::_createTestMenu();
		$menu_item_id = wp_insert_post( array(
				'post_title' => 'Gallery',
				'post_name' => 'gallery',
				'post_status' => 'publish',
				'post_type' => 'nav_menu_item',
				'menu_order' => -100,
			) );
		update_post_meta( $menu_item_id, '_menu_item_type', 'post_type_archive' );
		update_post_meta( $menu_item_id, '_menu_item_object', 'gallery' );
		update_post_meta( $menu_item_id, '_menu_item_menu_item_parent', 0 );
		update_post_meta( $menu_item_id, '_menu_item_object_id', 0 );
		update_post_meta( $menu_item_id, '_menu_item_url', '' );
		$mid = $menu['term_id'];
		$query = "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES ($menu_item_id, $mid, 0);";

		$wpdb->query( $query );
		$this->go_to( home_url( '/gallery' ) );
		$menu = Timber::get_menu();
		$this->assertContains( 'current-page-item', $menu->items[0]->classes );
	}

	function testMenuItemTarget() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$items = $menu->get_items();

		// Menu item without _menu_item_target set
		$item = $items[0];
		$this->assertEquals( '_self', $item->target() );

		// Menu item with _menu_item_target set to '_blank'
		$item = $items[1];
		$this->assertEquals( '_blank', $item->target() );

		// Menu item with _menu_item_target set to ''
		$item = $items[2];
		$this->assertEquals( '_self', $item->target() );
	}

	function testMenuItemMetaAlt() {
		$menu_info = $this->_createSimpleMenu();
		$menu = Timber::get_menu($menu_info['term_id']);
		$item = $menu->items[0];
		$this->assertEquals('molasses', $item->meta('flood'));
	}

	function testMenuItemMetaProperty() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$items = $menu->get_items();
		$item = $items[0];
		$this->assertEquals( 'funke', $item->tobias );
		// There are dozens of us! DOZENS!!
		$this->assertGreaterThan( 0, $item->id );
	}

	function testMenuItemMeta() {
		$menu_info = $this->_createSimpleMenu();
		$menu = Timber::get_menu($menu_info['term_id']);
		$item = $menu->items[0];
		$this->assertEquals('molasses', $item->meta('flood'));
	}

	function testMenuMetaSet() {
		$menu_arr = self::_createSimpleMenu('Tester');
		$menu = Timber::get_menu($menu_arr['term_id']);
		$items = $menu->get_items();
		$item = $items[0];
		$item->foo = 'bar';
		update_post_meta( $item->ID, 'ziggy', 'stardust' );
		$this->assertNotEquals( $item->ID, $item->master_object->ID );
		$this->assertEquals( 'bar', $item->foo );
		$this->assertEquals( 'bar', $item->meta('foo') );
		$this->assertEquals( 'stardust', $item->meta('ziggy') );
		$this->assertNull( $item->meta('asdfafds') );
	}

	function testMenuMeta() {
		$term = self::_createTestMenu();
		$menu_id = $term['term_id'];
		add_term_meta($menu_id, 'nationality', 'Canadian');
		$menu = Timber::get_menu($menu_id);
		$string = Timber::compile_string('{{menu.meta("nationality")}}', array('menu' => $menu));
		$this->assertEquals('Canadian', $string);
	}

	function testMenuItemWithHash() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$items = $menu->get_items();
		$item = $items[3];
		$this->assertEquals( '#people', $item->link() );
		$item = $items[4];
		$this->assertEquals( 'http://example.org/#people', $item->link() );
		$this->assertEquals( '/#people', $item->path() );
	}

	function testMenuHome() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$items = $menu->get_items();
		$item = $items[2];
		$this->assertEquals( '/', $item->link() );
		$this->assertEquals( '/', $item->path() );

		$item = $items[5];
		$this->assertEquals( 'http://example.org', $item->link() );
		//I'm unsure what the expected behavior should be here, so commenting-out for now.
		//$this->assertEquals('/', $item->path() );
	}

	function testMenuLevels() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$parent = $menu->items[0];
		$this->assertEquals(0, $parent->level);
		$child = $parent->children[0];
		$this->assertEquals(1, $child->level);
		$olderGrandchild = $child->children[0];
		$this->assertEquals('Grandchild Page', $olderGrandchild->title());
		$this->assertEquals(2, $olderGrandchild->level);
		$youngerGrandchild = $child->children[1];
		$this->assertEquals('Other Grandchild Page', $youngerGrandchild->title());
		$this->assertEquals(2, $youngerGrandchild->level);
	}

	function testMenuLevelsChildren() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$parent = $menu->items[0];
		$this->assertEquals(0, $parent->level);
		$children = $parent->children();
		$this->assertEquals(1, count($children));
		$this->assertEquals('Child Page', $children[0]->title());
	}

	function testMenuName() {
		$menu_arr = self::_createTestMenu();
		$menu = Timber::get_menu($menu_arr['term_id']);
		$str = Timber::compile_string('{{menu.items[0].title}}', array('menu' => $menu));
		$this->assertEquals('Home', $str);
		$str = Timber::compile_string('{{menu.items[0]}}', array('menu' => $menu));
		$this->assertEquals('Home', $str);
	}

	function testMenuLocations() {
		$items = array();
		$items[] = (object) array('type' => 'link', 'link' => '/');
		$items[] = (object) array('type' => 'link', 'link' => '/foo');
		$items[] = (object) array('type' => 'link', 'link' => '/bar/');

		$this->buildMenu('Froggy', $items);

		$built_menu = $this->buildMenu('Ziggy', $items);
		$built_menu_id = $built_menu['term_id'];

		$this->buildMenu('Zappy', $items);

		$this->registerNavMenus( array(
			'header-menu' => 0,
			'extra-menu'  => $built_menu_id,
			'bonus'       => 0,
		) );

		$menu = Timber::get_menu('extra-menu');
		$this->assertEquals('Ziggy', $menu->name);
	}

	function testConstructMenuByName() {
		$items = array();
		$items[] = (object) array('type' => 'link', 'link' => '/');
		$items[] = (object) array('type' => 'link', 'link' => '/foo');
		$items[] = (object) array('type' => 'link', 'link' => '/bar/');

		$this->buildMenu('Fancy Suit', $items);

		$menu = Timber::get_menu('Fancy Suit');
		$this->assertEquals( 3, count($menu->get_items()) );
	}

	function testConstructMenuBySlug() {
		$items = array();
		$items[] = (object) array('type' => 'link', 'link' => '/');
		$items[] = (object) array('type' => 'link', 'link' => '/foo');
		$items[] = (object) array('type' => 'link', 'link' => '/bar/');

		$this->buildMenu('Jolly Jeepers', $items);

		$menu = Timber::get_menu('jolly-jeepers');
		$this->assertEquals( 3, count($menu->get_items()) );
	}

  function testGetCurrentItem() {
    $items = array();
    $items[] = (object) array('type' => 'link', 'link' => '/');
    $items[] = (object) array('type' => 'link', 'link' => '/zazzy');
    $items[] = (object) array('type' => 'link', 'link' => '/stuffy');

    $this->buildMenu('The Zazziest Menu', $items);

    $menu = Timber::get_menu('The Zazziest Menu');

    // force a specific MenuItem to be the current one,
    // and put it on the Zazz Train to Zazzville
    $menu->items[0]->current_item_ancestor = true;
    $menu->items[1]->current = true;

    $current = $menu->current_item();
    $this->assertEquals( '/zazzy', $current->link() );
  }

  function testGetCurrentItemWithAncestor() {
    $items = array();
    $items[] = (object) array('type' => 'link', 'link' => '/');
    $items[] = (object) array('type' => 'link', 'link' => '/grandpa');
    $items[] = (object) array('type' => 'link', 'link' => '/joe-shmoe');

    $this->buildMenu('Ancestry.com Main Menu', $items);

    $menu = Timber::get_menu('Ancestry.com Main Menu');

    // force a MenuItem of olde to be the current one,
    // and listen reverently to its stories
    $menu->items[1]->current_item_ancestor = true;

    $current = $menu->current_item();
    $this->assertEquals( '/grandpa', $current->link() );
  }

  function testGetCurrentItemWithComplexAncestry() {
    $menu_arr = self::_createTestMenu();
    $menu = Timber::get_menu($menu_arr['term_id']);

    // pick a grandchild to inherit the great responsibility of current affairs
    $parent = $menu->items[0];
    $parent->current_item_ancestor = true;

    $child = $parent->children[0];
    $child->current_item_ancestor = true;

    $grandchild = $child->children[1];
    $grandchild->current = true;

    $current = $menu->current_item();
    $this->assertEquals( $grandchild->link(), $current->link() );
  }

  function testGetCurrentItemAntiClimactic() {
    $menu_arr = self::_createTestMenu();
    $menu = Timber::get_menu($menu_arr['term_id']);

    // nothing marked as current
    // womp womp
    $this->assertFalse($menu->current_item());
  }

  function testGetCurrentItemWithDepth() {
    $menu_arr = self::_createTestMenu();
    $menu = Timber::get_menu($menu_arr['term_id']);

    // pick a grandchild to inherit the great responsibility of current affairs
    $parent = $menu->items[0];
    $parent->current_item_ancestor = true;

    // although grandchild is current, we expect this one because of $depth
    $child = $parent->children[0];
    $child->current_item_ancestor = true;

    // mark grandchild as current, so when we get child back,
    // we can reason that the traversal was depth-limited
    $grandchild = $child->children[1];
    $grandchild->current = true;

    $current = $menu->current_item(2);
    $this->assertEquals( $child->link(), $current->link() );
  }

  function testGetCurrentItemSequence() {
    // make sure we're not caching current_item too eagerly
    // when calling current_item with $depth
    $menu_arr = self::_createTestMenu();
    $menu = Timber::get_menu($menu_arr['term_id']);

    // we'll expect parent first, but expect grandchild on subsequent calls
    // with no arguments
    $parent = $menu->items[0];
    $parent->current_item_ancestor = true;

    $child = $parent->children[0];
    $child->current = true;

    $this->assertEquals(
      $parent->link(),
      $menu->current_item(1)->link()
    );
    $this->assertEquals(
      $child->link(),
      $menu->current_item()->link()
    );
  }

  function testGetCurrentTopLevelItem() {
    $menu_arr = self::_createTestMenu();
    $menu = Timber::get_menu($menu_arr['term_id']);

    // we want this one
    $parent = $menu->items[0];
    $parent->current_item_ancestor = true;

    // although grandchild is current, we expect this one because of $depth
    $child = $parent->children[0];
    $child->current = true;

    $top = $menu->current_top_level_item();
    $this->assertEquals( $parent->link(), $top->link() );
  }

	function testThemeLocationProperty() {
		$term    = self::_createTestMenu();
		$menu_id = $term['term_id'];

		$this->registerNavMenus( array(
			'secondary' => $menu_id,
		) );

		$menu = Timber::get_menu( $menu_id );

		$this->assertEquals( 'secondary', $menu->theme_location );

		// Test property access from menu item.
		$this->assertEquals( $menu, $menu->items[0]->menu );
		$this->assertEquals( 'secondary', $menu->items[0]->menu->theme_location );
	}

	function testThemeLocationAccessInNavMenuCssClassFilter() {
		$term    = self::_createTestMenu();
		$menu_id = $term['term_id'];

		$this->registerNavMenus( array(
			'secondary' => $menu_id,
		) );

		$filter = function( $classes, $item, $args ) {
		    if ( 'secondary' === $item->menu->theme_location ) {
		        $classes[] = 'test-class';
		    }

		    return $classes;
		};

		add_filter( 'nav_menu_css_class', $filter, 10, 3 );

		$menu = Timber::get_menu( $menu_id );

		foreach ( $menu->items as $item ) {
			$this->assertContains( 'test-class', $item->classes );
		}

		remove_filter( 'nav_menu_css_class', $filter );
	}

	function testCustomMenuItemClass() {
		$term       = self::_createTestMenu();
		$menu_id    = $term['term_id'];
		$menu_items = wp_get_nav_menu_items($menu_id);
		$tmis       = [];

		foreach( $menu_items as $mi ) {
			$tmi = CustomMenuItemClass::build($mi);
			array_push($tmis, $tmi);
		}
		$this->assertEquals($tmis[4]->post_title, 'People');
	}

	function testMenuItemObjectProperty() {
		$term       = self::_createTestMenu();
		$menu_id    = $term['term_id'];

		$this->registerNavMenus( array(
			'secondary' => $menu_id,
		) );

		$menu      = Timber::get_menu($term['term_id']);
		$item      = $menu->items[0];
		$object_id = (int) get_post_meta( $item->ID, '_menu_item_object_id', true );

		$this->assertEquals( $object_id, $item->object_id );
	}

	/*
	 * Make sure we still get back nothing even though we have a fallback present
	 */
	function testMissingMenu() {
		$pg_1 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Foo Page', 'menu_order' => 10 ) );
		$pg_2 = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'Bar Page', 'menu_order' => 1 ) );
		$missing_menu = Timber::get_menu( 14 );
		$this->assertFalse( $missing_menu );
	}

	function testMenuTwig() {
		self::setPermalinkStructure();
		$context = Timber::context();
		$menu_arr = self::_createTestMenu();
		$this->go_to( home_url( '/child-page' ) );
		$context['menu'] = Timber::get_menu($menu_arr['term_id']);
		$str = Timber::compile( 'assets/child-menu.twig', $context );
		$str = preg_replace( '/\s+/', '', $str );
		$str = preg_replace( '/\s+/', '', $str );
		$this->assertStringStartsWith( '<ulclass="navnavbar-nav"><li><ahref="http://example.org/home/"class="has-children">Home</a><ulclass="dropdown-menu"role="menu"><li><ahref="http://example.org/child-page/">ChildPage</a></li></ul><li><ahref="http://upstatement.com"class="no-children">Upstatement</a><li><ahref="/"class="no-children">RootHome</a>', $str );
	}

}
