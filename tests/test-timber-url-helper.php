<?php

class TestTimberURLHelper extends Timber_UnitTestCase {

	private $mockUploadDir = false;

	function setUp() {
		$_SERVER['SERVER_PORT'] = 80;
	}

	function testHTTPSCurrentURL() {
		$this->go_to('/');
		$_SERVER['HTTPS'] = 'on';
		$_SERVER['SERVER_PORT'] = 443;
		$url = Timber\URLHelper::get_current_url();
		$this->assertEquals('https://example.org/', trailingslashit($url));
		$_SERVER['HTTPS'] = 'off';
		unset($_SERVER['HTTPS']);
	}

	function testSwapProtocolHTTPtoHTTPS() {
		$url = 'http://nytimes.com/news/reports/2017';
		$url = Timber\URLHelper::swap_protocol($url);
		$this->assertStringStartsWith('https://', $url);
	}

	function testSwapProtocolHTTPStoHTTP() {
		$url = 'https://nytimes.com/news/reports/2017';
		$url = Timber\URLHelper::swap_protocol($url);
		$this->assertStringStartsWith('http://', $url);
	}

	function testStartsWith() {
		$haystack = 'http://nytimes.com/news/reports/2017';
		$starts_with = 'http://nytimes.com/news';
		$nope = 'http://bostonglobe.com';
		$this->assertTrue(Timber\URLHelper::starts_with($haystack, $starts_with));
		$this->assertFalse(Timber\URLHelper::starts_with($haystack, $nope));
	}

	function testStartsWithHTTPs() {
		$haystack = 'http://nytimes.com/news/reports/2017';
		$starts_with = 'https://nytimes.com/news';
		$nope = 'http://bostonglobe.com';
		$this->assertTrue(Timber\URLHelper::starts_with($haystack, $starts_with));
		$this->assertFalse(Timber\URLHelper::starts_with($haystack, $nope));
	}

	function testStartsWithHTTPsFlip() {
		$haystack = 'https://nytimes.com/news/reports/2017';
		$starts_with = 'http://nytimes.com/news';
		$nope = 'http://bostonglobe.com';
		$this->assertTrue(Timber\URLHelper::starts_with($haystack, $starts_with));
		$this->assertFalse(Timber\URLHelper::starts_with($haystack, $nope));
	}

	function testFileSystemToURLWithWPML() {
		self::_setLanguage();
		add_filter('home_url', array($this, 'addWPMLHomeFilterForRegExTest'), 10, 2);
		$image = TestTimberImage::copyTestAttachment();

		$url = Timber\URLHelper::file_system_to_url($image);
		$this->assertStringEndsWith('://example2.org/wp-content/uploads/'.date('Y/m').'/arch.jpg', $url);
		remove_filter('home_url', array($this, 'addWPMLHomeFilterForRegExTest'));
	}

	function addWPMLHomeFilterForRegExTest($url, $path) {
		return 'http://example2.org/en'.$path;
	}

	function testFileSystemToURL() {
		$image = TestTimberImage::copyTestAttachment();
		$url = Timber\URLHelper::file_system_to_url($image);
		$this->assertStringEndsWith('://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpg', $url);
	}

	function addWPMLHomeFilter($url, $path) {
		return 'http://example.org/en'.$path;
	}

	function _setLanguage() {
		if ( !defined('ICL_LANGUAGE_CODE') ) {
			define('ICL_LANGUAGE_CODE', 'en');
		}
	}

	function _setupWPMLDirectory() {
		self::_setLanguage();
		add_filter('home_url', array($this, 'addWPMLHomeFilter'), 10, 2);
	}

	function testFileSystemToURLWithWPMLPrefix() {
		self::_setupWPMLDirectory();
		$image = TestTimberImage::copyTestAttachment();
		$url = Timber\URLHelper::file_system_to_url($image);
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpg', $url);
		remove_filter('home_url', array($this, 'addWPMLHomeFilter'));
	}

	function testContentSubDirectory() {
		$subdir = Timber\URLHelper::get_content_subdir();
		$this->assertEquals('/wp-content', $subdir);
	}

	function testURLToFileSystem() {
		$url = 'http://example.org/wp-content/uploads/2012/06/mypic.jpg';
		$file = Timber\URLHelper::url_to_file_system($url);
		$this->assertStringStartsWith(ABSPATH, $file);
		$this->assertStringEndsWith('/2012/06/mypic.jpg', $file);
		$this->assertNotContains($file, 'http://example.org');
		$this->assertNotContains($file, '//');
	}

	function testGetHost() {
		$http_host = $_SERVER['HTTP_HOST'];
		$server_name = $_SERVER['SERVER_NAME'];
		$_SERVER['HTTP_HOST'] = '';
		$_SERVER['SERVER_NAME'] = 'example.org';
		$host = Timber\URLHelper::get_host();
		$this->assertEquals('example.org', $host);
		$_SERVER['HTTP_HOST'] = $http_host;
		$_SERVER['SERVER_NAME'] = $server_name;
	}

	function testGetHostEmpty() {
		$http_host = $_SERVER['HTTP_HOST'];
		$server_name = $_SERVER['SERVER_NAME'];
		$_SERVER['HTTP_HOST'] = '';
		$_SERVER['SERVER_NAME'] = '';
		$host = Timber\URLHelper::get_host();
		$this->assertEquals('', $host);
		$_SERVER['HTTP_HOST'] = $http_host;
		$_SERVER['SERVER_NAME'] = $server_name;
	}

	function testPrepend() {
		$joined = Timber\URLHelper::prepend_to_url('example.com', '/thing/foo');
		$this->assertEquals('example.com/thing/foo', $joined);
	}

	function testPrependWithPort() {
		$joined = Timber\URLHelper::prepend_to_url('http://example.com:8080/thing/', '/jiggly');
		$this->assertEquals('http://example.com:8080/jiggly/thing/', $joined);
	}

	function testPrependWithFragment() {
		$joined = Timber\URLHelper::prepend_to_url('http://example.com/thing/#foo', '/jiggly');
		$this->assertEquals('http://example.com/jiggly/thing/#foo', $joined);
	}

	function testPrependWithQuery() {
		$joined = Timber\URLHelper::prepend_to_url('http://example.com/?s=foo&jolly=good', '/search');
		$this->assertEquals('http://example.com/search/?s=foo&jolly=good', $joined);
	}

	function testUserTrailingSlashIt() {
		global $wp_rewrite;
		$wp_rewrite->use_trailing_slashes = true;
		$link = '2016/04/my-silly-story';
		$url = Timber\URLHelper::user_trailingslashit($link);
		$this->assertEquals($link.'/', $url);
		$wp_rewrite->use_trailing_slashes = false;
	}

	function testDoubleSlashesWithHTTP() {
		$url = 'http://nytimes.com/news//world/thing.html';
		$expected_url = 'http://nytimes.com/news/world/thing.html';
		$url = Timber\URLHelper::remove_double_slashes($url);
		$this->assertEquals($expected_url, $url);
	}

	function testDoubleSlashesWithHTTPS() {
		$url = 'https://nytimes.com/news//world/thing.html';
		$expected_url = 'https://nytimes.com/news/world/thing.html';
		$url = Timber\URLHelper::remove_double_slashes($url);
		$this->assertEquals($expected_url, $url);
	}

	function testDoubleSlashesWithS3() {
		$url = 's3://bucket/folder//thing.html';
		$expected_url = 's3://bucket/folder/thing.html';
		$url = Timber\URLHelper::remove_double_slashes($url);
		$this->assertEquals($expected_url, $url);
	}

	function testDoubleSlashesWithGS() {
		$url = 'gs://bucket/folder//thing.html';
		$expected_url = 'gs://bucket/folder/thing.html';
		$url = Timber\URLHelper::remove_double_slashes($url);
		$this->assertEquals($expected_url, $url);
	}

	function testUserTrailingSlashItFailure() {
		$link = 'http:///example.com';
		$url = Timber\URLHelper::user_trailingslashit($link);
		$this->assertEquals($link, $url);
	}

	function testUnPreSlashIt() {
		$str = '/wp-content/themes/undefeated/style.css';
		$str = Timber\URLHelper::unpreslashit($str);
		$this->assertEquals('wp-content/themes/undefeated/style.css', $str);
	}

	function testPreSlashIt() {
		$before = 'thing/foo';
		$after = Timber\URLHelper::preslashit($before);
		$this->assertEquals('/'.$before, $after);
	}

	function testPreSlashItNadda() {
			$before = '/thing/foo';
			$after = Timber\URLHelper::preslashit($before);
			$this->assertEquals($before, $after);
	}

	function testPathBase() {
			$struc = '/%year%/%monthnum%/%postname%/';
			$this->setPermalinkStructure( $struc );
		$this->assertEquals('/', Timber\URLHelper::get_path_base());
	}

	function testIsLocal() {
		$this->assertFalse(Timber\URLHelper::is_local('http://wordpress.org'));
	}

	function testCurrentURLWithServerPort() {
		$old_port = $_SERVER['SERVER_PORT'];
		$_SERVER['SERVER_PORT'] = 3000;
		if (!isset($_SERVER['SERVER_NAME'])){
		$_SERVER['SERVER_NAME'] = 'example.org';
		}
		$this->go_to('/');
		$url = Timber\URLHelper::get_current_url();
		$this->assertEquals('http://example.org:3000/', $url);
		$_SERVER['SERVER_PORT'] = $old_port;
	}

	function testCurrentURL() {
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['SERVER_NAME'] = 'example.org';
		$this->go_to('/');
		$url = Timber\URLHelper::get_current_url();
		$this->assertEquals('http://example.org/', $url);
	}

	function testCurrentURLIsSecure(){
		if (!isset($_SERVER['SERVER_PORT'])){
			$_SERVER['SERVER_PORT'] = 443;
		}
		if (!isset($_SERVER['SERVER_NAME'])){
			$_SERVER['SERVER_NAME'] = 'example.org';
		}
		$_SERVER['HTTPS'] = 'on';
		$this->go_to('/');
		$url = Timber\URLHelper::get_current_url();
		$this->assertEquals('https://example.org/', $url);
	}

	function testUrlSchemeIsSecure() {
		$_SERVER['HTTPS'] = 'on';
		$scheme = Timber\URLHelper::get_scheme();
		$this->assertEquals('https', $scheme);
	}

	function testUrlSchemeIsNotSecure() {
		$_SERVER['HTTPS'] = 'off';
		$scheme = Timber\URLHelper::get_scheme();
		$this->assertEquals('http', $scheme);
	}

	function testIsURL(){
		$url = 'http://example.org';
		$not_url = '/blog/2014/05/whatever';
		$this->assertTrue(Timber\URLHelper::is_url($url));
		$this->assertFalse(Timber\URLHelper::is_url($not_url));
		$this->assertFalse(Timber\URLHelper::is_url(8000));
	}

	function testIsExternal(){
		$local = 'http://example.org';
		$subdomain = 'http://cdn.example.org';
		$external = 'http://upstatement.com';
		$protocol_relative = '//upstatement.com';

		$this->assertFalse(Timber\URLHelper::is_external($local));
		$this->assertFalse(Timber\URLHelper::is_external($subdomain));
		$this->assertTrue(Timber\URLHelper::is_external($external));
		$this->assertTrue(Timber\URLHelper::is_external($protocol_relative));
	}

	function testIsExternalContent() {
		$internal = 'http://example.org/wp-content/uploads/my-image.png';
		$internal_in_abspath = 'http://example.org/wp/uploads/my-image.png';
		$internal_in_uploads = 'http://example.org/uploads/uploads/my-image.png';
		$external = 'http://upstatement.com/my-image.png';

		$this->assertFalse( Timber\URLHelper::is_external_content( $internal ) );
		$this->assertTrue( Timber\URLHelper::is_external_content( $internal_in_uploads ) );
		$this->assertTrue( Timber\URLHelper::is_external_content( $internal_in_abspath ) );
		$this->assertTrue( Timber\URLHelper::is_external_content( $external ) );
	}

	function testIsExternalContentMovingFolders() {
		$internal = 'http://example.org/wp-content/uploads/my-image.png';
		$internal_in_abspath = 'http://example.org/wp/uploads/my-image.png';
		$internal_in_uploads = 'http://example.org/uploads/my-image.png';
		$external = 'http://upstatement.com/my-image.png';

		add_filter( 'upload_dir', array( &$this, 'mockUploadDir' ) );
		add_filter( 'content_url', array( &$this, 'mockContentUrl' ) );

		$this->mockUploadDir = true;

		$this->assertFalse( Timber\URLHelper::is_external_content( $internal ) );
		$this->assertFalse( Timber\URLHelper::is_external_content( $internal_in_uploads ) );
		$this->assertFalse( Timber\URLHelper::is_external_content( $internal_in_abspath ) );
		$this->assertTrue( Timber\URLHelper::is_external_content( $external ) );

		$this->mockUploadDir = false;
	}

	function mockContentUrl($url) {
		return ( $this->mockUploadDir ) ? site_url( 'wp' ) : $url;
	}

	function mockUploadDir($path) {
		if ( $this->mockUploadDir ) {
			$path['url'] = str_replace( $path['baseurl'], site_url().'/uploads', $path['url'] );
			$path['baseurl'] = site_url().'/uploads';

			$path['path'] = str_replace( $path['basedir'], ABSPATH.'uploads', $path['path'] );
			$path['basedir'] = ABSPATH . 'uploads';

			$path['relative'] = '/uploads';
		}

		return $path;
	}

	function testGetRelURL(){
		$local = 'http://example.org/directory';
		$subdomain = 'http://cdn.example.org/directory';
		$external = 'http://upstatement.com';
		$rel_url = '/directory/';
		$this->assertEquals('/directory', Timber\URLHelper::get_rel_url($local));
		$this->assertEquals($subdomain, Timber\URLHelper::get_rel_url($subdomain));
		$this->assertEquals($external, Timber\URLHelper::get_rel_url($external));
		$this->assertEquals($rel_url, Timber\URLHelper::get_rel_url($rel_url));
	}

	function testRemoveTrailingSlash(){
		$url_with_trailing_slash = 'http://example.org/directory/';
		$root_url = "/";
		$this->assertEquals('http://example.org/directory', Timber\URLHelper::remove_trailing_slash($url_with_trailing_slash));
		$this->assertEquals('/', Timber\URLHelper::remove_trailing_slash($root_url));
	}

	function testGetParams(){
		$_SERVER['REQUEST_URI'] = 'http://example.org/blog/post/news/2014/whatever';
		$params = Timber\URLHelper::get_params();
		$this->assertEquals(7, count($params));
		$whatever = Timber\URLHelper::get_params(-1);
		$blog = Timber\URLHelper::get_params(2);
		$this->assertEquals('whatever', $whatever);
		$this->assertEquals('blog', $blog);
	}

	function testGetParamsNada(){
		$_SERVER['REQUEST_URI'] = 'http://example.org/blog/post/news/2014/whatever';
		$params = Timber\URLHelper::get_params(93);
		$this->assertFalse($params);
	}
}
