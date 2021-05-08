<?php

class TestTimberSite extends Timber_UnitTestCase {

	function testStandardThemeLocation() {
		switch_theme( 'twentyfifteen' );
		$site = new \Timber\Site();
		$content_subdir = Timber\URLHelper::get_content_subdir();
		$this->assertEquals( $content_subdir.'/themes/twentyfifteen', $site->theme->path );
	}

	function testLanguageAttributes() {
		$this->restore_locale();
		$site = new \Timber\Site();
		$lang = $site->language_attributes();
		$this->assertEquals('lang="en-US"', $lang);
	}

	function testChildParentThemeLocation() {
		TestTimberLoader::_setupChildTheme();
		$content_subdir = Timber\URLHelper::get_content_subdir();
		$this->assertFileExists( WP_CONTENT_DIR.'/themes/fake-child-theme/style.css' );
		switch_theme( 'fake-child-theme' );
		$site = new Timber\Site();
		$this->assertEquals( $content_subdir.'/themes/fake-child-theme', $site->theme->path );
		$this->assertEquals( $content_subdir.'/themes/twentyfifteen', $site->theme->parent->path );
	}

	function testThemeFromContext() {
		switch_theme( 'twentyfifteen' );
		$context = Timber::context();
		$this->assertEquals( 'twentyfifteen', $context['theme']->slug );
	}

	function testThemeFromSiteContext() {
		switch_theme( 'twentyfifteen' );
		$context = Timber::context();
		$this->assertEquals( 'twentyfifteen', $context['site']->theme->slug );
	}

	function testSiteURL() {
		$site = new \Timber\Site();
		$this->assertEquals( 'http://example.org', $site->link() );
		$this->assertEquals(site_url(), $site->site_url);
	}

	function testHomeUrl() {
		$site = new \Timber\Site();
		$this->assertEquals($site->url, $site->home_url);
	}

	function testSiteIcon() {
		$icon_id = TestTimberImage::get_attachment(0, 'cardinals.jpg');
		update_option('site_icon', $icon_id);
		$site = new Timber\Site();
		$icon = $site->icon();
		$this->assertEquals('Timber\Image', get_class($icon));
		$this->assertContains('cardinals.jpg', $icon->src());
	}


	function testNullIcon() {
		delete_option('site_icon');
		$site = new Timber\Site();
		$this->assertNull($site->icon());
	}

	function testSiteGet() {
		update_option( 'foo', 'bar' );
		$site = new Timber\Site();
		$this->assertEquals( 'bar', $site->foo );
	}

	/**
	 * @expectedDeprecated {{ site.meta() }}
	 */
	function testSiteMeta() {
		$ts = new Timber\Site();
		update_option('foo', 'magoo');
		$this->assertEquals('magoo', $ts->meta('foo'));
	}

	function testSiteOption() {
		$ts = new Timber\Site();
		update_option('date_format', 'j. F Y');
		$this->assertEquals('j. F Y', $ts->option('date_format'));
	}

	function setUp() {
		global $wp_theme_directories;

		parent::setUp();

		$this->backup_wp_theme_directories = $wp_theme_directories;
		$wp_theme_directories = array( WP_CONTENT_DIR . '/themes' );

		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );

	}

	function tearDown() {
		global $wp_theme_directories;

		$wp_theme_directories = $this->backup_wp_theme_directories;

		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );
		parent::tearDown();
	}
}
