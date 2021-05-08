<?php

namespace Timber\Factory;

use Timber\Attachment;
use Timber\CoreInterface;
use Timber\Image;
use Timber\PathHelper;
use Timber\Post;
use Timber\PostArrayObject;
use Timber\PostQuery;

use WP_Query;
use WP_Post;

/**
 * Internal API class for instantiating posts
 */
class PostFactory {
	public function from($params) {
		if (is_int($params) || is_string($params) && is_numeric($params)) {
			return $this->from_id($params);
		}

		if ($params instanceof WP_Query) {
			return $this->from_wp_query($params);
		}

		if (is_object($params)) {
			return $this->from_post_object($params);
		}

		if ($this->is_numeric_array($params)) {
			return new PostArrayObject(array_map([$this, 'from'], $params));
		}

		if (is_array($params) && !empty($params['ID'])) {
			return $this->from_id($params['ID']);
		}

		if (is_array($params)) {
			return $this->from_wp_query(new WP_Query($params));
		}

		return false;
	}

	protected function from_id(int $id) {
		$wp_post = get_post($id);

		if (!$wp_post) {
			return false;
		}

		return $this->build($wp_post);
	}

	protected function from_post_object(object $obj) : CoreInterface {
		if ($obj instanceof CoreInterface) {
			return $obj;
		}

		if ($obj instanceof WP_Post) {
			return $this->build($obj);
		}

		throw new \InvalidArgumentException(sprintf(
			'Expected an instance of Timber\CoreInterface or WP_Post, got %s',
			get_class($obj)
		));
	}

	protected function from_wp_query(WP_Query $query) : Iterable {
		return new PostQuery($query);
	}

	protected function get_post_class(WP_Post $post) : string {
		// Get the user-configured Class Map
		$map = apply_filters( 'timber/post/classmap', [
			'post'       => Post::class,
			'page'       => Post::class,
			// Apply special logic for attachments.
			'attachment' => function(WP_Post $attachment) {
				return $this->is_image($attachment) ? Image::class : Attachment::class;
			},
		] );

		$class = $map[$post->post_type] ?? null;

		// If class is a callable, call it to get the actual class name
		if (is_callable($class)) {
			$class = $class($post);
		}

		// If we don't have a post class by now, fallback on the default class
		return $class ?? Post::class;
	}

	protected function is_image(WP_Post $post) {
		$src   = wp_get_attachment_url( $post->ID );
		$mimes = wp_get_mime_types();
		// Add mime types that Timber recongizes as images, regardless of config
		$mimes['svg'] = 'image/svg+xml';
		$mimes['webp'] = 'image/webp';
		$check = wp_check_filetype( PathHelper::basename( $src ), $mimes );

		$extensions = apply_filters( 'timber/post/image_extensions', [
			'jpg',
			'jpeg',
			'jpe',
			'gif',
			'png',
			'svg',
			'webp',
		] );

		return in_array( $check['ext'], $extensions );
	}

	protected function build(WP_Post $post) : CoreInterface {
		$class = $this->get_post_class($post);

		return $class::build($post);
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
