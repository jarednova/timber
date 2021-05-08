<?php

	/**
	 * @group posts-api
	 */
	class TestTimberRevisions extends Timber_UnitTestCase {

		public function setRevision( $post_id ) {
			global $wp_query;
			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);

		}

		function testParentOfPost() {
			// Register Custom Post Type

			$args = array(
				'label'                 => __( 'Box', 'text_domain' ),
				'description'           => __( 'Post Type Description', 'text_domain' ),
				'supports'              => array( 'title', 'editor', 'revisions' ),
				'taxonomies'            => array( 'category', 'post_tag' ),
				'hierarchical'          => true,
				'public'                => true,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'menu_position'         => 5,
				'show_in_admin_bar'     => true,
				'show_in_nav_menus'     => true,
				'can_export'            => true,
				'has_archive'           => true,
				'exclude_from_search'   => false,
				'publicly_queryable'    => true,
				'capability_type'       => 'page',
			);
			register_post_type( 'box', $args );

			global $current_user;
			global $wp_query;

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);
			$user->add_role('administrator');

			$parent_id = $this->factory->post->create(array(
				'post_content' => 'I am parent',
				'post_type' => 'box',
				'post_author' => $uid
			));

			$post_id = $this->factory->post->create(array(
				'post_content' => 'I am child',
				'post_type' => 'box',
				'post_author' => $uid,
				'post_parent' => $parent_id
 			));

 			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_content' => 'I am revised'
			));

			$post = Timber::get_post($post_id);
			$parent = Timber::get_post($parent_id);

			//$this->assertEquals($parent_id, $post->parent()->id);


			self::setRevision($post_id);
			$revision = Timber::get_post();

			$this->assertEquals('I am revised', trim(strip_tags($revision->content())) );

			$revision_parent = $revision->parent();
			$this->assertEquals($parent_id, $revision_parent->id);
			$this->assertEquals('I am parent', trim(strip_tags($revision_parent->content())) );

		}

		function testPreviewClass() {
			global $current_user;
			global $wp_query;

			$quote = 'The way to do well is to do well.';
			$post_id = $this->factory->post->create(array(
				'post_content' => $quote,
				'post_author' => 5
			));
			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_content' => $quote . 'Yes'
			));

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));

			$original_post = Timber::get_post($post_id);
			$user = wp_set_current_user($uid);

			$user->add_role('administrator');
			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);
			$post = Timber::get_post();
			$this->assertEquals( $original_post->class(), $post->class() );
		}

		function testPreviewTitleWithID() {
			global $current_user;
			global $wp_query;

			$post_id = $this->factory->post->create(array(
				'post_title' => 'I call it banana bread',
				'post_author' => 5
			));
			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_title' => 'I call it fromage'
			));

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);

			$user->add_role('administrator');
			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);
			$post = Timber::get_post($post_id);
			$this->assertEquals( 'I call it fromage', $post->title() );
		}

		function testPreviewContentWithID() {
			global $current_user;
			global $wp_query;

			$quote = 'The way to do well is to do well.';
			$post_id = $this->factory->post->create(array(
				'post_content' => $quote,
				'post_author' => 5
			));
			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_content' => $quote . 'Yes'
			));

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);

			$user->add_role('administrator');
			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);
			$post = Timber::get_post($post_id);
			$this->assertEquals( $quote . 'Yes', trim(strip_tags($post->content())) );
		}

		function testPreviewContent(){
			global $current_user;
			global $wp_query;

			$quote = 'The way to do well is to do well.';
			$post_id = $this->factory->post->create(array(
				'post_content' => $quote,
				'post_author' => 5
			));
			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_content' => $quote . 'Yes'
			));

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);

			$user->add_role('administrator');
			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);
			$post = Timber::get_post();
			$this->assertEquals( $quote . 'Yes', trim(strip_tags($post->content())) );
		}

		function testMultiPreviewRevisions(){
			global $current_user;
			global $wp_query;

			$quote = 'The way to do well is to do well.';
			$post_id = $this->factory->post->create(array(
				'post_content' => $quote,
				'post_author' => 5
			));
			$old_revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_content' => $quote . 'Yes'
			));

			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_content' => 'I am the one'
			));

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);

			$user->add_role('administrator');
			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);
			$post = Timber::get_post();
			$this->assertEquals('I am the one', trim(strip_tags($post->content())) );
		}

		function testCustomFieldPreviewRevisionMethod(){
			global $current_user;
			global $wp_query;

			$post_id = $this->factory->post->create(array(
				'post_author' => 5,
			));
			update_field('test_field', 'The custom field content', $post_id);

			$assertCustomFieldVal = 'This has been revised';
			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
			));
			update_field('test_field', $assertCustomFieldVal, $revision_id);

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);
			$user->add_role('administrator');

			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);
			$post = Timber::get_post($post_id);
			$str_getfield = Timber::compile_string('{{post.meta(\'test_field\')}}', array('post' => $post));
			$this->assertEquals( $assertCustomFieldVal, $str_getfield );
		}

		function testCustomFieldPreviewRevisionImported(){
			global $current_user;
			global $wp_query;

			$post_id = $this->factory->post->create(array(
				'post_author' => 5,
			));
			update_field('test_field', 'The custom field content', $post_id);

			$assertCustomFieldVal = 'This has been revised';
			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
			));
			update_field('test_field', $assertCustomFieldVal, $revision_id);

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);
			$user->add_role('administrator');

			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$_GET['preview'] = true;
			$_GET['preview_nonce'] = wp_create_nonce('post_preview_' . $post_id);
			$post = Timber::get_post($post_id);
			$str_direct = Timber::compile_string('{{ post.meta("test_field") }}', array('post' => $post));
			$this->assertEquals( $assertCustomFieldVal, $str_direct );
		}

		function testCustomFieldPreviewNotRevision() {
			global $current_user;
			global $wp_query;
			$original_content = 'The custom field content';

			$post_id = $this->factory->post->create(array(
				'post_author' => 5,
			));
			update_field('test_field', $original_content, $post_id);

			$assertCustomFieldVal = 'This has been revised';
			$revision_id = $this->factory->post->create(array(
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
			));
			update_field('test_field', $assertCustomFieldVal, $revision_id);

			$uid = $this->factory->user->create(array(
				'user_login' => 'timber',
				'user_pass' => 'timber',
			));
			$user = wp_set_current_user($uid);
			$user->add_role('administrator');

			$wp_query->queried_object_id = $post_id;
			$wp_query->queried_object = get_post($post_id);
			$post = Timber::get_post($post_id);

			$str_direct = Timber::compile_string('{{post.test_field}}', array('post' => $post));
			$str_getfield = Timber::compile_string('{{post.meta(\'test_field\')}}', array('post' => $post));

			$this->assertEquals( $original_content, $str_direct );
			$this->assertEquals( $original_content, $str_getfield );
		}
}
