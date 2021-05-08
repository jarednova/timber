<?php

namespace Timber;

use WP_Query;
use WP_Term;

use Timber\Factory\TermFactory;

/**
 * Class Term
 *
 * Terms: WordPress has got 'em, you want 'em. Categories. Tags. Custom Taxonomies. You don't care,
 * you're a fiend. Well let's get this under control:
 *
 * @api
 * @example
 * ```php
 * // Get a term by its ID
 * $context['term'] = Timber::get_term(6);
 *
 * // Get a term when on a term archive page
 * $context['term_page'] = Timber::get_term();
 *
 * // Get a term with a slug
 * $context['team'] = Timber::get_term('patriots');
 * Timber::render('index.twig', $context);
 * ```
 * ```twig
 * <h2>{{ term_page.name }} Archives</h2>
 * <h3>Teams</h3>
 * <ul>
 *     <li>{{ st_louis.name}} - {{ st_louis.description }}</li>
 *     <li>{{ team.name}} - {{ team.description }}</li>
 * </ul>
 * ```
 * ```html
 * <h2>Team Archives</h2>
 * <h3>Teams</h3>
 * <ul>
 *     <li>St. Louis Cardinals - Winner of 11 World Series</li>
 *     <li>New England Patriots - Winner of 6 Super Bowls</li>
 * </ul>
 * ```
 */
class Term extends CoreEntity implements CoreInterface, MetaInterface {

	public $object_type = 'term';
	public static $representation = 'term';

	public $_children;

	/**
	 * @api
	 * @var string the human-friendly name of the term (ex: French Cuisine)
	 */
	public $name;

	/**
	 * @api
	 * @var string the WordPress taxonomy slug (ex: `post_tag` or `actors`)
	 */
	public $taxonomy;

	/**
	 * @internal
	 */
	protected function __construct() {}

	/**
	 * @internal
	 * @param \WP_Term the vanilla WP term object to build from
	 * @return \Timber\Term
	 */
	public static function build(WP_Term $wp_term, array $_options = []) : self {
		$term = new static();
		$term->init($wp_term);
		return $term;
	}

	/**
	 * The string the term will render as by default
	 *
	 * @api
	 * @return string
	 */
	public function __toString() {
		return $this->name;
	}

	/**
	 *
	 * @deprecated 2.0.0, use TermFactory::from instead.
	 *
	 * @param $tid
	 * @param $taxonomy
	 *
	 * @return static
	 */
	public static function from( $tid, $taxonomy ) {
		Helper::deprecated(
			"Term::from()", 
			"Timber\Factory\TermFactory->from()",
			'2.0.0'
		);

		$termFactory = new TermFactory();
		return $termFactory->from($tid, $taxonomy);
	}


	/* Setup
	===================== */

	/**
	 * @internal
	 * @return integer
	 */
	protected function get_term_from_query() {
		global $wp_query;
		if ( isset($wp_query->queried_object) ) {
			$qo = $wp_query->queried_object;
			if ( isset($qo->term_id) ) {
				return $qo->term_id;
			}
		}
		if ( isset($wp_query->tax_query->queries[0]['terms'][0]) ) {
			return $wp_query->tax_query->queries[0]['terms'][0];
		}
	}

	/**
	 * @internal
	 * @param \WP_Term $term
	 */
	protected function init( WP_Term $term ) {
		$this->ID = $term->term_id;
		$this->id = $term->term_id;
		$this->import($term);
	}

	/**
	 * @internal
	 * @param int $tid
	 * @return mixed
	 */
	protected function get_term( $tid ) {
		if ( is_object($tid) || is_array($tid) ) {
			return $tid;
		}
		$tid = self::get_tid($tid);

		if ( isset($this->taxonomy) && strlen($this->taxonomy) ) {
			return get_term($tid, $this->taxonomy);
		} else {
			global $wpdb;
			$query = $wpdb->prepare("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d LIMIT 1", $tid);
			$tax = $wpdb->get_var($query);
			if ( isset($tax) && strlen($tax) ) {
				$this->taxonomy = $tax;
				return get_term($tid, $tax);
			}
		}
		return null;
	}

	/**
	 * @internal
	 * @param mixed $tid
	 * @return int|bool
	 */
	protected function get_tid( $tid ) {
		global $wpdb;
		if ( is_numeric($tid) ) {
			return $tid;
		}
		if ( gettype($tid) === 'object' ) {
			$tid = $tid->term_id;
		}
		$query = $wpdb->prepare("SELECT * FROM $wpdb->terms WHERE slug = %s", $tid);
		$result = $wpdb->get_row($query);
		if ( isset($result->term_id) ) {
			$result->ID = $result->term_id;
			$result->id = $result->term_id;
			return $result->ID;
		}
		return false;
	}


	/* Public methods
	===================== */

	/**
	 * @deprecated 2.0.0, use `{{ term.edit_link }}` instead.
	 * @return string
	 */
	public function get_edit_url() {
		Helper::deprecated('{{ term.get_edit_url }}', '{{ term.edit_link }}', '2.0.0');
		return $this->edit_link();
	}

	/**
	 * Gets a term meta value.
	 * @deprecated 2.0.0, use `{{ term.meta('field_name') }}` instead.
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @return string The meta field value.
	 */
	public function get_meta_field( $field_name ) {
		Helper::deprecated(
			"{{ term.get_meta_field('field_name') }}",
			"{{ term.meta('field_name') }}",
			'2.0.0'
		);
		return $this->meta($field_name);
	}

	/**
	 * @internal
	 * @return array
	 */
	public function children() {
		if ( !isset($this->_children) ) {
			$children = get_term_children($this->ID, $this->taxonomy);
			foreach ( $children as &$child ) {
				$child = Timber::get_term($child);
			}
			$this->_children = $children;
		}
		return $this->_children;
	}

	/**
	 * Return the description of the term
	 *
	 * @api
	 * @return string
	 */
	public function description() {
		$prefix = '<p>';
		$desc = term_description($this->ID, $this->taxonomy);
		if ( substr($desc, 0, strlen($prefix)) == $prefix ) {
			$desc = substr($desc, strlen($prefix));
		}
		$desc = preg_replace('/'.preg_quote('</p>', '/').'$/', '', $desc);
		return trim($desc);
	}

	/**
	 * @api
	 * @return string
	 */
	public function edit_link() {
		return get_edit_term_link($this->ID, $this->taxonomy);
	}

	/**
	 * Returns a full link to the term archive page like `http://example.com/category/news`
	 *
	 * @api
	 * @example
	 * ```twig
	 * See all posts in: <a href="{{ term.link }}">{{ term.name }}</a>
	 * ```
	 *
	 * @return string
	 */
	public function link() {
		$link = get_term_link($this);

		/**
		 * Filters the link to the term archive page.
		 *
		 * @see   \Timber\Term::link()
		 * @since 0.21.9
		 *
		 * @param string       $link The link.
		 * @param \Timber\Term $term The term object.
		 */
		$link = apply_filters('timber/term/link', $link, $this);

		/**
		 * Filters the link to the term archive page.
		 *
		 * @deprecated 0.21.9, use `timber/term/link`
		 */
		$link = apply_filters_deprecated(
			'timber_term_link',
			array( $link, $this ),
			'2.0.0',
			'timber/term/link'
		);

		return $link;
	}

	/**
	 * Gets a term meta value.
	 *
	 * @api
	 * @deprecated 2.0.0, use `{{ term.meta('field_name') }}` instead.
	 * @see \Timber\Term::meta()
	 *
	 * @param string $field_name The field name for which you want to get the value.
	 * @return mixed The meta field value.
	 */
	public function get_field( $field_name = null ) {
		Helper::deprecated(
			"{{ term.get_field('field_name') }}",
			"{{ term.meta('field_name') }}",
			'2.0.0'
		);

		return $this->meta( $field_name );
	}

	/**
	 * Returns a relative link (path) to the term archive page like `/category/news`
	 *
	 * @api
	 * @example
	 * ```twig
	 * See all posts in: <a href="{{ term.path }}">{{ term.name }}</a>
	 * ```
	 * @return string
	 */
	public function path() {
		$link = $this->link();
		$rel = URLHelper::get_rel_url($link, true);

		/**
		 * Filters the relative link (path) to a term archive page.
		 *
		 * @todo Add example
		 *
		 * @see   \Timber\Term::path()
		 * @since 0.21.9
		 *
		 * @param string       $rel  The relative link.
		 * @param \Timber\Term $term The term object.
		 */
		$rel = apply_filters('timber/term/path', $rel, $this);

		/**
		 * Filters the relative link (path) to a term archive page.
		 *
		 * @deprecated 2.0.0, use `timber/term/path`
		 */
		$rel = apply_filters_deprecated(
			'timber_term_path',
			array( $rel, $this ),
			'2.0.0',
			'timber/term/path'
		);

		return $rel;
	}

	/**
	 * Gets posts that have the current term assigned.
	 *
	 * @api
	 * @example
	 * Query the default posts_per_page for this Term:
	 *
	 * ```twig
	 * <h4>Recent posts in {{ term.name }}</h4>
	 *
	 * <ul>
	 * {% for post in term.posts() %}
	 *     <li>
	 *         <a href="{{ post.link }}">{{ post.title }}</a>
	 *     </li>
	 * {% endfor %}
	 * </ul>
	 * ```
	 *
	 * Query exactly 3 Posts from this Term:
	 *
	 * ```twig
	 * <h4>Recent posts in {{ term.name }}</h4>
	 *
	 * <ul>
	 * {% for post in term.posts(3) %}
	 *     <li>
	 *         <a href="{{ post.link }}">{{ post.title }}</a>
	 *     </li>
	 * {% endfor %}
	 * </ul>
	 * ```
	 *
	 * If you need more control over the query that is going to be performed, you can pass your
	 * custom query arguments in the first parameter.
	 *
	 * ```twig
	 * <h4>Our branches in {{ region.name }}</h4>
	 *
	 * <ul>
	 * {% for branch in region.posts({
	 *     post_type: 'branch',
	 *     posts_per_page: -1,
	 *     orderby: 'menu_order'
	 * }) %}
	 *     <li>
	 *         <a href="{{ branch.link }}">{{ branch.title }}</a>
	 *     </li>
	 * {% endfor %}
	 * </ul>
	 * ```
	 *
	 * @param int|array $numberposts_or_args Optional. Either the number of posts or an array of
	 *                                       arguments for the post query to be performed.
	 *                                       Default is an empty array, the equivalent of:
	 *                                       ```php
	 *                                       [
	 *                                         'posts_per_page' => get_option('posts_per_page'),
	 *                                         'post_type'      => 'any',
	 *                                         'tax_query'      => [ ...tax query for this Term... ]
	 *                                       ]
	 *                                       ```
	 * @param string $post_type_or_class     Deprecated. Before Timber 2.x this was a post_type to be
	 *                                       used for querying posts OR the Timber\Post subclass to
	 *                                       instantiate for each post returned. As of Timber 2.0.0,
	 *                                       specify `post_type` in the `$query` array argument. To
	 *                                       specify the class, use Class Maps.
	 * @see https://timber.github.io/docs/v2/guides/posts/
	 * @see https://timber.github.io/docs/v2/guides/class-maps/
	 * @return \Timber\PostQuery
	 */
	public function posts( $query = [], $post_type_or_class = null ) {
		if ( is_string($query) ) {
			Helper::doing_it_wrong(
				'Passing a query string to Term::posts()',
				'Pass a query array instead: e.g. `"posts_per_page=3"` should be replaced with `["posts_per_page" => 3]`',
				'2.0.0'
			);

			return false;
		}

		if ( is_int($query) ) {
			$query = [
				'posts_per_page' => $query,
				'post_type'      => 'any',
			];
		}

		if ( isset($post_type_or_class) ) {
			Helper::deprecated(
				'Passing post_type_or_class',
				'Pass post_type as part of the $query argument. For specifying class, use Class Maps: https://timber.github.io/docs/v2/guides/class-maps/',
				'2.0.0'
			);

			// Honor the non-deprecated posts_per_page param over the deprecated second arg.
			$query['post_type'] = $query['post_type'] ?? $post_type_or_class;
		}

		if ( func_num_args() > 2 ) {
			Helper::doing_it_wrong(
				'Passing a post class',
				'Use Class Maps instead: https://timber.github.io/docs/v2/guides/class-maps/',
				'2.0.0'
			);
		}

		$tax_query = [
			// Force a tax_query constraint on this term.
			'relation'   => 'AND',
			[
				'field'    => 'id',
				'terms'    => $this->ID,
				'taxonomy' => $this->taxonomy,
			],
		];

		// Merge a clause for this Term into any user-specified tax_query clauses.
		$query['tax_query'] = array_merge($query['tax_query'] ?? [], $tax_query);

		return Timber::get_posts( $query );
	}


	/**
	 * @api
	 * @return string
	 */
	public function title() {
		return $this->name;
	}

	/** DEPRECATED DOWN HERE
	 * ======================
	 **/

	/**
	 * Get Posts that have been "tagged" with the particular term
	 *
	 * @api
	 * @deprecated 2.0.0 use `{{ term.posts }}` instead
	 *
	 * @param int $numberposts
	 * @return array|bool|null
	 */
	public function get_posts( $numberposts = 10 ) {
		Helper::deprecated('{{ term.get_posts }}', '{{ term.posts }}', '2.0.0');
		return $this->posts($numberposts);
	}

	/**
	 * @api
	 * @deprecated 2.0.0, use `{{ term.children }}` instead.
	 *
	 * @return array
	 */
	public function get_children() {
		Helper::deprecated('{{ term.get_children }}', '{{ term.children }}', '2.0.0');

		return $this->children();
	}

	/**
	 * Updates term_meta of the current object with the given value.
	 *
	 * @deprecated 2.0.0 Use `update_term_meta()` instead.
	 *
	 * @param string $key   The key of the meta field to update.
	 * @param mixed  $value The new value.
	 */
	public function update( $key, $value ) {
		Helper::deprecated( 'Timber\Term::update()', 'update_term_meta()', '2.0.0' );

		/**
		 * Filters term meta value that is going to be updated.
		 *
		 * @deprecated 2.0.0 with no replacement
		 */
		$value = apply_filters_deprecated(
			'timber_term_set_meta',
			array( $value, $key, $this->ID, $this ),
			'2.0.0',
			false,
			'This filter will be removed in a future version of Timber. There is no replacement.'
		);

		/**
		 * Filters term meta value that is going to be updated.
		 *
		 * This filter is used by the ACF Integration.
		 *
		 * @deprecated 2.0.0, with no replacement
		 */
		$value = apply_filters_deprecated(
			'timber/term/meta/set',
			array( $value, $key, $this->ID, $this ),
			'2.0.0',
			false,
			'This filter will be removed in a future version of Timber. There is no replacement.'
		);

		$this->$key = $value;
	}

}
