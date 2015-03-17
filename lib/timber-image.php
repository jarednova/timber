<?php

class TimberImage extends TimberPost {

    public $_can_edit;
    public $_dimensions;
    public $abs_url;
    public $PostClass = 'TimberPost';
    public $object_type = 'image';

    public static $representation = 'image';

    public $file_loc;
    public $file;
    public $sizes = array();
    public $post_parent;
    public $caption;
    public $_wp_attached_file;

    /**
     * @param int $iid
     */
    function __construct($iid) {
        $this->init($iid);
    }

    /**
     * @return string
     */
    function __toString() {
        if ($this->get_src()) {
            return $this->get_src();
        }
        return '';
    }

    /**
     * @return mixed
     */
    function get_pathinfo() {
        return pathinfo($this->file);
    }

    /**
     * @param string $dim
     * @return array|int
     */
    function get_dimensions($dim = null) {
        if (isset($this->_dimensions)) {
            return $this->get_dimensions_loaded($dim);
        }
        list($width, $height) = getimagesize($this->file_loc);
        $this->_dimensions = array();
        $this->_dimensions[0] = $width;
        $this->_dimensions[1] = $height;
        return $this->get_dimensions_loaded($dim);
    }

    /**
     * @param string $dim
     * @return array|int
     */
    function get_dimensions_loaded($dim) {
        if ($dim == null) {
            return $this->_dimensions;
        }
        if ($dim == 'w' || $dim == 'width') {
            return $this->_dimensions[0];
        }
        if ($dim == 'h' || $dim == 'height') {
            return $this->_dimensions[1];
        }
        return null;
    }

    /**
     * @return int
     */
    function get_width() {
        return $this->get_dimensions('width');
    }

    /**
     * @return int
     */
    function get_height() {
        return $this->get_dimensions('height');
    }

    /**
     * @param string $size
     * @return bool|string
     */
    function get_src($size = '') {
        if (isset($this->abs_url)) {
            return $this->_maybe_secure_url($this->abs_url);
        }

        if ($size && is_string($size) && isset($this->sizes[$size])) {
            $image = image_downsize($this->ID, $size);
            return $this->_maybe_secure_url(reset($image));
        }

        if (!isset($this->file) && isset($this->_wp_attached_file)) {
            $this->file = $this->_wp_attached_file;
        }

        if (!isset($this->file)) {
            return false;
        }

        $dir = self::wp_upload_dir();
        $base = ($dir["baseurl"]);

        $src = trailingslashit($this->_maybe_secure_url($base)) . $this->file;
        return apply_filters('timber_image_src', $src);
    }

    private static function _maybe_secure_url($url) {
        if (is_ssl() && strpos($url, 'https') !== 0 && strpos($url, 'http') === 0) {
            $url = 'https' . substr($url, strlen('http'));
        }

        return $url;
    }

    public static function wp_upload_dir() {
        static $wp_upload_dir = false;

        if (!$wp_upload_dir) {
            $wp_upload_dir = wp_upload_dir();
        }

        return $wp_upload_dir;
    }

    /**
     * @return string
     */
    function get_path() {
        if (strlen($this->abs_url)) {
            return $this->abs_url;
        }
        return get_permalink($this->ID);
    }

    /**
     * @return bool|TimberImage
     */
    function get_parent() {
        if (!$this->post_parent) {
            return false;
        }
        return new $this->PostClass($this->post_parent);
    }

    function get_alt() {
        $alt = trim(strip_tags(get_post_meta($this->ID, '_wp_attachment_image_alt', true)));
        return $alt;
    }


    /**
     * @param int $iid
     */
    function init( $iid = false ) {
        if ( !is_numeric( $iid ) && is_string( $iid ) ) {
            if (strstr($iid, '://')) {
                $this->init_with_url($iid);
                return;
            }
            if ( strstr($iid, ABSPATH) ) {
                $this->init_with_file_path($iid);
                return;
            }
            if (strstr(strtolower($iid), '.jpg')) {
                $this->init_with_relative_path($iid);
                return;
            }
        }

        $image_info = $this->get_image_info($iid);

        $this->import($image_info);
        $basedir = self::wp_upload_dir();
        $basedir = $basedir['basedir'];
        if (isset($this->file)) {
            $this->file_loc = $basedir . DIRECTORY_SEPARATOR . $this->file;
        } else if (isset($this->_wp_attached_file)) {
            $this->file = reset($this->_wp_attached_file);
            $this->file_loc = $basedir . DIRECTORY_SEPARATOR . $this->file;
        } else if ( isset( $this->guid ) ) {
            if ( TimberURLHelper::is_absolute( $this->guid ) ) {
                $this->file_loc = TimberURLHelper::url_to_file_system( $this->guid );
            }
        }
        if (isset($image_info['id'])) {
            $this->ID = $image_info['id'];
        } else if (is_numeric($iid)) {
            $this->ID = $iid;
        }
        if (isset($this->ID)) {
            $custom = get_post_custom($this->ID);
            foreach ($custom as $key => $value) {
                $this->$key = $value[0];
            }
        } else {
            if (is_array($iid)) {
                TimberHelper::error_log('Not able to init in TimberImage with iid=');
                TimberHelper::error_log($iid);
            } else {
                TimberHelper::error_log('Not able to init in TimberImage with iid=' . $iid);
            }
        }
    }

    private function get_image_info( $iid ) {
        $image_info = $iid;
        if (is_numeric($iid)) {
            $image_info = wp_get_attachment_metadata($iid);
            if (!is_array($image_info)) {
                $image_info = array();
            }
            $image_custom = get_post_custom($iid);
            $basic = get_post($iid);
            if ($basic) {
                if (isset($basic->post_excerpt)) {
                    $this->caption = $basic->post_excerpt;
                }
                $image_custom = array_merge($image_custom, get_object_vars($basic));
            }
            return array_merge($image_info, $image_custom);
        }
        if (is_array($image_info) && isset($image_info['image'])) {
            return $image_info['image'];
        }
        if (is_object($image_info)) {
           return get_object_vars($image_info);
        }
        return $iid;
    }

    private function init_with_relative_path( $relative_path ) {
        $this->abs_url = home_url( $relative_path );
        $file_path = TimberURLHelper::get_full_path( $relative_path );
        $this->file_loc = $file_path;
        $this->file = $file_path;
    }

    private function init_with_file_path( $file_path ) {
        $url = TimberURLHelper::file_system_to_url( $file_path );
        $this->abs_url = $url;
        $this->file_loc = $file_path;
        $this->file = $file_path;
    }

    /**
     * @param string $url
     */
    private function init_with_url($url) {
        $this->abs_url = $url;
        if (TimberURLHelper::is_local($url)) {
            $this->file = ABSPATH . TimberURLHelper::get_rel_url($url);
            $this->file_loc = ABSPATH . TimberURLHelper::get_rel_url($url);
        }
    }

    /**
     * @deprecated
     * @return string
     */
    function get_url() {
        return $this->get_src();
    }

    /* Alias */

    /**
     * @return float
     */
    public function aspect() {
        $w = intval($this->width());
        $h = intval($this->height());
        return $w / $h;
    }

    /**
     * @return int
     */
    public function height() {
        return $this->get_height();
    }

    /**
     * @param string $size
     * @return bool|string
     */
    public function src($size = '') {
        return $this->get_src($size);
    }

    /**
     * @return int
     */
    public function width() {
        return $this->get_width();
    }

    public function alt() {
        return $this->get_alt();
    }

}
