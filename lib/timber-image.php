<?php

/**
 * If TimberPost is the class you're going to spend the most time, TimberImage is the class you're going to have the most fun with.
 * @example
 * ```php
 * $context = Timber::get_context();
 * $post = new TimberPost();
 * $context['post'] = $post;
 *
 * // lets say you have an alternate large 'cover image' for your post stored in a custom field which returns an image ID
 * $cover_image_id = $post->cover_image;
 * $context['cover_image'] = new TimberImage($cover_image_id);
 * Timber::render('single.twig', $context);
 * ```
 *
 * ```twig
 * <article>
 * 	<img src="{{cover_image.src}}" class="cover-image" />
 * 	<h1 class="headline">{{post.title}}</h1>
 * 	<div class="body">
 * 		{{post.content}}
 * 	</div>
 *
 * 	<img src="{{ Image(post.custom_field_with_image_id).src }}" alt="Another way to initialize images as TimberImages, but within Twig" />
 * </article>
 * ```
 *
 * ```html
 * <article>
 * 	<img src="http://example.org/wp-content/uploads/2015/06/nevermind.jpg" class="cover-image" />
 * 	<h1 class="headline">Now you've done it!</h1>
 * 	<div class="body">
 * 		Whatever whatever
 * 	</div>
 * 	<img src="http://example.org/wp-content/uploads/2015/06/kurt.jpg" alt="Another way to initialize images as TimberImages, but within Twig" />
 * </article>
 * ```
 */
class TimberImage extends TimberPost implements TimberCoreInterface {

	protected $_can_edit;
	protected $_dimensions;
	public $abs_url;
	/**
	 * @var string $object_type what does this class represent in WordPress terms?
	 */
	public $object_type = 'image';
	/**
	 * @var string $representation what does this class represent in WordPress terms?
	 */
	public static $representation = 'image';
	/**
	 * @var array of supported relative file types
	 */
	private $file_types = array('jpg', 'jpeg', 'png', 'svg', 'bmp', 'ico', 'gif', 'tiff', 'pdf');
	/**
	 * @api
	 * @var string $file_loc the location of the image file in the filesystem (ex: `/var/www/htdocs/wp-content/uploads/2015/08/my-pic.jpg`)
	 */
	public $file_loc;
	public $file;
	/**
	 * @api
	 * @var integer the ID of the image (which is a WP_Post)
	 */
	public $id;
	public $sizes = array();
	/**
	 * @api
	 * @var string $caption the string stored in the WordPress database
	 */
	public $caption;
	/**
	 * @var $_wp_attached_file the file as stored in the WordPress database
	 */
	protected $_wp_attached_file;

    protected static $_methods = array();

	/**
	 * Creates a new TimberImage object
	 * @example
	 * ```php
	 * // You can pass it an ID number
	 * $myImage = new TimberImage(552);
	 *
	 * //Or send it a URL to an image
	 * $myImage = new TimberImage('http://google.com/logo.jpg');
	 * ```
	 * @param int|string $iid
	 */
	public function __construct( $iid, $context = false ) {
		$this->init($iid);
        $this->_init_extendable( $context );
	}

	/**
	 * @return string the src of the file
	 */
	public function __toString() {
		if ( $this->get_src() ) {
			return $this->get_src();
		}
		return '';
	}

	/**
	 * Get a PHP array with pathinfo() info from the file
	 * @return array
	 */
	function get_pathinfo() {
		return pathinfo($this->file);
	}

	/**
	 * @internal
	 * @param string $dim
	 * @return array|int
	 */
	protected function get_dimensions($dim = null) {
		if ( isset($this->_dimensions) ) {
			return $this->get_dimensions_loaded($dim);
		}
		if ( file_exists($this->file_loc) && filesize($this->file_loc) ) {
			list($width, $height) = getimagesize($this->file_loc);
			$this->_dimensions = array();
			$this->_dimensions[0] = $width;
			$this->_dimensions[1] = $height;
			return $this->get_dimensions_loaded($dim);
		}
	}

	/**
	 * @internal
	 * @param string|null $dim
	 * @return array|int
	 */
	protected function get_dimensions_loaded($dim) {
		if ( $dim === null ) {
			return $this->_dimensions;
		}
		if ( $dim == 'w' || $dim == 'width' ) {
			return $this->_dimensions[0];
		}
		if ( $dim == 'h' || $dim == 'height' ) {
			return $this->_dimensions[1];
		}
		return null;
	}

	/**
	 * @internal
	 * @param  int $iid the id number of the image in the WP database
	 */
	protected function get_image_info( $iid ) {
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

	/**
	 * @internal
	 * @param  string $url for evaluation
	 * @return string with http/https corrected depending on what's appropriate for server
	 */
	protected static function _maybe_secure_url($url) {
		if ( is_ssl() && strpos($url, 'https') !== 0 && strpos($url, 'http') === 0 ) {
			$url = 'https' . substr($url, strlen('http'));
		}
		return $url;
	}

	public static function wp_upload_dir() {
		static $wp_upload_dir = false;

		if ( !$wp_upload_dir ) {
			$wp_upload_dir = wp_upload_dir();
		}

		return $wp_upload_dir;
	}

	/**
	 * @internal
	 * @param int $iid
	 */
	function init( $iid = false ) {
		if(!$iid) { TimberHelper::error_log('Initalized TimberImage without providing first parameter.'); return; }
		
		if ( !is_numeric( $iid ) && is_string( $iid ) ) {
			if (strstr($iid, '://')) {
				$this->init_with_url($iid);
				return;
			}
			if ( strstr($iid, ABSPATH) ) {
				$this->init_with_file_path($iid);
				return;
			}

			$relative = false;
			$iid_lower = strtolower($iid);
			foreach( $this->file_types as $type ) { if( strstr( $iid_lower, $type ) ) { $relative = true; break; } };
			if ( $relative ) {
				$this->init_with_relative_path( $iid );
				return;
			}
		} else if ( $iid instanceof WP_Post ) {
			$ref = new ReflectionClass($this);
			$post = $ref->getParentClass()->newInstance($iid->ID);
			if (isset($post->_thumbnail_id) && $post->_thumbnail_id) {
				return $this->init((int) $post->_thumbnail_id);
			}
			return $this->init($iid->ID);
		} else if ( $iid instanceof TimberPost ) {
			/**
			 * This will catch TimberPost and any post classes that extend TimberPost,
			 * see http://php.net/manual/en/internals2.opcodes.instanceof.php#109108
			 * and https://github.com/timber/timber/wiki/Extending-Timber
			 */
			$iid = (int) $iid->_thumbnail_id;
		}

		$image_info = $this->get_image_info($iid);

		$this->import($image_info);
		$basedir = self::wp_upload_dir();
		$basedir = $basedir['basedir'];
		if ( isset($this->file) ) {
			$this->file_loc = $basedir . DIRECTORY_SEPARATOR . $this->file;
		} else if ( isset($this->_wp_attached_file) ) {
			$this->file = reset($this->_wp_attached_file);
			$this->file_loc = $basedir . DIRECTORY_SEPARATOR . $this->file;
		}
		if ( isset($image_info['id']) ) {
			$this->ID = $image_info['id'];
		} else if ( is_numeric($iid) ) {
			$this->ID = $iid;
		}
		if ( isset($this->ID) ) {
			$custom = get_post_custom($this->ID);
			foreach ($custom as $key => $value) {
				$this->$key = $value[0];
			}
			$this->id = $this->ID;
		} else {
			if ( is_array($iid) || is_object($iid) ) {
				TimberHelper::error_log('Not able to init in TimberImage with iid=');
				TimberHelper::error_log($iid);
			} else {
				TimberHelper::error_log('Not able to init in TimberImage with iid=' . $iid);
			}
		}
	}

	/**
	 * @internal
	 * @param string $relative_path
	 */
	protected function init_with_relative_path( $relative_path ) {
		$this->abs_url = home_url( $relative_path );
		$file_path = TimberURLHelper::get_full_path( $relative_path );
		$this->file_loc = $file_path;
		$this->file = $file_path;
	}

	/**
	 * @internal
	 * @param string $file_path
	 */
	protected function init_with_file_path( $file_path ) {
		$url = TimberURLHelper::file_system_to_url( $file_path );
		$this->abs_url = $url;
		$this->file_loc = $file_path;
		$this->file = $file_path;
	}

	/**
	 * @internal
	 * @param string $url
	 */
	protected function init_with_url($url) {
		$this->abs_url = $url;
		if ( TimberURLHelper::is_local($url) ) {
			$this->file = ABSPATH . TimberURLHelper::get_rel_url($url);
			$this->file_loc = ABSPATH . TimberURLHelper::get_rel_url($url);
		}
	}

	/**
	 * @api
	 * @example
	 * ```twig
	 * <img src="{{ image.src }}" alt="{{ image.alt }}" />
	 * ```
	 * ```html
	 * <img src="http://example.org/wp-content/uploads/2015/08/pic.jpg" alt="W3 Checker told me to add alt text, so I am" />
	 * ```
	 * @return string alt text stored in WordPress
	 */
	public function alt() {
		$alt = trim(strip_tags(get_post_meta($this->ID, '_wp_attachment_image_alt', true)));
		return $alt;
	}

	/**
	 * @api
	 * @example
	 * ```twig
	 * {% if post.thumbnail.aspect < 1 %}
	 *     {# handle vertical image #}
	 *     <img src="{{ post.thumbnail.src|resize(300, 500) }}" alt="A basketball player" />
	 * {% else %}
	 * 	   <img src="{{ post.thumbnail.src|resize(500) }}" alt="A sumo wrestler" />
	 * {% endif %}
	 * ```
	 * @return float
	 */
	public function aspect() {
		$w = intval($this->width());
		$h = intval($this->height());
		return $w / $h;
	}

	/**
	 * @api
	 * @example
	 * ```twig
	 * <img src="{{ image.src }}" height="{{ image.height }}" />
	 * ```
	 * ```html
	 * <img src="http://example.org/wp-content/uploads/2015/08/pic.jpg" height="900" />
	 * ```
	 * @return int
	 */
	public function height() {
		return $this->get_dimensions('height');
	}

	/**
	 * Returns the link to an image attachment's Permalink page (NOT the link for the image itself!!)
	 * @api
	 * @example
	 * ```twig
	 * <a href="{{ image.link }}"><img src="{{ image.src }} "/></a>
	 * ```
	 * ```html
	 * <a href="http://example.org/my-cool-picture"><img src="http://example.org/wp-content/uploads/2015/whatever.jpg"/></a>
	 * ```
	 */
	public function link() {
		if ( strlen($this->abs_url) ) {
			return $this->abs_url;
		}
		return get_permalink($this->ID);
	}

	/**
	 * @api
	 * @return bool|TimberPost
	 */
	public function parent() {
		if ( !$this->post_parent ) {
			return false;
		}
		return new $this->PostClass($this->post_parent);
	}

	/**
	 * @api
	 * @example
	 * ```twig
	 * <img src="{{ image.path }}" />
	 * ```
	 * ```html
	 * <img src="/wp-content/uploads/2015/08/pic.jpg" />
	 * ```
	 * @return  string the /relative/path/to/the/file
	 */
	public function path() {
		return TimberURLHelper::get_rel_path($this->src());
	}

	/**
	 * @param string $size a size known to WordPress (like "medium")
	 * @api
	 * @example
	 * ```twig
	 * <h1>{{post.title}}</h1>
	 * <img src="{{post.thumbnail.src}}" />
	 * ```
	 * ```html
	 * <img src="http://example.org/wp-content/uploads/2015/08/pic.jpg" />
	 * ```
	 * @return bool|string
	 */
	public function src($size = '') {
		if ( isset($this->abs_url) ) {
			return $this->_maybe_secure_url($this->abs_url);
		}

		if ( $size && is_string($size) && isset($this->sizes[$size]) ) {
			$image = image_downsize($this->ID, $size);
			return $this->_maybe_secure_url(reset($image));
		}

		if ( !isset($this->file) && isset($this->_wp_attached_file) ) {
			$this->file = $this->_wp_attached_file;
		}

		if ( !isset($this->file) ) {
			return false;
		}

		$dir = self::wp_upload_dir();
		$base = $dir['baseurl'];

		$src = trailingslashit($this->_maybe_secure_url($base)) . $this->file;
		$src = apply_filters('timber/image/src', $src, $this->ID);
		return apply_filters('timber_image_src', $src, $this->ID);
	}

	/**
	 * @deprecated use src() instead
	 * @return string
	 */
	function url() {
		return $this->get_src();
	}

	/**
	 * @api
	 * @example
	 * ```twig
	 * <img src="{{ image.src }}" width="{{ image.width }}" />
	 * ```
	 * ```html
	 * <img src="http://example.org/wp-content/uploads/2015/08/pic.jpg" width="1600" />
	 * ```
	 * @return int
	 */
	public function width() {
		return $this->get_dimensions('width');
	}


	/**
	 * @deprecated 0.21.9 use TimberImage::width() instead
	 * @internal
	 * @return int
	 */
	function get_width() {
		return $this->width();
	}

	/**
	 * @deprecated 0.21.9 use TimberImage::height() instead
	 * @internal
	 * @return int
	 */
	function get_height() {
		return $this->height();
	}

	/**
	 * @deprecated 0.21.9 use TimberImage::src
	 * @internal
	 * @param string $size
	 * @return bool|string
	 */
	function get_src( $size = '' ) {
		return $this->src( $size );
	}

	/**
	 * @deprecated 0.21.9 use TimberImage::path()
	 * @internal
	 * @return string
	 */
	function get_path() {
		return $this->link();
	}

	/**
	 * @deprecated use src() instead
	 * @return string
	 */
	function get_url() {
		return $this->get_src();
	}

	/**
	 * @internal
	 * @deprecated 0.21.8
	 * @return bool|TimberPost
	 */
	function get_parent() {
		return $this->parent();
	}

	/**
	 * @internal
	 * @deprecated 0.21.9
	 * @see TimberImage::alt
	 * @return string
	 */
	function get_alt() {
		return $this->alt();
	}

}
