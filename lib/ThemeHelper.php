<?php

namespace Timber;

/**
 * Class ThemeHelper
 *
 * Helper class to work with current & parent theme's names, paths & directories.
 *
 * All class methods behave just like their corresponding WordPress functions,
 * with the noticeable difference that they won't return what comes after "/" in theme's name.
 *
 * For example, consider a theme with the following tree structure (2.x default child theme structure):
 * - my-theme-name/
 * -> images/
 * --> foo.jpg
 * -> theme/
 * --> functions.php
 * --> style.css
 *
 * Theme template in style.css is my-theme-name/theme
 *
 * Regular get_stylesheet_directory_uri() will output something like:
 * https://example.com/my-theme-name/theme
 *
 * When it should return the root uri of your theme, regardless of tree structure:
 * https://example.com/my-theme-name
 *
 * @api
 */
class ThemeHelper
{
	/**
	 * Retrieves and parses name of the current stylesheet.
	 *
	 * @see get_stylesheet()
	 *
	 * @return string Clean stylesheet name.
	 */
	public static function get_stylesheet() {
		list($stylesheet) = explode('/', get_stylesheet());

		return $stylesheet;
	}

	/**
	 * Retrieves and parses stylesheet directory path for current theme.
	 *
	 * @see get_stylesheet_directory()
	 *
	 * @return string Clean path to current theme's stylesheet directory.
	 */
	public static function get_stylesheet_directory() {
		return str_replace(get_stylesheet(), self::get_stylesheet(), get_stylesheet_directory());
	}

	/**
	 * Retrieves and parses stylesheet directory URI for current theme.
	 *
	 * @see get_stylesheet_directory_uri()
	 *
	 * @return string Clean URI to current theme's stylesheet directory.
	 */
	public static function get_stylesheet_directory_uri() {
		return str_replace(get_stylesheet(), self::get_stylesheet(), get_stylesheet_directory_uri());
	}

	/**
	 * Retrieves and parses name of the current theme.
	 *
	 * @see get_template()
	 *
	 * @return string Clean template name.
	 */
	public static function get_template() {
		list($template) = explode('/', get_template());

		return $template;
	}

	/**
	 * Retrieves and parses template directory path for current theme.
	 *
	 * @see get_template_directory()
	 *
	 * @return string Clean path to current theme's template directory.
	 */
	public static function get_template_directory() {
		return str_replace(get_template(), self::get_template(), get_template_directory());
	}

	/**
	 * Retrieves and parses template directory URI for current theme.
	 *
	 * @see get_template_directory_uri()
	 *
	 * @return string Clean URI to current theme's template directory.
	 */
	public static function get_template_directory_uri() {
		return str_replace(get_template(), self::get_template(), get_template_directory_uri());
	}
}
