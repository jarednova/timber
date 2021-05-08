<?php

namespace Timber;

/**
 * Class PostCollection
 *
 * PostCollections are internal objects used to hold a collection of posts.
 *
 * @api
 */
class PostCollection extends \ArrayObject {
	/**
	 * PostCollection constructor.
	 *
	 * @api
	 *
	 * @param array  $posts      An array of posts.
	 */
	public function __construct( array $posts = [] ) {
		parent::__construct( $posts, 0, PostsIterator::class );
	}

	/**
	 * @api
	 * @return array
	 */
	public function get_posts() {
		return $this->getArrayCopy();
	}

	/**
	 * @param array $posts
	 * @return array
	 */
	public static function maybe_set_preview( $posts ) {
		// @todo do this in a filter instead?
		if ( is_array($posts) && isset($_GET['preview']) && $_GET['preview']
			   && isset($_GET['preview_id']) && $_GET['preview_id']
			   && current_user_can('edit_post', $_GET['preview_id']) ) {
			// No need to check the nonce, that already happened in _show_post_preview on init

			$preview_id = $_GET['preview_id'];
			foreach ( $posts as &$post ) {
				if ( is_object($post) && $post->ID == $preview_id ) {
					// Based on _set_preview( $post ), but adds import_custom
					$preview = wp_get_post_autosave($preview_id);
					if ( is_object($preview) ) {

						$preview = sanitize_post($preview);

						$post->post_content = $preview->post_content;
						$post->post_title = $preview->post_title;
						$post->post_excerpt = $preview->post_excerpt;
						$post->import_custom($preview_id);

						add_filter('get_the_terms', '_wp_preview_terms_filter', 10, 3);
					}
				}
			}

		}

		return $posts;
	}
}
