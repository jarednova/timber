<?php

namespace Timber;

use Timber\Factory\CommentFactory;

/**
 * Class CommentThread
 *
 * This object is a special type of array that hold WordPress comments as `Timber\Comment` objects.
 * You probably won't use this directly. This object is returned when calling `{{ post.comments }}`
 * in Twig.
 *
 * @example
 * ```twig
 * {# single.twig #}
 * <div id="post-comments">
 *   <h4>Comments on {{ post.title }}</h4>
 *   <ul>
 *     {% for comment in post.comments %}
 *       {% include 'comment.twig' %}
 *     {% endfor %}
 *   </ul>
 *   <div class="comment-form">
 *     {{ function('comment_form') }}
 *   </div>
 * </div>
 * ```
 *
 * ```twig
 * {# comment.twig #}
 * <li>
 *   <div>{{ comment.content }}</div>
 *   <p class="comment-author">{{ comment.author.name }}</p>
 *   {{ function('comment_form') }}
 *   <!-- nested comments here -->
 *   {% if comment.children %}
 *     <div class="replies">
 *	     {% for child_comment in comment.children %}
 *         {% include 'comment.twig' with { comment:child_comment } %}
 *       {% endfor %}
 *     </div>
 *   {% endif %}
 * </li>
 * ```
 */
class CommentThread extends \ArrayObject {

	var $post_id;
	var $_orderby = '';
	var $_order = 'ASC';

	/**
	 * Creates a new `Timber\CommentThread` object.
	 *
	 * @param int           $post_id The post ID.
	 * @param array|boolean $args    Optional. An array of arguments or false if initialization
	 *                               should be skipped.
	 */
	public function __construct( $post_id, $args = array() ) {
		parent::__construct();
		$this->post_id = $post_id;
		if ( $args || is_array($args) ) {
			$this->init($args);
		}
	}

	/**
	 * @internal
	 */
	protected function fetch_comments( $args = array() ) {
		$args['post_id'] = $this->post_id;
		$comments = get_comments($args);
		return $comments;
	}

	/**
	 * Gets the number of comments on a post.
	 *
	 * @return int The number of comments on a post.
	 */
	public function mecount() {
		return get_comments_number($this->post_id);
	}

	protected function merge_args( $args ) {
		$base = array('status' => 'approve', 'order' => $this->_order);
		return array_merge($base, $args);
	}

	/**
	 * @internal
	 */
	public function order( $order = 'ASC' ) {
		$this->_order = $order;
		$this->init();
		return $this;
	}

  /**
	 * @internal
	 */
	public function orderby( $orderby = 'wp' ) {
		$this->_orderby = $orderby;
		$this->init();
		return $this;
	}

	/**
	 * Inits the object.
	 *
   * @internal
	 * @param array $args Optional.
	 */
	public function init( $args = array() ) {
		global $overridden_cpage;
		$args = self::merge_args($args);
		$comments = $this->fetch_comments($args);
		$tcs = array();
		if ( '' == get_query_var('cpage') && get_option('page_comments') ) {
			set_query_var('cpage', 'newest' == get_option('default_comments_page') ? get_comment_pages_count() : 1);
			$overridden_cpage = true;
		}
		foreach ( $comments as $key => &$comment ) {
			$factory = new CommentFactory();
			$timber_comment = $factory->from($comment);
			$tcs[$timber_comment->id] = $timber_comment;
		}

		$parents = array();
		$children = array();

		foreach( $tcs as $comment ) {
			if ( $comment->is_child() ) {
				$children[$comment->ID] = $comment;
			} else {
				$parents[$comment->ID] = $comment;
			}
		}

		foreach ( $children as &$comment ) {
			$parent_id = $comment->comment_parent;
			if ( isset($parents[$parent_id]) ) {
				$parents[$parent_id]->add_child($comment);
			}
			if ( isset($children[$parent_id]) ) {
				$children[$parent_id]->add_child($comment);
			}
		}
		//there's something in update_depth that breaks order?

		foreach ( $parents as $comment ) {
			$comment->update_depth();
		}
		$this->import_comments($parents);
	}

	/**
	 * @internal
	 */
	protected function clear() {
		$this->exchangeArray(array());
	}

	/**
	 * @internal
	 */
	protected function import_comments( $arr ) {
		$this->clear();
		$i = 0;
		foreach ( $arr as $comment ) {
			$this[$i] = $comment;
			$i++;
		}
	}

}
