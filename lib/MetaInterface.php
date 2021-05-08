<?php

namespace Timber;

/**
 * Interface MetaInterface
 *
 * An interface for classes that implement getting meta values from the database.
 *
 * @since 2.0.0
 */
interface MetaInterface {
	/**
	 * Gets a meta value.
	 *
	 * Returns a meta value for an object that’s saved in the database.
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @param array  $args       An array of arguments for getting the meta value. Third-party
	 *                           integrations can use this argument to make their API arguments
	 *                           available in Timber. Default empty.
	 * @return mixed The meta field value.
	 */
	public function meta( $field_name = '', $args = array() );

	/**
	 * Gets a meta value directly from the database.
	 *
	 * Returns a raw meta value for an object that’s saved in the database. Be aware that the value
	 * can still be filtered by plugins.
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @return mixed The raw meta field value.
	 */
	public function raw_meta( $field_name = '' );

	/**
	 * Gets a meta value.
	 *
	 * @api
	 * @deprecated 2.0.0
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @return mixed The meta field value.
	 */
	public function get_field( $field_name );
}
