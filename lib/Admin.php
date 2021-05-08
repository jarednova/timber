<?php

namespace Timber;

/**
 * Class Admin
 */
class Admin {

	public static function init() {
		global $wp_version;
		if ( version_compare('5.3.0', $wp_version) === 1 ) {
			// user is running something older that WordPress 5.3 show them an error
			$upgrade_url = admin_url('update-core.php');
			self::show_notice("<a href='https://github.com/timber/timber'>Timber 2.0</a> requires <strong>WordPress 5.3</strong> or greater, but you are running <strong>WordPress $wp_version</strong>. Please <a href='$upgrade_url'>upgrade WordPress</a> in order to use Timber 2.0.");
		}
		return true;
	}

	/**
	 * Display a message in the admin.
	 *
	 * @date    01/07/2020
	 *
	 * @param string  $text to display
	 * @param string  $class of the notice 'error' (red) or 'warning' (yellow)
	 */
	protected static function show_notice( $text, $class = 'error') {
		add_action( 'admin_notices', function() use ( $text, $class ) {
				echo '<div class="'.$class.'"><p>'.$text.'</p></div>';
			}, 1 );
	}

}
