<?php

/**
 * @group terms-api
 * @group posts-api
 */
class TestTimberPages extends Timber_UnitTestCase {

	function testTimberPostOnCategoryPage() {
		$post_id = $this->factory->post->create();
		$category_id = $this->factory->term->create(array('taxonomy' => 'category', 'name' => 'News'));
		$cat = Timber::get_term($category_id);
		$this->go_to($cat->path());
		$term = Timber::get_term();
		$this->assertEquals($category_id, $term->ID);
		$this->assertFalse(Timber::get_post());
	}



}
