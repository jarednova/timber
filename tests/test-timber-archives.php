<?php

	class TestTimberArchives extends Timber_UnitTestCase {

		function testArchivesLimit(){
			$dates = array('2013-11-08', '2013-12-08', '2013-11-09', '2013-06-08', '2013-03-03');
			foreach( $dates as $date ) {
				$this->factory->post->create(array('post_date' => $date.' 19:46:41'));
			}
			$this->go_to('/');
			$archives = new Timber\Archives(array('type' => 'monthly', 'show_year' => false, 'limit' => 3));
			$this->assertEquals(3, count($archives->items));
			$this->assertEquals(2, $archives->items[1]['post_count']);
		}


		function testArchiveMonthly(){
			$dates = array('2013-11-08', '2013-12-08', '2013-11-09', '2013-06-08');
			foreach( $dates as $date ) {
				$this->factory->post->create(array('post_date' => $date.' 19:46:41'));
			}
			$this->go_to('/');
			$archives = new Timber\Archives(array('type' => 'monthly', 'show_year' => false));
			$this->assertEquals('December', $archives->items[0]['name']);
			$this->assertEquals(3, count($archives->items));
			$this->assertEquals(2, $archives->items[1]['post_count']);
			$archives = new Timber\Archives(array('type' => 'monthly', 'show_year' => true));
			$this->assertEquals('December 2013', $archives->items[0]['name']);
		}

		function testArchiveYearly(){
			$dates = array('2011-11-08', '2011-12-08', '2013-11-09', '2014-07-04');
			foreach( $dates as $date ) {
				$this->factory->post->create(array('post_date' => $date.' 19:46:41'));
			}
			$this->go_to('/');
			$archives = new Timber\Archives(array('type' => 'yearly', 'show_year' => false));
			$this->assertEquals(3, count($archives->items));
			$this->assertEquals(2, $archives->items[2]['post_count']);
		}

		function testArchiveDaily(){
			$dates = array('2013-11-08', '2013-12-08', '2013-11-09', '2013-11-09', '2013-06-08', '2014-01-08'
				);
			foreach( $dates as $date ) {
				$this->factory->post->create(array('post_date' => $date.' 19:46:41'));
			}
			$this->go_to('/');
			$archives = new Timber\Archives(array('type' => 'daily'));
			$this->assertEquals(5, count($archives->items));
			$this->assertEquals(2, $archives->items[2]['post_count']);
		}

		function testArchiveYearlyMonthly(){
			$dates = array('2013-11-08', '2013-12-08', '2013-11-09', '2013-06-08', '2014-01-08'
				);
			foreach( $dates as $date ) {
				$this->factory->post->create(array('post_date' => $date.' 19:46:41'));
			}
			$this->go_to('/');
			$archives = new Timber\Archives(array('type' => 'monthly-nested'));
			$this->assertEquals(2, count($archives->items));
			$this->assertEquals(4, $archives->items[1]['post_count']);
			$this->assertEquals(2, $archives->items[1]['children'][1]['post_count']);
			$archives = new Timber\Archives(array('type' => 'yearlymonthly'));
			$this->assertEquals(2, count($archives->items));
			$this->assertEquals(4, $archives->items[1]['post_count']);
			$this->assertEquals(2, $archives->items[1]['children'][1]['post_count']);
		}

		function testArchiveWeekly(){
			$dates = array('2015-03-02', '2015-03-09', '2015-03-16', '2015-03-21', '2015-03-22'
				);
			foreach( $dates as $date ) {
				$this->factory->post->create(array('post_date' => $date.' 19:46:41'));
			}
			$this->go_to('/');
			$archives = new Timber\Archives(array('type' => 'weekly'));
			$this->assertEquals(3, count($archives->items));
			$this->assertEquals(3, $archives->items[0]['post_count']);
		}

		function testArchiveAlpha(){
			$posts = array(
				array('date' => '2015-03-02', 'post_title' => 'Jared loves Lauren'),
				array('date' => '2015-03-02', 'post_title' => 'Another fantastic post'),
				array('date' => '2015-03-02', 'post_title' => 'Foobar'),
				array('date' => '2015-03-02', 'post_title' => 'Quack Quack'),
			);
			foreach( $posts as $post ) {
				$this->factory->post->create(array('post_date' => $post['date'].' 19:46:41', 'post_title' => $post['post_title']));
			}
			$this->go_to('/');
			$archives = new Timber\Archives(array('type' => 'alpha'));
			$this->assertEquals(4, count($archives->items));
			$this->assertEquals('Quack Quack', $archives->items[3]['name']);
		}

		function testArchivesWithArgs() {
			register_post_type('book');
			$dates = array('2013-11-08', '2013-12-08', '2013-11-09', '2013-06-08', '2014-01-08'
				);
			foreach($dates as $date) {
				$this->factory->post->create(array('post_date' => $date. ' 19:46:41'));
			}
			$dates = array('2014-11-08', '2014-12-08', '2014-11-09', '2014-06-08', '2015-01-08', '2015-02-14'
				);
			foreach($dates as $date) {
				$this->factory->post->create(array('post_date' => $date. ' 19:46:41', 'post_type' => 'book'));
			}
			$this->go_to('/');
			$archives = new Timber\Archives();

			$this->assertEquals(2, count($archives->items));
			$archives = new Timber\Archives(array('post_type' => 'book', 'type' => 'monthly'));
			$this->assertEquals(5, count($archives->items));
		}

	}
