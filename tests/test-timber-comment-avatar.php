<?php

	/**
	 * @group comments-api
	 */
	class TestTimberCommentAvatar extends Timber_UnitTestCase {

		function testAvatarSize() {
			if ( !TestTimberImage::is_connected() ) {
				$this->markTestSkipped('Cannot test avatar images when not connected to internet');
			}
			$post_id = $this->factory->post->create();
			$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));
			$comment = Timber\Timber::get_comment($comment_id);

			# test default gravatr holding image
			$avatar = $comment->avatar("mystery");

			$this->assertTrue(substr ( $avatar , 0, 5 ) == "http:");
		}

		function testAvatarFalse() {
			update_option('show_avatars', false);
			$post_id = $this->factory->post->create();
			$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));
			$comment = Timber\Timber::get_comment($comment_id);

			# test default gravatr holding image
			$avatar = $comment->avatar();

			$this->assertFalse($avatar);
		}

		function testAvatarBlank() {
			if ( !TestTimberImage::is_connected() ) {
				$this->markTestSkipped('Cannot test avatar images when not connected to internet');
			}
			$post_id = $this->factory->post->create();
			$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));
			$comment = Timber\Timber::get_comment($comment_id);

			# test default gravatr holding image
			$avatar = $comment->avatar(92, "blank");

			$this->assertTrue(substr ( $avatar , 0, 5 ) == "http:");
		}

		function testAvatarGravatarDefault() {
			if ( !TestTimberImage::is_connected() ) {
				$this->markTestSkipped('Cannot test avatar images when not connected to internet');
			}
			$post_id = $this->factory->post->create();
			$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));
			$comment = Timber\Timber::get_comment($comment_id);

			# test default gravatr holding image
			$avatar = $comment->avatar(92, "gravatar_default");

			$this->assertTrue(substr ( $avatar , 0, 5 ) == "http:");
		}

		function testGravatar() {
			if (!TestTimberImage::is_connected()){
				$this->markTestSkipped('Cannot test avatar images when not connected to internet');
			}
			$post_id = $this->factory->post->create();
			$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_author' => 'jarednova', 'comment_author_email' => 'jarednova@upstatement.com'));
			$comment = Timber\Timber::get_comment($comment_id);
			$gravatar = md5(file_get_contents($comment->avatar()));
			/* this keeps changing b/c of compression tweaks on WP.org, disabling the test */
			//$this->assertEquals($gravatar, md5(file_get_contents(dirname(__FILE__).'/assets/jarednova.jpeg')));

			$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_author' => 'jarednova', 'comment_author_email' => 'notjared@upstatement.com'));
			$comment = Timber\Timber::get_comment($comment_id);
			$not_gravatar = md5(file_get_contents($comment->avatar()));
			$this->assertNotEquals($not_gravatar, md5(file_get_contents(dirname(__FILE__).'/assets/jarednova.jpeg')));
		}

		function testAvatarSimple(){
			if (!TestTimberImage::is_connected()){
				$this->markTestSkipped('Cannot test avatar images when not connected to internet');
			}
			$theme_url = get_theme_root_uri().'/'.Timber\ThemeHelper::get_stylesheet();
			$post_id = $this->factory->post->create();
			$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));
			$comment = Timber\Timber::get_comment($comment_id);

			# test default gravatr holding image
			$avatar = $comment->avatar(32, "mystery");

			$this->assertTrue(substr ( $avatar , 0, 5 ) == "http:");

			# does it work if its SSL?
			$_SERVER['HTTPS'] = 'on';
			$avatar = $comment->avatar(32, "mystery");
			$this->assertTrue(200 === $this->crawl($avatar));
			$this->assertTrue(substr ( $avatar , 0, 6 ) == "https:");
			$_SERVER['HTTPS'] = 'off';

			# pass custom url on different domain. can't check by crawling as
			# i get a 302 regardless of default url
			# so just check it comes back with it in the url
			$this->valid_avatar($comment, "http://upload.wikimedia.org/wikipedia/en/b/bc/Wiki.png");

			# same domain.
			$this->valid_avatar($comment, $theme_url . "/images/default.png");

			#relative
			$default_url = "/images/default.png";
			$avatar = $comment->avatar(32, $default_url );
			if (strstr($avatar, '?')){
	  			list($url, $params) = explode('?', $avatar);
	  			$default_url = $theme_url . $default_url;
	  			# you get back the absoulte url to default in the avatar url?
	  			$this->assertEquals($params, "d=$default_url&amp;s=32");
	  		}
	  		# you get back url?
	  		$this->assertTrue(substr ( $theme_url. $avatar , 0, 5 ) == "http:");
		}


		function valid_avatar($comment, $default_url){
			$avatar = $comment->avatar(32, $default_url);
			if (strstr($avatar, '?')){
	  			list($url, $params) = explode('?', $avatar);
	  			# you get back the default in the avatar url?
	  			$this->assertEquals($params, "d=$default_url&amp;s=32");
	  		}
	  		# you get back url?
	  		$this->assertTrue(substr ( $avatar , 0, 5 ) == "http:");
		}


		function crawl($url){
			$handle = curl_init($url);
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);
			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			curl_close($handle);
	  		return $httpCode;
		}

	}
