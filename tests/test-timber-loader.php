<?php

	class TestTimberLoader extends Timber_UnitTestCase {

		function testTwigLoaderFilter() {
		    $php_unit = $this;
		    add_filter('timber/loader/loader', function ($loader) use ($php_unit) {
		        $php_unit->assertInstanceOf('Twig_LoaderInterface', $loader);
		        return $loader;
		    });
		    $str = Timber::compile('assets/single.twig', array());
		}

		function testBogusTemplate() {
			$str = Timber::compile('assets/darkhelmet.twig');
			$this->assertFalse($str);
		}

		function testBogusTemplates() {
			$str = Timber::compile( array('assets/barf.twig', 'assets/lonestar.twig') );
			$this->assertFalse($str);
		}

		function testTemplateChainWithMissingTwigFiles() {
			$str = Timber::compile( array('assets/lonestar.twig', 'assets/single.twig') );
			$this->assertEquals('I am single.twig', trim($str));
		}

		function testWhitespaceTrimForTemplate(){
			$str = Timber::compile('assets/single.twig ', array());
			$this->assertEquals('I am single.twig', trim($str));
		}

		/**
		 * @expectedDeprecated  timber/loader/paths
		 * @expectedDeprecated add_filter( 'timber/loader/paths', ['path/to/my/templates'] ) in a non-associative array
		 */
		function testTwigPathFilterAdded() {
			$php_unit = $this;
			add_filter('timber/loader/paths', function($paths) use ($php_unit) {
				$paths[] = __DIR__.'/october/';
				return $paths;
			});
			$str = Timber::compile('spooky.twig', array());
			$this->assertEquals('Boo!', $str);
		}

		/**
		 * @expectedDeprecated  timber/loader/paths
		 */
		function testUpdatedTwigPathFilterAdded() {
			$php_unit = $this;
			add_filter('timber/loader/paths', function($paths) use ($php_unit) {
				$paths[] = array( __DIR__ . '/october/' );
				return $paths;
			});
			$str = Timber::compile('spooky.twig', array());
			$this->assertEquals('Boo!', $str);
		}

		/**
		 * @expectedDeprecated  timber/loader/paths
		 * @expectedDeprecated add_filter( 'timber/loader/paths', ['path/to/my/templates'] ) in a non-associative array
		 */
		function testTwigPathFilter() {
			$php_unit = $this;
			add_filter('timber/loader/paths', function($paths) use ($php_unit) {
				$paths = call_user_func_array('array_merge', $paths);
				$count = count($paths);
				$php_unit->assertEquals(3, count($paths));
				$pos = array_search('/', $paths);
				unset($paths[$pos]);
				$php_unit->assertEquals(2, count($paths));
				return $paths;
			});
			$str = Timber::compile('assets/single.twig', array());
		}

		function testTimberLocationsFilterAdded() {
			$php_unit = $this;
			add_filter('timber/locations', function($paths) use ($php_unit) {
				$paths[] = array( __DIR__ . '/october/' );
				return $paths;
			});
			$str = Timber::compile('spooky.twig', array());
			$this->assertEquals('Boo!', $str);
		}

		function testTwigLoadsFromChildTheme(){
			$this->_setupParentTheme();
			$this->_setupChildTheme();
			$this->assertFileExists(WP_CONTENT_DIR.'/themes/fake-child-theme/style.css');
			switch_theme('fake-child-theme');
			$child_theme = Timber\ThemeHelper::get_stylesheet_directory_uri();
			$this->assertEquals(WP_CONTENT_URL.'/themes/fake-child-theme', $child_theme);
			$context = array();
			$str = Timber::compile('single.twig', $context);
			$this->assertEquals('I am single.twig', trim($str));
		}

		static function _setupChildTheme(){
			$dest_dir = WP_CONTENT_DIR.'/themes/fake-child-theme';
			if (!file_exists($dest_dir)) {
    			mkdir($dest_dir, 0777, true);
			}
			if (!file_exists($dest_dir.'/views')) {
    			mkdir($dest_dir.'/views', 0777, true);
			}
			copy(__DIR__.'/assets/style.css', $dest_dir.'/style.css');
			copy(__DIR__.'/assets/single.twig', $dest_dir.'/views/single.twig');
		}

		static function _setupParentTheme(){
			$dest_dir = WP_CONTENT_DIR.'/themes/twentyfifteen';
			if (!file_exists($dest_dir.'/views')) {
    			mkdir($dest_dir.'/views', 0777, true);
			}
			copy(__DIR__.'/assets/single-parent.twig', $dest_dir.'/views/single.twig');
			copy(__DIR__.'/assets/single-parent.twig', $dest_dir.'/views/single-parent.twig');
		}

		function testTwigLoadsFromParentTheme(){
			$this->_setupParentTheme();
			$this->_setupChildTheme();
			switch_theme('fake-child-theme');
			$templates = array('single-parent.twig');
			$str = Timber::compile($templates, array());
			$this->assertEquals('I am single.twig in parent theme', trim($str));
		}

		function _setupRelativeViews(){
			if (!file_exists(__DIR__.'/views')) {
    			mkdir(__DIR__.'/views', 0777, true);
			}
			copy(__DIR__.'/assets/relative.twig', __DIR__.'/views/single.twig');
		}

		function _teardownRelativeViews(){
			if (file_exists(__DIR__.'/views/single.twig')){
				unlink(__DIR__.'/views/single.twig');
			}
			if (file_exists(__DIR__.'/views')) {
    			rmdir(__DIR__.'/views');
			}
		}

		function testTwigLoadsFromRelativeToScript(){
			$this->_setupRelativeViews();
			$str = Timber::compile('single.twig');
			$this->assertEquals('I am in the assets directory', trim($str));
			$this->_teardownRelativeViews();
		}

		function testTwigLoadsFromAbsolutePathOnServer(){
			$str = Timber::compile(__DIR__.'/assets/image-test.twig');
			$this->assertEquals('<img src="" />', trim($str));
		}

		function _testTwigLoadsFromAbsolutePathOnServerWithSecurityRestriction(){
			$str = Timber::compile('assets/single-foo.twig');
		}

		function testTwigLoadsFromAlternateDirName(){
			Timber::$dirname = array(\Timber\Loader::MAIN_NAMESPACE => array('foo', 'views'));
			if (!file_exists(get_template_directory().'/foo')) {
    			mkdir(get_template_directory().'/foo', 0777, true);
			}
			copy(__DIR__.'/assets/single-foo.twig', get_template_directory().'/foo/single-foo.twig');
			$str = Timber::compile('single-foo.twig');
			$this->assertEquals('I am single-foo', trim($str));
		}

		function testTwigLoadsFromAlternateDirNameWithoutNamespace(){
			Timber::$dirname = array(array('foo', 'views'));
			if (!file_exists(get_template_directory().'/foo')) {
    			mkdir(get_template_directory().'/foo', 0777, true);
			}
			copy(__DIR__.'/assets/single-foo.twig', get_template_directory().'/foo/single-foo.twig');
			$str = Timber::compile('single-foo.twig');
			$this->assertEquals('I am single-foo', trim($str));
		}

		function testTwigLoadsFromAlternateDirNameWithoutNamespaceAndSimpleArray(){
			Timber::$dirname = array('foo', 'views');
			if (!file_exists(get_template_directory().'/foo')) {
    			mkdir(get_template_directory().'/foo', 0777, true);
			}
			copy(__DIR__.'/assets/single-foo.twig', get_template_directory().'/foo/single-foo.twig');
			$str = Timber::compile('single-foo.twig');
			$this->assertEquals('I am single-foo', trim($str));
		}

		function testTwigLoadsFromLocation(){
			Timber::$locations = __DIR__.'/assets';
			$str = Timber::compile('thumb-test.twig');
			$this->assertEquals('<img src="" />', trim($str));
		}

		function testTwigLoadsFromLocationWithNamespace(){
			Timber::$locations = array( __DIR__.'/assets' => 'assets' );
			$str = Timber::compile('@assets/thumb-test.twig');
			$this->assertEquals('<img src="" />', trim($str));
		}

		function testTwigLoadsFromLocationWithNestedNamespace(){
			Timber::$locations = array( __DIR__.'/namespaced' => 'namespaced' );
			$str = Timber::compile('@namespaced/test-nested.twig');
			$this->assertEquals('This is a namespaced template.', trim($str));
		}

		function testTwigLoadsFromLocationWithAndWithoutNamespaces(){
			Timber::$locations = array( __DIR__.'/namespaced' => 'namespaced', __DIR__ . '/assets' );

			// Namespaced location
			$str = Timber::compile('@namespaced/test-namespaced.twig');
			$this->assertEquals('This is a namespaced template.', trim($str));

			// Non namespaced location
			$str = Timber::compile('thumb-test.twig');
			$this->assertEquals('<img src="" />', trim($str));
		}

		function testTwigLoadsFromLocationWithAndWithoutNamespacesAndDirs(){
			Timber::$dirname = array(\Timber\Loader::MAIN_NAMESPACE => array('foo', 'views'));
			Timber::$locations = array( __DIR__.'/namespaced' => 'namespaced', __DIR__ . '/assets' );

			// Namespaced location
			$str = Timber::compile('@namespaced/test-namespaced.twig');
			$this->assertEquals('This is a namespaced template.', trim($str));

			// Non namespaced location
			$str = Timber::compile('thumb-test.twig');
			$this->assertEquals('<img src="" />', trim($str));

			if (!file_exists(get_template_directory().'/foo')) {
				mkdir(get_template_directory().'/foo', 0777, true);
			}
			copy(__DIR__.'/assets/single-foo.twig', get_template_directory().'/foo/single-foo.twig');

			// Dir
			$str = Timber::compile('single-foo.twig');
			$this->assertEquals('I am single-foo', trim($str));
		}

		function testTwigLoadsFromMultipleLocationsWithNamespace(){
			Timber::$locations = array( __DIR__.'/assets' => 'assets', __DIR__ .'/namespaced' => 'assets' );
			$str = Timber::compile('@assets/thumb-test.twig');
			$this->assertEquals('<img src="" />', trim($str));

			$str = Timber::compile('@assets/test-namespaced.twig');
			$this->assertEquals('This is a namespaced template.', trim($str));
		}

		function testTwigLoadsFirstTemplateWhenMultipleLocationsWithSameNamespace(){
			Timber::$locations = array( __DIR__.'/assets' => 'assets', __DIR__ .'/namespaced' => 'assets' );
			$str = Timber::compile('@assets/thumb-test.twig');
			$this->assertEquals('<img src="" />', trim($str));
		}

	}
