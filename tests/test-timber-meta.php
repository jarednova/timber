<?php

use Timber\Comment;
use Timber\Post;
use Timber\Term;
use Timber\Timber;
use Timber\User;

use Timber\Integrations\ACF;

/**
 * Class TestTimberMeta
 *
 * @group comments-api
 * @group users-api
 * @group terms-api
 */
class TestTimberMeta extends Timber_UnitTestCase {
	/**
	 * Function hit helper.
	 *
	 * @var bool
	 */
	protected $is_get_post_meta_hit;
	protected $is_get_term_meta_hit;
	protected $is_get_comment_meta_hit;

	public function setUp() {
		parent::setUp();

		require_once 'php/MetaPost.php';
		require_once 'php/MetaTerm.php';
		require_once 'php/MetaUser.php';
		require_once 'php/MetaComment.php';

		remove_filter( 'timber/post/pre_meta', array( ACF::class, 'post_get_meta_field' ) );
		remove_filter( 'timber/post/meta_object_field', array( ACF::class, 'post_meta_object' ) );
		remove_filter( 'timber/term/pre_meta', array( ACF::class, 'term_get_meta_field' ) );
		remove_filter( 'timber/user/pre_meta', array( ACF::class, 'user_get_meta_field' ) );
	}

	/**
	 * Tests accessing all meta values instead of only one meta value.
	 */
	function testAllMeta() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		update_post_meta( $post_id, 'meta1', 'Meta 1' );
		update_post_meta( $post_id, 'meta2', 'Meta 2' );
		update_term_meta( $term_id, 'meta1', 'Meta 1' );
		update_term_meta( $term_id, 'meta2', 'Meta 2' );
		update_user_meta( $user_id, 'meta1', 'Meta 1' );
		update_user_meta( $user_id, 'meta2', 'Meta 2' );
		update_comment_meta( $comment_id, 'meta1', 'Meta 1' );
		update_comment_meta( $comment_id, 'meta2', 'Meta 2' );

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$this->assertEquals( 'Meta 1', $post->meta()['meta1'] );
		$this->assertEquals( 'Meta 2', $post->meta()['meta2'] );

		$this->assertEquals( 'Meta 1', $term->meta()['meta1'] );
		$this->assertEquals( 'Meta 2', $term->meta()['meta2'] );

		$this->assertEquals( 'Meta 1', $user->meta()['meta1'] );
		$this->assertEquals( 'Meta 2', $user->meta()['meta2'] );

		$this->assertEquals( 'Meta 1', $comment->meta()['meta1'] );
		$this->assertEquals( 'Meta 2', $comment->meta()['meta2'] );
	}

	function testMetaReturnsNullWhenResultIsEmpty() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$this->assertEquals( null, $post->meta( 'not_found' ) );
		$this->assertEquals( null, $term->meta( 'not_found' ) );
		$this->assertEquals( null, $user->meta( 'not_found' ) );
		$this->assertEquals( null, $comment->meta( 'not_found' ) );
	}

	function testPreMetaFilter() {
		$filter = function ( $meta, $object_id, $field_name, $object, $args ) {
			if ( 'filtered_meta' === $field_name ) {
				return 'Only I should exist.';
			}

			return $meta;
		};

		$this->add_filter_temporarily( 'timber/post/pre_meta', $filter, 10, 5 );
		$this->add_filter_temporarily( 'timber/term/pre_meta', $filter, 10, 5 );
		$this->add_filter_temporarily( 'timber/user/pre_meta', $filter, 10, 5 );
		$this->add_filter_temporarily( 'timber/comment/pre_meta', $filter, 10, 5 );

		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		update_post_meta( $post_id, 'filtered_meta', 'I shouldn’t exist later.' );
		update_term_meta( $term_id, 'filtered_meta', 'I shouldn’t exist later.' );
		update_user_meta( $user_id, 'filtered_meta', 'I shouldn’t exist later.' );
		update_comment_meta( $comment_id, 'filtered_meta', 'I shouldn’t exist later.' );

		$this->assertEquals( 'Only I should exist.', $post->meta( 'filtered_meta' ) );
		$this->assertEquals( 'Only I should exist.', $term->meta( 'filtered_meta' ) );
		$this->assertEquals( 'Only I should exist.', $comment->meta( 'filtered_meta' ) );
		$this->assertEquals( 'Only I should exist.', $user->meta( 'filtered_meta' ) );
	}

	/**
	 * We can’t check whether a user meta function is hit, because user metadata
	 * is requested by other functionality as well.
	 */
	function testNonNullReturnInPreMetaFilterDisablesDatabaseFetch() {
		$this->is_get_post_meta_hit    = false;
		$this->is_get_term_meta_hit    = false;
		$this->is_get_comment_meta_hit = false;

		$post_filter = function( $value ) {
			$this->is_get_post_meta_hit = true;

			return $value;
		};

		$term_filter = function( $value ) {
			$this->is_get_term_meta_hit = true;

			return $value;
		};

		$comment_filter = function( $value ) {
			$this->is_get_comment_meta_hit = true;

			return $value;
		};

		$this->add_filter_temporarily( 'timber/post/pre_meta', '__return_false' );
		$this->add_filter_temporarily( 'timber/term/pre_meta', '__return_false' );
		$this->add_filter_temporarily( 'timber/comment/pre_meta', '__return_false' );

		$this->add_filter_temporarily( 'get_post_metadata', $post_filter );
		$this->add_filter_temporarily( 'get_term_metadata', $term_filter );
		$this->add_filter_temporarily( 'get_comment_metadata', $comment_filter );

		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$comment_id = $this->factory->comment->create();

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$comment = Timber::get_comment( $comment_id );

		$this->assertEquals( false, $this->is_get_post_meta_hit );
		$this->assertEquals( false, $this->is_get_term_meta_hit );
		$this->assertEquals( false, $this->is_get_comment_meta_hit );

		// Run fetch.
		$post->meta();
		$term->meta();
		$comment->meta();

		$this->assertEquals( false, $this->is_get_post_meta_hit );
		$this->assertEquals( false, $this->is_get_term_meta_hit );
		$this->assertEquals( false, $this->is_get_comment_meta_hit );
	}

	function testMetaFilter() {
		$filter = function ( $meta, $object_id, $field_name, $object, $args ) {
			$this->assertEquals( 'name', $field_name );
			$this->assertEquals( 'A girl has no name.', $meta );
			$this->assertSame( $object->ID, $object_id );

			// Update the meta value.
			$meta = 'Frank Drebin';

			return $meta;
		};

		$this->add_filter_temporarily( 'timber/post/meta', $filter, 10, 5 );
		$this->add_filter_temporarily( 'timber/term/meta', $filter, 10, 5 );
		$this->add_filter_temporarily( 'timber/user/meta', $filter, 10, 5 );
		$this->add_filter_temporarily( 'timber/comment/meta', $filter, 10, 5 );

		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		update_post_meta( $post_id, 'name', 'A girl has no name.' );
		update_term_meta( $term_id, 'name', 'A girl has no name.' );
		update_user_meta( $user_id, 'name', 'A girl has no name.' );
		update_comment_meta( $comment_id, 'name', 'A girl has no name.' );

		$this->assertEquals( 'Frank Drebin', $post->meta( 'name' ) );
		$this->assertEquals( 'Frank Drebin', $term->meta( 'name' ) );
		$this->assertEquals( 'Frank Drebin', $comment->meta( 'name' ) );
		$this->assertEquals( 'Frank Drebin', $user->meta( 'name' ) );
	}

	/**
	 * Tests accessing a post meta value through the raw_meta() method.
	 */
	function testRawMeta() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		update_post_meta( $post_id, 'my_custom_property', 'Sweet Honey' );
		update_term_meta( $term_id, 'my_custom_property', 'Sweet Honey' );
		update_user_meta( $user_id, 'my_custom_property', 'Sweet Honey' );
		update_comment_meta( $comment_id, 'my_custom_property', 'Sweet Honey' );

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			"{{ post.raw_meta('my_custom_property') }}", [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			"{{ term.raw_meta('my_custom_property') }}", [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			"{{ user.raw_meta('my_custom_property') }}", [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			"{{ comment.raw_meta('my_custom_property') }}", [ 'comment' => $comment ]
		);

		$this->assertEquals( 'Sweet Honey', $post->raw_meta( 'my_custom_property' ) );
		$this->assertEquals( 'Sweet Honey', $post_string );

		$this->assertEquals( 'Sweet Honey', $term->raw_meta( 'my_custom_property' ) );
		$this->assertEquals( 'Sweet Honey', $term_string );

		$this->assertEquals( 'Sweet Honey', $user->raw_meta( 'my_custom_property' ) );
		$this->assertEquals( 'Sweet Honey', $user_string );

		$this->assertEquals( 'Sweet Honey', $comment->raw_meta( 'my_custom_property' ) );
		$this->assertEquals( 'Sweet Honey', $comment_string );
	}

	/**
	 * Meta values still need to fetchable through raw_meta() even when the pre_meta filter is used.
	 */
	function testRawMetaWhenPreMetaFilterReturnsFalse(){
		$this->add_filter_temporarily( 'timber/post/pre_meta', '__return_false' );
		$this->add_filter_temporarily( 'timber/term/pre_meta', '__return_false' );
		$this->add_filter_temporarily( 'timber/user/pre_meta', '__return_false' );
		$this->add_filter_temporarily( 'timber/comment/pre_meta', '__return_false' );

		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		update_post_meta( $post_id, 'meta_value', 'I am a meta value' );
		update_term_meta( $term_id, 'meta_value', 'I am a meta value' );
		update_user_meta( $user_id, 'meta_value', 'I am a meta value' );
		update_comment_meta( $comment_id, 'meta_value', 'I am a meta value' );

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$this->assertEquals( 'I am a meta value', $post->raw_meta( 'meta_value' ) );
		$this->assertEquals( false, $post->meta( 'meta_value' ) );

		$this->assertEquals( 'I am a meta value', $term->raw_meta( 'meta_value' ) );
		$this->assertEquals( false, $term->meta( 'meta_value' ) );

		$this->assertEquals( 'I am a meta value', $user->raw_meta( 'meta_value' ) );
		$this->assertEquals( false, $user->meta( 'meta_value' ) );

		$this->assertEquals( 'I am a meta value', $comment->raw_meta( 'meta_value' ) );
		$this->assertEquals( false, $comment->meta( 'meta_value' ) );
	}

	/**
	 * Tests accessing an inexistent meta value through raw_meta().
	 */
	function testRawMetaInexistent() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			"{{ post.raw_meta('my_custom_property_inexistent') }}", [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			"{{ term.raw_meta('my_custom_property_inexistent') }}", [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			"{{ user.raw_meta('my_custom_property_inexistent') }}", [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			"{{ comment.raw_meta('my_custom_property_inexistent') }}", [ 'comment' => $comment ]
		);

		$this->assertEquals( null, $post->raw_meta( 'my_custom_property_inexistent' ) );
		$this->assertEquals( null, $post_string );

		$this->assertEquals( null, $term->raw_meta( 'my_custom_property_inexistent' ) );
		$this->assertEquals( null, $term_string );

		$this->assertEquals( null, $user->raw_meta( 'my_custom_property_inexistent' ) );
		$this->assertEquals( null, $user_string );

		$this->assertEquals( null, $comment->raw_meta( 'my_custom_property_inexistent' ) );
		$this->assertEquals( null, $comment_string );
	}

	/**
	 * Tests accessing a meta value directly through magic methods.
	 */
	function testMetaDirectAccess() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		update_post_meta( $post_id, 'my_custom_property', 'Sweet Honey' );
		update_term_meta( $term_id, 'my_custom_property', 'Sweet Honey' );
		update_user_meta( $user_id, 'my_custom_property', 'Sweet Honey' );
		update_comment_meta( $comment_id, 'my_custom_property', 'Sweet Honey' );

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );
		$user    = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			'My {{ post.my_custom_property }}', [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			'My {{ term.my_custom_property }}', [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			'My {{ user.my_custom_property }}', [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			'My {{ comment.my_custom_property }}', [ 'comment' => $comment ]
		);

		$this->assertEquals( 'Sweet Honey', $post->my_custom_property );
		$this->assertEquals( 'My Sweet Honey', $post_string );

		$this->assertEquals( 'Sweet Honey', $term->my_custom_property );
		$this->assertEquals( 'My Sweet Honey', $term_string );

		$this->assertEquals( 'Sweet Honey', $user->my_custom_property );
		$this->assertEquals( 'My Sweet Honey', $user_string );

		$this->assertEquals( 'Sweet Honey', $comment->my_custom_property );
		$this->assertEquals( 'My Sweet Honey', $comment_string );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing public method on the object.
	 *
	 * The result of the method should take precedence over the value of the custom field.
	 */
	function testMetaDirectAccessPublicMethodConflict() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));

		update_post_meta( $post_id, 'public_method', 'I am a meta value' );
		update_term_meta( $term_id, 'public_method', 'I am a meta value' );
		update_user_meta( $user_id, 'public_method', 'I am a meta value' );
		update_comment_meta( $comment_id, 'public_method', 'I am a meta value' );

		$this->register_post_classmap_temporarily([
			'post' => MetaPost::class,
		]);

		$post    = Timber::get_post( $post_id );

		$this->add_filter_temporarily('timber/term/classmap', function() {
			return [
				'post_tag' => MetaTerm::class
			];
		});
		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});
		$this->add_filter_temporarily('timber/comment/classmap', function() {
			return [
				'post' => MetaComment::class,
			];
		});

		$term = Timber::get_term( $term_id );

		$user = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			'{{ post.public_method }}', [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			'{{ term.public_method }}', [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			'{{ user.public_method }}', [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			'{{ comment.public_method }}', [ 'comment' => $comment ]
		);

		$this->assertEquals( 'I am a public method', $post->public_method() );
		$this->assertEquals( 'I am a meta value', $post->public_method );
		$this->assertEquals( 'I am a public method', $post_string );

		$this->assertEquals( 'I am a public method', $term->public_method() );
		$this->assertEquals( 'I am a meta value', $term->public_method );
		$this->assertEquals( 'I am a public method', $term_string );

		$this->assertEquals( 'I am a public method', $user->public_method() );
		$this->assertEquals( 'I am a meta value', $user->public_method );
		$this->assertEquals( 'I am a public method', $user_string );

		$this->assertEquals( 'I am a public method', $comment->public_method() );
		$this->assertEquals( 'I am a meta value', $comment->public_method );
		$this->assertEquals( 'I am a public method', $comment_string );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing public method on the object.
	 *
	 * The result of the method should take precedence over the value of the custom field.
	 */
	function testMetaDirectAccessProtectedMethodConflict() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));

		update_post_meta( $post_id, 'protected_method', 'I am a meta value' );
		update_term_meta( $term_id, 'protected_method', 'I am a meta value' );
		update_user_meta( $user_id, 'protected_method', 'I am a meta value' );
		update_comment_meta( $comment_id, 'protected_method', 'I am a meta value' );

		$this->register_post_classmap_temporarily([
			'post' => MetaPost::class,
		]);

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );

		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});
		$this->add_filter_temporarily('timber/comment/classmap', function() {
			return [
				'post' => MetaComment::class,
			];
		});

		$user = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			'{{ post.protected_method }}', [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			'{{ term.protected_method }}', [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			'{{ user.protected_method }}', [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			'{{ comment.protected_method }}', [ 'comment' => $comment ]
		);

		$this->assertEquals( 'I am a meta value', $post->protected_method() );
		$this->assertEquals( 'I am a meta value', $post->protected_method );
		$this->assertEquals( 'I am a meta value', $post_string );

		$this->assertEquals( 'I am a meta value', $term->protected_method() );
		$this->assertEquals( 'I am a meta value', $term->protected_method );
		$this->assertEquals( 'I am a meta value', $term_string );

		$this->assertEquals( 'I am a meta value', $user->protected_method() );
		$this->assertEquals( 'I am a meta value', $user->protected_method );
		$this->assertEquals( 'I am a meta value', $user_string );

		$this->assertEquals( 'I am a meta value', $comment->protected_method() );
		$this->assertEquals( 'I am a meta value', $comment->protected_method );
		$this->assertEquals( 'I am a meta value', $comment_string );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing public method on the object that has at least one required parameter.
	 *
	 * @expectedException \ArgumentCountError
	 */
	function testPostMetaDirectAccessMethodWithRequiredParametersConflict() {
		$post_id = $this->factory->post->create();

		update_post_meta( $post_id, 'public_method_with_args', 'I am a meta value' );

		$this->register_post_classmap_temporarily([
			'post' => MetaPost::class,
		]);

		$post        = Timber::get_post( $post_id );
		$post_string = Timber::compile_string( '{{ post.public_method_with_args }}', [ 'post' => $post ] );

		$this->assertEquals( 'I am a meta value', $post_string );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing method on the object that has at least one required parameter.
	 *
	 * @expectedException \ArgumentCountError
	 */
	function testTermMetaDirectAccessMethodWithRequiredParametersConflict() {
		$term_id = $this->factory->term->create();

		update_term_meta( $term_id, 'public_method_with_args', 'I am a meta value' );

		$this->add_filter_temporarily('timber/term/classmap', function() {
			return [
				'post_tag' => MetaTerm::class
			];
		});

		$term        = Timber::get_term( $term_id );
		$term_string = Timber::compile_string( '{{ term.public_method_with_args }}', [
			'term' => $term
		]);

		$this->assertEquals( 'I am a meta value', $term_string );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing method on the object that has at least one required parameter.
	 *
	 * @expectedException \ArgumentCountError
	 */
	function testUserMetaDirectAccessMethodWithRequiredParametersConflict() {
		$user_id = $this->factory->user->create();

		update_user_meta( $user_id, 'public_method_with_args', 'I am a meta value' );

		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});
		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});

		$user        = Timber::get_user( $user_id );
		$user_string = Timber::compile_string( '{{ user.public_method_with_args }}', [ 'user' => $user ] );

		$this->assertEquals( 'I am a meta value', $user_string );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing method on the object that has at least one required parameter.
	 *
	 * @expectedException \ArgumentCountError
	 */
	function testCommentMetaDirectAccessMethodWithRequiredParametersConflict() {

		$post_id    = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));

		update_comment_meta( $comment_id, 'public_method_with_args', 'I am a meta value' );

		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});
		$this->add_filter_temporarily('timber/comment/classmap', function() {
			return [
				'post' => MetaComment::class,
			];
		});

		$comment        = Timber::get_comment( $comment_id );
		$comment_string = Timber::compile_string( '{{ comment.public_method_with_args }}', [ 'comment' => $comment ] );

		$this->assertEquals( 'I am a meta value', $comment_string );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing public property on the object.
	 *
	 * The value of the property should take precedence over the value of the custom field.
	 */
	function testMetaDirectAccessPublicPropertyConflict() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));

		update_post_meta( $post_id, 'public_property', 'I am a meta value' );
		update_term_meta( $term_id, 'public_property', 'I am a meta value' );
		update_user_meta( $user_id, 'public_property', 'I am a meta value' );
		update_comment_meta( $comment_id, 'public_property', 'I am a meta value' );

		$this->register_post_classmap_temporarily([
			'post' => MetaPost::class,
		]);

		$post    = Timber::get_post( $post_id );

		$this->add_filter_temporarily('timber/term/classmap', function() {
			return [
				'post_tag' => MetaTerm::class
			];
		});
		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});
		$this->add_filter_temporarily('timber/comment/classmap', function() {
			return [
				'post' => MetaComment::class,
			];
		});

		$term = Timber::get_term( $term_id );

		$user = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			'{{ post.public_property }}', [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			'{{ term.public_property }}', [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			'{{ user.public_property }}', [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			'{{ comment.public_property }}', [ 'comment' => $comment ]
		);

		$this->assertEquals( 'I am a public property', $post_string );
		$this->assertEquals( 'I am a public property', $post->public_property );

		$this->assertEquals( 'I am a public property', $term_string );
		$this->assertEquals( 'I am a public property', $term->public_property );

		$this->assertEquals( 'I am a public property', $user_string );
		$this->assertEquals( 'I am a public property', $user->public_property );

		$this->assertEquals( 'I am a public property', $comment_string );
		$this->assertEquals( 'I am a public property', $comment->public_property );
	}

	/**
	 * Tests when you try to directly access a custom field value that is also the name of an
	 * existing inaccessible property on the object.
	 *
	 * The value of the custom field should take precedence over the value of the property.
	 */
	function testMetaDirectAccessInaccessiblePropertyConflict() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		update_post_meta( $post_id, 'protected_property', 'I am a meta value' );
		update_term_meta( $term_id, 'protected_property', 'I am a meta value' );
		update_user_meta( $user_id, 'protected_property', 'I am a meta value' );
		update_comment_meta( $comment_id, 'protected_property', 'I am a meta value' );

		$this->register_post_classmap_temporarily([
			'post' => MetaPost::class,
		]);

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );

		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});
		$this->add_filter_temporarily('timber/comment/classmap', function() {
			return [
				'post' => MetaComment::class,
			];
		});

		$user = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			'{{ post.protected_property }}', [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			'{{ term.protected_property }}', [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			'{{ user.protected_property }}', [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			'{{ comment.protected_property }}', [ 'comment' => $comment ]
		);

		$this->assertEquals( 'I am a meta value', $post_string );
		$this->assertEquals( 'I am a meta value', $post->protected_property );

		$this->assertEquals( 'I am a meta value', $term_string );
		$this->assertEquals( 'I am a meta value', $term->protected_property );

		$this->assertEquals( 'I am a meta value', $user_string );
		$this->assertEquals( 'I am a meta value', $user->protected_property );

		$this->assertEquals( 'I am a meta value', $comment_string );
		$this->assertEquals( 'I am a meta value', $comment->protected_property );
	}

	/**
	 * Tests when you try to directly access a custom field value through the custom property.
	 *
	 * @expectedDeprecated Accessing a meta value through {{ post.custom }}
	 */
	function testPostMetaDirectAccessInaccessibleCustomProperty() {
		$post_id = $this->factory->post->create();

		update_post_meta( $post_id, 'inaccessible', 'Boo!' );

		$post   = Timber::get_post( $post_id );
		$string = Timber::compile_string( '{{ post.custom.inaccessible }}', array( 'post' => $post ) );

		$this->assertEquals( '', $string );
		$this->assertEquals( false, $post->custom );
	}

	/**
	 * Tests when you try to directly access a custom field value through the custom property.
	 *
	 * @expectedDeprecated Accessing a meta value through {{ term.custom }}
	 */
	function testTermMetaDirectAccessInaccessibleCustomProperty() {
		$term_id = $this->factory->term->create();

		update_term_meta( $term_id, 'inaccessible', 'Boo!' );

		$term   = Timber::get_term( $term_id );
		$string = Timber::compile_string( '{{ term.custom.inaccessible }}', array( 'term' => $term ) );

		$this->assertEquals( '', $string );
		$this->assertEquals( false, $term->custom );
	}

	/**
	 * Tests when you try to directly access a custom field value through the custom property.
	 *
	 * @expectedDeprecated Accessing a meta value through {{ user.custom }}
	 */
	function testUserMetaDirectAccessInaccessibleCustomProperty() {
		$user_id = $this->factory->user->create();

		update_user_meta( $user_id, 'inaccessible', 'Boo!' );

		$user   = Timber::get_user( $user_id );
		$string = Timber::compile_string( '{{ user.custom.inaccessible }}', array( 'user' => $user ) );

		$this->assertEquals( '', $string );
		$this->assertEquals( false, $user->custom );
	}

	/**
	 * Tests when you try to directly access a custom field value through the custom property.
	 *
	 * @expectedDeprecated Accessing a meta value through {{ comment.custom }}
	 */
	function testCommentMetaDirectAccessInaccessibleCustomProperty() {
		$comment_id = $this->factory->comment->create();

		update_comment_meta( $comment_id, 'inaccessible', 'Boo!' );

		$comment = Timber::get_comment( $comment_id );
		$string  = Timber::compile_string( '{{ comment.custom.inaccessible }}', array( 'comment' => $comment ) );

		$this->assertEquals( '', $string );
		$this->assertEquals( false, $comment->custom );
	}

	/**
	 * Tests when you try to directly access a custom field value that doesn’t exist on the
	 * object.
	 */
	function testPostMetaDirectAccessInexistent() {
		$post_id    = $this->factory->post->create();
		$term_id    = $this->factory->term->create();
		$user_id    = $this->factory->user->create();
		$comment_id = $this->factory->comment->create();

		update_post_meta( $post_id, 'protected_property', 'I am a meta value' );
		update_term_meta( $term_id, 'protected_property', 'I am a meta value' );
		update_user_meta( $user_id, 'protected_property', 'I am a meta value' );
		update_comment_meta( $comment_id, 'protected_property', 'I am a meta value' );

		$this->register_post_classmap_temporarily([
			'post' => MetaPost::class,
		]);

		$post    = Timber::get_post( $post_id );
		$term    = Timber::get_term( $term_id );

		$this->add_filter_temporarily('timber/user/classmap', function() {
			return MetaUser::class;
		});
		$this->add_filter_temporarily('timber/comment/classmap', function() {
			return [
				'post' => MetaComment::class,
			];
		});

		$user = Timber::get_user( $user_id );
		$comment = Timber::get_comment( $comment_id );

		$post_string    = Timber::compile_string(
			'{{ post.inexistent }}', [ 'post' => $post ]
		);
		$term_string    = Timber::compile_string(
			'{{ term.inexistent }}', [ 'term' => $term ]
		);
		$user_string    = Timber::compile_string(
			'{{ user.inexistent }}', [ 'user' => $user ]
		);
		$comment_string = Timber::compile_string(
			'{{ comment.inexistent }}', [ 'comment' => $comment ]
		);

		$this->assertEquals( '', $post_string );
		$this->assertEquals( false, $post->inexistent );

		$this->assertEquals( '', $term_string );
		$this->assertEquals( false, $term->inexistent );

		$this->assertEquals( '', $user_string );
		$this->assertEquals( false, $user->inexistent );

		$this->assertEquals( '', $comment_string );
		$this->assertEquals( false, $comment->inexistent );
	}

	/**
	 * Tests what happens when a custom field attempts to overwrite a Timber\Post method
	 */
	function testCustomTimeField() {
		$pid = $this->factory->post->create(array('post_content' => 'Cool content bro!', 'post_date' => '2020-02-07 08:03:00'));
		update_field( '_time', 'I am custom time', $pid );
		update_field( 'time', 'I am custom time', $pid );
		$str = '{{ post.time }}';
		$post = Timber::get_post( $pid );
		$str = Timber::compile_string( $str, array( 'post' => $post ) );
		$this->assertEquals( '8:03 am', trim($str) );
	}

	/**
	 * Tests what happens when a custom field attempts to overwrite a Timber\Post method
	 */
	function testCustomContentField() {
		$pid = $this->factory->post->create(array('post_content' => 'Cool content bro!'));
		update_field( '_content', 'I am custom content', $pid );
		$str = '{{ post.content }}';
		$post = Timber::get_post( $pid );
		$str = Timber::compile_string( $str, array( 'post' => $post ) );
		$this->assertEquals( '<p>Cool content bro!</p>', trim($str) );
	}
}
