<?php

namespace Timber;

use Timber\Factory\PostFactory;
use Timber\Menu;

/**
 * Class MenuItem
 *
 * @api
 */
class MenuItem extends Core implements CoreInterface, MetaInterface {
	/**
	 * @api
	 * @var array Array of children of a menu item. Empty if there are no child menu items.
	 */
	public $children = array();

	/**
	 * @api
	 * @var bool Whether the menu item has a `menu-item-has-children` CSS class.
	 */
	public $has_child_class = false;

	/**
	 * @api
	 * @var array Array of class names.
	 */
	public $classes = array();
	public $class = '';
	public $level = 0;
	public $post_name;
	public $url;

	/**
	 * Inherited property. Listed here to make it available in the documentation.
	 *
	 * @api
	 * @see _wp_menu_item_classes_by_context()
	 * @var bool Whether the menu item links to the currently displayed page.
	 */
	public $current;

	/**
	 * Inherited property. Listed here to make it available in the documentation.
	 *
	 * @api
	 * @see _wp_menu_item_classes_by_context()
	 * @var bool Whether the menu item refers to the parent item of the currently displayed page.
	 */
	public $current_item_parent;

	/**
	 * Inherited property. Listed here to make it available in the documentation.
	 *
	 * @api
	 * @see _wp_menu_item_classes_by_context()
	 * @var bool Whether the menu item refers to an ancestor (including direct parent) of the
	 *      currently displayed page.
	 */
	public $current_item_ancestor;

	/**
	 * Timber Menu. Previously this was a public property, but converted to a method to avoid
	 * recursion (see #2071).
	 *
	 * @since 1.12.0
	 * @see \Timber\MenuItem::menu()
	 * @var \Timber\Menu The `Timber\Menu` object the menu item is associated with.
	 */
	protected $menu;

	/**
	 * Object ID.
	 *
	 * @api
	 * @since 2.0.0
	 * @var int|null Linked object ID.
	 */
	public $object_id = null;

	protected $_name;
	protected $_menu_item_url;
	protected $menu_object;

	/**
	 * @internal
	 * @param array|object $data The data this MenuItem is wrapping
	 * @param \Timber\Menu $menu The `Timber\Menu` object the menu item is associated with.
	 * @return \Timber\MenuItem a new MenuItem instance
	 */
	public static function build( $data, Menu $menu = null ) : self {
		return new static($data, $menu);
	}

	/**
	 * @internal
	 * @param array|object $data
	 * @param \Timber\Menu $menu The `Timber\Menu` object the menu item is associated with.
	 */
	protected function __construct( $data, $menu = null ) {
		$this->menu = $menu;
		$data       = (object) $data;
		$this->import($data);
		$this->import_classes($data);
		$this->id = $data->ID;
		$this->ID = $data->ID;

		$factory = new PostFactory();
		$this->menu_object = $factory->from($data);

		$this->_name       = $data->name ?? '';
		$this->add_class('menu-item-'.$this->ID);

		$this->object_id = (int) get_post_meta( $this->ID, '_menu_item_object_id', true );
	}

	/**
	 * Add a CSS class the menu item should have.
	 *
	 * @param string $class_name CSS class name to be added.
	 */
	public function add_class( $class_name ) {
		$this->classes[] = $class_name;
		$this->class    .= ' ' . $class_name;
	}

	/**
	 * Get the label for the menu item.
	 *
	 * @api
	 * @return string The label for the menu item.
	 */
	public function name() {
		return $this->title();
	}

	/**
	 * Magic method to get the label for the menu item.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <a href="{{ item.link }}">{{ item }}</a>
	 * ```
	 * @see \Timber\MenuItem::name()
	 * @return string The label for the menu item.
	 */
	public function __toString() {
		return $this->name();
	}

	/**
	 * Get the slug for the menu item.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <ul>
	 *     {% for item in menu.items %}
	 *         <li class="{{ item.slug }}">
	 *             <a href="{{ item.link }}">{{ item.name }}</a>
	 *          </li>
	 *     {% endfor %}
	 * </ul>
	 * ```
	 * @return string The URL-safe slug of the menu item.
	 */
	public function slug() {
		$mo = $this->master_object();
		if ( $mo && $mo->post_name ) {
			return $mo->post_name;
		}
		return $this->post_name;
	}

	/**
	 * Allows dev to access the "master object" (ex: post or page) the menu item represents
	 *
	 * @api
	 * @example
	 * ```twig
	 * <div>
	 *     {% for item in menu.items %}
	 *         <a href="{{ item.link }}"><img src="{{ item.master_object.thumbnail }}" /></a>
	 *     {% endfor %}
	 * </div>
	 * ```
	 * @return mixed Whatever object (Timber\Post, Timber\Term, etc.) the menu item represents.
	 */
	public function master_object() {
		static $factory;
		$factory = $factory ?: new PostFactory();

		if ( $this->object_id ) {
			return $factory->from( $this->object_id );
		}
		if ( $this->menu_object ) {
			return $factory->from( $this->menu_object );
		}
	}

	/**
	 * Add a new `Timber\MenuItem` object as a child of this menu item.
	 *
	 * @api
	 *
	 * @param \Timber\MenuItem $item The menu item to add.
	 */
	public function add_child( $item ) {
		if ( ! $this->has_child_class ) {
			$this->add_class('menu-item-has-children');
			$this->has_child_class = true;
		}
		$this->children[] = $item;
		$item->level      = $this->level + 1;
		if ( count($this->children) ) {
			$this->update_child_levels();
		}
	}

	/**
	 * Update the level data associated with $this.
	 *
	 * @internal
	 * @return bool|null
	 */
	public function update_child_levels() {
		if ( is_array($this->children) ) {
			foreach ( $this->children as $child ) {
				$child->level = $this->level + 1;
				$child->update_child_levels();
			}
			return true;
		}
	}

	/**
	 * Imports the classes to be used in CSS.
	 *
	 * @internal
	 *
	 * @param array|object $data to import.
	 */
	public function import_classes( $data ) {
		if ( is_array($data) ) {
			$data = (object) $data;
		}
		$this->classes = array_merge($this->classes, $data->classes ?? []);
		$this->classes = array_unique($this->classes);

		$options = new \stdClass();
		if ( isset($this->menu()->options) ) {
			// The options need to be an object.
			$options = (object) $this->menu()->options;
		}

		/**
		 * Filters the CSS classes applied to a menu item’s list item.
		 *
		 * @param string[]         $classes An array of the CSS classes that can be applied to the
		 *                                  menu item’s `<li>` element.
		 * @param \Timber\MenuItem $item    The current menu item.
		 * @param \stdClass $args           An object of wp_nav_menu() arguments. In Timber, we
		 *                                  don’t have these arguments because we don’t use a menu
		 *                                  walker. Instead, you get the options that were used to
		 *                                  create the `Timber\Menu` object.
		 * @param int              $depth   Depth of menu item.
		 */
		$this->classes = apply_filters(
			'nav_menu_css_class',
			$this->classes,
			$this,
			$options,
			0
		);

		$this->class = trim(implode(' ', $this->classes));
	}

	/**
	 * Get children of a menu item.
	 *
	 * You can also directly access the children through the `$children` property (`item.children`
	 * in Twig).
	 *
	 * @internal
	 * @deprecated 2.0.0, use `item.children` instead.
	 * @example
	 * ```twig
	 * {% for child in item.get_children %}
	 *     <li class="nav-drop-item">
	 *         <a href="{{ child.link }}">{{ child.title }}</a>
	 *     </li>
	 * {% endfor %}
	 * ```
	 * @return array|bool Array of children of a menu item. Empty if there are no child menu items.
	 */
	public function get_children() {
		Helper::deprecated(
			"{{ item.get_children }}",
			"{{ item.children }}",
			'2.0.0'
		);
		return $this->children();
	}

	/**
	 * Checks to see if the menu item is an external link.
	 *
	 * If your site is `example.org`, then `google.com/whatever` is an external link. This is
	 * helpful when you want to style external links differently or create rules for the target of a
	 * link.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <a href="{{ item.link }}" target="{{ item.is_external ? '_blank' : '_self' }}">
	 * ```
	 *
	 * Or when you only want to add a target attribute if it is really needed:
	 *
	 * ```twig
	 * <a href="{{ item.link }}" {{ item.is_external ? 'target="_blank"' }}>
	 * ```
	 *
	 * In combination with `is_target_blank()`:
	 *
	 * ```twig
	 * <a href="{{ item.link }}" {{ item.is_external or item.is_target_blank ? 'target="_blank"' }}>
	 * ```
	 *
	 * @return bool Whether the link is external or not.
	 */
	public function is_external() {
		if ( $this->type() !== 'custom' ) {
			return false;
		}
		return URLHelper::is_external( $this->link() );
	}

	/**
	 * Checks whether the «Open in new tab» option checked in the menu item options.
	 *
	 * @example
	 * ```twig
	 * <a href="{{ item.link }}" {{ item.is_target_blank ? 'target="_blank"' }}>
	 * ```
	 *
	 * In combination with `is_external()`
	 *
	 * ```twig
	 * <a href="{{ item.link }}" {{ item.is_target_blank or item.is_external ? 'target="_blank"' }}>
	 * ```
	 *
	 * @return bool Whether the menu item has the «Open in new tab» option checked in the menu item
	 *              options.
	 */
	public function is_target_blank() {
		return '_blank' === $this->meta( '_menu_item_target' );
	}

	/**
	 * Gets the target of a menu item according to the «Open in new tab» option in the menu item
	 * options.
	 *
	 * This function return `_blank` when the option to open a menu item in a new tab is checked in
	 * the WordPress backend, and `_self` if the option is not checked. Beware `_self` is the
	 * default value for the target attribute, which means you could leave it out. You can use
	 * `item.is_target_blank` if you want to use a conditional.
	 *
	 * @example
	 * ```twig
	 * <a href="{{ item.link }}" target="{{ item.target }}">
	 * ```
	 *
	 * @return string
	 */
	public function target() {
		$target = $this->meta( '_menu_item_target' );
		if ( !$target ) {
			return '_self';
		}
		return $target;
	}

	/**
	 * Get the type of the menu item.
	 *
	 * Depending on what is the menu item links to. Can be `post_type` for posts, pages and custom
	 * posts, `post_type_archive` for post type archive pages, `taxonomy` for terms or `custom` for
	 * custom links.
	 *
	 * @api
	 * @since 1.0.4
	 * @return string The type of the menu item.
	 */
	public function type() {
		return $this->meta('_menu_item_type');
	}

	/**
	 * Timber Menu.
	 *
	 * @api
	 * @since 1.12.0
	 * @return \Timber\Menu The `Timber\Menu` object the menu item is associated with.
	 */
	public function menu() {
		return $this->menu;
	}


	/**
	 * Get a meta value of the menu item.
	 *
	 * Plugins like Advanced Custom Fields allow you to set custom fields for menu items.
	 * With this method you can retrieve the value of these.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <a class="icon-{{ item.meta('icon') }}" href="{{ item.link }}">{{ item.title }}</a>
	 * ```
	 * @param string $field_name Optional. The field name for which you want to get the value. If
	 *                           no field name is provided, this function will fetch values for all
	 *                           custom fields. Default empty string.
	 * @param array  $args       An array of arguments for getting the meta value. Third-party
	 *                           integrations can use this argument to make their API arguments
	 *                           available in Timber. Default empty.
	 * @return mixed Whatever value is stored in the database. Null if no value could be found.
	 */
	public function meta( $field_name = '', $args = array() ) {
		if ( isset($this->$field_name) ) {
			return $this->$field_name;
		}
		if ( is_object($this->menu_object) && method_exists($this->menu_object, 'meta') ) {
			return $this->menu_object->meta($field_name, $args);
		}
	}

	/**
	 * Gets a menu item’s meta value directly from the database.
	 *
	 * Returns a raw meta value for a menu item that’s saved in the post meta database table. Be
	 * aware that the value can still be filtered by plugins.
	 *
	 * @api
	 * @since 2.0.0
	 * @param string $field_name The field name for which you want to get the value.
	 * @return null|mixed The meta field value. Null if no value could be found.
	 */
	public function raw_meta( $field_name = '' ) {
		if ( is_object( $this->menu_object ) && method_exists( $this->menu_object, 'raw_meta' ) ) {
			return $this->menu_object->raw_meta( $field_name );
		}

		return null;
	}

	/**
	 * Gets a menu item meta value.
	 *
	 * @api
	 * @deprecated 2.0.0, use `{{ item.meta('field_name') }}` instead.
	 * @see \Timber\MenuItem::meta()
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @return mixed The meta field value.
	 */
	public function get_field( $field_name = null ) {
		Helper::deprecated(
			"{{ item.get_field('field_name') }}",
			"{{ item.meta('field_name') }}",
			'2.0.0'
		);
		return $this->meta( $field_name );
	}

	/**
	 * Get the child menu items of a `Timber\MenuItem`.
	 *
	 * @api
	 * @example
	 * ```twig
	 * {% for child in item.children %}
	 *     <li class="nav-drop-item">
	 *         <a href="{{ child.link }}">{{ child.title }}</a>
	 *     </li>
	 * {% endfor %}
	 * ```
	 * @return array|bool Array of children of a menu item. Empty if there are no child menu items.
	 */
	public function children() {
		return $this->children;
	}

	/**
	 * Checks to see if the menu item is an external link.
	 *
	 * @api
	 * @deprecated 2.0.0, use `{{ item.is_external }}`
	 * @see \Timber\MenuItem::is_external()
	 *
	 * @return bool Whether the link is external or not.
	 */
	public function external() {
		Helper::warn( '{{ item.external }} is deprecated. Use {{ item.is_external }} instead.' );
		return $this->is_external();
	}

	/**
	 * Get the full link to a menu item.
	 *
	 * @api
	 * @example
	 * ```twig
	 * {% for item in menu.items %}
	 *     <li><a href="{{ item.link }}">{{ item.title }}</a></li>
	 * {% endfor %}
	 * ```
	 * @return string A full URL, like `http://mysite.com/thing/`.
	 */
	public function link() {
		return $this->url;
	}

	/**
	 * Get the relative path of the menu item’s link.
	 *
	 * @api
	 * @example
	 * ```twig
	 * {% for item in menu.items %}
	 *     <li><a href="{{ item.path }}">{{ item.title }}</a></li>
	 * {% endfor %}
	 * ```
	 * @return string The path of a URL, like `/foo`.
	 */
	public function path() {
		return URLHelper::get_rel_url($this->link());
	}

	/**
	 * Get the public label for the menu item.
	 *
	 * @api
	 * @example
	 * ```twig
	 * {% for item in menu.items %}
	 *     <li><a href="{{ item.link }}">{{ item.title }}</a></li>
	 * {% endfor %}
	 * ```
	 * @return string The public label, like "Foo".
	 */
	public function title() {
		if ( isset($this->__title) ) {
			return $this->__title;
		}
	}

}
