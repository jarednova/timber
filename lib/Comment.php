<?php

namespace Timber;

use WP_Comment;

/**
 * Class Comment
 *
 * The `Timber\Comment` class is used to view the output of comments. 99% of the time this will be
 * in the context of the comments on a post. However you can also fetch a comment directly using its
 * comment ID.
 *
 * @api
 * @example
 * ```php
 * $comment = Timber::get_comment( $comment_id );
 *
 * $context = [
 *     'comment_of_the_day' => $comment
 * ];
 *
 * Timber::render('index.twig', $context);
 * ```
 *
 * ```twig
 * <p class="comment">{{comment_of_the_day.content}}</p>
 * <p class="comment-attribution">- {{comment.author.name}}</p>
 * ```
 *
 * ```html
 * <p class="comment">But, O Sarah! If the dead can come back to this earth and flit unseen around those they loved, I shall always be near you; in the garish day and in the darkest night -- amidst your happiest scenes and gloomiest hours - always, always; and if there be a soft breeze upon your cheek, it shall be my breath; or the cool air fans your throbbing temple, it shall be my spirit passing by.</p>
 * <p class="comment-attribution">- Sullivan Ballou</p>
 * ```
 */
class Comment extends CoreEntity implements CoreInterface, MetaInterface {

	public $object_type = 'comment';

	public static $representation = 'comment';

	/**
	 * @api
	 * @var int
	 */
	public $ID;

	/**
	 * @api
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $comment_approved;

	/**
	 * @api
	 * @var string
	 */
	public $comment_author_email;

	/**
	 * @api
	 * @var string
	 */
	public $comment_content;

	/**
	 * @api
	 * @var string
	 */
	public $comment_date;

	/**
	 * @api
	 * @var int
	 */
	public $comment_ID;

	/**
	 * @var int
	 */
	public $comment_parent;

	/**
	 * @api
	 * @var int
	 */
	public $user_id;

	/**
	 * @api
	 * @var int
	 */
	public $post_id;

	/**
	 * @api
	 * @var string
	 */
	public $comment_author;

	public $_depth = 0;

	protected $children = array();

	/**
	 * Construct a Timber\Comment. This is protected to prevent direct instantiation,
	 * which is no longer supported. Use `Timber::get_comment()` instead.
	 *
	 * @internal
	 */
	protected function __construct() {
	}

	/**
	 * Build a Timber\Comment. Do not call this directly. Use `Timber::get_comment()` instead.
	 *
	 * @internal
	 * @param \WP_Comment $wp_comment a native WP_Comment instance
	 */
	public static function build( WP_Comment $wp_comment ) : self {
		$comment = new static();
		$comment->import($wp_comment);
		$comment->ID = $wp_comment->comment_ID;
		$comment->id = $wp_comment->comment_ID;

		return $comment;
	}

	/**
	 * Gets the content.
	 *
	 * @api
	 * @return string
	 */
	public function __toString() {
		return $this->content();
	}

	/**
	 * @internal
	 * @param integer $cid
	 */
	public function init( $cid ) {
		$comment_data = $cid;
		if ( is_integer($cid) ) {
			$comment_data = get_comment($cid);
		}
		$this->import($comment_data);
		$this->ID = $this->comment_ID;
		$this->id = $this->comment_ID;
	}

	/**
	 * Gets the author.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <h3>Comments by...</h3>
	 * <ol>
	 * {% for comment in post.comments %}
	 *     <li>{{comment.author.name}}, who is a {{comment.author.roles[0]}}</li>
	 * {% endfor %}
	 * </ol>
	 * ```
	 * ```html
	 * <h3>Comments by...</h3>
	 * <ol>
	 *  <li>Jared Novack, who is a contributor</li>
	 *  <li>Katie Ricci, who is a subscriber</li>
	 *  <li>Rebecca Pearl, who is a author</li>
	 * </ol>
	 * ```
	 * @return User
	 */
	public function author() {
		if ( $this->user_id ) {
			return Timber::get_user($this->user_id);
		} else {
			// We can't (and shouldn't) construct a full-blown User object,
			// so just return a stdclass inst with a name
			return (object) [
				'name' => $this->comment_author ?: 'Anonymous',
			];
		}
	}

	/**
	 * Fetches the Gravatar.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <img src="{{comment.avatar(36,template_uri~"/img/dude.jpg")}}" alt="Image of {{comment.author.name}}" />
	 * ```
	 * ```html
	 * <img src="http://gravatar.com/i/sfsfsdfasdfsfa.jpg" alt="Image of Katherine Rich" />
	 * ```
	 * @param int|mixed    $size     Size of avatar.
	 * @param string       $default  Default avatar URL.
	 * @return bool|mixed|string
	 */
	public function avatar( $size = 92, $default = '' ) {
		if ( !get_option('show_avatars') ) {
			return false;
		}
		if ( !is_numeric($size) ) {
			$size = '92';
		}

		$email = $this->avatar_email();

		$args = array('size' => $size, 'default' => $default);
		$args = apply_filters('pre_get_avatar_data', $args, $email);
		if ( isset($args['url']) ) {
			return $args['url'];
		}

		$email_hash = '';
		if ( !empty($email) ) {
			$email_hash = md5(strtolower(trim($email)));
		}
		$host = $this->avatar_host($email_hash);
		$default = $this->avatar_default($default, $email, $size, $host);
		if ( !empty($email) ) {
			$avatar = $this->avatar_out($default, $host, $email_hash, $size);
		} else {
			$avatar = $default;
		}
		return $avatar;
	}

	/**
	 * Gets the content.
	 *
	 * @api
	 * @return string
	 */
	public function content() {
		return trim(apply_filters('comment_text', $this->comment_content));
	}

	/**
	 * Gets the comment children.
	 *
	 * @api
	 * @return array Comments
	 */
	public function children() {
		return $this->children;
	}

	/**
	 * Adds a child.
	 *
	 * @api
	 * @param \Timber\Comment $child_comment Comment child to add.
	 * @return array Comment children.
	 */
	public function add_child( Comment $child_comment ) {
		return $this->children[] = $child_comment;
	}

	/**
	 * Updates the comment depth.
	 *
	 * @api
	 * @param int $depth Level of depth.
	 */
	public function update_depth( $depth = 0 ) {
		$this->_depth = $depth;
		$children = $this->children();
		foreach ( $children as $comment ) {
			$child_depth = $depth + 1;
			$comment->update_depth( $child_depth );
		}
	}

	/**
	 * At what depth is this comment?
	 *
	 * @api
	 * @return int
	 */
	public function depth() {
		return $this->_depth;
	}

	/**
	 * Is the comment approved?
	 *
	 * @api
	 * @example
	 * ```twig
	 * {% if comment.approved %}
	 *   Your comment is good
	 * {% else %}
	 *   Do you kiss your mother with that mouth?
	 * {% endif %}
	 * ```
	 * @return boolean
	 */
	public function approved() {
		return Helper::is_true($this->comment_approved);
	}

	/**
	 * The date for the comment.
	 *
	 * @api
	 * @example
	 * ```twig
	 * {% for comment in post.comments %}
	 * <article class="comment">
	 *   <p class="date">Posted on {{ comment.date }}:</p>
	 *   <p class="comment">{{ comment.content }}</p>
	 * </article>
	 * {% endfor %}
	 * ```
	 * ```html
	 * <article class="comment">
	 *   <p class="date">Posted on September 28, 2015:</p>
	 *   <p class="comment">Happy Birthday!</p>
	 * </article>
	 * ```
	 * @param string $date_format of desired PHP date format (eg "M j, Y").
	 * @return string
	 */
	public function date( $date_format = '' ) {
		$df = $date_format ? $date_format : get_option('date_format');
		$the_date = (string) mysql2date($df, $this->comment_date);
		return apply_filters('get_comment_date ', $the_date, $df);
	}

	/**
	 * What time was the comment posted?
	 *
	 * @api
	 * @example
	 * ```twig
	 * {% for comment in post.comments %}
	 * <article class="comment">
	 *   <p class="date">Posted on {{ comment.date }} at {{comment.time}}:</p>
	 *   <p class="comment">{{ comment.content }}</p>
	 * </article>
	 * {% endfor %}
	 * ```
	 * ```html
	 * <article class="comment">
	 *   <p class="date">Posted on September 28, 2015 at 12:45 am:</p>
	 *   <p class="comment">Happy Birthday!</p>
	 * </article>
	 * ```
	 * @param string $time_format of desired PHP time format (eg "H:i:s").
	 * @return string
	 */
	public function time( $time_format = '' ) {
		$tf = $time_format ? $time_format : get_option('time_format');
		$the_time = (string) mysql2date($tf, $this->comment_date);
		return apply_filters('get_comment_time', $the_time, $tf);
	}

	/**
	 * Gets a comment meta value.
	 *
	 * @api
	 * @deprecated 2.0.0, use `{{ comment.meta('field_name') }}` instead.
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @return mixed The meta field value.
	 */
	public function get_meta_field( $field_name ) {
		Helper::deprecated(
			"{{ comment.get_meta_field('field_name') }}",
			"{{ comment.meta('field_name') }}",
			'2.0.0'
		);

		return $this->meta($field_name);
	}

	/**
	 * Checks if the comment is a child.
	 *
	 * @api
	 * @return bool
	 */
	public function is_child() {
		return $this->comment_parent > 0;
	}

	/**
	 * Gets a comment meta value.
	 *
	 * @api
	 * @deprecated 2.0.0, use `{{ comment.meta('field_name') }}` instead.
	 * @see \Timber\Comment::meta()
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @return mixed The meta field value.
	 */
	public function get_field( $field_name = null ) {
		Helper::deprecated(
			"{{ comment.get_field('field_name') }}",
			"{{ comment.meta('field_name') }}",
			'2.0.0'
		);

		return $this->meta( $field_name );
	}

	/**
	 * Enqueue the WP threaded comments JavaScript, and fetch the reply link for various comments.
	 *
	 * @api
	 * @param string $reply_text Text of the reply link.
	 * @return string
	 */
	public function reply_link( $reply_text = 'Reply' ) {
		if ( is_singular() && comments_open() && get_option('thread_comments') ) {
			wp_enqueue_script('comment-reply');
		}

		// Get the comments depth option from the admin panel
		$max_depth = get_option('thread_comments_depth');

		// Default args
		$args = array(
			'add_below' => 'comment',
			'respond_id' => 'respond',
			'reply_text' => $reply_text,
			'depth' => $this->depth() + 1,
			'max_depth' => $max_depth,
		);

		return get_comment_reply_link($args, $this->ID, $this->post_id);
	}

	/* AVATAR Stuff
	======================= */

	/**
	 * @internal
	 * @return string
	 */
	protected function avatar_email() {
		$id = (int) $this->user_id;
		$user = get_userdata($id);
		if ( $user ) {
			$email = $user->user_email;
		} else {
			$email = $this->comment_author_email;
		}
		return $email;
	}

	/**
	 * @internal
	 * @param string $email_hash
	 * @return string
	 */
	protected function avatar_host( $email_hash ) {
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com';
		} else {
			if ( !empty($email_hash) ) {
				$host = sprintf("http://%d.gravatar.com", (hexdec($email_hash[0]) % 2));
			} else {
				$host = 'http://0.gravatar.com';
			}
		}
		return $host;
	}

	/**
	 * @internal
	 * @todo  what if it's relative?
	 * @param string $default
	 * @param string $email
	 * @param string $size
	 * @param string $host
	 * @return string
	 */
	protected function avatar_default( $default, $email, $size, $host ) {
		if ( substr($default, 0, 1) == '/' ) {
			$default = home_url().$default;
		}

		if ( empty($default) ) {
			$avatar_default = get_option('avatar_default');
			if ( empty($avatar_default) ) {
				$default = 'mystery';
			} else {
				$default = $avatar_default;
			}
		}
		if ( 'mystery' == $default ) {
			$default = $host.'/avatar/ad516503a11cd5ca435acc9bb6523536?s='.$size;
			// ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
		} else if ( 'blank' == $default ) {
			$default = $email ? 'blank' : includes_url('images/blank.gif');
		} else if ( !empty($email) && 'gravatar_default' == $default ) {
			$default = '';
		} else if ( 'gravatar_default' == $default ) {
			$default = $host.'/avatar/?s='.$size;
		} else if ( empty($email) && !strstr($default, 'http://') ) {
			$default = $host.'/avatar/?d='.$default.'&amp;s='.$size;
		}
		return $default;
	}

	/**
	 * @internal
	 * @param string $default
	 * @param string $host
	 * @param string $email_hash
	 * @param string $size
	 * @return mixed
	 */
	protected function avatar_out( $default, $host, $email_hash, $size ) {
		$out = $host.'/avatar/'.$email_hash.'?s='.$size.'&amp;d='.urlencode($default);
		$rating = get_option('avatar_rating');
		if ( !empty($rating) ) {
			$out .= '&amp;r='.$rating;
		}
		return str_replace('&#038;', '&amp;', esc_url($out));
	}

}
