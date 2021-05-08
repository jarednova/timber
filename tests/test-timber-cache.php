<?php

    use Timber\Cache\TimberKeyGeneratorInterface;

  /**
   * @group called-post-constructor
   */
	class TestTimberCache extends Timber_UnitTestCase {

        private function _generate_transient_name() {
            static $i = 0;
            $i++;
            return 'timber_test_transient_' . $i;
        }

        function testTransientLock() {

            $transient = $this->_generate_transient_name();
            Timber\Helper::_lock_transient( $transient, 5 );
            $this->assertTrue( Timber\Helper::_is_transient_locked( $transient ) );
        }

        function testTransientUnlock() {
            $transient = $this->_generate_transient_name();
            Timber\Helper::_lock_transient( $transient, 5 );
            Timber\Helper::_unlock_transient( $transient, 5 );
            $this->assertFalse( Timber\Helper::_is_transient_locked( $transient ) );
        }

        function testTransientExpire() {
            $transient = $this->_generate_transient_name();

            Timber\Helper::_lock_transient( $transient, 1 );
            sleep(2);
            $this->assertFalse( Timber\Helper::_is_transient_locked( $transient ) );
        }

        function testTransientLocksInternal() {
            $transient = $this->_generate_transient_name();

            $is_locked = Timber\Helper::transient( $transient, function() use ( $transient ) {
                return Timber\Helper::_is_transient_locked( $transient );
            }, 30 );

            $this->assertTrue( $is_locked );
        }

        function testTransientLocksExternal() {
            $transient = $this->_generate_transient_name();

            Timber\Helper::_lock_transient($transient, 30);
            $get_transient = Timber\Helper::transient( $transient, '__return_true', 30 );

            $this->assertFalse( $get_transient );
        }

		function testTransientAsAnonymousFunction(){
            $transient = $this->_generate_transient_name();

			$result = Timber\Helper::transient( $transient, function(){
				return 'pooptime';
			}, 200);
			$this->assertEquals( $result, 'pooptime');
		}

        function testSetTransient() {
            $transient = $this->_generate_transient_name();

            $first_value = Timber\Helper::transient( $transient, function(){
                return 'first_value';
            }, 30 );

            $second_value = Timber\Helper::transient( $transient, function(){
                return 'second_value';
            }, 30 );

            $this->assertEquals( 'first_value', $second_value );
        }

        function testDisableTransients() {
            $transient = $this->_generate_transient_name();

            $first_value = Timber\Helper::transient( $transient, function(){
                return 'first_value';
            }, 30 );

            $second_value = Timber\Helper::transient( $transient, function(){
                return 'second_value';
            }, false );

            $this->assertEquals( 'second_value', $second_value );
        }

		function testTransientAsString(){
            $transient = $this->_generate_transient_name();

			$result = Timber\Helper::transient( $transient, 'my_test_callback', 200);
			$this->assertEquals($result, 'lbj');
		}

        function testTransientLocked() {
            $transient = $this->_generate_transient_name();

            Timber\Helper::_lock_transient($transient, 30);

            // Transient is locked and won't be forced, so it should return false
            $get_transient = Timber\Helper::transient( $transient, '__return_true' );

            $this->assertFalse( $get_transient );
        }

        function testTransientForce() {
            $transient = $this->_generate_transient_name();

            Timber\Helper::_lock_transient($transient, 30);
            $get_transient = Timber\Helper::transient( $transient, '__return_true', 0, 5, true );

            $this->assertTrue( $get_transient );
        }

        function testTransientForceAllFilter() {
            $transient = $this->_generate_transient_name();

            Timber\Helper::_lock_transient($transient, 30);

            add_filter( 'timber/transient/force_transients', '__return_true' );
            $get_transient = Timber\Helper::transient( $transient, '__return_true' );
            remove_filter( 'timber/transient/force_transients', '__return_true' );

            $this->assertTrue( $get_transient );
        }

        function testKeyGenerator(){
        	$kg = new Timber\Cache\KeyGenerator();
        	$post_id = $this->factory->post->create(array('post_title' => 'My Test Post'));
					$post = Timber::get_post($post_id);
        	$key = $kg->generateKey($post);
        	$this->assertStringStartsWith('Timber\Post|', $key);
        }

        function testKeyGeneratorWithTimberKeyGeneratorInterface() {
            $kg = new Timber\Cache\KeyGenerator();
            $thing = new MyFakeThing();
            $key = $kg->generateKey($thing);
            $this->assertEquals('iamakey', $key);
        }

        function testKeyGeneratorWithArray() {
            $kg = new Timber\Cache\KeyGenerator();
            $thing = array('_cache_key' => 'iAmAKeyButInAnArray');
            $key = $kg->generateKey($thing);
            $this->assertEquals('iAmAKeyButInAnArray', $key);
        }

        function testTransientForceFilter() {
            $transient = $this->_generate_transient_name();

            Timber\Helper::_lock_transient($transient, 30);

            add_filter( 'timber_force_transient_' . $transient, '__return_true' );
            $get_transient = Timber\Helper::transient( $transient, '__return_true' );
            remove_filter( 'timber_force_transient_' . $transient, '__return_true' );

            $this->assertTrue( $get_transient );
        }

        function testExpireTransient() {
            $transient = $this->_generate_transient_name();

            $first_value = Timber\Helper::transient( $transient, function(){
                return 'first_value';
            }, 1 );

            sleep(2);

            $second_value = Timber\Helper::transient( $transient, function(){
                return 'second_value';
            }, 1 );

            $this->assertEquals( 'second_value', $second_value );
        }

		/**
		 * @expectedDeprecated Timber::$cache and Timber::$twig_cache
		 */
        function testTwigCacheDeprecated(){
        	$cache_dir = __DIR__.'/../cache/twig';
        	if (is_dir($cache_dir)){
        		Timber\Loader::rrmdir($cache_dir);
        	}
        	$this->assertFileNotExists($cache_dir);
        	Timber::$twig_cache = true;
        	$pid = $this->factory->post->create();
					$post = Timber::get_post($pid);
        	Timber::compile('assets/single-post.twig', array('post' => $post));
        	sleep(1);
        	$this->assertFileExists($cache_dir);
        	$loader = new Timber\Loader();
        	$loader->clear_cache_twig();
        	Timber::$twig_cache = false;
        	$this->assertFileNotExists($cache_dir);
        }

        /**
		 * @expectedDeprecated Timber::$cache and Timber::$twig_cache
		 */
        function testTwigCacheAliasDeprecated(){
        	$cache_dir = __DIR__.'/../cache/twig';
        	if (is_dir($cache_dir)){
        		Timber\Loader::rrmdir($cache_dir);
        	}
        	$this->assertFileNotExists($cache_dir);
        	Timber::$cache = true;
        	$pid = $this->factory->post->create();
					$post = Timber::get_post($pid);
        	Timber::compile('assets/single-post.twig', array('post' => $post));
        	//sleep(1);
        	$this->assertFileExists($cache_dir);
        	$loader = new Timber\Loader();
        	$loader->clear_cache_twig();
        	Timber::$cache = false;
        	Timber::$twig_cache = false;
        	$this->assertFileNotExists($cache_dir);
        }

        function testTwigCache(){
	        $cache_dir = __DIR__ . '/../cache/twig';

	        if ( is_dir( $cache_dir ) ) {
		        Timber\Loader::rrmdir( $cache_dir );
	        }

	        $this->assertFileNotExists( $cache_dir );

	        $cache_enabler = function( $options ) {
				$options['cache'] = true;

				return $options;
			};

			add_filter( 'timber/twig/environment/options', $cache_enabler );

	        $pid  = $this->factory->post->create();
	        $post = Timber::get_post( $pid );
	        Timber::compile( 'assets/single-post.twig', array( 'post' => $post ) );
	        sleep( 1 );

	        $this->assertFileExists( $cache_dir );

	        $loader = new Timber\Loader();
	        $loader->clear_cache_twig();
	        $this->assertFileNotExists( $cache_dir );

	        remove_filter( 'timber/twig/environment/options', $cache_enabler );
        }

        function testTimberLoaderCache(){
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $str_old = Timber::compile('assets/single-post.twig', array('post' => $post), 600);
            $str_another = Timber::compile('assets/single-parent.twig', array('post' => $post, 'rand' => rand(0, 99)), 500);
            //sleep(1);
            $str_new = Timber::compile('assets/single-post.twig', array('post' => $post), 600);
            $this->assertEquals($str_old, $str_new);
            $loader = new Timber\Loader();
            $clear = $loader->clear_cache_timber();
            $this->assertGreaterThan(0, $clear);
            global $wpdb;
            $query = "SELECT * FROM $wpdb->options WHERE option_name LIKE '_transient_timberloader_%'";
            $wpdb->query( $query );
            $this->assertEquals(0, $wpdb->num_rows);
        }


        function testTimberLoaderCacheObject(){
            global $_wp_using_ext_object_cache;
            global $wp_object_cache;
            $_wp_using_ext_object_cache = true;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $str_old = Timber::compile('assets/single-post.twig', array('post' => $post), 600, \Timber\Loader::CACHE_OBJECT);
            //sleep(1);
            $str_new = Timber::compile('assets/single-post.twig', array('post' => $post), 600, \Timber\Loader::CACHE_OBJECT);
            $this->assertEquals($str_old, $str_new);
            $loader = new Timber\Loader();
            $clear = $loader->clear_cache_timber(\Timber\Loader::CACHE_OBJECT);
            $this->assertTrue($clear);
            $works = true;
            if ( isset($wp_object_cache->cache[\Timber\Loader::CACHEGROUP])
                && !empty($wp_object_cache->cache[\Timber\Loader::CACHEGROUP]) ) {
                $works = false;
            }
            $this->assertTrue($works);
        }

        function tearDown() {
            global $_wp_using_ext_object_cache;
            $_wp_using_ext_object_cache = false;
            global $wpdb;
            $query = "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timberloader_%'";
            $wpdb->query($query);
            parent::tearDown();
        }

        function testTimberLoaderCacheTransients() {
            $time = 1;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $str_old = Timber::compile('assets/single-post.twig', array('post' => $post, 'rand' => rand(0, 99999)), $time);
            sleep(2);
            $str_new = Timber::compile('assets/single-post.twig', array('post' => $post, 'rand' => rand(0, 99999)), $time);
            $this->assertEquals($str_old, $str_new);
            global $wpdb;
            $query = "SELECT * FROM $wpdb->options WHERE option_name LIKE '_transient_timberloader_%'";
            $data = $wpdb->get_results( $query );
            $this->assertEquals(1, $wpdb->num_rows);
        }

        function testTimberLoaderCacheTransientsAdminLoggedIn() {
            wp_set_current_user(1);
            $time = 1;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $r1 = rand(0, 999999);
            $r2 = rand(0, 999999);
            $str_old = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r1), array(600, false));
            self::_swapFiles();
            $str_new = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r2), array(600, false));
            $this->assertNotEquals($str_old, $str_new);
            self::_unswapFiles();

        }

        function _swapFiles() {
            rename(__DIR__.'/assets/single-post-rand.twig', __DIR__.'/assets/single-post-rand.twig.tmp');
            rename(__DIR__.'/assets/relative.twig', __DIR__.'/assets/single-post-rand.twig');
        }

        function _unswapFiles() {
            rename(__DIR__.'/assets/single-post-rand.twig', __DIR__.'/assets/relative.twig');
            rename(__DIR__.'/assets/single-post-rand.twig.tmp', __DIR__.'/assets/single-post-rand.twig');
        }

        function testTimberLoaderCacheTransientsAdminLoggedOut() {
            $time = 1;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $r1 = rand(0, 999999);
            $str_old = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r1), array(600, false));
            self::_swapFiles();
            $str_new = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r1), array(600, false));
            $this->assertEquals($str_old, $str_new);
            self::_unswapFiles();
        }

        function testTimberLoaderCacheTransientsAdminLoggedOutWithSiteCache() {
            $time = 1;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $r1 = rand(0, 999999);
            $str_old = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r1), array(600, false), \Timber\Loader::CACHE_SITE_TRANSIENT);
            self::_swapFiles();
            $str_new = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r1), array(600, false), \Timber\Loader::CACHE_SITE_TRANSIENT);
            $this->assertEquals($str_old, $str_new);
            self::_unswapFiles();
        }

        function testTimberLoaderCacheTransientsAdminLoggedOutWithObjectCache() {
            global $_wp_using_ext_object_cache;
            $_wp_using_ext_object_cache = true;
            $time = 1;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $r1 = rand(0, 999999);
            $str_old = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r1), array(600, false), \Timber\Loader::CACHE_OBJECT);
            self::_swapFiles();
            $str_new = Timber::compile('assets/single-post-rand.twig', array('post' => $post, 'rand' => $r1), array(600, false), \Timber\Loader::CACHE_OBJECT);
            $this->assertEquals($str_old, $str_new);
            self::_unswapFiles();
            $_wp_using_ext_object_cache = false;
        }

        function testTimberLoaderCacheTransientsWithExtObjectCache() {
            global $_wp_using_ext_object_cache;
            $_wp_using_ext_object_cache = true;
            $time = 1;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            $r1 = rand(0, 999999);
            $r2 = rand(0, 999999);
            $str_old = Timber::compile('assets/single-post.twig', array('post' => $post, 'rand' => $r1), $time);
            $str_new = Timber::compile('assets/single-post.twig', array('post' => $post, 'rand' => $r2), $time);
            $this->assertEquals($str_old, $str_new);
            global $wpdb;
            $query = "SELECT * FROM $wpdb->options WHERE option_name LIKE '_transient_timberloader_%'";
            $data = $wpdb->get_results( $query );
            $this->assertEquals(0, $wpdb->num_rows);
            $_wp_using_ext_object_cache = false;
        }

        function testTimberLoaderCacheTransientsButKeepOtherTransients() {
            $time = 1;
            $pid = $this->factory->post->create();
            $post = Timber::get_post($pid);
            set_transient( 'random_600', 'foo', 600 );
            $random_post = Timber::compile('assets/single-post.twig', array('post' => $post, 'rand' => rand(0, 99999)), 600);
            $str_old = Timber::compile('assets/single-post.twig', array('post' => $post, 'rand' => rand(0, 99999)), $time);
            sleep(2);
            $str_new = Timber::compile('assets/single-post.twig', array('post' => $post, 'rand' => rand(0, 99999)), $time);
            $this->assertEquals($str_old, $str_new);
            global $wpdb;
            $query = "SELECT * FROM $wpdb->options WHERE option_name LIKE '_transient_timberloader_%'";
            $data = $wpdb->get_results( $query );
            $this->assertEquals(2, $wpdb->num_rows);
            $this->assertEquals('foo', get_transient('random_600'));
        }

	}

    class MyFakeThing implements TimberKeyGeneratorInterface {
        public function _get_cache_key() {
            return 'iamakey';
        }
    }

	function my_test_callback(){
		return "lbj";
	}
