<?php

namespace Timber\Integrations;

class WPML {

	public function __construct() {
		add_filter('timber/URLHelper/file_system_to_url', array($this, 'file_system_to_url'), 10, 1);
		add_filter('timber/URLHelper/get_content_subdir/home_url', array($this, 'file_system_to_url'), 10, 1);
		add_filter('timber/URLHelper/url_to_file_system/path', array($this, 'file_system_to_url'), 10, 1);

	}

	public function file_system_to_url($url) {
		if ( defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE ) {
			$url = preg_replace('/(?<!:\/)\/' . ICL_LANGUAGE_CODE . '/', '', $url);
		}
		return $url;
	}

}
