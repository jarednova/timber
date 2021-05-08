<?php
/**
 * Integration with Advanced Custom Fields (ACF)
 *
 * @package Timber
 */

namespace Timber\Integrations;

use Timber\Helper;

/**
 * Class used to handle integration with Advanced Custom Fields
 */
class ACF {
	
	public function __construct() {
		add_filter('timber/post/pre_meta', array( __CLASS__, 'post_get_meta_field' ), 10, 5);
		add_filter('timber/post/meta_object_field', array( __CLASS__, 'post_meta_object' ), 10, 3);
		add_filter('timber/term/pre_meta', array( __CLASS__, 'term_get_meta_field' ), 10, 5);
		add_filter('timber/user/pre_meta', array( __CLASS__, 'user_get_meta_field' ), 10, 5);

		/**
		 * Allowed a user to set a meta value
		 *
		 * @deprecated 2.0.0 with no replacement
		 */
		add_filter('timber/term/meta/set', array( $this, 'term_set_meta' ), 10, 4);
	}

	/**
	 * Gets meta value for a post through ACF’s API.
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
			'format_value' => true,
		) );

		return get_field( $field_name, $post_id, $args['format_value'] );
	}

	public static function post_meta_object( $value, $post_id, $field_name ) {
		return get_field_object($field_name, $post_id);
	}

	/**
	 * Gets meta value for a term through ACF’s API.
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
			'format_value' => true,
		) );

		return get_field(
			$field_name,
			$term->taxonomy . '_' . $term->ID,
			$args['format_value']
		);
	}

	/**
	 * @deprecated 2.0.0, with no replacement
	 *
	 * @return mixed
	 */
	public function term_set_meta( $value, $field, $term_id, $term ) {
		$searcher = $term->taxonomy . '_' . $term->ID;
		update_field($field, $value, $searcher);
		return $value;
	}

	/**
	 * Gets meta value for a user through ACF’s API.
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
			'format_value' => true,
		) );

		return get_field( $field_name, 'user_' . $user_id, $args );
	}
}
