<?php

namespace Timber;

/**
 * Class Site
 *
 * `Timber\Site` gives you access to information you need about your site. In Multisite setups, you
 * can get info on other sites in your network.
 *
 * @api
 * @example
 * ```php
 * $other_site_id = 2;
 *
 * $context = Timber::context( [
 *     'other_site' => new Timber\Site( $other_site_id ),
 * ] );
 *
 * Timber::render('index.twig', $context);
 * ```
 * ```twig
 * My site is called {{site.name}}, another site on my network is {{other_site.name}}
 * ```
 * ```html
 * My site is called Jared's blog, another site on my network is Upstatement.com
 * ```
 */
class Site extends Core implements CoreInterface {

	/**
	 * @api
	 * @var string The admin email address set in the WP admin panel
	 */
	public $admin_email;

	/**
	 * @api
	 * @var string
	 */
	public $blogname;

	/**
	 * @api
	 * @var string
	 */
	public $charset;

	/**
	 * @api
	 * @var string
	 */
	public $description;

	/**
	 * @api
	 * @var int the ID of a site in multisite
	 */
	public $id;

	/**
	 * @api
	 * @var string the language setting ex: en-US
	 */
	public $language;

	/**
	 * @api
	 * @var bool true if multisite, false if plain ole' WordPress
	 */
	public $multisite;

	/**
	 * @api
	 * @var string
	 */
	public $name;

	/**
	 * @deprecated 2.0.0, use $pingback_url
	 * @var string for people who like trackback spam
	 */
	public $pingback;

	/**
	 * @api
	 * @var string for people who like trackback spam
	 */
	public $pingback_url;

	/**
	 * @api
	 * @var string
	 */
	public $siteurl;

	/**
	 * @api
	 * @var \Timber\Theme
	 */
	public $theme;

	/**
	 * @api
	 * @var string
	 */
	public $title;

	/**
	 * @api
	 * @var string
	 */
	public $url;

	/**
	 * @api
	 * @var string
	 */
	public $home_url;

	/**
	 * @api
	 * @var string
	 */
	public $site_url;

	/**
	 * @api
	 * @var string
	 */

	public $rdf;
	public $rss;
	public $rss2;
	public $atom;

	/**
	 * Constructs a Timber\Site object
	 * @api
	 * @example
	 * ```php
	 * //multisite setup
	 * $site = new Timber\Site(1);
	 * $site_two = new Timber\Site("My Cool Site");
	 * //non-multisite
	 * $site = new Timber\Site();
	 * ```
	 * @param string|int $site_name_or_id
	 */
	public function __construct( $site_name_or_id = null ) {
		if ( is_multisite() ) {
			$blog_id = self::switch_to_blog($site_name_or_id);
			$this->init();
			$this->init_as_multisite($blog_id);
			restore_current_blog();
		} else {
			$this->init();
			$this->init_as_singlesite();
		}
	}

	/**
	 * Switches to the blog requested in the request
	 *
	 * @param string|integer|null $site_name_or_id
	 * @return integer with the ID of the new blog
	 */
	protected static function switch_to_blog( $site_name_or_id ) {
		if ( $site_name_or_id === null ) {
			$site_name_or_id = get_current_blog_id();
		}
		$info = get_blog_details($site_name_or_id);
		switch_to_blog($info->blog_id);
		return $info->blog_id;
	}

	/**
	 * @internal
	 * @param integer $site_id
	 */
	protected function init_as_multisite( $site_id ) {
		$info = get_blog_details($site_id);
		$this->import($info);
		$this->ID = $info->blog_id;
		$this->id = $this->ID;
		$this->name = $this->blogname;
		$this->title = $this->blogname;
		$theme_slug = get_blog_option($info->blog_id, 'stylesheet');
		$this->theme = new Theme($theme_slug);
		$this->description = get_blog_option($info->blog_id, 'blogdescription');
		$this->admin_email = get_blog_option($info->blog_id, 'admin_email');
		$this->multisite = true;
	}

	/**
	 * Executed for single-blog sites
	 * @internal
	 */
	protected function init_as_singlesite() {
		$this->admin_email = get_bloginfo('admin_email');
		$this->name = get_bloginfo('name');
		$this->title = $this->name;
		$this->description = get_bloginfo('description');
		$this->theme = new Theme();
		$this->multisite = false;
	}

	/**
	 * Executed for all types of sites: both multisite and "regular"
	 * @internal
	 */
	protected function init() {
		$this->url = home_url();
		$this->home_url = $this->url;
		$this->site_url = site_url();
		$this->rdf = get_bloginfo('rdf_url');
		$this->rss = get_bloginfo('rss_url');
		$this->rss2 = get_bloginfo('rss2_url');
		$this->atom = get_bloginfo('atom_url');
		$this->language = get_locale();
		$this->charset = get_bloginfo('charset');
		$this->pingback = $this->pingback_url = get_bloginfo('pingback_url');
	}

	/**
	 * Returns the language attributes that you're looking for
	 * @return string
	 */
	public function language_attributes() {
		return get_language_attributes();
	}

	/**
	 * Get the value for a site option.
	 *
	 * @api
	 * @example
	 * ```twig
	 * Published on: {{ post.date|date(site.date_format) }}
	 * ```
	 *
	 * @param string $option The name of the option to get the value for.
	 *
	 * @return mixed The option value.
	 */
	public function __get( $option ) {
		if ( ! isset( $this->$option ) ) {
			if ( is_multisite() ) {
				$this->$option = get_blog_option( $this->ID, $option );
			} else {
				$this->$option = get_option( $option );
			}
		}

		return $this->$option;
	}

	/**
	 * Get the value for a site option.
	 *
	 * @api
	 * @example
	 * ```twig
	 * Published on: {{ post.date|date(site.option('date_format')) }}
	 * ```
	 *
	 * @param string $option The name of the option to get the value for.
	 *
	 * @return mixed The option value.
	 */
	public function option( $option ) {
		return $this->__get( $option );
	}

	/**
	 * Get the value for a site option.
	 *
	 * @api
	 * @deprecated 2.0.0, use `{{ site.option }}` instead
	 */
	public function meta( $option ) {
		Helper::deprecated( '{{ site.meta() }}', '{{ site.option() }}', '2.0.0' );

		return $this->__get( $option );
	}

	/**
	 * @api
	 * @return null|\Timber\Image
	 */
	public function icon() {
		if ( is_multisite() ) {
			return $this->icon_multisite($this->ID);
		}
		$iid = get_option('site_icon');
		if ( $iid ) {
			return Timber::get_post($iid);
		}
	}

	protected function icon_multisite( $site_id ) {
		$image = null;
		$blog_id = self::switch_to_blog($site_id);
		$iid = get_blog_option($blog_id, 'site_icon');
		if ( $iid ) {
			$image = Timber::get_post($iid);
		}
		restore_current_blog();
		return $image;
	}

	/**
	 * Returns the link to the site's home.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <a href="{{ site.link }}" title="Home">
	 * 	  <img src="/wp-content/uploads/logo.png" alt="Logo for some stupid thing" />
	 * </a>
	 * ```
	 * ```html
	 * <a href="http://example.org" title="Home">
	 * 	  <img src="/wp-content/uploads/logo.png" alt="Logo for some stupid thing" />
	 * </a>
	 * ```
	 *
	 * @return string
	 */
	public function link() {
		return $this->url;
	}

	/**
	 * Updates a site option.
	 *
	 * @deprecated 2.0.0 Use `update_option()` or `update_blog_option()` instead.
	 *
	 * @param string $key   The key of the site option to update.
	 * @param mixed  $value The new value.
	 */
	public function update( $key, $value ) {
		Helper::deprecated( 'Timber\Site::update()', 'update_option()', '2.0.0' );

		/**
		 * Filters a value before it is updated in the site options.
		 *
		 * @since 2.0.0
		 *
		 * @param mixed        $value   The new value.
		 * @param string       $key     The option key.
		 * @param int          $site_id The site ID.
		 * @param \Timber\Site $site    The site object.
		 */
		$value = apply_filters( 'timber/site/update_option', $value, $key, $this->ID, $this );

		/**
		 * Filters a value before it is updated in the site options.
		 *
		 * @deprecated 2.0.0, use `timber/site/update_option`
		 * @since 0.20.0
		 */
		$value = apply_filters_deprecated(
			'timber_site_set_meta',
			array( $value, $key, $this->ID, $this ),
			'2.0.0',
			'timber/site/update_option'
		);

		if ( is_multisite() ) {
			update_blog_option($this->ID, $key, $value);
		} else {
			update_option($key, $value);
		}
		$this->$key = $value;
	}
}
