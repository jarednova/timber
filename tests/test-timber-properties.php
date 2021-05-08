<?php

/**
 * @group users-api
 * @group comments-api
 * @group called-post-constructor
 * @group called-term-constructor
 * @todo #2094 replace direct Timber\User instantiations
 * @todo #2094 replace direct Timber\Comment instantiations
 */
class TestTimberProperty extends Timber_UnitTestCase {

	public function testPropertyID() {
		$post_id = $this->factory->post->create();
		$user_id = $this->factory->user->create();
		$comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		$term_id = wp_insert_term( 'baseball', 'post_tag' );
		$term_id = $term_id['term_id'];
		$post = Timber::get_post( $post_id );
		$user = Timber::get_user( $user_id );
		$term = Timber::get_term( $term_id );
		$comment = Timber\Timber::get_comment( $comment_id );
		$this->assertEquals( $post_id, $post->ID );
		$this->assertEquals( $post_id, $post->id );
		$this->assertEquals( $user_id, $user->ID );
		$this->assertEquals( $user_id, $user->id );
		$this->assertEquals( $term_id, $term->ID );
		$this->assertEquals( $term_id, $term->id );
		$this->assertEquals( $comment_id, $comment->ID );
		$this->assertEquals( $comment_id, $comment->id );
	}


	protected function _initObjects() {
		$post_id = $this->factory->post->create();
		$user_id = $this->factory->user->create();
		$comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		$term_id = wp_insert_term( 'baseball', 'post_tag' );
		$term_id = $term_id['term_id'];
		$post = Timber::get_post( $post_id );
		$user = Timber::get_user( $user_id );
		$term = Timber::get_term( $term_id );
		$comment = Timber\Timber::get_comment( $comment_id );
		$site = new Timber\Site();
		return array( 'post' => $post, 'user' => $user, 'term' => $term, 'comment' => $comment, 'site' => $site );
	}

	public function testMetaForTerm() {
		$vars = $this->_initObjects();
		extract( $vars );
		update_term_meta($term->ID, 'abraham', 'lincoln');
		$this->assertEquals( 'lincoln', $term->abraham );
		$this->assertEquals( 'lincoln', Timber::compile_string( '{{term.abraham}}', array( 'term' => $term ) ) );
	}

	/**
	 * @expectedDeprecated Timber\Site::update()
	 * @expectedDeprecated Timber\Post::update()
	 * @expectedDeprecated Timber\Core::update()
	 */
	public function testMeta() {
		$vars = $this->_initObjects();
		extract( $vars );

		$site->update( 'bill', 'clinton' );
		$post->update( 'thomas', 'jefferson' );
		//
		$user->update( 'dwight', 'einsenhower' );
		$user->update( 'teddy', 'roosevelt' );
		$user->update( 'john', 'kennedy' );
		$comment->update( 'george', 'washington' );
		$this->assertEquals( 'jefferson', $post->thomas );

		$this->assertEquals( 'roosevelt', $user->teddy );
		$this->assertEquals( 'washington', $comment->george );
		$this->assertEquals( 'clinton', $site->bill );

		$this->assertEquals( 'jefferson', Timber::compile_string( '{{post.thomas}}', array( 'post' => $post ) ) );

		$this->assertEquals( 'roosevelt', Timber::compile_string( '{{user.teddy}}', array( 'user' => $user ) ) );
		$this->assertEquals( 'washington', Timber::compile_string( '{{comment.george}}', array( 'comment' => $comment ) ) );
		$this->assertEquals( 'clinton', Timber::compile_string( '{{site.bill}}', array( 'site' => $site ) ) );
	}

}
