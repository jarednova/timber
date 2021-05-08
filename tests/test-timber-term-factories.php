<?php

/**
 * @group terms-api
 */
class TestTimberTermFactories extends Timber_UnitTestCase {

	function setUp() {
		$this->truncate('term_relationships');
		$this->truncate('term_taxonomy');
		$this->truncate('terms');
		$this->truncate('termmeta');
		parent::setUp();
	}

	function testGetTerm() {
		$term_id = $this->factory->term->create(['name' => 'Thingo', 'taxonomy' => 'post_tag']);
		$term = Timber::get_term($term_id);
		$this->assertEquals('Thingo', $term->name);
	}

	/**
	 * @expectedDeprecated Term::from()
	 */
	function testGetMultiTerm() {
		register_taxonomy('cars', 'post');
		$term_ids[] = $this->factory->term->create(['name' => 'Toyota', 'taxonomy' => 'cars']);
		$term_ids[] = $this->factory->term->create(['name' => 'Honda', 'taxonomy' => 'cars']);
		$term_ids[] = $this->factory->term->create(['name' => 'Chevy', 'taxonomy' => 'cars']);
		$post_id = $this->factory->post->create();
		wp_set_object_terms( $post_id, $term_ids, 'cars' );

		$term_get = Timber::get_terms(['taxonomy' => 'cars']);
		$this->assertEquals('Chevy', $term_get[0]->title());

		$terms_from = Timber\Term::from(get_terms(['taxonomy' => 'cars']), 'cars');
		$this->assertEquals('Chevy', $terms_from[0]->title());
	}

	/**
	 * @expectedDeprecated Term::from()
	 */
	function testTermFrom() {
		register_taxonomy('baseball', array('post'));
		register_taxonomy('hockey', array('post'));
		$term_id = $this->factory->term->create(array('name' => 'Rangers', 'taxonomy' => 'baseball'));
		$term_id = $this->factory->term->create(array('name' => 'Cardinals', 'taxonomy' => 'baseball'));
		$term_id = $this->factory->term->create(array('name' => 'Rangers', 'taxonomy' => 'hockey'));
		$baseball_teams = Timber\Term::from(get_terms(array('taxonomy' => 'baseball', 'hide_empty' => false)), 'baseball');
		$this->assertEquals(2, count($baseball_teams));
		$this->assertEquals('Cardinals', $baseball_teams[0]->name);
	}

	/**
	 * @expectedDeprecated Term::from()
	 */
	function testGetSingleTermFrom() {
		register_taxonomy('cars', 'post');
		$term_id = $this->factory->term->create(['name' => 'Toyota', 'taxonomy' => 'cars']);
		$post_id = $this->factory->post->create();
		wp_set_object_terms( $post_id, $term_id, 'cars' );

		$term_from = Timber\Term::from(get_terms(['taxonomy' => 'cars', 'hide_empty' => false]), 'cars');
		$this->assertEquals($term_id, $term_from[0]->ID);

		$term_get = Timber::get_term(['taxonomy' => 'cars']);
		$this->assertEquals($term_id, $term_get->ID);
	}
}