<?php

use Timber\Post;

/**
 * Implements custom JSON serialization.
 * FOR BRITISH EYES ONLY!
 */
class SerializablePost extends Post implements JsonSerializable {
	public function jsonSerialize() {
		return [
			'post_title'     => $this->title(),
			'post_type'      => $this->post_type,
			'how_many_of_us' => $this->meta('how_many_of_us'),
		];
	}
}