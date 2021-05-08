<?php

namespace Timber;

abstract class CoreEntity extends Core implements CoreInterface, MetaInterface {

	/**
	 * Gets an object meta value.
	 *
	 * Returns a meta value or all meta values for all custom fields of an object saved in the
	 * meta database table.
	 *
	 * Fetching all values is only advised during development, because it can have a big performance
	 * impact, when all filters are applied.
	 *
	 * @api
	 *
	 * @param string $field_name Optional. The field name for which you want to get the value. If
	 *                           no field name is provided, this function will fetch values for all
	 *                           custom fields. Default empty string.
	 * @param array $args An array of arguments for getting the meta value. Third-party integrations
	 *                    can use this argument to make their API arguments available in Timber.
	 *                    Default empty array.
	 * @return mixed The custom field value or an array of custom field values. Null if no value
	 *               could be found.
	 */
	public function meta( $field_name = '', $args = [] ) {
		return $this->fetch_meta( $field_name, $args );
	}

	/**
	 * Gets an object meta value directly from the database.
	 *
	 * Returns a raw meta value or all raw meta values saved in the meta database table. In
	 * comparison to `meta()`, this function will return raw values that are not filtered by third-
	 * party plugins.
	 *
	 * Fetching raw values for all custom fields will not have a big performance impact, because
	 * WordPress gets all meta values, when the first meta value is accessed.
	 *
	 * @api
	 * @since 2.0.0
	 *
	 * @param string $field_name Optional. The field name for which you want to get the value. If
	 *                           no field name is provided, this function will fetch values for all
	 *                           custom fields. Default empty string.
	 *
	 * @return null|mixed The meta field value(s). Null if no value could be found, an empty array
	 *                    if all fields were requested but no values could be found.
	 */
	public function raw_meta( $field_name = '' ) {
		return $this->fetch_meta( $field_name, [], false );
	}

	/**
	 * Gets an object meta value.
	 *
	 * Returns a meta value or all meta values for all custom fields of an object saved in the object
	 * meta database table.
	 *
	 * Fetching all values is only advised during development, because it can have a big performance
	 * impact, when all filters are applied.
	 *
	 * @api
	 *
	 * @param string $field_name Optional. The field name for which you want to get the value. If
	 *                           no field name is provided, this function will fetch values for all
	 *                           custom fields. Default empty string.
	 * @param array  $args       {
	 *      An array of arguments for getting the meta value. Third-party integrations can use this
	 *      argument to make their API arguments available in Timber. Default empty array.
	 * }
	 * @param bool $apply_filters Whether to apply filtering of meta values. You can also use
	 *             				  the `raw_meta()` method as a shortcut to apply this argument.
	 *                            Default true.
	 *
	 * @return mixed The custom field value or an array of custom field values. Null if no value
	 *               could be found.
	 */
	protected function fetch_meta( $field_name = '', $args = [], $apply_filters = true ) {

		/**
		 * Filters whether to transform a meta value.
		 *
		 * If the filter returns `true`, all meta value will be transformed to Timber/standard PHP objects if possible.
		 *
		 * @api
		 * @since 2.0.0
		 * @example
		 * ```php
		 * // Transforms all meta value.
		 * add_filter( 'timber/meta/transform_value', '__return_true' );
		 * ```
		 *
		 * @param bool $transform_value
		 */
		$transform_value = apply_filters('timber/meta/transform_value', false );

		$args = wp_parse_args( $args, [
			'transform_value' => $transform_value,
		] );

		$object_meta = null;

		$object_type = $this->get_object_type();

		if ( $apply_filters ) {
			/**
			 * Filters object meta data before it is fetched from the database.
			 *
			 * @example
			 * ```php
			 * // Disable fetching meta values.
			 * add_filter( 'timber/post/pre_meta', '__return_false' );
			 *
			 * // Add your own meta data.
			 * add_filter( 'timber/post/pre_meta', function( $post_meta, $post_id, $post ) {
			 *     $post_meta = array_merge( $post_meta, [
			 *         'custom_data_1' => 73,
			 *         'custom_data_2' => 274,
			 *     ] );
			 *
			 *     return $post_meta;
			 * }, 10, 3 );
			 * ```
			 *
			 * @since 2.0.0
			 *
			 * @param string       $object_meta  The field value. Default null. Passing a non-null
			 *                                 value will skip fetching the value from the database
			 *                                 and will use the value from the filter instead.
			 * @param int          $post_id    The post ID.
			 * @param string       $field_name The name of the meta field to get the value for.
			 * @param \Timber\{Object} $object       The Timber object.
			 * @param array        $args       An array of arguments.
			 */
			$object_meta = apply_filters(
				"timber/{$object_type}/pre_meta",
				$object_meta,
				$this->ID,
				$field_name,
				$this,
				$args
			);

			// @todo Remove when deprecated filters will be gone
			if( $object_type !== 'term' ) {
				/**
				 * Filters the value for a post meta field before it is fetched from the database.
				 *
				 * @deprecated 2.0.0, use `timber/{object_type}/pre_meta`
				 */
				$object_meta = apply_filters_deprecated(
					"timber_{$object_type}_get_meta_field_pre",
					[ $object_meta, $this->ID, $field_name, $this ],
					'2.0.0',
					"timber/{$object_type}/pre_meta"
				);

				/**
				 * Filters post meta data before it is fetched from the database.
				 *
				 * @deprecated 2.0.0, use `timber/{object_type}/pre_meta`
				 */
				do_action_deprecated(
					"timber_{$object_type}_get_meta_pre",
					[ $object_meta, $this->ID, $this ],
					'2.0.0',
					"timber/{$object_type}/pre_meta"
				);
			}
		}

		if ( null === $object_meta ) {
			// Fetch values. Auto-fetches all values if $field_name is empty.
			$object_meta = call_user_func_array( "get_{$object_type}_meta", [ $this->ID, $field_name, true ] );

			// Mimick $single argument when fetching all meta values.
			if ( empty( $field_name ) && is_array( $object_meta ) && ! empty( $object_meta ) ) {
				$object_meta = array_map( function( $meta ) {
					if ( 1 === count( $meta ) && isset( $meta[0] ) ) {
						return $meta[0];
					}

					return $meta;
				}, $object_meta );
			}

			// Empty result.
			if ( empty( $object_meta ) ) {
				$object_meta = empty( $field_name ) ? [] : null;
			}
		}

		if ( $apply_filters ) {
			/**
			 * Filters the value for a post meta field.
			 *
			 * This filter is used by the ACF Integration.
			 *
			 * @example
			 * ```php
			 * add_filter( 'timber/post/meta', function( $post_meta, $post_id, $field_name, $post ) {
			 *     if ( 'event' === $post->post_type ) {
			 *         // Do something special.
			 *         $post_meta['foo'] = $post_meta['foo'] . ' bar';
			 *     }
			 *
			 *     return $post_meta;
			 * }, 10, 4 );
			 * ```
			 *
			 * @see   \Timber\Post::meta()
			 * @since 2.0.0
			 *
			 * @param string       $post_meta  The field value.
			 * @param int          $post_id    The post ID.
			 * @param string       $field_name The name of the meta field to get the value for.
			 * @param \Timber\Post $post       The post object.
			 * @param array        $args       An array of arguments.
			 */
			$object_meta = apply_filters(
				"timber/{$object_type}/meta",
				$object_meta,
				$this->ID,
				$field_name,
				$this,
				$args
			);

			// @todo Remove when deprecated filters will be gone
			if( $object_type === 'term' ) {
				/**
				 * Filters the value for a term meta field.
				 *
				 * @deprecated 2.0.0, use `timber/term/meta`
				 */
				$object_meta = apply_filters_deprecated(
					'timber/term/meta/field',
					[ $object_meta, $this->ID, $field_name, $this ],
					'2.0.0',
					'timber/term/meta'
				);
			}

			/**
			 * Filters the value for an object meta field.
			 *
			 * @deprecated 2.0.0, use `timber/{object_type}/meta`
			 */
			$object_meta = apply_filters_deprecated(
				"timber_{$object_type}_get_meta_field",
				[ $object_meta, $this->ID, $field_name, $this ],
				'2.0.0',
				"timber/{$object_type}/meta"
			);

			/**
			 * Filters object meta data fetched from the database.
			 *
			 * @deprecated 2.0.0, use `timber/{object_type}/meta`
			 */
			$object_meta = apply_filters_deprecated(
				"timber_{$object_type}_get_meta",
				[ $object_meta, $this->ID, $this ],
				'2.0.0',
				"timber/{$object_type}/meta"
			);

			// Maybe convert values to Timber objects.
			if ( $args['transform_value'] ) {
				$object_meta = $this->convert($object_meta);
			}

		}

		return $object_meta;
	}

	/**
	 * Finds any WP_Post objects and converts them to Timber\Posts
	 *
	 * @api
	 * @param array|WP_Post $data
	 * @param string $class
	 */
	public function convert( $data ) {
		if ( is_object($data) ) {
			$data = Helper::convert_wp_object($data);
		} else if ( is_array($data) ) {
			$data = array_map([$this, 'convert'], $data);
		}
		return $data;
	}

	/**
	 * Get the base object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return $this->object_type;
	}

}
