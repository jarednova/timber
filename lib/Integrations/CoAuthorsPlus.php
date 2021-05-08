<?php

namespace Timber\Integrations;

class CoAuthorsPlus {

	public static $prefer_gravatar = false;
	
	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		add_filter('timber/post/authors', array($this, 'authors'), 10, 2);

		add_filter( 'timber/user/classmap', function( $class, \WP_User $user ) {
    		return CoAuthorsPlusUser::class;
		}, 10, 2 );
	}

	/**
	 * Filters {{ post.authors }} to return authors stored from Co-Authors Plus
	 * @since 1.1.4
	 * @param array $author
	 * @param \Timber\Post $post
	 * @return array of User objects
	 */
	public function authors( $author, $post ) {
		$authors = array();
		$cauthors = get_coauthors($post->ID);
		foreach ( $cauthors as $author ) {
			$uid = $this->get_user_uid( $author );
			if ( $uid ) {
				$authors[] = \Timber\Timber::get_user($uid);
			} else {
				$wp_user = new \WP_User($author);
				$user = \Timber\Timber::get_user($wp_user);
				$user->import($wp_user->data);
				unset($user->user_pass);
				$user->id = $user->ID = (int) $wp_user->ID;
				$authors[] = $user;
			}
		}
		return $authors;
	}

	/**
	 * return the user id for normal authors
	 * the user login for guest authors if it exists and self::prefer_users == true
	 * or null
	 * @internal
	 * @param object $cauthor
	 * @return int|null
	 */
	protected function get_user_uid( $cauthor ) {
		// if is guest author
		if( is_object($cauthor) && isset($cauthor->type) && $cauthor->type == 'guest-author'){
			// if have have a linked user account
			global $coauthors_plus;
			if( !$coauthors_plus->force_guest_authors && isset($cauthor->linked_account) && !empty($cauthor->linked_account ) ){
				$wp_user = get_user_by('slug', $cauthor->linked_account);
				return $wp_user->ID;
			} else {
				return null;
			}
		} else {
			return $cauthor->ID;
		}
	}
}
