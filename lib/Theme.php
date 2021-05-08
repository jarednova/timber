<?php

namespace Timber;

/**
 * Class Theme
 *
 * Need to display info about your theme? Well you've come to the right place. By default info on
 * the current theme comes for free with what's fetched by `Timber::context()` in which case you
 * can access it your theme like so:
 *
 * @api
 * @example
 * ```php
 * <?php
 * $context = Timber::context();
 *
 * Timber::render('index.twig', $context);
 * ?>
 * ```
 * ```twig
 * <script src="{{ theme.link }}/static/js/all.js"></script>
 * ```
 * ```html
 * <script src="http://example.org/wp-content/themes/my-theme/static/js/all.js"></script>
 * ```
 */
class Theme extends Core {

	/**
	 * The human-friendly name of the theme (ex: `My Timber Starter Theme`)
	 *
	 * @api
	 * @var string the human-friendly name of the theme (ex: `My Timber Starter Theme`)
	 */
	public $name;

	/**
	 * The version of the theme (ex: `1.2.3`)
	 *
	 * @api
	 * @var string the version of the theme (ex: `1.2.3`)
	 */
	public $version;

	/**
	 * Timber\Theme object for the parent theme.
	 *
	 * Always returns the top-most theme. If the current theme is also the parent theme, it will
	 * return itself.
	 *
	 * @api
	 * @var \Timber\Theme the Timber\Theme object for the parent theme
	 */
	public $parent;

	/**
	 * Slug of the parent theme (ex: `_s`)
	 *
	 * @api
	 * @var string the slug of the parent theme (ex: `_s`)
	 */
	public $parent_slug;

	/**
	 * @api
	 * @var string the slug of the theme (ex: `my-super-theme`)
	 */
	public $slug;

	/**
	 * @api
	 * @var string
	 */
	public $uri;

	/**
	 * @var \WP_Theme the underlying WordPress native Theme object
	 */
	private $theme;

	/**
	 * Constructs a new `Timber\Theme` object.
	 *
	 * The `Timber\Theme` object of the current theme comes in the default `Timber::context()`
	 * call. You can access this in your twig template via `{{ site.theme }}`.
	 *
	 * @api
	 * @example
	 * ```php
	 * <?php
	 *     $theme = new Timber\Theme("my-theme");
	 *     $context['theme_stuff'] = $theme;
	 *     Timber::render('single.twig', $context);
	 * ```
	 * ```twig
	 * We are currently using the {{ theme_stuff.name }} theme.
	 * ```
	 * ```html
	 * We are currently using the My Theme theme.
	 * ```
	 *
	 * @param string $slug
	 */
	public function __construct( $slug = null ) {
		$this->init($slug);
	}

	/**
	 * Initalizes the Theme object
	 *
	 * @internal
	 * @param string $slug of theme (eg 'twentysixteen').
	 */
	protected function init( $slug = null ) {
		$this->theme = wp_get_theme($slug);
		$this->name = $this->theme->get('Name');
		$this->version = $this->theme->get('Version');
		$this->slug = $this->theme->get_stylesheet();

		$this->uri = $this->theme->get_template_directory_uri();

		$this->parent = $this;
		$this->parent_slug = $this->theme->get_stylesheet();
		if ( $this->theme->parent() ) {
			$this->parent_slug = $this->theme->parent()->get_stylesheet();
			$this->parent = new Theme($this->parent_slug);
		}
	}

	/**
	 * @api
	 * @return string the absolute path to the theme (ex: `http://example.org/wp-content/themes/my-timber-theme`)
	 */
	public function link() {
		return $this->theme->get_stylesheet_directory_uri();
	}

	/**
	 * @api
	 * @return  string the relative path to the theme (ex: `/wp-content/themes/my-timber-theme`)
	 */
	public function path() {
		// force = true to work with specifying the port
		// @see https://github.com/timber/timber/issues/1739
		return URLHelper::get_rel_url($this->link(), true);
	}

	/**
	 * @api
	 * @param string $name
	 * @param bool $default
	 * @return string
	 */
	public function theme_mod( $name, $default = false ) {
		return get_theme_mod($name, $default);
	}

	/**
	 * @api
	 * @return array
	 */
	public function theme_mods() {
		return get_theme_mods();
	}

	/**
	 * Gets a raw, unformatted theme header.
	 *
	 * @api
	 * @see \WP_Theme::get()
	 * @example
	 * ```twig
	 * {{ theme.get('Version') }}
	 * ```
	 *
	 * @param string $header Name of the theme header. Name, Description, Author, Version,
	 *                       ThemeURI, AuthorURI, Status, Tags.
	 *
	 * @return false|string String on success, false on failure.
	 */
	public function get( $header ) {
		return $this->theme->get( $header );
	}

	/**
	 * Gets a theme header, formatted and translated for display.
	 *
	 * @api
	 * @see \WP_Theme::display()
	 * @example
	 * ```twig
	 * {{ theme.display('Description') }}
	 * ```
	 *
	 * @param string $header Name of the theme header. Name, Description, Author, Version,
	 *                       ThemeURI, AuthorURI, Status, Tags.
	 *
	 * @return false|string
	 */
	public function display( $header ) {
		return $this->theme->display( $header );
	}
}
