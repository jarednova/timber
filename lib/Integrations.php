<?php

namespace Timber;

/**
 * Class Integrations
 *
 * This is for integrating external plugins into timber
 */
class Integrations {

	public $acf;
	public $coauthors_plus;
	public $wpml;

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action('init', array($this, 'maybe_init_integrations'));
	}

	public function maybe_init_integrations() {
		if ( class_exists('ACF') ) {
			$this->acf = new Integrations\ACF();
		}
		if ( class_exists('CoAuthors_Plus') ) {
			$this->coauthors_plus = new Integrations\CoAuthorsPlus();
		}
		$this->wpml = new Integrations\WPML();
	}
}
