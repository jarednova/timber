<?php

	/**
	 * @group called-post-constructor
	 */
	class TestTimberPostType extends Timber_UnitTestCase {

		function testPostTypeObject() {
			$this->restore_locale();
			$obj = get_post_type_object('post');
			$this->assertEquals('Posts', $obj->labels->name);
		}

		function testPostTypeProperty(){
			$post_id = $this->factory->post->create();
			$post = Timber::get_post($post_id);
			$this->assertEquals('post', $post->post_type);
		}

		/**
		 * @ticket #2111
		 */
		function testNonExistentPostType() {
			$post_type = new Timber\PostType('foobar');
			$this->assertEquals('foobar', $post_type);
			$this->assertEquals('Timber\PostType', get_class($post_type));
		}

		function testPostTypeMethodInTwig() {
			$post_id = $this->factory->post->create();
			$post = Timber::get_post($post_id);
			$template = '{{post.post_type}}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('post', $str);
		}

		function testTypeMethodInTwig() {
			$post_id = $this->factory->post->create();
			$post = Timber::get_post($post_id);
			$template = '{{post.type}}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('post', $str);
		}

		function testTypeMethodInTwigLabels() {
			$post_id = $this->factory->post->create();
			$post = Timber::get_post($post_id);
			$template = '{{post.type.labels.name}}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Posts', $str);
		}
	}
