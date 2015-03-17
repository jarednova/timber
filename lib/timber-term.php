<?php

class TimberTerm extends TimberWPObject {

    public $PostClass = 'TimberPost';
    public $TermClass = 'TimberTerm';

    public $object_type = 'term';
    public static $representation = 'term';

    public $_children;
    public $name;
    public $taxonomy;

    /**
     * @param int $tid
     * @param string $tax
     */
    function __construct($tid = null, $tax = '') {
        if ($tid === null) {
            $tid = $this->get_term_from_query();
        }
        if (strlen($tax)) {
            $this->taxonomy = $tax;
        }
        $this->init($tid);
    }

    /**
     * @return string
     */
    function __toString() {
        return $this->name;
    }



    /* Setup
    ===================== */

    /**
     * @return integer
     */
    private function get_term_from_query() {
        global $wp_query;
        $qo = $wp_query->queried_object;
        return $qo->term_id;
    }

    /**
     * @param int $tid
     */
    private function init($tid) {
        $term = $this->get_term($tid);
        if (isset($term->id)) {
            $term->ID = $term->id;
        } else if (isset($term->term_id)) {
            $term->ID = $term->term_id;
        } else if (is_string($tid)) {
            //echo 'bad call using '.$tid;
            //TimberHelper::error_log(debug_backtrace());
        }
        if (isset($term->ID)){
            $term->id = $term->ID;
            $this->import($term);
            if (isset($term->term_id)) {
                $custom = $this->get_term_meta($term->term_id);
                $this->import($custom);
            }
        }
    }

    /**
     * @param int $tid
     * @return array
     */
    private function get_term_meta($tid) {
        $customs = array();
        $customs = apply_filters('timber_term_get_meta', $customs, $tid, $this);
        return $customs;
    }

    /**
     * @param int $tid
     * @return mixed
     */
    private function get_term($tid) {
        if (is_object($tid) || is_array($tid)) {
            return $tid;
        }
        $tid = self::get_tid($tid);

        if (isset($this->taxonomy) && strlen($this->taxonomy)) {
            return get_term($tid, $this->taxonomy);
        } else {
            global $wpdb;
            $query = $wpdb->prepare("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d LIMIT 1", $tid);
            $tax = $wpdb->get_var($query);
            if (isset($tax) && strlen($tax)) {
                return get_term($tid, $tax);
            }
        }
        return null;
    }

    /**
     * @param int $tid
     * @return int
     */
    private function get_tid($tid) {
        global $wpdb;
        if (is_numeric($tid)) {
            return $tid;
        }
        if (gettype($tid) == 'object') {
            $tid = $tid->term_id;
        }
        if (is_numeric($tid)) {
            $query = $wpdb->prepare("SELECT * FROM $wpdb->terms WHERE term_id = %d", $tid);
        } else {
            $query = $wpdb->prepare("SELECT * FROM $wpdb->terms WHERE slug = %s", $tid);
        }
        $result = $wpdb->get_row($query);
        if (isset($result->term_id)) {
            $result->ID = $result->term_id;
            $result->id = $result->term_id;
            return $result->ID;
        }
        return 0;
    }

    /* Public methods
    ===================== */

    /**
     * @return string
     */
    public function get_edit_url() {
        return get_edit_term_link($this->ID, $this->taxonomy);
    }

    /**
     * @param string $field_name
     * @return string
     */
    public function get_meta_field($field_name) {
        if (!isset($this->$field_name)) {
            $field_value = '';
            $field_value = apply_filters('timber_term_get_meta_field', $field_value, $this->ID, $field_name, $this);
            $this->$field_name = $field_value;
        }
        return $this->$field_name;
    }

    /**
     * @return string
     */
    public function get_path() {
        $link = $this->get_link();
        $rel = TimberURLHelper::get_rel_url($link, true);
        return apply_filters('timber_term_path', $rel, $this);
    }

    /**
     * @return string
     */
    public function get_link() {
        $link = get_term_link($this);
        return apply_filters('timber_term_link', $link, $this);
    }

    /**
     * @param int $numberposts
     * @param string $post_type
     * @param string $PostClass
     * @return array|bool|null
     */
    public function get_posts($numberposts = 10, $post_type = 'any', $PostClass = '') {
        if (!strlen($PostClass)) {
            $PostClass = $this->PostClass;
        }
        $default_tax_query = array(array(
            'field' => 'id',
            'terms' => $this->ID,
            'taxonomy' => $this->taxonomy,
        ));
        if (is_string($numberposts) && strstr($numberposts, '=')) {
            $args = $numberposts;
            $new_args = array();
            parse_str($args, $new_args);
            $args = $new_args;
            $args['tax_query'] = $default_tax_query;
            if (!isset($args['post_type'])) {
                $args['post_type'] = 'any';
            }
            if (class_exists($post_type)) {
                $PostClass = $post_type;
            }
        } else if (is_array($numberposts)) {
            //they sent us an array already baked
            $args = $numberposts;
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = $default_tax_query;
            }
            if (class_exists($post_type)) {
                $PostClass = $post_type;
            }
            if (!isset($args['post_type'])) {
                $args['post_type'] = 'any';
            }
        } else {
            $args = array(
                'numberposts' => $numberposts,
                'tax_query' => $default_tax_query,
                'post_type' => $post_type
            );
        }
        return Timber::get_posts($args, $PostClass);
    }

    /**
     * @return array
     */
    public function get_children() {
        if (!isset($this->_children)) {
            $children = get_term_children($this->ID, $this->taxonomy);
            foreach ($children as &$child) {
                $child = new TimberTerm($child);
            }
            $this->_children = $children;
        }
        return $this->_children;
    }

    /**
     *
     *
     * @param string  $key
     * @param mixed   $value
     */
    function update( $key, $value ) {
        $value = apply_filters( 'timber_term_set_meta', $value, $key, $this->ID, $this );
        $this->$key = $value;
    }

    /* Alias
    ====================== */

    /**
     * @return array
     */
    public function children() {
        return $this->get_children();
    }

    /**
     * @return string
     */
    public function edit_link() {
        return $this->get_edit_url();
    }

    /**
     * @return string
     */
    public function get_url() {
        return $this->get_link();
    }

    /**
     * @return string
     */
    public function link() {
        return $this->get_link();
    }

    /**
     * @param string $field_name
     * @return string
     */
    public function meta($field_name) {
        return $this->get_meta_field($field_name);
    }

    /**
     * @return string
     */
    public function path() {
        return $this->get_path();
    }

    /**
     * @param int $numberposts_or_args
     * @param string $post_type_or_class
     * @param string $post_class
     * @return array|bool|null
     */
    public function posts($numberposts_or_args = 10, $post_type_or_class = 'any', $post_class = '') {
        return $this->get_posts($numberposts_or_args, $post_type_or_class, $post_class);
    }

    /**
     * @return string
     */
    public function title() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function url() {
        return $this->get_url();
    }

    /* Deprecated
    ===================== */

    /**
     * @deprecated
     * @param int $i
     * @return string
     */
    function get_page($i) {
        return $this->get_path() . '/page/' . $i;
    }

}
