<?php

use Timber\Integrations\CarbonFields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * @group users-api
 * @group comments-api
 * @group integrations
 * @group posts-api
 */
class TestTimberIntegrationCarbonFields extends Timber_UnitTestCase {

	function testCarbonFieldsInit() {
		$carbon_fields = new CarbonFields();
		$this->assertInstanceOf('Timber\Integrations\CarbonFields', $carbon_fields);
	}

	function testCarbonFieldsGetPostMeta() {
		$field_name = 'my_post_text';
		$this->register_post_field($field_name, 'text');
		$post_id = $this->factory->post->create();
		carbon_set_post_meta($post_id, $field_name, 'foo');
		$post = Timber::get_post($post_id);
		$this->assertEquals('foo', $post->meta($field_name));
	}

	function testCarbonFieldsGetTermMeta() {
		$field_name = 'my_term_text';
		$this->register_term_field($field_name, 'text');
		$term_id = $this->factory->term->create();
		carbon_set_term_meta($term_id, $field_name, 'foo');
		$term = Timber::get_term($term_id);
		$this->assertEquals('foo', $term->meta($field_name));
	}

	function testCarbonFieldsGetUserMeta() {
		$field_name = 'my_user_text';
		$this->register_user_field($field_name, 'text');
		$user_id = $this->factory->user->create();
		carbon_set_user_meta($user_id, $field_name, 'foo');
		$user = Timber::get_user($user_id);
		$this->assertEquals('foo', $user->meta($field_name));
	}

	function testCarbonFieldsGetTimberImage() {
		$field_name = 'my_post_image';
		$this->register_post_field($field_name, 'image');
		$post_id = $this->factory->post->create();
		$image_id = TimberAttachment_UnitTestCase::get_attachment($post_id);
		carbon_set_post_meta($post_id, $field_name, $image_id);
		$post = Timber::get_post($post_id);
		$image = $post->meta($field_name);
		$this->assertInstanceOf('Timber\Image', $image);
		$this->assertEquals($image_id, $image->ID);
	}

	function testCarbonFieldsGetTimberImageGallery() {
		$field_name = 'my_post_gallery';
		$this->register_post_field($field_name, 'media_gallery');
		$post_id = $this->factory->post->create();
		$image_1_id = TimberAttachment_UnitTestCase::get_attachment($post_id);
		$image_2_id = TimberAttachment_UnitTestCase::get_attachment($post_id, 'arch-2night.jpg');
		carbon_set_post_meta($post_id, $field_name, [$image_1_id, $image_2_id]);
		$post = Timber::get_post($post_id);
		$images = $post->meta($field_name);

		$this->assertTrue(is_array($images));
		$this->assertEquals(2, count($images));
		foreach($images as $image) {
			$this->assertInstanceOf('Timber\Image', $image);
		}
		$this->assertEquals($image_1_id, $images[0]->ID);
		$this->assertEquals($image_2_id, $images[1]->ID);
	}

	function testCarbonFieldsGetDateTimeImmutableFromDate() {
		$field_name = 'my_post_date';
		$this->register_post_field($field_name, 'date');
		$post_id = $this->factory->post->create();
		carbon_set_post_meta($post_id, $field_name, '2021-02-22');
		$post = Timber::get_post($post_id);
		$date = $post->meta($field_name);
		$this->assertInstanceOf('DateTimeImmutable', $date);
		$this->assertEquals('2021-02-22', $date->format('Y-m-d'));
	}

	function testCarbonFieldsGetDateTimeImmutableFromDateWithCustomFormat() {
		$field_name = 'my_post_date_with_custom_format';
		Container::make( 'post_meta', 'Custom Data' )
			->where( 'post_type', '=', 'post' )
			->add_fields( [
				Field::make( 'date', $field_name )->set_storage_format('dYm'),
			] );

		$post_id = $this->factory->post->create();
		carbon_set_post_meta($post_id, $field_name, '22202102');

		$post = Timber::get_post($post_id);
		$date = $post->meta($field_name);
		$this->assertInstanceOf('DateTimeImmutable', $date);
		$this->assertEquals('2021-02-22', $date->format('Y-m-d'));
	}

	function testCarbonFieldsGetDateTimeImmutableFromDateTime() {
		$field_name = 'my_post_date_time';
		$this->register_post_field($field_name, 'date_time');
		$post_id = $this->factory->post->create();
		carbon_set_post_meta($post_id, $field_name, '2021-02-22 17:30:25');
		$post = Timber::get_post($post_id);
		$date = $post->meta($field_name);
		$this->assertInstanceOf('DateTimeImmutable', $date);
		$this->assertEquals('2021-02-22 17:30:25', $date->format('Y-m-d H:i:s'));
	}

	function testCarbonFieldsGetDateTimeImmutableFromTime() {
		$field_name = 'my_post_time';
		$this->register_post_field($field_name, 'time');
		$post_id = $this->factory->post->create();
		carbon_set_post_meta($post_id, $field_name, '17:30:25');
		$post = Timber::get_post($post_id);
		$date = $post->meta($field_name);
		$this->assertInstanceOf('DateTimeImmutable', $date);
		$this->assertEquals('17:30:25', $date->format('H:i:s'));
	}

	function testCarbonFieldsGetMetaUntransformed() {
		$field_name = 'my_post_date_time';
		$this->register_post_field($field_name, 'date_time');
		$post_id = $this->factory->post->create();
		carbon_set_post_meta($post_id, $field_name, '2021-02-22 17:30:25');
		$post = Timber::get_post($post_id);
		$date = $post->meta($field_name, ['convert_value' => false]);
		$this->assertEquals('2021-02-22 17:30:25', $date);
	}

	function testCarbonFieldsGetComplex() {
		$field_name = 'my_post_slides';
		Container::make( 'post_meta', 'Post meta' )
        ->where( 'post_type', '=', 'post' )
        ->add_fields( array(
			Field::make( 'complex', $field_name )
				->add_fields( array(
                    Field::make( 'image', 'my_image', 'Image' ),
					Field::make( 'complex', 'my_slide_captions' )
						->add_fields( array(
							Field::make( 'text', 'my_title', 'Title' ),
							Field::make( 'date', 'my_date', 'Date' ),
							Field::make( 'date_time', 'my_image', 'Image' ),
						))
				)),
		) );
		$post_id = $this->factory->post->create();
		$image_1_id = TimberAttachment_UnitTestCase::get_attachment($post_id);
		$image_2_id = TimberAttachment_UnitTestCase::get_attachment($post_id, 'arch-2night.jpg');
		carbon_set_post_meta($post_id, $field_name, [
			[
				'my_image' => $image_1_id,
				'my_slide_captions' => [
					[
						'my_title' => 'Title 1',
						'my_date' => '2021-02-22',
						'my_image' => '2021-02-22 17:30:25',
					],
					[
						'my_title' => 'Title 2',
						'my_date' => '2017-02-22',
						'my_image' => '2021-02-22 17:30:25',
					],
				]
			],
			[
				'my_image' => $image_2_id,
				'my_slide_captions' => [
					[
						'my_title' => 'Title 3',
						'my_date' => '2021-12-14',
						'my_image' => '2021-02-22 17:30:25',
					],
					[
						'my_title' => 'Title 4',
						'my_date' => '2020-12-14',
						'my_image' => '2021-02-22 17:30:25',
					],
				]
			],
		]);
		$post = Timber::get_post($post_id);
		$slides = $post->meta($field_name);

		$this->assertInstanceOf('DateTimeImmutable', $slides[0]['my_slide_captions'][0]['my_date']);
		$this->assertEquals('2021-02-22', $slides[0]['my_slide_captions'][0]['my_date']->format('Y-m-d'));

		$this->assertInstanceOf('DateTimeImmutable', $slides[0]['my_slide_captions'][0]['my_image']);
		$this->assertEquals('2021-02-22 17:30:25', $slides[0]['my_slide_captions'][0]['my_image']->format('Y-m-d H:i:s'));

		$this->assertInstanceOf('Timber\Image', $slides[1]['my_image']);
		$this->assertEquals($image_2_id, $slides[1]['my_image']->ID);
	}

	private function register_post_field($name, $type) {
		return Container::make( 'post_meta', 'Post meta' )
			->where( 'post_type', '=', 'post' )
			->add_fields( [
				Field::make( $type, $name ),
			] );
	}

	private function register_term_field($name, $type) {
		Container::make( 'term_meta', 'Term meta' )
			->where( 'term_taxonomy', '=', 'category' )
			->add_fields( [
				Field::make( $type, $name ),
			] );
	}

	private function register_user_field($name, $type) {
		Container::make( 'user_meta', 'User meta' )
			->add_fields( [
				Field::make( $type, $name ),
			] );
	}

}
