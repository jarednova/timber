<?php
/**
 * Integration with Carbon Fields
 *
 * @package Timber
 */

namespace Timber\Integrations;

use Carbon_Fields\Helper\Helper;
use DateTimeImmutable;
use Timber;
use Carbon_Fields\Field\Field;
use Carbon_Fields\Field\Group_Field;

/**
 * Class used to handle integration with Carbon Fields
 */
class CarbonFields {

	public function __construct() {
		add_filter('timber/post/pre_meta', array( __CLASS__, 'post_get_meta_field' ), 10, 5);
		add_filter('timber/post/meta_object_field', array( __CLASS__, 'post_meta_object' ), 10, 3);
		add_filter('timber/term/pre_meta', array( __CLASS__, 'term_get_meta_field' ), 10, 5);
		add_filter('timber/user/pre_meta', array( __CLASS__, 'user_get_meta_field' ), 10, 5);
	}

	/**
	 * Gets meta value for a post through Carbon Fields's API.
	 *
	 * @param string       $value      The field value. Default null.
	 * @param int          $post_id    The post ID.
	 * @param string       $field_name The name of the meta field to get the value for.
	 * @param \Timber\Post $post       The post object.
	 * @param array        $args       An array of arguments.
	 * @return mixed|false
	 */
	public static function post_get_meta_field( $value, $post_id, $field_name, $post, $args ) {

		$args = wp_parse_args( $args, array(
			'convert_value' => true,
		) );

		$value = carbon_get_post_meta( $post_id, $field_name );

		if ( ! $args['convert_value'] ) {
			return $value;
		}

		$field = Helper::get_field('post_meta', null, $field_name);
		return self::get_converted_meta( $value, $field );
	}

	public static function post_meta_object( $value, $post_id, $field_name ) {
		return Helper::get_field('post_meta', null, $field_name);
	}

	/**
	 * Gets meta value for a term through Carbon Fields's API.
	 *
	 * @param string       $value      The field value. Default null.
	 * @param int          $term_id    The term ID.
	 * @param string       $field_name The name of the meta field to get the value for.
	 * @param \Timber\Term $term       The term object.
	 * @param array        $args       An array of arguments.
	 * @return mixed|false
	 */
	public static function term_get_meta_field( $value, $term_id, $field_name, $term, $args ) {

		$args = wp_parse_args( $args, array(
			'convert_value' => true,
		) );

		$value = carbon_get_term_meta( $term_id, $field_name );

		if ( ! $args['convert_value'] ) {
			return $value;
		}

		$field = Helper::get_field('term_meta', null, $field_name);
		return self::get_converted_meta( $value, $field );
	}

	/**
	 * Gets meta value for a user through Carbon Fields's API.
	 *
	 * @param string       $value      The field value. Default null.
	 * @param int          $user_id    The user ID.
	 * @param string       $field_name The name of the meta field to get the value for.
	 * @param \Timber\User $user       The user object.
	 * @param array        $args       An array of arguments.
	 * @return mixed|false
	 */
	public static function user_get_meta_field( $value, $user_id, $field_name, $user, $args ) {

		$args = wp_parse_args( $args, array(
			'convert_value' => true,
		) );

		$value = carbon_get_user_meta( $user_id, $field_name );

		if ( ! $args['convert_value'] ) {
			return $value;
		}

		$field = Helper::get_field('user_meta', null, $field_name);
		return self::get_converted_meta( $value, $field );
	}

	/**
	 * Gets converted values
	 *
	 * @param mixed $value
	 * @param Field $field
	 * @return DateTimeImmutable|\Timber\Image|mixed
	 */
	private static function get_converted_meta( $value, Field $field ) {
		$type = $field->get_type();
		if ( ! in_array( $type, [ 'image', 'file', 'date', 'date_time', 'time', 'media_gallery', 'complex' ] ) ) {
			return $value;
		}

		switch ( $type ) {
			case 'image':
			case 'file':
				return Timber::get_post( $value );
				break;
			case 'media_gallery':
				return array_map( function( $attachment_id ) {
					return Timber::get_post( $attachment_id );
				}, $value );
				break;
			case 'date':
			case 'date_time':
			case 'time':
				return DateTimeImmutable::createFromFormat( $field->get_storage_format(), $value );
				break;
			case 'complex':
				$fields = $field->get_fields();
				foreach ( $value as $group_index => $field_group ) {
					foreach( $field_group as $field_name => $field_value ) {
						if ( $field_name === '_type' ) {
							continue;
						}
						foreach( $fields as $field ) {
							if ( ! $field instanceof Field ) {
								continue;
							}
							if ( $field->get_base_name() !== $field_name ) {
								continue;
							}
							$value[ $group_index ][ $field_name ] = self::get_converted_meta( $field_value, $field );
						}
					}
				}
				return $value;
				break;
		}

		return $value;
	}

}
