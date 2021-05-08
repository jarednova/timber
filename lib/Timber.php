<?php

namespace Timber;

use WP_Query;
use WP_Post;

use Timber\Factory\CommentFactory;
use Timber\Factory\MenuFactory;
use Timber\Factory\PostFactory;
use Timber\Factory\TermFactory;
use Timber\Factory\UserFactory;
use Timber\Helper;
use Timber\PostCollectionInterface;
use Timber\URLHelper;

/**
 * Class Timber
 *
 * Main class called Timber for this plugin.
 *
 * @api
 * @example
 * ```php
 * // Get default posts on an archive page
 * $posts = Timber::get_posts();
 *
 * // Query for some posts
 * $posts = Timber::get_posts( [
 *     'post_type' => 'article',
 *     'category_name' => 'sports',
 * ] );
 *
 * $context = Timber::context( [
 *     'posts' => $posts,
 * ] );
 *
 * Timber::render( 'index.twig', $context );
 * ```
 */
class Timber {

	public static $version = '2.0.0';
	public static $locations;
	public static $dirname = 'views';
	public static $auto_meta = true;

	/**
	 * Global context cache.
	 *
	 * @var array An array containing global context variables.
	 */
	public static $context_cache = array();

	/**
	 * Caching option for Twig.
	 *
	 * @deprecated 2.0.0
	 * @var bool
	 */
	public static $twig_cache = false;

	/**
	 * Caching option for Twig.
	 *
	 * Alias for `Timber::$twig_cache`.
	 *
	 * @deprecated 2.0.0
	 * @var bool
	 */
	public static $cache = false;

	/**
	 * Autoescaping option for Twig.
	 *
	 * @deprecated 2.0.0
	 * @var bool
	 */
	public static $autoescape = false;

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		if ( !defined('ABSPATH') ) {
			return;
		}
		if ( class_exists('\WP') && !defined('TIMBER_LOADED') ) {
			$this->test_compatibility();
			$this->init_constants();
			self::init();
		}
	}

	/**
	 * Tests whether we can use Timber
	 * @codeCoverageIgnore
	 */
	protected function test_compatibility() {
		if ( is_admin() || $_SERVER['PHP_SELF'] == '/wp-login.php' ) {
			return;
		}
		if ( version_compare(phpversion(), '5.3.0', '<') && !is_admin() ) {
			trigger_error('Timber requires PHP 5.3.0 or greater. You have '.phpversion(), E_USER_ERROR);
		}
		if ( ! class_exists( 'Twig\Token' ) ) {
			trigger_error('You have not run "composer install" to download required dependencies for Timber, you can read more on https://github.com/timber/timber#installation', E_USER_ERROR);
		}
	}

	public function init_constants() {
		defined("TIMBER_LOC") or define("TIMBER_LOC", realpath(dirname(__DIR__)));
	}

	/**
	 * @codeCoverageIgnore
	 */
	protected static function init() {
		if ( class_exists('\WP') && !defined('TIMBER_LOADED') ) {
			Twig::init();
			ImageHelper::init();
			Admin::init();
			new Integrations();

			// @todo find a more permanent home for this stuff, maybe in a QueryHelper class?
			add_filter('pre_get_posts', function(WP_Query $query) {
				$cat = $query->query['category'] ?? null;
				if ( $cat && !isset($query->query['cat']) ) {
					unset($query->query['category']);
					$query->set('cat', $cat);
				}
			});

			add_filter('pre_get_posts', function(WP_Query $query) {
				$count = $query->query['numberposts'] ?? null;
				if ( $count && !isset($query->query['posts_per_page']) ) {
					$query->set('posts_per_page', $count);
				}
			});

			add_filter('timber/post/import_data', function($data) {
				if ( isset($_GET['preview']) && isset($_GET['preview_id']) ) {
					$preview = wp_get_post_autosave($_GET['preview_id']);
					if ( is_object($preview) ) {

						$preview = sanitize_post($preview);

						$data->post_content = $preview->post_content;
						$data->post_title = $preview->post_title;
						$data->post_excerpt = $preview->post_excerpt;

						// @todo I think we can safely delete this?
						// It was included in the old PostCollection method but not defined anywhere,
						// so I think it was always just falling into a magic __call() and doing nothing.
						// $post->import_custom($preview_id);

						add_filter('get_the_terms', '_wp_preview_terms_filter', 10, 3);
					}
				}

				return $data;
			});

			/**
			 * Make an alias for the Timber class.
			 *
			 * This way, developers can use Timber::render() instead of Timber\Timber::render, which
			 * is more user-friendly.
			 */
			class_alias( 'Timber\Timber', 'Timber' );

			define('TIMBER_LOADED', true);
		}
	}

	/* Post Retrieval Routine
	================================ */

	/**
	 * Get a Timber Post from a post ID, WP_Post object, a WP_Query object, or an associative
	 * array of arguments for WP_Query::__construct().
	 *
	 * By default, Timber will use the `Timber\Post` class to create a new post object. To control
	 * which class is instantiated for your Post object, use [Class Maps](https://timber.github.io/docs/v2/guides/class-maps/)
	 *
	 * @api
	 * @example
	 * ```php
	 * // Using a post ID.
	 * $post = Timber::get_post( 75 );
	 *
	 * // Using a WP_Post object.
	 * $wp_post = get_post( 123 );
	 * $post    = Timber::get_post( $wp_post );
	 *
	 * // Using a WP_Query argument array
	 * $post = Timber::get_post( [
	 *   'post_type' => 'page',
	 * ] );
	 *
	 * // Use currently queried post. Same as using get_the_ID() as a parameter.
	 * $post = Timber::get_post();
	 *
	 * // From an associative array with an `ID` key. For ACF compatibility. Using this
	 * // approach directly is not recommended. If you can, configure the return type of your
	 * // ACF field to just the ID.
	 * $post = Timber::get_post( get_field('associated_post_array') ); // Just OK.
	 * $post = Timber::get_post( get_field('associated_post_id') ); // Better!
	 * ```
	 * @see https://developer.wordpress.org/reference/classes/wp_query/__construct/
	 *
	 * @param mixed $query   Optional. Post ID or query (as an array of arguments for WP_Query).
	 * 	                     If a query is provided, only the first post of the result will be
	 *                       returned. Default false.
	 * @param array $options Optional associative array of options. Defaults to an empty array.
	 *
	 * @return \Timber\Post|bool Timber\Post object if a post was found, false if no post was
	 *                           found.
	 */
	public static function get_post( $query = false, $options = [] ) {
		if ( is_string( $query ) && ! is_numeric( $query ) ) {
			Helper::doing_it_wrong(
				'Timber::get_post()',
				'Getting a post by post slug or post name was removed from Timber::get_post() in Timber 2.0. Use Timber::get_post_by() instead.',
				'2.0.0'
			);
		}

		if ( is_string( $options ) ) {
			Helper::doing_it_wrong(
				'Timber::get_post()',
				'The $PostClass parameter for passing in the post class to use in Timber::get_posts() was replaced with an $options array in Timber 2.0. To customize which class to instantiate for your post, use Class Maps instead: https://timber.github.io/docs/v2/guides/class-maps/',
				'2.0.0'
			);

			$options = [];
		}

		$factory = new PostFactory();

		global $wp_query;

		$options = wp_parse_args($options, [
			'merge_default' => false
		]);

		// Has WP already queried and found a post?
		if ($query === false && ($wp_query->queried_object instanceof WP_Post)) {
			$query = $wp_query->queried_object;
		} elseif (is_array($query) && $options['merge_default']) {
			$query = wp_parse_args($wp_query->query_vars);
		}

		// Default to the global query.
		$result = $factory->from($query ?: $wp_query);

		// If we got a Collection, return the first Post.
		if ($result instanceof PostCollectionInterface) {
			return $result[0] ?? false;
		}

		return $result;
	}

	/**
	 * Behaves just like Timber::get_post(), except that it returns false if it
	 * finds a Post that is not an Attachment. Honors Class Maps and falsifies return
	 * value *after* Class Map for the found Post has been resolved.
	 *
	 * @api
	 * @see Timber::get_post()
	 * @see https://timber.github.io/docs/v2/guides/class-maps/
	 * @param mixed $query query or post identifier
	 * @param array $options options to ::get_post()
	 * @return Attachment|false
	 */
	public static function get_attachment( $query = false, $options = [] ) {
		$post = static::get_post( $query, $options );

		// @todo make this determination at the Factory level.
		// No need to instantiate a Post we're not going to use.
		return ( $post instanceof Attachment ) ? $post : false;
	}

	/**
	 * Behaves just like Timber::get_post(), except that it returns false if it
	 * finds a Post that is not an Image. Honors Class Maps and falsifies return
	 * value *after* Class Map for the found Post has been resolved.
	 *
	 * @api
	 * @see Timber::get_post()
	 * @see https://timber.github.io/docs/v2/guides/class-maps/
	 * @param mixed $query query or post identifier
	 * @param array $options options to ::get_post()
	 * @return Image|false
	 */
	public static function get_image( $query = false, $options = [] ) {
		$post = static::get_post( $query, $options );

		// @todo make this determination at the Factory level.
		// No need to instantiate a Post we're not going to use.
		return ( $post instanceof Image ) ? $post : false;
	}

	/**
	 * Get posts.
	 *
	 * @api
	 *
	 * @todo improve this docblock
	 * @param mixed $query
	 * @param array $options
	 *
	 * @return array|bool|null
	 */
	public static function get_posts( $query = false, $options = [] ) {
		if ( is_string( $query ) ) {
			Helper::doing_it_wrong(
				'Timber::get_posts()',
				"Querying posts by using a query string was removed in Timber 2.0. Pass in the query string as an options array instead. For example, change Timber::get_posts( 'post_type=portfolio&posts_per_page=3') to Timber::get_posts( [ 'post_type' => 'portfolio', 'posts_per_page' => 3 ] ). Learn more: https://timber.github.io/docs/v2/reference/timber-timber/#get_posts",
				'2.0.0'
			);

			$query = new WP_Query( $query );
		}

		if ( is_string( $options ) ) {
			Helper::doing_it_wrong(
				'Timber::get_posts()',
				'The $PostClass parameter for passing in the post class to use in Timber::get_posts() was replaced with an $options array in Timber 2.0. To customize which class to instantiate for your post, use Class Maps instead: https://timber.github.io/docs/v2/guides/class-maps/',
				'2.0.0'
			);
			$options = [];
		}

		if ( 3 === func_num_args() ) {
			Helper::doing_it_wrong(
				'Timber::get_posts()',
				'The $return_collection parameter to control whether a post collection is returned in Timber::get_posts() was removed in Timber 2.0.',
				'2.0.0'
			);
		}

		/**
		 * @todo Are there any more default options to support?
		 */
		$options = wp_parse_args( $options, [
			'merge_default' => false,
		] );

		global $wp_query;

		if ( is_array($query) && $options['merge_default'] ) {
			$query = wp_parse_args( $query, $wp_query->query_vars );
		}

		$factory = new PostFactory();

		// Default to the global query.
		return $factory->from($query ?: $wp_query);
	}

	/**
	 * Gets a post by title or slug.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * // By slug
	 * $post = Timber::get_post_by( 'slug', 'about-us' );
	 *
	 * // By title
	 * $post = Timber::get_post_by( 'title', 'About us' );
	 * ```
	 *
	 * @param string       $type         The type to look for. One of `slug` or `title`.
	 * @param string       $search_value The post slug or post title to search for. When searching
	 *                                   for `title`, this parameter doesn’t need to be
	 *                                   case-sensitive, because the `=` comparison is used in
	 *                                   MySQL.
	 * @param array        $args {
	 *     Optional. An array of arguments to configure what is returned.
	 *
	 * 	   @type string|array     $post_type   Optional. What WordPress post type to limit the
	 *                                         results to. Defaults to 'any'
	 *     @type string           $order_by    Optional. The field to sort by. Defaults to
	 *                                         'post_date'
	 *     @type string           $order       Optional. The sort to apply. Defaults to ASC
	 *
	 * }
	 *
	 * @return \Timber\Post|false A Timber post or `false` if no post could be found. If multiple
	 *                            posts with the same slug or title were found, it will select the
	 *                            post with the oldest date.
	 */
	public static function get_post_by( $type, $search_value, $args = array() ) {
		$post_id = false;
		$args = wp_parse_args( $args, [
			'post_type' => 'any',
			'order_by'  => 'post_date',
			'order'     => 'ASC'
		] );
		if ( 'slug' === $type ) {
			$args = wp_parse_args($args, [
				'name'      => $search_value,
				'fields'    => 'ids'
			]);
			$query = new \WP_Query( $args );

			if ( $query->post_count < 1 ) {
				return false;
			}

			$posts   = $query->get_posts();
			$post_id = array_shift( $posts );
		} elseif ( 'title' === $type ) {
			/**
			 * The following section is inspired by post_exists() as well as get_page_by_title().
			 *
			 * These two functions always return the post with lowest ID. However, we want the post
			 * with oldest post date.
			 *
			 * @see \post_exists()
			 * @see \get_page_by_title()
			 */
			global $wpdb;

			$sql = "SELECT ID FROM $wpdb->posts WHERE post_title = %s";
			$query_args = [ $search_value ];
			if ( is_array( $args['post_type'] ) ) {
				$post_type           = esc_sql( $args['post_type'] );
				$post_type_in_string = "'" . implode( "','", $args['post_type'] ) . "'";

				$sql .= " AND post_type IN ($post_type_in_string)";
			} elseif ( 'any' !== $args['post_type'] ) {
				$sql .= ' AND post_type = %s';
				$query_args[] = $args['post_type'];
			}

			// Always return the oldest post first.
			$sql .= ' ORDER BY post_date ASC';

			$post_id = $wpdb->get_var( $wpdb->prepare( $sql, $query_args ) );
		}

		if ( ! $post_id ) {
			return false;
		}

		return self::get_post( $post_id );
	}

	/**
	 * Query post.
	 *
	 * @api
	 * @deprecated since 2.0.0 Use `Timber::get_post()` instead.
	 *
	 * @param mixed $query
	 * @param array $options
	 *
	 * @return Post|array|bool|null
	 */
	public static function query_post( $query = false, array $options = [] ) {
		Helper::deprecated('Timber::query_post()', 'Timber::get_post()', '2.0.0');

		return self::get_post($query, $options);
	}

	/**
	 * Query posts.
	 *
	 * @api
	 * @deprecated since 2.0.0 Use `Timber::get_posts()` instead.
	 *
	 * @param mixed $query
	 * @param array $options
	 *
	 * @return PostCollection
	 */
	public static function query_posts( $query = false, array $options = [] ) {
		Helper::deprecated('Timber::query_posts()', 'Timber::get_posts()', '2.0.0');

		return self::get_posts($query, $options);
	}

	/**
	 * Gets an attachment by its URL or absolute file path.
	 *
	 * Honors the `timber/post/image_extensions` filter, returning a Timber\Image if the found
	 * attachment is identified as an image. Also honors Class Maps.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * // Get attachment by URL.
	 * $attachment = Timber::get_attachment_by( 'url', 'https://example.com/uploads/2020/09/cat.gif' );
	 *
	 * // Get attachment by filepath.
	 * $attachment = Timber::get_attachment_by( 'path', '/path/to/wp-content/uploads/2020/09/cat.gif' );
	 *
	 * // Try to handle either case.
	 * $mystery_string = some_function();
	 * $attachment     = Timber::get_attachment_by( $mystery_string );
	 * ```
	 *
	 * @param string $field_or_ident Can be "url", "path", an attachment URL, or the absolute
	 *                               path of an attachment file. If "url" or "path" is given, a
	 *                               second arg is required.
	 * @param string $ident          Optional. An attachment URL or absolute path. Default empty
	 *                               string.
	 *
	 * @return \Timber\Attachment|false
	 */
	public static function get_attachment_by( string $field_or_ident, string $ident = '' ) {
		if ($field_or_ident === 'url') {
			if (empty($ident)) {
				Helper::doing_it_wrong(
					'Timber::get_attachment_by()',
					'Passing "url" as the first arg requires passing a URL as the second arg.',
					'2.0.0'
				);

				return false;
			}

			$id = attachment_url_to_postid($ident);

			return $id ? (new PostFactory())->from($id) : false;
		}

		if ($field_or_ident === 'path') {
			if (empty($ident)) {
				Helper::doing_it_wrong(
					'Timber::get_attachment_by()',
					'Passing "path" as the first arg requires passing an absolute path as the second arg.',
					'2.0.0'
				);

				return false;
			}

			if (!file_exists($ident)) {
				// Deal with a relative path.
				$ident = URLHelper::get_full_path($ident);
			}

			return self::get_attachment_by('url', URLHelper::file_system_to_url($ident));
		}

		if (empty($ident)) {
			$field = URLHelper::starts_with($field_or_ident, ABSPATH) ? 'path' : 'url';

			return self::get_attachment_by($field, $field_or_ident);
		}

		return false;
	}

	/* Term Retrieval
	================================ */

	/**
	 * Get terms.
	 * @api
	 * @param string|array $args a string or array identifying the taxonomy or
	 * `WP_Term_Query` args. Numeric strings are treated as term IDs; non-numeric
	 * strings are treated as taxonomy names. Numeric arrays are treated as a
	 * list a of term identifiers; associative arrays are treated as args to
	 * `WP_Term_Query::__construct()` and accepts any valid parameters to that
	 * constructor.
	 * @param array        $options optional; none are currently supported.
	 * @return Iterable
	 * @see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
	 * @example
	 * ```php
	 * // Get all tags.
	 * $tags = Timber::get_terms('post_tag');
	 * // Note that this is equivalent to:
	 * $tags = Timber::get_terms( 'tag' );
	 * $tags = Timber::get_terms( 'tags' );
	 *
	 * // Get all categories.
	 * $cats = Timber::get_terms('category');
	 *
	 * // Get all terms in a custom taxonomy.
	 * $cats = Timber::get_terms('my_taxonomy');
	 *
	 * // Perform a custom Term query.
	 * $cats = Timber::get_terms([
	 *   'taxonomy' => 'my_taxonomy',
	 *   'orderby'  => 'slug',
	 *   'order'    => 'DESC',
	 * ]);
	 * ```
	 */
	public static function get_terms( $args = null, array $options = [] ) : Iterable {
		// default to all queryable taxonomies
		$args = $args ?? [
			'taxonomy'   => get_taxonomies(),
		];

		$factory = new TermFactory();

		return $factory->from($args);
	}

	/**
	 * Get term.
	 * @api
	 * @param int|\WP_Term $term a WP_Term or term_id
	 * @return \Timber\Term|false
	 * @example
	 * ```php
	 * // Get a Term.
	 * $tag = Timber::get_term(123);
	 * ```
	 */
	public static function get_term( $term = null ) {
		
		if (null === $term) {
			// get the fallback term_id from the current query
			global $wp_query;
			$term = $wp_query->queried_object->term_id ?? null;
		}
		if (null === $term) {
			// not able to get term_id from the current query; bail
			return false;
		}

		$factory = new TermFactory();

		$terms = $factory->from($term);
		if ( is_array($terms) ) {
			$terms = $terms[0];
		}
		return $terms;
	}

	/**
	 * Gets a term by field.
	 *
	 * This function works like
	 * [`get_term_by()`](https://developer.wordpress.org/reference/functions/get_term_by/), but
	 * returns a `Timber\Term` object.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * // Get a term by slug.
	 * $term = Timber::get_term_by( 'slug', 'security' );
	 *
	 * // Get a term by name.
	 * $term = Timber::get_term_by( 'name', 'Security' );
	 *
	 * // Get a term by slug from a specific taxonomy.
	 * $term = Timber::get_term_by( 'slug', 'security', 'category' );
	 * ```
	 *
	 * @param string     $field    The name of the field to retrieve the term with. One of: `id`,
	 *                             `ID`, `slug`, `name` or `term_taxonomy_id`.
	 * @param int|string $value    The value to search for by `$field`.
	 * @param string     $taxonomy The taxonomy you want to retrieve from. Empty string will search 
	 *                             from all.
	 *
	 * @return \Timber\Term|null
	 */
	public static function get_term_by( string $field, $value, string $taxonomy = '' ) {

		$wp_term = get_term_by($field, $value, $taxonomy);

		if ( $wp_term === false ) {
			if ( empty($taxonomy) && $field != 'term_taxonomy_id' ) {
				$search = [$field => $value, $taxonomy => 'any', 'hide_empty' => false];
				return static::get_term($search);
			}
			return false;
		}

		return static::get_term($wp_term);
	}

	/* User Retrieval
	================================ */

	/**
	 * Gets one or more users as an array.
	 *
	 * By default, Timber will use the `Timber\User` class to create a your post objects. To
	 * control which class is used for your post objects, use [Class Maps]().
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * // Get users with on an array of user IDs.
	 * $users = Timber::get_users( [ 24, 81, 325 ] );
	 *
	 * // Get all users that only have a subscriber role.
	 * $subscribers = Timber::get_users( [
	 *     'role' => 'subscriber',
	 * ] );
	 *
	 * // Get all users that have published posts.
	 * $post_authors = Timber::get_users( [
	 *     'has_published_posts' => [ 'post' ],
	 * ] );
	 * ```
	 *
	 * @todo  Add links to Class Maps documentation in function summary.
	 *
	 * @param array $query   Optional. A WordPress-style query or an array of user IDs. Use an
	 *                       array in the same way you would use the `$args` parameter in
	 *                       [WP_User_Query](https://developer.wordpress.org/reference/classes/wp_user_query/).
	 *                       See
	 *                       [WP_User_Query::prepare_query()](https://developer.wordpress.org/reference/classes/WP_User_Query/prepare_query/)
	 *                       for a list of all available parameters. Passing an empty parameter
	 *                       will return an empty array. Default empty array
	 *                       `[]`.
	 * @param array $options Optional. An array of options. None are currently supported. This
	 *                       parameter exists to prevent future breaking changes. Default empty
	 *                       array `[]`.
	 *
	 * @return \Iterable An array of users objects. Will be empty if no users were found.
	 */
	public static function get_users( array $query = [], array $options = [] ) : Iterable {
		$factory = new UserFactory();
		// TODO return a Collection type?
		return $factory->from($query);
	}

	/**
	 * Gets a single user.
	 *
	 * By default, Timber will use the `Timber\User` class to create a your post objects. To
	 * control which class is used for your post objects, use [Class Maps]().
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * $current_user = Timber::get_user();
	 *
	 * // Get user by ID.
	 * $user = Timber::get_user( $user_id );
	 *
	 * // Convert a WP_User object to a Timber\User object.
	 * $user = Timber::get_user( $wp_user_object );
	 *
	 * // Check if a user is logged in.
	 *
	 * $user = Timber::get_user();
	 *
	 * if ( $user ) {
	 *     // Yay, user is logged in.
	 * }
	 * ```
	 *
	 * @todo Add links to Class Maps documentation in function summary.
	 *
	 * @param int|\WP_User $user A WP_User object or a WordPress user ID. Defaults to the ID of the
	 *                           currently logged-in user.
	 *
	 * @return \Timber\User|false
	 */
	public static function get_user( $user = null ) {
		/*
		 * TODO in the interest of time, I'm implementing this logic here. If there's
		 * a better place to do this or something that already implements this, let me know
		 * and I'll switch over to that.
		 */
		$user = $user ?: get_current_user_id();

		$factory = new UserFactory();
		return $factory->from($user);
	}

	/**
	 * Gets a user by field.
	 *
	 * This function works like
	 * [`get_user_by()`](https://developer.wordpress.org/reference/functions/get_user_by/), but
	 * returns a `Timber\User` object.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * // Get a user by email.
	 * $user = Timber::get_user_by( 'email', 'user@example.com' );
	 *
	 * // Get a user by login.
	 * $user = Timber::get_user_by( 'login', 'keanu-reeves' );
	 * ```
	 *
	 * @param string     $field The name of the field to retrieve the user with. One of: `id`,
	 *                          `ID`, `slug`, `email` or `login`.
	 * @param int|string $value The value to search for by `$field`.
	 *
	 * @return \Timber\User|null
	 */
	public static function get_user_by( string $field, $value ) {
		$wp_user = get_user_by($field, $value);

		if ($wp_user === false) {
			return false;
		}

		return static::get_user($wp_user);
	}


	/* Menu Retrieval
	================================ */

	/**
	 * Gets a nav menu object.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * // Get a menu by location
	 * $menu = Timber::get_menu( 'primary-menu' );
	 *
	 * // Get a menu by slug
	 * $menu = Timber::get_menu( 'my-menu' );
	 *
	 * // Get a menu by name
	 * $menu = Timber::get_menu( 'Main Menu' );
	 *
	 * // Get a menu by ID (term_id)
	 * $menu = Timber::get_menu( 123 );
	 * ```
	 *
	 * @param int|string $ident A menu identifier: a term_id, slug, menu name, or menu location name
	 * @param array      $options An associative array of options. Currently only one option is
	 * supported:
	 * - `depth`: How deep down the tree of menu items to query. Useful if you only want
	 *   the first N levels of items in the menu.
	 *
	 * @return \Timber\Menu|false
	 */
	public static function get_menu( $ident = null, array $options = [] ) {
		$factory   = new MenuFactory();

		return $factory->from($ident, $options);
	}

	/**
	 * @todo implement PagesMenuFactory
	 */
	public static function get_pages_menu( array $pages = [], array $options = [] ) {
		$menu = new Menu( $pages, $options );
		$menu->init_as_page_menu();
		return $menu;
	}


	/* Comment Retrieval
	================================ */

	/**
	 * Get comments.
	 * @api
	 * @param array   $query
	 * @param array   $options optional; none are currently supported
	 * @return mixed
	 */
	public static function get_comments( array $query = [], array $options = [] ) : Iterable {
		$factory = new CommentFactory();
		// TODO return a Collection type?
		return $factory->from($query);
	}

	/**
	 * Get comment.
	 * @api
	 * @param int|\WP_Comment $comment
	 * @return \Timber\Comment|null
	 */
	public static function get_comment( $comment ) {
		$factory = new CommentFactory();
		return $factory->from($comment);
	}

	/* Site Retrieval
	================================ */

	/**
	 * Get sites.
	 * @api
	 * @param array|bool $blog_ids
	 * @return array
	 */
	public static function get_sites( $blog_ids = false ) {
		if ( !is_array($blog_ids) ) {
			global $wpdb;
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id ASC");
		}
		$return = array();
		foreach ( $blog_ids as $blog_id ) {
			$return[] = new Site($blog_id);
		}
		return $return;
	}


	/*  Template Setup and Display
	================================ */

	/**
	 * Get context.
	 * @api
	 * @deprecated 2.0.0, use `Timber::context()` instead.
	 *
	 * @return array
	 */
	public static function get_context() {
		Helper::deprecated( 'get_context', 'context', '2.0.0' );

		return self::context();
	}

	/**
	 * Gets the global context.
	 *
	 * The context always contains the global context with the following variables:
	 *
	 * - `site` – An instance of `Timber\Site`.
	 * - `request` - An instance of `Timber\Request`.
	 * - `theme` - An instance of `Timber\Theme`.
	 * - `user` - An instance of `Timber\User`.
	 * - `http_host` - The HTTP host.
	 * - `wp_title` - Title retrieved for the currently displayed page, retrieved through
	 * `wp_title()`.
	 * - `body_class` - The body class retrieved through `get_body_class()`.
	 *
	 * The global context will be cached, which means that you can call this function again without
	 * losing performance.
	 *
	 * Additionally to that, the context will contain template contexts depending on which template
	 * is being displayed. For archive templates, a `posts` variable will be present that will
	 * contain a collection of `Timber\Post` objects for the default query. For singular templates,
	 * a `post` variable will be present that that contains a `Timber\Post` object of the `$post`
	 * global.
	 *
	 * @api
	 * @since 2.0.0
	 *
	 * @param array $extra any extra data to merge in. Overrides whatever is
	 * already there for this call only. In other words, the underlying context
	 * data is immutable and unaffected by passing this param.
	 * @return array An array of context variables that is used to pass into Twig templates through
	 *               a render or compile function.
	 */
	public static function context(array $extra = []) {
		$context = self::context_global();

		if ( is_singular() ) {
			// NOTE: this also handles the is_front_page() case.
			$context['post'] = Timber::get_post()->setup();
		} elseif ( is_home() ) {
			// show_on_front = page
			$context['post']  = Timber::get_post()->setup();
			$context['posts'] = Timber::get_posts();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$context['term']  = Timber::get_term();
			$context['posts'] = Timber::get_posts();
		} elseif ( is_search() ) {
			$context['posts']        = Timber::get_posts();
			$context['search_query'] = get_search_query();
		} elseif ( is_author() ) {
			$context['author'] = Timber::get_user(get_query_var('author'));
			$context['posts']  = Timber::get_posts();
		} elseif ( is_archive() ) {
			$context['posts'] = Timber::get_posts();
		}

		return array_merge( $context, $extra );
	}

	/**
	 * Gets the global context.
	 *
	 * This function is used by `Timber::context()` to get the global context. Usually, you don’t
	 * call this function directly, except when you need the global context in a partial view.
	 *
	 * The global context will be cached, which means that you can call this function again without
	 * losing performance.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 * ```php
	 * add_shortcode( 'global_address', function() {
	 *     return Timber::compile(
	 *         'global_address.twig',
	 *         Timber::context_global()
	 *     );
	 * } );
	 * ```
	 *
	 * @return array An array of global context variables.
	 */
	public static function context_global() {
		if ( empty( self::$context_cache ) ) {
			self::$context_cache['site']       = new Site();
			self::$context_cache['request']    = new Request();
			self::$context_cache['theme']      = self::$context_cache['site']->theme;
			self::$context_cache['user']       = is_user_logged_in() ? static::get_user() : false;

			self::$context_cache['http_host']  = URLHelper::get_scheme() . '://' . URLHelper::get_host();
			self::$context_cache['wp_title']   = Helper::get_wp_title();
			self::$context_cache['body_class'] = implode( ' ', get_body_class() );

			/**
			 * Filters the global Timber context.
			 *
			 * By using this filter, you can add custom data to the global Timber context, which
			 * means that this data will be available on every page that is initialized with
			 * `Timber::context()`.
			 *
			 * Be aware that data will be cached as soon as you call `Timber::context()` for the
			 * first time. That’s why you should add this filter before you call
			 * `Timber::context()`.
			 *
			 * @see \Timber\Timber::context()
			 * @since 0.21.7
			 * @example
			 * ```php
			 * add_filter( 'timber/context', function( $context ) {
			 *     // Example: A custom value
			 *     $context['custom_site_value'] = 'Hooray!';
			 *
			 *     // Example: Add a menu to the global context.
			 *     $context['menu'] = new \Timber\Menu( 'primary-menu' );
			 *
			 *     // Example: Add all ACF options to global context.
			 *     $context['options'] = get_fields( 'options' );
			 *
			 *     return $context;
			 * } );
			 * ```
			 * ```twig
			 * <h1>{{ custom_site_value|e }}</h1>
			 *
			 * {% for item in menu.items %}
			 *     {# Display menu item #}
			 * {% endfor %}
			 *
			 * <footer>
			 *     {% if options.footer_text is not empty %}
			 *         {{ options.footer_text|e }}
			 *     {% endif %}
			 * </footer>
			 * ```
			 *
			 * @param array $context The global context.
			 */
			self::$context_cache = apply_filters( 'timber/context', self::$context_cache );

			/**
			 * Filters the global Timber context.
			 *
			 * @deprecated 2.0.0, use `timber/context`
			 */
			self::$context_cache = apply_filters_deprecated(
				'timber_context',
				array( self::$context_cache ),
				'2.0.0',
				'timber/context'
			);

		}

		return self::$context_cache;
	}

	/**
	 * Compile a Twig file.
	 *
	 * Passes data to a Twig file and returns the output.
	 * If the template file doesn't exist it will throw a warning when WP_DEBUG is enabled.
	 *
	 * @api
	 * @example
	 * ```php
	 * $data = array(
	 *     'firstname' => 'Jane',
	 *     'lastname' => 'Doe',
	 *     'email' => 'jane.doe@example.org',
	 * );
	 *
	 * $team_member = Timber::compile( 'team-member.twig', $data );
	 * ```
	 * @param array|string $filenames  Name of the Twig file to render. If this is an array of files, Timber will
	 *                                 render the first file that exists.
	 * @param array        $data       Optional. An array of data to use in Twig template.
	 * @param bool|int     $expires    Optional. In seconds. Use false to disable cache altogether. When passed an
	 *                                 array, the first value is used for non-logged in visitors, the second for users.
	 *                                 Default false.
	 * @param string       $cache_mode Optional. Any of the cache mode constants defined in Timber\Loader.
	 * @param bool         $via_render Optional. Whether to apply optional render or compile filters. Default false.
	 * @return bool|string The returned output.
	 */
	public static function compile( $filenames, $data = array(), $expires = false, $cache_mode = Loader::CACHE_USE_DEFAULT, $via_render = false ) {
		if ( !defined('TIMBER_LOADED') ) {
			self::init();
		}
		$caller = LocationManager::get_calling_script_dir(1);
		$loader = new Loader($caller);
		$file = $loader->choose_template($filenames);

		$caller_file = LocationManager::get_calling_script_file(1);

		/**
		 * Fires after the calling PHP file was determined in Timber’s compile
		 * function.
		 *
		 * This action is used by the Timber Debug Bar extension.
		 *
		 * @since 1.1.2
		 * @since 2.0.0 Switched from filter to action.
		 *
		 * @param string|null $caller_file The calling script file.
		 */
		do_action( 'timber/calling_php_file', $caller_file );

		if ( $via_render ) {
			/**
			 * Filters the Twig template that should be rendered.
			 *
			 * @since 2.0.0
			 *
			 * @param string $file The chosen Twig template name to render.
			 */
			$file = apply_filters( 'timber/render/file', $file );

			/**
			 * Filters the Twig file that should be rendered.
			 *
			 * @codeCoverageIgnore
			 * @deprecated 2.0.0, use `timber/render/file`
			 */
			$file = apply_filters_deprecated(
				'timber_render_file',
				array( $file ),
				'2.0.0',
				'timber/render/file'
			);
		} else {
			/**
			 * Filters the Twig template that should be compiled.
			 *
			 * @since 2.0.0
			 *
			 * @param string $file The chosen Twig template name to compile.
			 */
			$file = apply_filters( 'timber/compile/file', $file );

			/**
			 * Filters the Twig template that should be compiled.
			 *
			 * @deprecated 2.0.0
			 */
			$file = apply_filters_deprecated(
				'timber_compile_file',
				array( $file ),
				'2.0.0',
				'timber/compile/file'
			);
		}
		$output = false;

		if ($file !== false) {
			if ( is_null($data) ) {
				$data = array();
			}

			if ( $via_render ) {
				/**
				 * Filters the data that should be passed for rendering a Twig template.
				 *
				 * @since 2.0.0
				 *
				 * @param array  $data The data that is used to render the Twig template.
				 * @param string $file The name of the Twig template to render.
				 */
				$data = apply_filters( 'timber/render/data', $data, $file );
				/**
				 * Filters the data that should be passed for rendering a Twig template.
				 *
				 * @codeCoverageIgnore
				 * @deprecated 2.0.0
				 */
				$data = apply_filters_deprecated(
					'timber_render_data',
					array( $data ),
					'2.0.0',
					'timber/render/data'
				);
			} else {
				/**
				 * Filters the data that should be passed for compiling a Twig template.
				 *
				 * @since 2.0.0
				 *
				 * @param array  $data The data that is used to compile the Twig template.
				 * @param string $file The name of the Twig template to compile.
				 */
				$data = apply_filters( 'timber/compile/data', $data, $file );

				/**
				 * Filters the data that should be passed for compiling a Twig template.
				 *
				 * @deprecated 2.0.0, use `timber/compile/data`
				 */
				$data = apply_filters_deprecated(
					'timber_compile_data',
					array( $data ),
					'2.0.0',
					'timber/compile/data'
				);
			}

			$output = $loader->render($file, $data, $expires, $cache_mode);
		} else {
			if ( is_array($filenames) ) {
				$filenames = implode(", ", $filenames);
			}
			Helper::error_log( 'Error loading your template files: '.$filenames.'. Make sure one of these files exists.' );
		}

		/**
		 * Filters the compiled result before it is returned in `Timber::compile()`.
		 *
		 * It adds the posibility to filter the output ready for render.
		 *
		 * @since 2.0.0
		 *
		 * @param string $output
		 */
		$output = apply_filters( 'timber/compile/result', $output );

		/**
		 * Fires after a Twig template was compiled and before the compiled data
		 * is returned.
		 *
		 * This action can be helpful if you need to debug Twig template
		 * compilation.
		 *
		 * @todo Add parameter descriptions
		 *
		 * @since 2.0.0
		 *
		 * @param string $output
		 * @param string $file
		 * @param array  $data
		 * @param bool   $expires
		 * @param string $cache_mode
		 */
		do_action( 'timber/compile/done', $output, $file, $data, $expires, $cache_mode );

		/**
		 * Fires after a Twig template was compiled and before the compiled data
		 * is returned.
		 *
		 * @deprecated 2.0.0, use `timber/compile/done`
		 */
		do_action_deprecated( 'timber_compile_done', array(), '2.0.0', 'timber/compile/done' );

		return $output;
	}

	/**
	 * Compile a string.
	 *
	 * @api
	 * @example
	 * ```php
	 * $data = array(
	 *     'username' => 'Jane Doe',
	 * );
	 *
	 * $welcome = Timber::compile_string( 'Hi {{ username }}, I’m a string with a custom Twig variable', $data );
	 * ```
	 * @param string $string A string with Twig variables.
	 * @param array  $data   Optional. An array of data to use in Twig template.
	 * @return bool|string
	 */
	public static function compile_string( $string, $data = array() ) {
		$dummy_loader = new Loader();
		$twig = $dummy_loader->get_twig();
		$template = $twig->createTemplate($string);
		return $template->render($data);
	}

	/**
	 * Fetch function.
	 *
	 * @api
	 * @deprecated 2.0.0 use Timber::compile()
	 * @param array|string $filenames  Name of the Twig file to render. If this is an array of files, Timber will
	 *                                 render the first file that exists.
	 * @param array        $data       Optional. An array of data to use in Twig template.
	 * @param bool|int     $expires    Optional. In seconds. Use false to disable cache altogether. When passed an
	 *                                 array, the first value is used for non-logged in visitors, the second for users.
	 *                                 Default false.
	 * @param string       $cache_mode Optional. Any of the cache mode constants defined in Timber\Loader.
	 * @return bool|string The returned output.
	 */
	public static function fetch( $filenames, $data = array(), $expires = false, $cache_mode = Loader::CACHE_USE_DEFAULT ) {
		Helper::deprecated(
			'fetch',
			'Timber::compile() (see https://timber.github.io/docs/reference/timber/#compile for more information)',
			'2.0.0'
		);
		$output = self::compile( $filenames, $data, $expires, $cache_mode, true );

		/**
		 * Filters the compiled result before it is returned.
		 *
		 * @see \Timber\Timber::fetch()
		 * @since 0.16.7
		 * @deprecated 2.0.0 use timber/compile/result
		 *
		 * @param string $output The compiled output.
		 */
		$output = apply_filters_deprecated(
			'timber_compile_result',
			array( $output ),
			'2.0.0',
			'timber/compile/result'
		);

		return $output;
	}

	/**
	 * Render function.
	 *
	 * Passes data to a Twig file and echoes the output.
	 *
	 * @api
	 * @example
	 * ```php
	 * $context = Timber::context();
	 *
	 * Timber::render( 'index.twig', $context );
	 * ```
	 * @param array|string $filenames  Name of the Twig file to render. If this is an array of files, Timber will
	 *                                 render the first file that exists.
	 * @param array        $data       Optional. An array of data to use in Twig template.
	 * @param bool|int     $expires    Optional. In seconds. Use false to disable cache altogether. When passed an
	 *                                 array, the first value is used for non-logged in visitors, the second for users.
	 *                                 Default false.
	 * @param string       $cache_mode Optional. Any of the cache mode constants defined in Timber\Loader.
	 * @return bool|string The echoed output.
	 */
	public static function render( $filenames, $data = array(), $expires = false, $cache_mode = Loader::CACHE_USE_DEFAULT ) {
		$output = self::compile($filenames, $data, $expires, $cache_mode, true);
		echo $output;
	}

	/**
	 * Render a string with Twig variables.
	 *
	 * @api
	 * @example
	 * ```php
	 * $data = array(
	 *     'username' => 'Jane Doe',
	 * );
	 *
	 * Timber::render_string( 'Hi {{ username }}, I’m a string with a custom Twig variable', $data );
	 * ```
	 * @param string $string A string with Twig variables.
	 * @param array  $data   An array of data to use in Twig template.
	 * @return bool|string
	 */
	public static function render_string( $string, $data = array() ) {
		$compiled = self::compile_string($string, $data);
		echo $compiled;
	}


	/*  Sidebar
	================================ */

	/**
	 * Get sidebar.
	 * @api
	 * @param string  $sidebar
	 * @param array   $data
	 * @return bool|string
	 */
	public static function get_sidebar( $sidebar = 'sidebar.php', $data = array() ) {
		if ( strstr(strtolower($sidebar), '.php') ) {
			return self::get_sidebar_from_php($sidebar, $data);
		}
		return self::compile($sidebar, $data);
	}

	/**
	 * Get sidebar from PHP
	 * @api
	 * @param string  $sidebar
	 * @param array   $data
	 * @return string
	 */
	public static function get_sidebar_from_php( $sidebar = '', $data = array() ) {
		$caller = LocationManager::get_calling_script_dir( 1 );
		$uris   = LocationManager::get_locations( $caller );
		ob_start();
		$found = false;
		foreach ( $uris as $namespace => $uri_locations ) {
			if ( is_array( $uri_locations ) ) {
				foreach ( $uri_locations as $uri ) {
					if ( file_exists( trailingslashit( $uri ) . $sidebar ) ) {
						include trailingslashit( $uri ) . $sidebar;
						$found = true;
					}
				}
			}
		}
		if ( ! $found ) {
			Helper::error_log( 'error loading your sidebar, check to make sure the file exists' );
		}
		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}

	/**
	 * Get widgets.
	 *
	 * @api
	 * @param int|string $widget_id Optional. Index, name or ID of dynamic sidebar. Default 1.
	 * @return string
	 */
	public static function get_widgets( $widget_id ) {
		return trim( Helper::ob_function( 'dynamic_sidebar', array( $widget_id ) ) );
	}

	/**
	 * Get pagination.
	 *
	 * @api
	 * @deprecated 2.0.0
	 * @link https://timber.github.io/docs/guides/pagination/
	 * @param array $prefs an array of preference data.
	 * @return array|mixed
	 */
	public static function get_pagination( $prefs = array() ) {
		Helper::deprecated(
			'get_pagination',
			'{{ posts.pagination }} (see https://timber.github.io/docs/guides/pagination/ for more information)',
			'2.0.0'
		);

		return Pagination::get_pagination($prefs);
	}

}
