<?php

namespace Timber\Factory;

use Timber\CoreInterface;
use Timber\Comment;

use WP_Comment_Query;
use WP_Comment;

/**
 * Internal API class for instantiating Comments
 */
class CommentFactory {
	public function from($params) {
		if (is_int($params) || is_string($params) && is_numeric($params)) {
			return $this->from_id((int) $params);
		}

		if ($params instanceof WP_Comment_Query) {
			return $this->from_wp_comment_query($params);
		}

		if (is_object($params)) {
			return $this->from_comment_object($params);
		}

		if ($this->is_numeric_array($params)) {
			return array_map([$this, 'from'], $params);
		}

		if (is_array($params)) {
			return $this->from_wp_comment_query(new WP_Comment_Query($params));
		}
	}

	protected function from_id(int $id) {
		$wp_comment = get_comment($id);

		if (!$wp_comment) {
			return false;
		}

		return $this->build($wp_comment);
	}

	protected function from_comment_object(object $comment) : CoreInterface {
		if ($comment instanceof CoreInterface) {
			// We already have some kind of Timber Core object
			return $comment;
		}

		if ($comment instanceof WP_Comment) {
			return $this->build($comment);
		}

		throw new \InvalidArgumentException(sprintf(
			'Expected an instance of Timber\CoreInterface or WP_Comment, got %s',
			get_class($comment)
		));
	}

	protected function from_wp_comment_query(WP_Comment_Query $query) : Iterable {
		return array_map([$this, 'build'], $query->get_comments());
	}

	protected function get_comment_class(WP_Comment $comment) : string {
		// Get the user-configured Class Map
		$map = apply_filters( 'timber/comment/classmap', []);

		$type  = get_post_type($comment->comment_post_ID);
		$class = $map[$type] ?? null;

		if (is_callable($class)) {
			$class = $class($comment);
		}

		// If we don't have a Comment class by now, fallback on the default class
		return $class ?? Comment::class;
	}

	protected function build(WP_Comment $comment) : CoreInterface {
		$class = $this->get_comment_class($comment);

		return $class::build($comment);
	}

	protected function is_numeric_array($arr) {
		if ( ! is_array($arr) ) {
			return false;
		}
		foreach (array_keys($arr) as $k) {
			if ( ! is_int($k) ) return false;
		}
		return true;
	}
}
