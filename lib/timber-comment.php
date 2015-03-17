<?php

class TimberComment extends TimberWPObject {

    public $PostClass = 'TimberPost';
    public $object_type = 'comment';

    public static $representation = 'comment';

    public $ID;
    public $id;
    public $comment_author_email;
    public $comment_content;
    public $comment_date;
    public $comment_ID;
    public $user_id;
    public $comment_author;

    /**
     * @param int $cid
     */
    function __construct($cid) {
        $this->init($cid);
    }

    function __toString(){
        return $this->content();
    }

    /**
     * @param integer $cid
     */
    function init($cid) {
        $comment_data = $cid;
        if (is_integer($cid)) {
            $comment_data = get_comment($cid);
        }
        $this->import($comment_data);
        $this->ID = $this->comment_ID;
        $this->id = $this->comment_ID;
        $comment_meta_data = $this->get_meta_fields($this->ID);
        $this->import($comment_meta_data);
    }

    /**
     * @return TimberUser
     */
    public function author() {
        if ($this->user_id) {
            return new TimberUser($this->user_id);
        } else {
            $author = new TimberUser(0);
            if (isset($this->comment_author) && $this->comment_author) {
                $author->name = $this->comment_author;
            } else {
                $author->name = 'Anonymous';
            }
        }
        return $author;
    }

    /**
     * @param int $size
     * @param string $default
     * @return bool|mixed|string
     */
    public function avatar($size = 92, $default = '') {
        // Fetches the Gravatar
        // use it like this
        // {{comment.avatar(36,template_uri~"/img/dude.jpg")}}

        if (!get_option('show_avatars')) {
            return false;
        }
        if (!is_numeric($size)) {
            $size = '92';
        }

        $email = $this->avatar_email();
        $email_hash = '';
        if (!empty($email)) {
            $email_hash = md5(strtolower(trim($email)));
        }
        $host = $this->avatar_host($email_hash);
        $default = $this->avatar_default($default, $email, $size, $host);
        if (!empty($email)) {
            $avatar = $this->avatar_out($email, $default, $host, $email_hash, $size);
        } else {
            $avatar = $default;
        }
        return $avatar;
    }

    /**
     * @return string
     */
    public function content() {
        return $this->comment_content;
    }

    /**
     * @return string
     */
    public function status() {
        return $this->comment_status;
    }

    /**
     * @return string
     */
    public function date() {
        return $this->comment_date;
    }

    /**
     * @param string $field_name
     * @return mixed
     */
    public function meta($field_name) {
        return $this->get_meta_field($field_name);
    }

    /**
     * @param int $comment_id
     * @return mixed
     */
    private function get_meta_fields($comment_id = null) {
        if ($comment_id === null) {
            $comment_id = $this->ID;
        }
        //Could not find a WP function to fetch all comment meta data, so I made one.
        apply_filters('timber_comment_get_meta_pre', array(), $comment_id);
        $comment_metas = get_comment_meta($comment_id);
        foreach ($comment_metas as &$cm) {
            if (is_array($cm) && count($cm) == 1) {
                $cm = $cm[0];
            }
        }
        $comment_metas = apply_filters('timber_comment_get_meta', $comment_metas, $comment_id);
        return $comment_metas;
    }

    /**
     * @param string $field_name
     * @return mixed
     */
    private function get_meta_field($field_name) {
        $value = apply_filters('timber_comment_get_meta_field_pre', null, $this->ID, $field_name, $this);
        if ($value === null) {
            $value = get_comment_meta($this->ID, $field_name, true);
        }
        $value = apply_filters('timber_comment_get_meta_field', $value, $this->ID, $field_name, $this);
        return $value;
    }

    /* AVATAR Stuff
    ======================= */

    /**
     * @return string
     */
    private function avatar_email() {
        $id = (int)$this->user_id;
        $user = get_userdata($id);
        if ($user) {
            $email = $user->user_email;
        } else {
            $email = $this->comment_author_email;
        }
        return $email;
    }

    /**
     * @param string $email_hash
     * @return string
     */
    private function avatar_host($email_hash) {
        if (is_ssl()) {
            $host = 'https://secure.gravatar.com';
        } else {
            if (!empty($email_hash)) {
                $host = sprintf("http://%d.gravatar.com", (hexdec($email_hash[0]) % 2));
            } else {
                $host = 'http://0.gravatar.com';
            }
        }
        return $host;
    }

    /**
     * @param string $default
     * @param string $email
     * @param string $size
     * @param string $host
     * @return string
     */
    private function avatar_default($default, $email, $size, $host) {
        # what if its relative.
        if (substr($default, 0, 1) == '/') {
            $default = home_url() . $default;
        }

        if (empty($default)) {
            $avatar_default = get_option('avatar_default');
            if (empty($avatar_default)) {
                $default = 'mystery';
            } else {
                $default = $avatar_default;
            }
        }
        if ('mystery' == $default) {
            $default = $host . '/avatar/ad516503a11cd5ca435acc9bb6523536?s=' . $size;
            // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
        } else if ('blank' == $default) {
            $default = $email ? 'blank' : includes_url('images/blank.gif');
        } else if (!empty($email) && 'gravatar_default' == $default) {
            $default = '';
        } else if ('gravatar_default' == $default) {
            $default = $host . '/avatar/?s=' . $size;
        } else if (empty($email) && !strstr($default, 'http://')) {
            $default = $host . '/avatar/?d=' . $default . '&amp;s=' . $size;
        } else if (strpos($default, 'http://') === 0) {
            //theyre just linking to an image so don't do anything else
            //$default = add_query_arg( 's', $size, $default );
        }
        return $default;
    }

    /**
     * @param string $email
     * @param string $default
     * @param string $host
     * @param string $email_hash
     * @param string $size
     * @return mixed
     */
    private function avatar_out($email, $default, $host, $email_hash, $size) {
        $out = $host . '/avatar/' . $email_hash . '?s=' . $size . '&amp;d=' . urlencode($default);
        $rating = get_option('avatar_rating');
        if (!empty($rating)) {
            $out .= '&amp;r=' . $rating;
        }
        return str_replace('&#038;', '&amp;', esc_url($out));
    }

}
