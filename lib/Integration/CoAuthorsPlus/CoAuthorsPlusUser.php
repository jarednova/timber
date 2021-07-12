<?php

namespace Timber\Integration\CoAuthorsPlus;

use Timber\Timber;
use Timber\User;

class CoAuthorsPlusUser extends User {
	/**
	 * This user's avatar thumbnail
	 *
	 * @var string
	 */
	protected $thumbnail;

	public static function from_guest_author( \stdclass $coauthor ) {
		$user = new static();
		$user->init($coauthor);

		return $user;
	}

	/**
	 * @internal
	 * @param false|object $coauthor co-author object
	 */
	protected function init( $coauthor = false ) {
		parent::init($coauthor);
		$this->id = $this->ID = (int) $coauthor->ID;
		$this->first_name = $coauthor->first_name;
		$this->last_name = $coauthor->last_name;
		$this->user_nicename = $coauthor->user_nicename;
		$this->description = $coauthor->description;

		/**
		 * @property string name
		 */
		$this->display_name = $coauthor->display_name;
		$this->_link = get_author_posts_url(null, $coauthor->user_nicename );
	}

	/**
	 * Get the user's avatar or Gravatar URL.
	 *
	 * @param array $args optional array arg to `get_avatar_url()`
	 * @return string
	 */
	public function avatar( $args = null ) {
		$prefer_gravatar = apply_filters(
			'timber/co_authors_plus/prefer_gravatar',
			false
		);
		if ( $prefer_gravatar ) {
			return get_avatar_url( $this->user_email, $args );
		} else {
			// 96 is the default wordpress avatar size
			return get_the_post_thumbnail_url( $this->id, 96 );
		}
	}
}
