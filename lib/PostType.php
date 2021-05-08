<?php

namespace Timber;

/**
 * Wrapper for the post_type object provided by WordPress
 * @since 1.0.4
*/
class PostType {

	/**
	 * @var string
	 */
	private $slug;

	/**
	 * @param string $post_type
	 */
	public function __construct( $post_type ) {
		$this->slug = $post_type;
		$this->init($post_type);
	}

	public function __toString() {
		return $this->slug;
	}

	protected function init( $post_type ) {
		$obj = get_post_type_object($post_type);
		if ( ! empty( $obj ) ) {
			foreach ( get_object_vars($obj) as $key => $value ) {
				if ( $key === '' || ord($key[0]) === 0 ) {
					continue;
				}
				$this->$key = $value;
			}
		}
	}
}
