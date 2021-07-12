<?php

namespace Timber\Integration;

class WpmlIntegration implements IntegrationInterface {

	public function should_init() : bool {
		return function_exists('wpml_object_id_filter');
	}

	public function init() : void {
		add_filter('timber/url_helper/file_system_to_url', array($this, 'file_system_to_url'), 10, 1);
		add_filter('timber/url_helper/get_content_subdir/home_url', array($this, 'file_system_to_url'), 10, 1);
		add_filter('timber/url_helper/url_to_file_system/path', array($this, 'file_system_to_url'), 10, 1);
		add_filter('timber/menu/id_from_location', array($this, 'menu_object_id_filter'), 10, 1);
		add_filter('timber/image_helper/_get_file_url/home_url', array($this, 'file_system_to_url'), 10, 1);
	}

	public function file_system_to_url($url) {
		if ( defined('ICL_LANGUAGE_CODE') ) {
			$url = preg_replace('/(?<!:\/)\/' . ICL_LANGUAGE_CODE . '/', '', $url);
		}
		return $url;
	}

	public function menu_object_id_filter($id) {
		return wpml_object_id_filter($id, 'nav_menu');
	}

}
