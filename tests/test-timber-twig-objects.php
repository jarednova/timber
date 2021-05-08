<?php

use Timber\Timber;

/**
 * @group posts-api
 * @group terms-api
 * @group users-api
 * @group comments-api
 * @group twig
 * @group attachments
 */
class TestTimberTwigObjects extends Timber_UnitTestCase {

	/**
	 * @expectedDeprecated {{ TimberImage() }}
	 */
	function testTimberImageInTwig() {
		$iid = TestTimberImage::get_attachment();
		$str = '{{ TimberImage('.$iid.').src }}';
		$compiled = Timber::compile_string($str);
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y').'/'.date('m').'/arch.jpg', $compiled);
	}

	/**
	 * @expectedDeprecated {{ Image() }}
	 */
	function testImageInTwig() {
		$compiled = Timber::compile_string('{{ Image(iid).src }}', [
			'iid' => TestTimberImage::get_attachment(),
		]);

		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpg', $compiled);
	}

	function testImageWithGetPostInTwig() {
		$compiled = Timber::compile_string('{{ get_post(iid).src }}', [
			'iid' => TestTimberImage::get_attachment(),
		]);

		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpg', $compiled);
	}

	/**
	 * @expectedDeprecated {{ Image() }}
	 */
	function testImagesInTwig() {
		$images = array();
		$images[] = TestTimberImage::get_attachment( 0, 'arch.jpg' );
		$images[] = TestTimberImage::get_attachment( 0, 'city-museum.jpg' );
		$str = '{% for image in Image(images) %}{{image.src}}{% endfor %}';
		$compiled = Timber::compile_string($str, array('images' => $images));
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y').'/'.date('m').'/arch.jpghttp://example.org/wp-content/uploads/'.date('Y').'/'.date('m').'/city-museum.jpg', $compiled);
	}

	function testImagesWithGetPostsInTwig() {
		$images = array();
		$images[] = TestTimberImage::get_attachment( 0, 'arch.jpg' );
		$images[] = TestTimberImage::get_attachment( 0, 'city-museum.jpg' );
		$str = '{% for image in get_posts(images) %}{{image.src}}{% endfor %}';
		$compiled = Timber::compile_string($str, array('images' => $images));
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y').'/'.date('m').'/arch.jpghttp://example.org/wp-content/uploads/'.date('Y').'/'.date('m').'/city-museum.jpg', $compiled);
	}

	/**
	 * @expectedDeprecated {{ TimberImage() }}
	 */
	function testTimberImagesInTwig() {
		$images = array();
		$images[] = TestTimberImage::get_attachment( 0, 'arch.jpg' );
		$images[] = TestTimberImage::get_attachment( 0, 'city-museum.jpg' );
		$str = '{% for image in TimberImage(images) %}{{image.src}}{% endfor %}';
		$compiled = Timber::compile_string($str, array('images' => $images));
		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y').'/'.date('m').'/arch.jpghttp://example.org/wp-content/uploads/'.date('Y').'/'.date('m').'/city-museum.jpg', $compiled);
	}

	function testTimberImageInTwigToString() {
		$compiled = Timber::compile_string('{{ get_post(iid) }}', [
			'iid' => TestTimberImage::get_attachment(),
		]);

		$this->assertEquals('http://example.org/wp-content/uploads/'.date('Y/m').'/arch.jpg', $compiled);
	}

	function testTimberImageWithGetPostInTwigToString() {
		$iid      = TestTimberImage::get_attachment();
		$str      = '{{ get_post(' . $iid . ') }}';
		$compiled = Timber::compile_string( $str );
		$this->assertEquals(
			'http://example.org/wp-content/uploads/' . date( 'Y' ) . '/' . date( 'm' ) . '/arch.jpg',
			$compiled
		);
	}

	/**
	 * @expectedDeprecated {{ TimberPost() }}
	 */
	function testTimberPostInTwig(){
		$pid = $this->factory->post->create(array('post_title' => 'Foo'));
		$str = '{{ TimberPost('.$pid.').title }}';
		$this->assertEquals('Foo', Timber::compile_string($str));
	}

	/**
	 * @expectedDeprecated {{ Post() }}
	 */
	function testPostInTwig(){
		$pid = $this->factory->post->create(array('post_title' => 'Foo'));
		$str = '{{Post('.$pid.').title}}';
		$this->assertEquals('Foo', Timber::compile_string($str));
	}

	function testGetPostInTwig() {
		$pid = $this->factory->post->create( [ 'post_title' => 'Foo' ] );
		$this->assertEquals( 'Foo', Timber::compile_string( '{{ get_post(pid).title }}', [
			'pid' => $pid,
		] ) );
	}

	/**
	 * @expectedDeprecated {{ TimberPost() }}
	 */
	function testTimberPostsInTwig(){
		$pids[] = $this->factory->post->create(array('post_title' => 'Foo'));
		$pids[] = $this->factory->post->create(array('post_title' => 'Bar'));
		$str = '{% for post in TimberPost(pids) %}{{post.title}}{% endfor %}';
		$this->assertEquals('FooBar', Timber::compile_string($str, array('pids' => $pids)));
	}

	/**
	 * @expectedDeprecated {{ Post() }}
	 */
	function testPostsInTwig(){
		$pids[] = $this->factory->post->create(array('post_title' => 'Foo'));
		$pids[] = $this->factory->post->create(array('post_title' => 'Bar'));
		$str = '{% for post in Post(pids) %}{{post.title}}{% endfor %}';
		$this->assertEquals('FooBar', Timber::compile_string($str, array('pids' => $pids)));
	}

	function testGetPostsInTwig() {
		$pids[] = $this->factory->post->create( [ 'post_title' => 'Foo' ] );
		$pids[] = $this->factory->post->create( [ 'post_title' => 'Bar' ] );
		$str    = '{% for post in get_posts(pids) %}{{post.title}}{% endfor %}';
		$this->assertEquals( 'FooBar', Timber::compile_string( $str, [ 'pids' => $pids ] ) );
	}

	/**
	 * @expectedIncorrectUsage Timber::get_posts()
	 */
	function testGetPostsWithQueryStringInTwig(){
		$pids[] = $this->factory->post->create( array( 'post_title' => 'Foo' ) );
		$pids[] = $this->factory->post->create( array( 'post_title' => 'Bar' ) );
		$str    = "{% for post in get_posts('post_type=post&posts_per_page=-1&order=ASC') %}{{ post.title }}{% endfor %}";

		$this->assertEquals( 'FooBar', Timber::compile_string( $str, array( 'pids' => $pids ) ) );
	}

	function testGetPostsWithArgsInTwig() {
		$pids[] = $this->factory->post->create( [ 'post_title' => 'Foo' ] );
		$pids[] = $this->factory->post->create( [ 'post_title' => 'Bar' ] );
		$str    = "{% for post in get_posts({ post_type: 'post', posts_per_page: -1, order: 'ASC'}) %}{{ post.title }}{% endfor %}";

		$this->assertEquals( 'FooBar', Timber::compile_string( $str, [ 'pids' => $pids ] ) );
	}

	/**
	 * @expectedDeprecated {{ TimberUser() }}
	 */
	function testTimberUserInTwig(){
		$uid = $this->factory->user->create(array('display_name' => 'Pete Karl'));
		$template = '{{ TimberUser('.$uid.').name }}';
		$str = Timber::compile_string($template);
		$this->assertEquals('Pete Karl', $str);
	}

	/**
	 * @expectedDeprecated {{ User() }}
	 */
	function testUsersInTwig(){
		$uids[] = $this->factory->user->create(array('display_name' => 'Mark Watabe'));
		$uids[] = $this->factory->user->create(array('display_name' => 'Austin Tzou'));
		$str = '{% for user in User(uids) %}{{user.name}} {% endfor %}';
		$this->assertEquals('Mark Watabe Austin Tzou', trim(Timber::compile_string($str, array('uids' => $uids))));
	}

	function testGetUsersInTwig() {
		$uids[] = $this->factory->user->create( [ 'display_name' => 'Mark Watabe' ] );
		$uids[] = $this->factory->user->create( [ 'display_name' => 'Austin Tzou' ] );
		$str    = '{% for user in get_users(uids) %}{{ user.name }} {% endfor %}';
		$this->assertEquals(
			'Mark Watabe Austin Tzou',
			trim( Timber::compile_string( $str, [ 'uids' => $uids ] ) )
		);
	}

	/**
	 * @expectedDeprecated {{ User() }}
	 */
	function testUserInTwig(){
		$uid = $this->factory->user->create(array('display_name' => 'Nathan Hass'));
		$str = '{{User('.$uid.').name}}';
		$this->assertEquals('Nathan Hass', Timber::compile_string($str));
	}

	function testGetUserInTwig() {
		$uid = $this->factory->user->create( [ 'display_name' => 'Nathan Hass' ] );
		$str = '{{ get_user(' . $uid . ').name }}';
		$this->assertEquals( 'Nathan Hass', Timber::compile_string( $str ) );
	}

	/**
	 * @expectedDeprecated {{ TimberUser() }}
	 */
	function testTimberUsersInTwig() {
		$uids[] = $this->factory->user->create(array('display_name' => 'Estelle Getty'));
		$uids[] = $this->factory->user->create(array('display_name' => 'Bea Arthur'));
		$str = '{% for user in TimberUser(uids) %}{{user.name}} {% endfor %}';
		$this->assertEquals('Estelle Getty Bea Arthur', trim(Timber::compile_string($str, array('uids' => $uids))));
	}

	/**
	 * @expectedDeprecated {{ TimberTerm() }}
	 */
	function testTimberTermInTwig(){
		$tid = $this->factory->term->create(array('name' => 'Golden Girls'));
		$str = '{{ TimberTerm(tid).title }}';
		$this->assertEquals('Golden Girls', Timber::compile_string($str, array('tid' => $tid)));
	}

	/**
	 * @expectedDeprecated {{ Term() }}
	 */
	function testTermInTwig(){
		$tid = $this->factory->term->create(array('name' => 'Mythbusters'));
		$str = '{{Term(tid).title}}';
		$this->assertEquals('Mythbusters', Timber::compile_string($str, array('tid' => $tid)));
	}

	function testGetTermInTwig() {
		$tid = $this->factory->term->create( [ 'name' => 'Mythbusters' ] );
		$str = '{{ get_term(tid).title }}';
		$this->assertEquals( 'Mythbusters', Timber::compile_string( $str, [ 'tid' => $tid ] ) );
	}

	/**
	 * @expectedDeprecated {{ TimberTerm() }}
	 */
	function testTimberTermsInTwig(){
		$tids[] = $this->factory->term->create(array('name' => 'Foods'));
		$tids[] = $this->factory->term->create(array('name' => 'Cars'));
		$str = '{% for term in TimberTerm(tids) %}{{term.title}} {% endfor %}';
		$this->assertEquals('Foods Cars ', Timber::compile_string($str, array('tids' => $tids)));
	}

	/**
	 * @expectedDeprecated {{ Term() }}
	 */
	function testTermsInTwig(){
		$tids[] = $this->factory->term->create(array('name' => 'Animals'));
		$tids[] = $this->factory->term->create(array('name' => 'Germans'));
		$str = '{% for term in Term(tids) %}{{term.title}} {% endfor %}';
		$this->assertEquals('Animals Germans ', Timber::compile_string($str, array('tids' => $tids)));
	}

	function testGetTermsInTwig() {
		$tids[] = $this->factory->term->create( [ 'name' => 'Animals' ] );
		$tids[] = $this->factory->term->create( [ 'name' => 'Germans' ] );
		$str    = '{% for term in get_terms(tids) %}{{term.title}} {% endfor %}';
		$this->assertEquals( 'Animals Germans ', Timber::compile_string( $str, [ 'tids' => $tids ] ) );
	}
}
