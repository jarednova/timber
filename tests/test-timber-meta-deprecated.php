<?php

use Timber\Comment;
use Timber\Post;
use Timber\Term;
use Timber\User;

use Timber\Integrations\ACF;

/**
 * Class TestTimberMeta
 *
 * @group comments-api
 * @group users-api
 * @group terms-api
 * @group called-post-constructor
 */
class TestTimberMetaDeprecated extends Timber_UnitTestCase {
	public function setUp() {
		parent::setUp();

		remove_filter( 'timber/post/pre_meta', array( ACF::class, 'post_get_meta_field' ) );
		remove_filter( 'timber/post/meta_object_field', array( ACF::class, 'post_meta_object' ) );
		remove_filter( 'timber/term/pre_meta', array( ACF::class, 'term_get_meta_field' ) );
		remove_filter( 'timber/user/pre_meta', array( ACF::class, 'user_get_meta_field' ) );
	}

	/**
	 * @expectedDeprecated timber_post_get_meta_field_pre
	 */
	function testDeprecatedTimberPostGetMetaFieldPreFilter() {
		$filter = function ( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( null, $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_post_get_meta_field_pre', $filter, 10, 4 );

		$post_id = $this->factory->post->create();
		$post    = Timber::get_post( $post_id );

		update_post_meta( $post_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $post->meta( 'name' ) );

		remove_filter( 'timber_post_get_meta_field_pre', $filter );
	}

	/**
	 * @expectedDeprecated timber_post_get_meta_pre
	 */
	function testDeprecatedTimberPostGetMetaPreAction() {
		$action = function ( $meta, $object_id, $object ) {
			$this->assertEquals( null, $meta );
			$this->assertSame( $object->ID, $object_id );
		};

		add_action( 'timber_post_get_meta_pre', $action, 10, 3 );

		$post_id = $this->factory->post->create();
		$post    = Timber::get_post( $post_id );

		update_post_meta( $post_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $post->meta( 'name' ) );

		remove_action( 'timber_post_get_meta_pre', $action );
	}

	/**
	 * @expectedDeprecated timber_post_get_meta_field
	 */
	function testDeprecatedTimberPostGetMetaFieldFilter() {
		$filter = function ( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_post_get_meta_field', $filter, 10, 4 );

		$post_id = $this->factory->post->create();
		$post    = Timber::get_post( $post_id );

		update_post_meta( $post_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $post->meta( 'name' ) );

		remove_filter( 'timber_post_get_meta_field', $filter );
	}

	/**
	 * @expectedDeprecated timber_post_get_meta
	 */
	function testDeprecatedTimberPostGetMetaFilter() {
		$filter = function ( $meta, $object_id, $object ) {
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_post_get_meta', $filter, 10, 3 );

		$post_id = $this->factory->post->create();
		$post    = Timber::get_post( $post_id );

		update_post_meta( $post_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $post->meta( 'name' ) );

		remove_filter( 'timber_post_get_meta', $filter );
	}

	/**
	 * @expectedDeprecated timber_term_get_meta
	 */
	function testDeprecatedTimberTermGetMetaFilter() {
		$filter = function ( $meta, $object_id, $object ) {
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_term_get_meta', $filter, 10, 3 );

		$term_id = $this->factory->term->create();
		$term    = Timber::get_term( $term_id );

		update_term_meta( $term_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $term->meta( 'name' ) );

		remove_filter( 'timber_term_get_meta', $filter );
	}

	/**
	 * @expectedDeprecated timber/term/meta/field
	 */
	function testDeprecatedTimberTermMetaFieldFilter() {
		$filter = function ( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber/term/meta/field', $filter, 10, 4 );

		$term_id = $this->factory->term->create();
		$term    = Timber::get_term( $term_id );

		update_term_meta( $term_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $term->meta( 'name' ) );

		remove_filter( 'timber/term/meta/field', $filter );
	}

	/**
	 * @expectedDeprecated timber_term_get_meta_field
	 */
	function testDeprecatedTimberTermGetMetaFieldFilter() {
		$filter = function ( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_term_get_meta_field', $filter, 10, 4 );

		$term_id = $this->factory->term->create();
		$term    = Timber::get_term( $term_id );

		update_term_meta( $term_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $term->meta( 'name' ) );

		remove_filter( 'timber_term_get_meta_field', $filter );
	}

	/**
	 * @expectedDeprecated timber_user_get_meta_pre
	 */
	function testDeprecatedTimberUserGetMetaPreFilter() {
		$filter = function( $meta, $object_id, $object ) {
			$this->assertEquals( null, $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_user_get_meta_pre', $filter, 10, 3 );

		$user_id = $this->factory->user->create();
		$user    = Timber::get_user( $user_id );

		update_user_meta( $user_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $user->meta( 'name' ) );

		remove_filter( 'timber_user_get_meta_pre', $filter );
	}

	/**
	 * @expectedDeprecated timber_user_get_meta_field_pre
	 */
	function testDeprecatedTimberUserGetMetaFieldPreFilter() {
		$filter = function( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( null, $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_user_get_meta_field_pre', $filter, 10, 4 );

		$user_id = $this->factory->user->create();
		$user    = Timber::get_user( $user_id );

		update_user_meta( $user_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $user->meta( 'name' ) );

		remove_filter( 'timber_user_get_meta_field_pre', $filter );
	}

	/**
	 * @expectedDeprecated timber_user_get_meta
	 */
	function testDeprecatedTimberUserGetMetaFilter() {
		$filter = function( $meta, $object_id, $object ) {
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_user_get_meta', $filter, 10, 3 );

		$user_id = $this->factory->user->create();
		$user    = Timber::get_user( $user_id );

		update_user_meta( $user_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $user->meta( 'name' ) );

		remove_filter( 'timber_user_get_meta', $filter );
	}

	/**
	 * @expectedDeprecated timber_user_get_meta_field
	 */
	function testDeprecatedTimberUserGetMetaFieldFilter() {
		$filter = function( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_user_get_meta_field', $filter, 10, 4 );

		$user_id = $this->factory->user->create();
		$user    = Timber::get_user( $user_id );

		update_user_meta( $user_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $user->meta( 'name' ) );

		remove_filter( 'timber_user_get_meta_field', $filter );
	}

	/**
	 * @expectedDeprecated timber_comment_get_meta_field_pre
	 */
	function testDeprecatedTimberCommentGetMetaFieldPreFilter() {
		$filter = function( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( null, $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_comment_get_meta_field_pre', $filter, 10, 4 );

		$comment_id = $this->factory->comment->create();
		$comment    = Timber\Timber::get_comment( $comment_id );

		update_comment_meta( $comment_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $comment->meta( 'name' ) );

		remove_filter( 'timber_comment_get_meta_field_pre', $filter );
	}

	/**
	 * @expectedDeprecated timber_comment_get_meta_pre
	 */
	function testDeprecatedTimberCommentGetMetaPreAction() {
		$action = function ( $meta, $object_id ) {
			$this->assertEquals( null, $meta );
		};

		add_action( 'timber_comment_get_meta_pre', $action, 10, 2 );

		$comment_id = $this->factory->comment->create();
		$comment    = Timber\Timber::get_comment( $comment_id );

		update_comment_meta( $comment_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $comment->meta( 'name' ) );

		remove_action( 'timber_comment_get_meta_pre', $action );
	}

	/**
	 * @expectedDeprecated timber_comment_get_meta
	 */
	function testDeprecatedTimberCommentGetMetaFilter() {
		$filter = function( $meta, $object_id ) {
			$this->assertEquals( 'A girl has no name.', $meta );

			return $meta;
		};

		add_filter( 'timber_comment_get_meta', $filter, 10, 2 );

		$comment_id = $this->factory->comment->create();
		$comment    = Timber\Timber::get_comment( $comment_id );

		update_comment_meta( $comment_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $comment->meta( 'name' ) );

		remove_filter( 'timber_comment_get_meta', $filter );
	}

	/**
	 * @expectedDeprecated timber_comment_get_meta_field
	 */
	function testDeprecatedTimberCommentGetMetaFieldFilter() {
		$filter = function( $meta, $object_id, $field_name, $object ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			return $meta;
		};

		add_filter( 'timber_comment_get_meta_field', $filter, 10, 4 );

		$comment_id = $this->factory->comment->create();
		$comment = Timber\Timber::get_comment( $comment_id );

		update_comment_meta( $comment_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'A girl has no name.', $comment->meta( 'name' ) );

		remove_filter( 'timber_comment_get_meta_field', $filter );
	}
}
