<?php

	/**
	 * @group posts-api
	 */
	class TestTimberPostContent extends Timber_UnitTestCase {


	function testContent(){
		$quote = 'The way to do well is to do well.';
		$post_id = $this->factory->post->create();
		$post = Timber::get_post( $post_id );
		$post->post_content = $quote;
		wp_update_post($post);
		$this->assertEquals($quote, trim(strip_tags($post->content())));
	}

	function testContentPaged(){
		$quote = $page1 = 'The way to do well is to do well.';
		$quote .= '<!--nextpage-->';
		$quote .= $page2 = "And do not let your tongue get ahead of your mind.";

		$post_id = $this->factory->post->create();
		$post = Timber::get_post( $post_id );
		$post->post_content = $quote;
		wp_update_post($post);

		$this->assertEquals($page1, trim(strip_tags($post->content(1))));
		$this->assertEquals($page2, trim(strip_tags($post->content(2))));
	}

	function testPagedContent(){
		$quote = $page1 = 'Named must your fear be before banish it you can.';
		$quote .= '<!--nextpage-->';
		$quote .= $page2 = "No, try not. Do or do not. There is no try.";

		$post_id = $this->factory->post->create(array('post_content' => $quote));

		$this->go_to( get_permalink( $post_id ) );

		setup_postdata( get_post( $post_id ) );

		$post = Timber::get_post();
		$this->assertEquals($page1, trim(strip_tags( $post->paged_content() )));

		$pagination = $post->pagination();
		$this->go_to( $pagination['pages'][1]['link'] );

		setup_postdata( get_post( $post_id ) );
		$post = Timber::get_post();

		$this->assertEquals($page2, trim(strip_tags( $post->paged_content() )));
	}

	/**
	 * @ticket 2218
	 */
	function testGutenbergExcerptOption() {
		global $wp_version;
		if ( $wp_version < 5.0 ) {
			$this->markTestSkipped('Only applies to Block editor which is avaialble in WP 5.x');
		}
		$content_1 = '<!-- wp:paragraph -->
<p>Heres the start to a thing</p>
<!-- /wp:paragraph -->

<!-- wp:more {"noTeaser":true} -->
<!--more-->
<!--noteaser-->
<!-- /wp:more -->

<!-- wp:paragraph -->
<p>Heres the read more stuff that we shant see!</p>
<!-- /wp:paragraph -->';
		$post_id = $this->factory->post->create(['post_content' => $content_1 ]);
		$post = Timber::get_post($post_id);
		
		$this->assertEquals('<p>Heres the read more stuff that we shant see!</p>', trim($post->content()));
	}

}
