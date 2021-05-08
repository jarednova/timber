<?php

	/**
	 * @group posts-api
	 */
	class TestTimberPostExcerptObject extends Timber_UnitTestCase {

		protected $gettysburg = 'Four score and seven years ago our fathers brought forth on this continent a new nation, conceived in liberty, and dedicated to the proposition that all men are created equal.';


		function test1886Error() {
			$expected = '<p>Govenment:</p> <ul> <li>of the <strong>people</strong></li> <li>by the people</li> <li>for the people</li> </ul>';
			$post_id = $this->factory->post->create(array('post_content' => $expected.'<blockquote>Lincoln</blockquote>', 'post_excerpt' => false));
			$post = Timber::get_post($post_id);
			$template = "{{ post.excerpt( {strip:'<p><strong><ul><ol><li><br>'}) }}";
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals($expected.' <p>Lincoln</p>&hellip; <a href="http://example.org/?p='.$post_id.'" class="read-more">Read More</a>', $str);
		}

		function test1886ErrorWithForce() {
			$expected = '<p>Government:</p> <ul> <li>of the <strong>people</strong></li> <li>by the people</li> <li>for the people</li> </ul>';
			$post_id = $this->factory->post->create(array('post_excerpt' => $expected, 'post_content' => $this->gettysburg));
			$post = Timber::get_post($post_id);
			$template = '{{ post.excerpt({
				strip: "<ul><li>",
				words:10,
				force:true })
			}}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Government: <ul> <li>of the people</li> <li>by the people</li> <li>for the</li></ul>&hellip; <a href="http://example.org/?p='.$post_id.'" class="read-more">Read More</a>', $str);
		}

		public function testExcerptConstructorWithWords() {
			$post_id = $this->factory->post->create(array(
				'post_excerpt' => $this->gettysburg,
			));
			$post = Timber::get_post($post_id);
			$excerpt = $post->excerpt(array(
				'words'     => 4,
				'force'     => true,
				'read_more' => false,
			));

			// Coerce excerpt to a string and test it.
			$this->assertEquals('Four score and seven&hellip;', ''.$excerpt);
		}

		public function testExcerptConstructorWithChars() {
			$post_id = $this->factory->post->create(array(
				'post_excerpt' => $this->gettysburg,
			));
			$post    = Timber::get_post($post_id);
			$excerpt = $post->excerpt(array(
				'chars'     => 20,
				'force'     => true,
				'read_more' => false,
			));

			// Coerce excerpt to a string and test it.
			$this->assertEquals('Four score and seven&hellip;', ''.$excerpt);
		}

		public function testExcerptConstructorWithEnd() {
			$post_id = $this->factory->post->create(array(
				'post_excerpt' => $this->gettysburg,
			));
			$post    = Timber::get_post($post_id);
			$excerpt = $post->excerpt(array(
				'chars'     => 20,
				'force'     => true,
				'read_more' => false,
				'end'       => ' - kthxbi',
			));

			// Coerce excerpt to a string and test it.
			$this->assertEquals('Four score and seven - kthxbi', ''.$excerpt);
		}

		public function testExcerptConstructorWithHtml() {
			$post_id = $this->factory->post->create(array(
				'post_excerpt' => '<span>Yo</span>'
					. ' <a href="/">CLICK</a>'
					. ' <strong>STRONG</strong> '
					. $this->gettysburg,
			));
			$post    = Timber::get_post($post_id);
			$excerpt = $post->excerpt(array(
				'chars'     => 26,
				'read_more' => false,
				'force'     => true,
			));

			// Coerce excerpt to a string and test it.
			$this->assertEquals('Yo CLICK STRONG Four score&hellip;', ''.$excerpt);
		}

		public function testExcerptConstructorStrippingSomeTags() {
			$post_id = $this->factory->post->create(array(
				'post_excerpt' => '<span>Yo</span>'
					. ' <a href="/">CLICK</a>'
					. ' <strong>STRONG</strong> '
					. $this->gettysburg,
			));
			$post    = Timber::get_post($post_id);
			$excerpt = $post->excerpt(array(
				'words'     => 5,
				'read_more' => false,
				'force'     => true,
				'strip'     => '<span><a>',
			));

			// Coerce excerpt to a string and test it.
			$this->assertEquals(
				'<span>Yo</span> <a href="/">CLICK</a> STRONG Four&hellip;',
				''.$excerpt
			);
		}

		public function testExcerptConstructorWithReadMore() {
			$post_id = $this->factory->post->create(array(
				'post_excerpt' => $this->gettysburg,
			));
			$post     = Timber::get_post($post_id);
			$readmore = 'read more! if you dare...';
			$excerpt  = $post->excerpt(array(
				'chars'     => 20,
				'force'     => true,
				'read_more' => 'read more! if you dare...',
			));

			$expected = sprintf(
				'Four score and seven&hellip; <a href="%s" class="read-more">%s</a>',
				$post->link(),
				$readmore
			);

			// Coerce excerpt to a string and test it.
			$this->assertEquals($expected, ''.$excerpt);
		}

		function testExcerptWithStyleTags() {
			global $wpdb;
			$style = '<style>body { background-color: red; }</style><b>Yo.</b> ';
			$id = $wpdb->insert(
				$wpdb->posts,
				array(
					'post_author' => '1',
					'post_content' => $style.$this->gettysburg,
					'post_title' => 'Thing',
					'post_date' => '2017-03-01 00:21:40',
					'post_date_gmt' => '2017-03-01 00:21:40'
				)
			);
			$post_id = $wpdb->insert_id;
			$post = Timber::get_post($post_id);
			$template = '{{ post.excerpt.length(9).read_more(false).strip(true) }}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Yo. Four score and seven years ago our fathers&hellip;', $str);
		}

		function testExcerptTags() {
			$post_id = $this->factory->post->create(array('post_excerpt' => 'It turned out that just about anyone in authority — cops, judges, city leaders — was in on the game.'));
			$post = Timber::get_post($post_id);
			$template = '{{ post.excerpt.length(3).read_more(false).strip(false) }}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertNotContains('</p>', $str);
		}

		function testPostExcerptObjectWithCharAndWordLengthWordsWin() {
			$pid = $this->factory->post->create( array('post_content' => $this->gettysburg, 'post_excerpt' => '') );
			$template = '{{ post.excerpt.length(2).chars(20) }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Four score&hellip; <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a>', $str);
		}

		function testPostExcerptObjectWithCharAndWordLengthCharsWin() {
			$pid = $this->factory->post->create( array('post_content' => $this->gettysburg, 'post_excerpt' => '') );
			$template = '{{ post.excerpt.length(20).chars(20) }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Four score and seven&hellip; <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a>', $str);
		}

		function testPostExcerptObjectWithCharLength() {
			$pid = $this->factory->post->create( array('post_content' => $this->gettysburg, 'post_excerpt' => '') );
			$template = '{{ post.excerpt.chars(20) }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Four score and seven&hellip; <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a>', $str);
		}

		function testPostExcerptObjectWithLength() {
			$pid = $this->factory->post->create( array('post_content' => 'Lauren is a duck she a big ole duck!', 'post_excerpt' => '') );
			$template = '{{ post.excerpt.length(4) }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Lauren is a duck&hellip; <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a>', $str);
		}

		function testPostExcerptObjectWithForcedLength() {
			$pid = $this->factory->post->create( array('post_content' => 'Great Gatsby', 'post_excerpt' => 'In my younger and more vulnerable years my father gave me some advice that I’ve been turning over in my mind ever since.') );
			$template = '{{ post.excerpt.force.length(3) }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('In my younger&hellip; <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a>', $str);
		}

		function testPostExcerptObject() {
			$pid = $this->factory->post->create( array('post_content' => 'Great Gatsby', 'post_excerpt' => 'In my younger and more vulnerable years my father gave me some advice that I’ve been <a href="http://google.com">turning over</a> in my mind ever since.') );
			$template = '{{ post.excerpt }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('In my younger and more vulnerable years my father gave me some advice that I’ve been turning over in my mind ever since. <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a>', $str);
		}

		function testPostExcerptObjectStrip() {
			$pid = $this->factory->post->create( array('post_content' => 'Great Gatsby', 'post_excerpt' => 'In my younger and more vulnerable years my father gave me some advice that I’ve been <a href="http://google.com">turning over</a> in my mind ever since.') );
			$template = '{{ post.excerpt.strip(false) }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('In my younger and more vulnerable years my father gave me some advice that I’ve been <a href="http://google.com">turning over</a> in my mind ever since. <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a>', $str);
		}

		function testPostExcerptObjectWithReadMore() {
			$pid = $this->factory->post->create( array('post_content' => 'Great Gatsby', 'post_excerpt' => 'In my younger and more vulnerable years my father gave me some advice that I’ve been turning over in my mind ever since.') );
			$template = '{{ post.excerpt.read_more("Keep Reading") }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('In my younger and more vulnerable years my father gave me some advice that I’ve been turning over in my mind ever since. <a href="http://example.org/?p='.$pid.'" class="read-more">Keep Reading</a>', $str);
		}

		function testPostExcerptObjectWithEverything() {
			$pid = $this->factory->post->create( array('post_content' => 'Great Gatsby', 'post_excerpt' => 'In my younger and more vulnerable years my father gave me some advice that I’ve been turning over in my mind ever since.') );
			$template = '{{ post.excerpt.length(6).force.end("-->").read_more("Keep Reading") }}';
			$post = Timber::get_post($pid);
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('In my younger and more vulnerable--> <a href="http://example.org/?p='.$pid.'" class="read-more">Keep Reading</a>', $str);
		}

		function testExcerptWithMoreTagAndForcedLength() {
			$pid = $this->factory->post->create( array('post_content' => 'Lauren is a duck<!-- more--> Lauren is not a duck', 'post_excerpt' => '') );
			$post = Timber::get_post( $pid );

			$this->assertEquals(
				'Lauren is a duck <a href="'.$post->link().'" class="read-more">Read More</a>',
				(string) $post->excerpt()
			);
		}

		function testExcerptWithCustomMoreTag() {
			$pid = $this->factory->post->create( array('post_content' => 'Eric is a polar bear <!-- more But what is Elaina? --> Lauren is not a duck', 'post_excerpt' => '') );
			$post = Timber::get_post( $pid );
			$this->assertEquals('Eric is a polar bear <a href="'.$post->link().'" class="read-more">But what is Elaina?</a>', $post->excerpt());
		}

		function testExcerptWithSpaceInMoreTag() {
			$pid = $this->factory->post->create( array('post_content' => 'Lauren is a duck, but a great duck let me tell you why <!--more--> Lauren is not a duck', 'post_excerpt' => '') );
			$post = Timber::get_post( $pid );
			$template = '{{post.excerpt.length(3).force}}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('Lauren is a&hellip; <a href="'.$post->link().'" class="read-more">Read More</a>', $str);
		}

		function testExcerptWithStripAndClosingPTag() {
			$pid = $this->factory->post->create( array('post_excerpt' => '<p>Lauren is a duck, but a great duck let me tell you why</p>') );
			$post = Timber::get_post( $pid );
			$template = '{{post.excerpt.strip(false)}}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('<p>Lauren is a duck, but a great duck let me tell you why <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a></p>', $str);
		}

		function testExcerptWithStripAndClosingPTagForced() {
			$pid = $this->factory->post->create( array('post_excerpt' => '<p>Lauren is a duck, but a great duck let me tell you why</p>') );
			$post = Timber::get_post( $pid );
			$template = '{{post.excerpt.strip(false).force(4)}}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('<p>Lauren is a duck, but a great duck let me tell you why&hellip;  <a href="http://example.org/?p='.$pid.'" class="read-more">Read More</a></p>', $str);
		}

		function testEmptyExcerpt() {
			$pid = $this->factory->post->create( array('post_excerpt' => '', 'post_content' => '') );
			$post = Timber::get_post( $pid );
			$template = '{{ post.excerpt }}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals('', $str);
		}

		/**
		 * @ticket #2045
		 */
		function testPageExcerptOnSearch() {
			$pid = $this->factory->post->create(array('post_type' => 'page', 'post_content' => 'What a beautiful day for a ballgame!', 'post_excerpt' => ''));
			$post = Timber::get_post( $pid );
			$template = '{{ post.excerpt }}';
			$str = Timber::compile_string($template, array('post' => $post));
			$this->assertEquals(
				'What a beautiful day for a ballgame!',
				$str
			);
		}
	}
