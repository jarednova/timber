<?php

	class TestTimberImageMultisite extends Timber_UnitTestCase {

		function tearDown() {
			if (is_multisite()) {
				switch_to_blog(1);
			}
			TestTimberMultisite::clear();
		}

		function testSubDomainImageLocaion() {
			if ( !is_multisite() ) {
				$this->markTestSkipped('Test is only for Multisite');
				return;
			}
			$blog_id = TestTimberMultisite::createSubDomainSite();
			$this->assertGreaterThan(1, $blog_id);
			$pretend_image = 'http://example.org/wp-content/2015/08/fake-pic.jpg';
			$is_external = Timber\URLHelper::is_external_content( $pretend_image );
			$this->assertFalse($is_external);
		}

		function testSubDirectoryImageLocaion() {
			if ( !is_multisite() ) {
				$this->markTestSkipped('Test is only for Multisite');
				return;
			}
			$blog_id = TestTimberMultisite::createSubDirectorySite();
			$this->assertGreaterThan(1, $blog_id);
			$blog_details = get_blog_details($blog_id);
			$pretend_image = 'http://example.org/wp-content/2015/08/fake-pic.jpg';
			$is_external = Timber\URLHelper::is_external_content( $pretend_image );
			$this->assertFalse($is_external);
		}

	}
