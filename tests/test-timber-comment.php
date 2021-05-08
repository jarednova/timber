<?php

/**
 * @group comments-api
 */
class TestTimberComment extends Timber_UnitTestCase {

	function testComment(){
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));
		$comment = Timber\Timber::get_comment($comment_id);
		$this->assertEquals('Timber\Comment', get_class($comment));
		$this->assertEquals($comment_id, $comment->ID);
	}

	function testCommentWithMeta(){
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id));
		update_comment_meta( $comment_id, 'rebney', 'Winnebago Man');
		update_comment_meta( $comment_id, 'quote', 'Will you do me a kindness?');
		$comment = Timber\Timber::get_comment($comment_id);
		$this->assertEquals('Winnebago Man', $comment->rebney);
	}

	function testCommentToString(){
		$quote = 'Jerry, just remember, it’s not a lie if you believe it.';
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $quote));
		$comment = Timber\Timber::get_comment($comment_id);
		$str = Timber::compile_string('{{comment}}', array('comment' => $comment));
		$this->assertEquals('<p>'.$quote.'</p>', $str);
	}

	function testCommentContent(){
		$costanza_quote = "Divorce is always hard. Especially on the kids. ‘Course I am the result of my parents having stayed together so ya never know.";
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $costanza_quote));
		$comment = Timber\Timber::get_comment($comment_id);
		$this->assertEquals('<p>'.$costanza_quote.'</p>', $comment->content());
	}

	function testCommentApproval(){
		$kramer_quote = "Oh, you gotta eat before surgery. You need your strength.";
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $kramer_quote));
		$comment = Timber\Timber::get_comment($comment_id);
		$this->assertTrue($comment->approved());

		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'You ever dream in 3-D? It’s like the Boogie Man is coming RIGHT AT YOU.', 'comment_approved' => false));
		$comment = Timber\Timber::get_comment($comment_id);
		$this->assertFalse($comment->approved());
	}

	function testCommentDate(){
		$quote = "So he just shaves his head for no reason? That’s like using a wheelchair for the fun of it!";
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $quote, 'comment_date' => '2015-08-21 03:24:07'));
		$comment = Timber\Timber::get_comment($comment_id);
		$this->assertEquals('August 21, 2015', $comment->date());
	}

	function testCommentTime(){
		$quote = "My grandmother used to swear by this, but personally I was always skeptical.";
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $quote, 'comment_date' => '2015-08-21 03:24:07'));
		$comment = Timber\Timber::get_comment($comment_id);
		$this->assertEquals('3:24 am', $comment->time());
	}

	function testCommentReplyLink() {
		$comment_text = "Try the soup";
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => $comment_text, 'comment_date' => '2015-08-21 03:24:07'));
		$comment = Timber\Timber::get_comment($comment_id);
		$link = $comment->reply_link('Respond');
		$this->assertEquals('Respond', strip_tags($link));
	}

	function testAnonymousComment() {
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'Mystery', 'user_id' => 0, 'comment_author' => false));
		$comment = Timber\Timber::get_comment($comment_id);
		$twig_string = '{{comment.author.name}}';
		$result = Timber::compile_string($twig_string, array('comment' => $comment));
		$this->assertEquals('Anonymous', $result);
	}

	function testAnonymousCommentWithName() {
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'Mystery', 'user_id' => 0, 'comment_author' => 'Milhouse Van Houten'));
		$comment = Timber\Timber::get_comment($comment_id);
		$twig_string = '{{comment.author.name}}';
		$result = Timber::compile_string($twig_string, array('comment' => $comment));
		$this->assertEquals('Milhouse Van Houten', $result);
	}

	function testCommentWithChildren() {
		$kramer = $this->factory->user->create(array('display_name' => 'Kramer'));
		$post_id = $this->factory->post->create();
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'These pretzels are making me thirsty.', 'user_id' => $kramer, 'comment_date' => '2015-08-21 03:24:07'));
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'Perhaps there’s more to Newman than meets the eye.', 'comment_date' => '2015-08-21 03:25:07'));
		$child_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'No, there’s less.', 'comment_parent' => $comment_id, 'comment_date' => '2015-08-21 03:26:07'));
		$post = Timber::get_post($post_id);
		$comments = $post->comments();
		$this->assertEquals(2, count($comments));
		$this->assertEquals(1, count($comments[1]->children()));
		$twig_string = '{{comment.author.name}}';
		$result = Timber::compile_string($twig_string, array('comment' => $comments[0]));
		$this->assertEquals('Kramer', $result);
	}

	function _makeCommentPost() {
		$elaine = $this->factory->user->create(array('display_name' => 'Elaine Benes'));
		$kramer = $this->factory->user->create(array('display_name' => 'Kramer'));
		$peterman = $this->factory->user->create(array('display_name' => 'J. Peterman'));


		$post_id = $this->factory->post->create(array('post_date' => '2016-11-28 02:58:18'));
		//1st parent @4:58am
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'These pretzels are making me thirsty.', 'user_id' => $kramer, 'comment_date' => '2016-11-28 04:58:18'));
		//2nd parent @5:58am
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'Perhaps there’s more to Newman than meets the eye.', 'comment_date' => '2016-11-28 05:58:18', 'user_id' => $elaine));
		$child_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'No, there’s less.', 'comment_parent' => $comment_id, 'comment_date' => '2016-11-28 06:58:18'));
		$child_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'for real?', 'comment_parent' => $child_id, 'comment_date' => '2016-11-28 06:59:18'));
		//3rd parent @7:58am
		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'The very pants you were returning', 'comment_date' => '2016-11-28 07:58:18', 'user_id' => $peterman));
		return $post_id;
	}

	function testCommentDepth() {
		$post_id = $this->_makeCommentPost();
		$post = Timber::get_post($post_id);
		$comments = $post->comments();
		$children = $comments[1]->children();
		$grand_children = $children[0]->children();
		$this->assertEquals(3, count($comments));
		$this->assertEquals(1, count($children));

		$this->assertEquals(0, $comments[1]->depth());
		$this->assertEquals(1, $children[0]->depth());
		$this->assertEquals(2, $grand_children[0]->depth());
		$this->assertEquals(0, $comments[2]->depth());

		$comment_id = $this->factory->comment->create(array('comment_post_ID' => $post_id, 'comment_content' => 'Perhaps there’s more to Newman than meets the eye.', 'comment_date' => '2016-11-28 05:58:18'));
		$twig_string = '{{comment.author.name}}';
		$comments = $post->comments();
		$result = Timber::compile_string($twig_string, array('comment' => $comments[0]));
		$this->assertEquals('Kramer', $result);
	}

	function testCommentOrder() {
		$post_id = $this->_makeCommentPost();
		$post = Timber::get_post($post_id);
		$str = '{% for comment in post.comments %}{{comment.author.name}}, {% endfor %}';
		$compiled = Timber::compile_string($str, array('post' => $post));
		$this->assertEquals('Kramer, Elaine Benes, J. Peterman, ', $compiled);
		$str = '{% for comment in post.comments.order("DESC") %}{{comment.author.name}}, {% endfor %}';
		$compiled = Timber::compile_string($str, array('post' => $post));
		$this->assertEquals('J. Peterman, Elaine Benes, Kramer, ', $compiled);
	}

	function testCommentOrderBy() {
		$post_id = $this->_makeCommentPost();
		$post = Timber::get_post($post_id);
		$str = '{% for comment in post.comments %}{{comment.author.name}}, {% endfor %}';
		$compiled = Timber::compile_string($str, array('post' => $post));
		$this->assertEquals('Kramer, Elaine Benes, J. Peterman, ', $compiled);
		$str = '{% for comment in post.comments.orderby("comment_author") %}{{comment.author.name}}, {% endfor %}';
		$compiled = Timber::compile_string($str, array('post' => $post));
		$this->assertEquals('Kramer, Elaine Benes, J. Peterman, ', $compiled);
	}
}
