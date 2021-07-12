<?php

use Timber\User;

	/**
	 * @group integrations
	 * @group users-api
	 */
	class TestTimberUser extends Timber_UnitTestCase {
		function setUp() {
			parent::setUp();

			// Restore integration-free Class Map for users.
			$this->add_filter_temporarily('timber/user/classmap', function() {
				return User::class;
			});
		}

		function testIDDataType() {
			$uid = $this->factory->user->create(array('display_name' => 'James Marshall'));
			$user = Timber::get_user($uid);
			$this->assertEquals('integer', gettype($user->id));
			$this->assertEquals('integer', gettype($user->ID));
		}

		function testInitWithID(){
			$uid = $this->factory->user->create(array('display_name' => 'Baberaham Lincoln'));
			$user = Timber::get_user($uid);
			$this->assertEquals('Baberaham Lincoln', $user->name);
			$this->assertEquals($uid, $user->id);
		}

		function testInitWithSlug(){
			$uid = $this->factory->user->create(array('display_name' => 'Tito Bottitta', 'user_login' => 'mbottitta'));
			$user = Timber::get_user_by('login', 'mbottitta');
			$this->assertEquals('Tito Bottitta', $user->name);
			$this->assertEquals($uid, $user->id);
		}

		function testPostWithBlankUser() {
			$post_id = wp_insert_post(
			    [ 'post_title' => 'Baseball'
			    , 'post_content' => 'is fine, I guess'
			    , 'post_status' => 'publish'
			    ]);
			$post = Timber::get_post($post_id);
			$template = '{{ post.title }} by {{ post.author }}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Baseball by', trim($str));
		}

		function testUserCapability() {
			$uid = $this->factory->user->create(array('display_name' => 'Tito Bottitta', 'user_login' => 'mbottitta', 'role' => 'editor'));
			$user = Timber::get_user_by('login', 'mbottitta');
			$this->assertTrue($user->can('edit_posts'));
			$this->assertFalse($user->can('activate_plugins'));
		}

		function testUserRole() {
			$uid = $this->factory->user->create(array('display_name' => 'Tito Bottitta', 'user_login' => 'mbottitta', 'role' => 'editor'));
			$user = Timber::get_user_by('login', 'mbottitta');
			$this->assertArrayHasKey('editor', $user->roles());
		}

		function testDescription() {
			$uid = $this->factory->user->create(array('display_name' => 'Baberaham Lincoln', 'user_login' => 'blincoln'));
			update_user_meta($uid, 'description', 'Sixteenth President');
			$user = Timber::get_user($uid);
			$this->assertEquals('Sixteenth President', $user->meta('description'));

			$pid = $this->factory->post->create(array('post_author' => $uid));
			$post = Timber::get_post($pid);
			$str = Timber::compile_string("{{post.author.meta('description')}}", array('post' => $post));
			$this->assertEquals('Sixteenth President', $str);
		}

		function testInitShouldUnsetPassword() {
			$uid = $this->factory->user->create(array('display_name' => 'Tom Riddle'));
			$user = Timber::get_user($uid);
			$this->assertFalse(property_exists( $user, 'user_pass'));
		}

		function testInitWithObject(){
			$uid = $this->factory->user->create(array('display_name' => 'Baberaham Lincoln'));
			$wp_user = get_user_by('id', $uid);
			$user = Timber::get_user($wp_user);
			$this->assertEquals('Baberaham Lincoln', $user->name);
		}

		function testLinks() {
			$this->setPermalinkStructure('/blog/%year%/%monthnum%/%postname%/');
			$uid = $this->factory->user->create(array('display_name' => 'Baberaham Lincoln', 'user_login' => 'lincoln'));
			$uid = get_user_by('id', $uid);
			$user = Timber::get_user($uid);
			$this->assertEquals('http://example.org/blog/author/lincoln/', trailingslashit($user->link()) );
			$this->assertEquals('/blog/author/lincoln/', trailingslashit($user->path()) );
			$user->president = '16th';
			$this->assertEquals('16th', $user->president);
		}

		function testAvatar() {
			// Restore integration-free Class Map for users.
			// CoAuthorsPlus overrides avatar behavior, so we disable it explicitly.
			$this->add_filter_temporarily('timber/user/classmap', function() {
				return User::class;
			});

			$uid = $this->factory->user->create(array('display_name' => 'Maciej Palmowski', 'user_login' => 'palmiak', 'user_email' => 'm.palmowski@spiders.agency'));
			$user = Timber::get_user($uid);
			$this->assertEquals('http://2.gravatar.com/avatar/b2965625410b81a2b25ef02b54493ce0?s=96&d=mm&r=g', $user->avatar());
			$this->assertEquals('http://2.gravatar.com/avatar/b2965625410b81a2b25ef02b54493ce0?s=120&d=mm&r=g', $user->avatar(['size' => 120]));
		}
	}
