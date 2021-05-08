<?php

use Timber\Integrations\Command;
use Timber\Post;

/**
 * @group called-post-constructor
 */
class TestTimberIntegrations extends Timber_UnitTestCase {

	function testIntegrationClasses() {
		$integrations = new \Timber\Integrations();
		$integrations->maybe_init_integrations();
		$this->assertEquals('Timber\Integrations', get_class($integrations));
		$this->assertEquals('Timber\Integrations\ACF', get_class($integrations->acf));
		$this->assertEquals('Timber\Integrations\CoAuthorsPlus', get_class($integrations->coauthors_plus));
	}

	function testWPPostConvert() {
		$pid = $this->factory->post->create();
		$wp_post = get_post( $pid );
		$post = Timber::get_post( $pid );

		$this->assertInstanceOf( Post::class, $post->convert($wp_post) );
	}

	function testWPCLIClearCacheTimber(){
		$str = Timber::compile('assets/single.twig', array('rand' => 4004), 600);
		$success = Command::clear_cache('timber');
		$this->assertGreaterThan(0, $success);
	}

	/**
	 * @expectedDeprecated Timber::$cache and Timber::$twig_cache
	 */
	function testWPCLIClearCacheTwigDeprecated(){
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
    	$success = Command::clear_cache('twig');
		$this->assertTrue($success);
    	Timber::$twig_cache = false;
	}

	function testWPCLIClearCache(){
		$cache_dir = __DIR__.'/../cache/twig';
    	if (is_dir($cache_dir)){
    		Timber\Loader::rrmdir($cache_dir);
    	}
    	$this->assertFileNotExists($cache_dir);

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
		$success = Command::clear_cache( 'twig' );
		$this->assertTrue( $success );

    	remove_filter( 'timber/twig/environment/options', $cache_enabler );
	}

	/**
	 * @expectedDeprecated Timber::$cache and Timber::$twig_cache
	 */
	function testWPCLIClearCacheAllDeprecated(){
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
    	Timber::compile('assets/single.twig', array('data' => 'foobar'), 600);
    	$success = Command::clear_cache('all');
		$this->assertTrue($success);
    	Timber::$twig_cache = false;
	}

	function testWPCLIClearCacheAll() {
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
		Timber::compile( 'assets/single.twig', array( 'data' => 'foobar' ), 600 );
		$success = Command::clear_cache( 'all' );
		$this->assertTrue( $success );
		remove_filter( 'timber/twig/environment/options', $cache_enabler );
	}

	/**
	 * @expectedDeprecated Timber::$cache and Timber::$twig_cache
	 */
	function testWPCLIClearCacheAllArrayDeprecated(){
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
    	Timber::compile('assets/single.twig', array('data' => 'foobar'), 600);
    	$success = Command::clear_cache(array('all'));
		$this->assertTrue($success);
    	Timber::$twig_cache = false;

    	$success = Command::clear_cache('bunk');
    	$this->assertNull($success);
	}

	function testWPCLIClearCacheAllArray(){
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
		Timber::compile( 'assets/single.twig', array( 'data' => 'foobar' ), 600 );
		$success = Command::clear_cache( array( 'all' ) );
		$this->assertTrue( $success );
		remove_filter( 'timber/twig/environment/options', $cache_enabler );

		$success = Command::clear_cache( 'bunk' );
		$this->assertNull( $success );
	}
}
