<?php

class TimberMenuItem extends TimberCore {

    public $children;
    public $has_child_class = false;

    public $classes = array();
    public $class = '';
    public $post_name;
    public $type;

    public $PostClass = 'TimberPost';

    private $menu_object;
    private $parent_object;

    /**
     *
     *
     * @param array|object $data
     */
    function __construct( $data ) {
        $this->import( $data );
        $this->import_classes( $data );
        if ( isset( $this->name ) ) {
            $this->_name = $this->name;
        }
        $this->name = $this->name();
        $this->add_class( 'menu-item-' . $this->ID );
        $this->menu_object = $data;
    }

    function __toString() {
        return $this->name();
    }

    /**
     *
     *
     * @param string  $class_name
     */
    function add_class( $class_name ) {
        $this->classes[] = $class_name;
        $this->class .= ' ' . $class_name;
    }

    /**
     *
     *
     * @return string
     */
    function name() {
        if ( isset( $this->title ) ) {
            return $this->title;
        }
        if ( isset( $this->_name ) ) {
            return $this->_name;
        }
        return '';
    }

    /**
     *
     *
     * @return string
     */
    function slug() {
        if ( !isset( $this->parent_object ) ) {
            $this->parent_object = $this->get_parent_object();
        }
        if ( isset( $this->parent_object->post_name ) && $this->parent_object->post_name ) {
            return $this->parent_object->post_name;
        }
        return $this->post_name;
    }

    function get_parent_object() {
        if ( isset( $this->_menu_item_object_id ) ) {
            return new $this->PostClass( $this->_menu_item_object_id );
        }
    }

    /**
     *
     *
     * @return string
     */
    function get_link() {
        if ( !isset( $this->url ) || !$this->url ) {
            if ( isset( $this->_menu_item_type ) && $this->_menu_item_type == 'custom' ) {
                $this->url = $this->_menu_item_url;
            } else if ( isset( $this->menu_object ) && method_exists( $this->menu_object, 'get_link' ) ) {
                    $this->url = $this->menu_object->get_link();
                }
        }
        return $this->url;
    }

    /**
     *
     *
     * @return string
     */
    function get_path() {
        return TimberURLHelper::get_rel_url( $this->get_link() );
    }

    /**
     *
     *
     * @param TimberMenuItem $item
     */
    function add_child( $item ) {
        if ( !$this->has_child_class ) {
            $this->add_class( 'menu-item-has-children' );
            $this->has_child_class = true;
        }
        if ( !isset( $this->children ) ) {
            $this->children = array();
        }
        $this->children[] = $item;
    }

    /**
     *
     *
     * @param object  $data
     */
    function import_classes( $data ) {
        $this->classes = array_merge($this->classes, $data->classes);
        $this->classes = array_unique($this->classes);
        $this->classes = apply_filters( 'nav_menu_css_class', $this->classes, $this );
        $this->class = trim( implode( ' ', $this->classes ) );
    }

    /**
     *
     *
     * @return array|bool
     */
    function get_children() {
        if ( isset( $this->children ) ) {
            return $this->children;
        }
        return false;
    }

    /**
     *
     *
     * @return bool
     */
    function is_external() {
        if ( $this->type != 'custom' ) {
            return false;
        }
        return TimberURLHelper::is_external( $this->url );
    }

    /**
     *
     * @param $key string lookup key
     * @return mixed whatever value is stored in the database
     */
    public function meta( $key ) {
        if ( is_object( $this->menu_object ) && method_exists( $this->menu_object, 'meta' ) ) {
            return $this->menu_object->meta( $key );
        }
        if ( isset( $this->$key ) ) {
            return $this->$key;
        }
    }

    /* Aliases */

    /**
     *
     *
     * @return array|bool
     */
    public function children() {
        return $this->get_children();
    }

    /**
     *
     *
     * @return bool
     */
    public function external() {
        return $this->is_external();
    }

    /**
     *
     *
     * @return string a full URL like http://mysite.com/thing/
     */
    public function link() {
        return $this->get_link();
    }

    /**
     *
     * @see get_path()
     * @return string the path of a URL like /foo
     */
    public function path() {
        return $this->get_path();
    }

    /**
     *
     * @see link()
     * @return string a full URL like http://mysite.com/thing/
     */
    public function permalink() {
        return $this->get_link();
    }

    /**
     *
     * @see link()
     * @return string a full URL like http://mysite.com/thing/
     */
    public function get_permalink() {
        return $this->get_link();
    }

    /**
     *
     *
     * @return string the public label like Foo
     */
    public function title() {
        if (isset($this->__title)){
            return $this->__title;
        }
    }

}
