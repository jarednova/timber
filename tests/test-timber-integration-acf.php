<?php

use Timber\Integrations\ACF;
use Timber\User;

/**
 * @group users-api
 * @group comments-api
 * @group integrations
 * @group posts-api
 */
class TestTimberIntegrationACF extends Timber_UnitTestCase {
	
	function testACFInit() {
		$acf = new ACF();
		$this->assertInstanceOf( 'Timber\Integrations\ACF', $acf );
	}

	function testACFGetFieldPost() {
		$post_id = $this->factory->post->create();
		update_field( 'subhead', 'foobar', $post_id );
		$str = '{{post.meta("subhead")}}';
		$post = Timber::get_post( $post_id );
		$str = Timber::compile_string( $str, array( 'post' => $post ) );
		$this->assertEquals( 'foobar', $str );
	}

	function testACFHasFieldPostFalse() {
		$post_id = $this->factory->post->create();
		$str = '{% if post.has_field("heythisdoesntexist") %}FAILED{% else %}WORKS{% endif %}';
		$post = Timber::get_post( $post_id );
		$str = Timber::compile_string( $str, array( 'post' => $post ) );
		$this->assertEquals('WORKS', $str);
	}

	function testACFHasFieldPostTrue() {
		$post_id = $this->factory->post->create();
		update_post_meta($post_id, 'best_radiohead_album', 'in_rainbows');
		$str = '{% if post.has_field("best_radiohead_album") %}In Rainbows{% else %}OK Computer{% endif %}';
		$post = Timber::get_post( $post_id );
		$str = Timber::compile_string( $str, array( 'post' => $post ) );
		$this->assertEquals('In Rainbows', $str);
	}

	function testACFGetFieldTermCategory() {
		$tid = $this->factory->term->create();
		update_field( 'color', 'blue', "category_${tid}" );
		$cat = Timber::get_term( $tid );
		$this->assertEquals( 'blue', $cat->color );
		$str = '{{term.color}}';
		$this->assertEquals( 'blue', Timber::compile_string( $str, array( 'term' => $cat ) ) );
	}

	function testACFCustomFieldTermTag() {
		$tid = $this->factory->term->create();
		update_field( 'color', 'green', 'post_tag_'.$tid );
		$term = Timber::get_term( $tid );
		$str = '{{term.color}}';
		$this->assertEquals( 'green', Timber::compile_string( $str, array( 'term' => $term ) ) );
	}

	function testACFGetFieldTermTag() {
		$tid = $this->factory->term->create();
		update_field( 'color', 'blue', 'post_tag_'.$tid );
		$term = Timber::get_term( $tid );
		$str = '{{term.meta("color")}}';
		$this->assertEquals( 'blue', Timber::compile_string( $str, array( 'term' => $term ) ) );
	}

	function testACFFieldObject() {
		$key = 'field_5ba2c660ed26d';

		$fp_id = $this->factory->post->create( [
			'post_content' => 'a:10:{s:4:"type";s:4:"text";s:12:"instructions";s:0:"";s:8:"required";i:0;s:17:"conditional_logic";i:0;s:7:"wrapper";a:3:{s:5:"width";s:0:"";s:5:"class";s:0:"";s:2:"id";s:0:"";}s:13:"default_value";s:0:"";s:11:"placeholder";s:0:"";s:7:"prepend";s:0:"";s:6:"append";s:0:"";s:9:"maxlength";s:0:"";}',
			'post_title'   => 'Thinger',
			'post_name'    => $key,
			'post_type'    => 'acf-field',
		] );

		$post_id = $this->factory->post->create();

		update_field( 'thinger', 'foo', $post_id );
		update_field( '_thinger', $key, $post_id );

		$post     = Timber::get_post($post_id);
		$template = '{{ post.meta("thinger") }} / {{ post.field_object("thinger").key }}';
		$str      = Timber::compile_string($template, array( 'post' => $post ));

		$this->assertEquals('foo / '.$key, $str);
	}

	function testACFFormatValue() {
		acf_add_local_field_group( array(
			'key'      => 'group_1',
			'title'    => 'Group 1',
			'fields'   => [
				[
					'key'   => 'field_1',
					'label' => 'Lead',
					'name'  => 'lead',
					'type'  => 'wysiwyg',
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					],
				],
			],
		) );

		$post_id = $this->factory->post->create();
		$post    = Timber::get_post( $post_id );
		update_field( 'lead', 'Murder Spagurders are dangerous sneks.', $post_id );

		$string = trim( Timber::compile_string( "{{ post.meta('lead') }}", [ 'post' => $post ] ) );
		$this->assertEquals( '<p>Murder Spagurders are dangerous sneks.</p>', $string );

		$string = trim( Timber::compile_string( "{{ post.meta('lead', { format_value: false }) }}", [ 'post' => $post ] ) );
		$this->assertEquals( 'Murder Spagurders are dangerous sneks.', $string );
	}

	function testACFTranformImage() {
		$field_name = 'my_image_meta';
		$this->register_field($field_name, 'image');

		$post_id = $this->factory->post->create();
		$image_id = TimberAttachment_UnitTestCase::get_attachment($post_id);
		update_field( $field_name, $image_id, $post_id );
		$post = Timber::get_post( $post_id );

		$image = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\Image', $image);
		$this->assertEquals($image_id, $image->ID);
	}

	function testACFTransformFilter() {
		$field_name = 'my_image_meta';
		$this->register_field($field_name, 'image');

		$post_id = $this->factory->post->create();
		$image_id = TimberAttachment_UnitTestCase::get_attachment($post_id);
		update_field( $field_name, $image_id, $post_id );
		$post = Timber::get_post( $post_id );

		add_filter('timber/meta/transform_value', '__return_true');

		$image = $post->meta($field_name);
		$this->assertInstanceOf('Timber\Image', $image);
		$this->assertEquals($image_id, $image->ID);
	}

	function testACFNoTransformImage() {
		$field_name = 'my_image_meta_no_convert';
		$this->register_field($field_name, 'image');

		$post_id = $this->factory->post->create();
		$image_id = TimberAttachment_UnitTestCase::get_attachment($post_id);
		update_field( $field_name, $image_id, $post_id );
		$post = Timber::get_post( $post_id );

		$image = $post->meta($field_name, ['transform_value' => false]);
		$this->assertTrue(is_array($image));
		$this->assertEquals($image['id'], $image_id);
	}

	function testACFTransformImageCustomReturnFormat() {
		$field_name = 'my_image_meta_custom_return_format';
		$this->register_field($field_name, 'image', ['return_format' => 'id']);

		$post_id = $this->factory->post->create();
		$image_id = TimberAttachment_UnitTestCase::get_attachment($post_id);
		update_field( $field_name, $image_id, $post_id );
		$post = Timber::get_post( $post_id );

		$image = $post->meta($field_name, ['transform_value' => false]);

		$this->assertTrue(is_numeric($image));
		$this->assertEquals($image, $image_id);
	}

	function testACFTransformDatePicker() {
		$field_name = 'my_date_meta';
		$this->register_field($field_name, 'date_picker');

		$post_id = $this->factory->post->create();
		update_field( $field_name, '20210222', $post_id );
		$post = Timber::get_post( $post_id );

		$date = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('DateTimeImmutable', $date);
		$this->assertEquals('2021-02-22', $date->format('Y-m-d'));
	}

	function testACFTransformDateTimePicker() {
		$field_name = 'my_date_time_meta';
		$this->register_field($field_name, 'date_time_picker');

		$post_id = $this->factory->post->create();
		update_field( $field_name, '2021-02-22 17:30:25', $post_id );
		$post = Timber::get_post( $post_id );

		$date_time = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('DateTimeImmutable', $date_time);
		$this->assertEquals('2021-02-22 17:30:25', $date_time->format('Y-m-d H:i:s'));
	}

	function testACFTransformPostObject() {
		$field_name = 'my_post_object_meta';
		$this->register_field($field_name, 'post_object');

		$post_id = $this->factory->post->create();
		$related_post_id = $this->factory->post->create();
		update_field( $field_name, $related_post_id, $post_id );
		$post = Timber::get_post( $post_id );

		$related_post = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\Post', $related_post);
		$this->assertEquals($related_post_id, $related_post->ID);
	}

	function testACFTransformPostObjectMultiple() {
		$field_name = 'my_post_object_multiple_meta';
		$this->register_field($field_name, 'post_object', ['multiple' => true]);

		$post_id = $this->factory->post->create();
		$related_post_1_id = $this->factory->post->create();
		$related_post_2_id = $this->factory->post->create();
		update_field( $field_name, [$related_post_1_id, $related_post_2_id], $post_id );
		$post = Timber::get_post( $post_id );

		$related_posts = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\PostArrayObject', $related_posts);
		$this->assertEquals($related_post_2_id, $related_posts[1]->ID);
	}

	function testACFTransformRelationship() {
		$field_name = 'my_relationship_meta';
		$this->register_field($field_name, 'relationship');

		$post_id = $this->factory->post->create();
		$related_post_1_id = $this->factory->post->create();
		$related_post_2_id = $this->factory->post->create();
		update_field( $field_name, [$related_post_1_id, $related_post_2_id], $post_id );
		$post = Timber::get_post( $post_id );

		$related_posts = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\PostArrayObject', $related_posts);
		$this->assertEquals($related_post_2_id, $related_posts[1]->ID);
	}

	function testACFTransformTaxonomy() {
		$field_name = 'my_taxonomy_meta';
		$this->register_field($field_name, 'taxonomy');

		$post_id = $this->factory->post->create();
		$term_id = $this->factory->term->create();
		update_field( $field_name, $term_id, $post_id );
		$post = Timber::get_post( $post_id );

		$term = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\Term', $term[0]);
		$this->assertEquals($term_id, $term[0]->ID);
	}

	function testACFTransformTaxonomyMultiple() {
		$field_name = 'my_taxonomy_multiple';
		$this->register_field($field_name, 'taxonomy', ['field_type' => 'radio']);

		$post_id = $this->factory->post->create();
		$term_id = $this->factory->term->create();
		update_field( $field_name, $term_id, $post_id );
		$post = Timber::get_post( $post_id );

		$term = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\Term', $term);
		$this->assertEquals($term_id, $term->ID);
	}

	function testACFTransformUser() {
		$field_name = 'my_user_meta';
		$this->register_field($field_name, 'user');

		$post_id = $this->factory->post->create();
		$user_id = $this->factory->user->create();
		update_field( $field_name, $user_id, $post_id );
		$post = Timber::get_post( $post_id );

		$user = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\User', $user);
		$this->assertEquals($user_id, $user->ID);
	}

	function testACFTransformUserMultiple() {

		add_filter( 'timber/user/classmap', function( $class, \WP_User $user ) {
    		return User::class;
		}, 100, 2 );

		$field_name = 'my_user_multiple_meta';
		$this->register_field($field_name, 'user', ['multiple' => true]);

		$post_id = $this->factory->post->create();
		$user_1_id = $this->factory->user->create();
		$user_2_id = $this->factory->user->create();
		update_field( $field_name, [$user_1_id, $user_2_id], $post_id );
		$post = Timber::get_post( $post_id );

		$users = $post->meta($field_name, ['transform_value' => true]);
		$this->assertInstanceOf('Timber\User', $users[0]);
		$this->assertEquals($user_2_id, $users[1]->ID);
	}

	/**
	 * @expectedDeprecated {{ post.get_field('field_name') }}
	 */
	function testPostGetFieldDeprecated() {
		$post_id = $this->factory->post->create();
		$post    = Timber::get_post( $post_id );

		$post->get_field( 'field_name' );
	}

	/**
	 * @expectedDeprecated {{ term.get_field('field_name') }}
	 */
	function testTermGetFieldDeprecated() {
		$term_id = $this->factory->term->create();
		$term    = Timber::get_term( $term_id );

		$term->get_field( 'field_name' );
	}

	/**
	 * @expectedDeprecated {{ user.get_field('field_name') }}
	 */
	function testUserGetFieldDeprecated() {
		$user_id = $this->factory->user->create();
		$user    = Timber::get_user( $user_id );

		$user->get_field( 'field_name' );
	}

	/**
	 * @expectedDeprecated {{ comment.get_field('field_name') }}
	 */
	function testCommentGetFieldDeprecated() {
		$comment_id = $this->factory->comment->create();
		$comment    = Timber\Timber::get_comment( $comment_id );

		$comment->get_field( 'field_name' );
	}

	function testACFContentField() {
		$post_id = $this->factory->post->create(array('post_content' => 'Cool content bro!'));
		update_field( 'content', 'I am custom content', $post_id );
		update_field( '_content', 'I am also custom content', $post_id );
		$str = '{{ post.content }}';
		$post = Timber::get_post( $post_id );
		$str = Timber::compile_string( $str, array( 'post' => $post ) );
		$this->assertEquals( '<p>Cool content bro!</p>', trim($str) );
	}

	private function register_field( $field_name, $field_type, $field_args = [] ) {

		$group_key = sprintf('group_%s', uniqid());

		$field = array_merge([
			'key'   => 'field_2',
			'label' => 'Field',
			'name'  => $field_name,
			'type'  => $field_type,
		], $field_args);

		acf_add_local_field_group( array(
			'key'      => $group_key,
			'title'    => 'Group',
			'fields'   => [
				$field,
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					],
				],
			],
		) );
	}
}
