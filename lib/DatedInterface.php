<?php

namespace Timber;

/**
 * Interface DateTimeInterface
 *
 * An interface for classes that implement getting date/time values from objects.
 *
 * @since 2.0.0
 */
interface DatedInterface {
	/**
	 * Gets the timestamp when the object was published.
	 *
	 * @api
	 * @since 2.0.0
	 *
	 * @return false|int Unix timestamp on success, false on failure.
	 */
	public function timestamp();

	/**
	 * Gets the timestamp when the object was last modified.
	 *
	 * @api
	 * @since 2.0.0
	 *
	 * @return false|int Unix timestamp on success, false on failure.
	 */
	public function modified_timestamp();

	/**
	 * Gets the publishing date of the object.
	 *
	 * @api
	 * @since 2.0.0
	 *
	 * @param string|null $date_format Optional. PHP date format. Will use the `date_format` option
	 *                                 as a default.
	 * @return string
	 */
	public function date( $date_format = null );

	/**
	 * Gets the date of the last modification of the object.
	 *
	 * @param string|null $date_format Optional. PHP date format. Will use the `date_format` option
	 *                                 as a default.
	 *
	 * @return string
	 */
	public function modified_date( $date_format = null );
}
