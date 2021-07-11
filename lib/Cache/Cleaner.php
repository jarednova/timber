<?php

namespace Timber\Cache;

class Cleaner {

	protected static function delete_transients_single_site() {
		global $wpdb;
		$sql = "
				DELETE
					a, b
				FROM
					{$wpdb->options} a, {$wpdb->options} b
				WHERE
					a.option_name LIKE '%_transient_%' AND
					a.option_name NOT LIKE '%_transient_timeout_%' AND
					b.option_name = CONCAT(
						'_transient_timeout_',
						SUBSTRING(
							a.option_name,
							CHAR_LENGTH('_transient_') + 1
						)
					)
				AND b.option_value < UNIX_TIMESTAMP()
			";
		return $wpdb->query($sql);
	}

	protected static function delete_transients_multisite() {
		global $wpdb;
		$sql = "
					DELETE
						a, b
					FROM
						{$wpdb->sitemeta} a, {$wpdb->sitemeta} b
					WHERE
						a.meta_key LIKE '_site_transient_%' AND
						a.meta_key NOT LIKE '_site_transient_timeout_%' AND
						b.meta_key = CONCAT(
							'_site_transient_timeout_',
							SUBSTRING(
								a.meta_key,
								CHAR_LENGTH('_site_transient_') + 1
							)
						)
					AND b.meta_value < UNIX_TIMESTAMP()
				";

				$clean = $wpdb->query($sql);
		return $clean;
	}

	public static function delete_transients( ) {
		global $_wp_using_ext_object_cache;

		if ( $_wp_using_ext_object_cache ) {
			return 0;
		}

		global $wpdb;
		$records = 0;

		// Delete transients from options table
		$records .= self::delete_transients_single_site();

		// Delete transients from multisite, if configured as such

		if ( is_multisite() ) {

			$records .= self::delete_transients_multisite();
		}
		return $records;

	}


}