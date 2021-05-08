<?php

namespace Timber;

/**
 * Class Archive
 *
 * The `Timber\Archives` class is used to generate a menu based on the date archives of your posts.
 *
 * The [Nieman Foundation News site](http://nieman.harvard.edu/news/) has an example of how the
 * output can be used in a real site ([screenshot](https://cloud.githubusercontent.com/assets/1298086/9610076/3cdca596-50a5-11e5-82fd-acb74c09c482.png)).
 *
 * @api
 * @example
 * ```php
 * $context['archives'] = new Timber\Archives( $args );
 * ```
 * ```twig
 * <ul>
 * {% for item in archives.items %}
 *     <li><a href="{{item.link}}">{{item.name}}</a></li>
 *     {% for child in item.children %}
 *         <li class="child"><a href="{{child.link}}">{{child.name}}</a></li>
 *     {% endfor %}
 * {% endfor %}
 * </ul>
 * ```
 * ```html
 * <ul>
 *     <li>2015</li>
 *     <li class="child">May</li>
 *     <li class="child">April</li>
 *     <li class="child">March</li>
 *     <li class="child">February</li>
 *     <li class="child">January</li>
 *     <li>2014</li>
 *     <li class="child">December</li>
 *     <li class="child">November</li>
 *     <li class="child">October</li>
 * </ul>
 * ```
 */
class Archives extends Core {

	/**
	 * @var array Preserves arguments sent with the constructor for possible later use when
	 * displaying items.
	 */
	protected $args;

	/**
	 * URL prefix.
	 *
	 * @api
	 * @var string
	 */
	public $base = '';

	/**
	 * @api
	 * @var array The items of the archives to iterate through and markup for your page.
	 */
	public $items;

	/**
	 * Build an Archives menu
	 *
	 * @api
	 * @param array  $args {
	 *      Optional. Array of arguments.
	 *
	 *      @type bool   $show_year => false
	 *      @type string
	 *      @type string $type => 'monthly-nested'
	 *      @type int    $limit => -1
	 *      @type bool   $show_post_count => false
	 *      @type string $order => 'DESC'
	 *      @type string $post_type => 'post'
	 *      @type bool   $show_year => false
	 *      @type bool   $nested => false
	 * }
	 * @param string $base Any additional paths that need to be prepended to the URLs that are
	 *                     generated, for example: "tags". Default ''.
	 */
	public function __construct( $args = null, $base = '' ) {
		$this->init($args, $base);
	}

	/**
	 * Initialize the Archives
	 *
	 * @internal
	 * @param array|string $args
	 * @param string       $base
	 */
	public function init( $args = null, $base = '' ) {
		$this->base = $base;
		$this->items = $this->items($args);
		$this->args = $args;
	}

	/**
	 * @internal
	 * @param string $url
	 * @param string $text
	 * @param int    $post_count
	 * @return mixed
	 */
	protected function get_archives_link( $url, $text, $post_count = 0 ) {
		$ret = array();
		$ret['text'] = $ret['title'] = $ret['name'] = wptexturize($text);
		$ret['url'] = $ret['link'] = esc_url(URLHelper::prepend_to_url($url, $this->base));
		if ($post_count) {
			$ret['post_count'] = (int) $post_count;
		}
		return $ret;
	}

	/**
	 * @internal
	 * @param array  $args
	 * @param string $last_changed
	 * @param string $join
	 * @param string $where
	 * @param string $order
	 * @param string $limit
	 * @return array
	 */
	protected function get_items_yearly( $args, $last_changed, $join, $where, $order, $limit ) {
		global $wpdb;
		$output = array();
		$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM {$wpdb->posts} $join $where GROUP BY YEAR(post_date) ORDER BY post_date $order $limit";
		$key = md5($query);
		$key = "wp_get_archives:$key:$last_changed";
		if ( !$results = wp_cache_get($key, 'posts') ) {
			$results = $wpdb->get_results($query);
			wp_cache_set($key, $results, 'posts');
		}
		if ( $results ) {
			foreach ( (array) $results as $result ) {
				$url = get_year_link($result->year);
				$text = sprintf('%d', $result->year);
				$output[] = $this->get_archives_link($url, $text, $result->posts);
			}
		}
		return $output;
	}

	/**
	 * @internal
	 * @param array|string $args
	 * @param string $last_changed
	 * @param string $join
	 * @param string $where
	 * @param string $order
	 * @param string $limit
	 * @param bool $nested
	 * @return array
	 */
	protected function get_items_monthly( $args, $last_changed, $join, $where, $order, $limit = '', $nested = true ) {
		global $wpdb, $wp_locale;
		$output = array();
		$defaults = array(
			'show_year' => false,
		);
		$r = wp_parse_args($args, $defaults);

		$show_year = $r['show_year'];
		//will need to specify which year we're looking for
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts "
			. "FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) "
			. "ORDER BY post_date $order $limit";
		$key = md5($query);
		$key = "wp_get_archives:$key:$last_changed";
		if ( !$results = wp_cache_get($key, 'posts') ) {
			$results = $wpdb->get_results($query);
			wp_cache_set($key, $results, 'posts');
		}
		if ( $results ) {
			foreach ( (array) $results as $result ) {
				$url = get_month_link($result->year, $result->month);
				if ( $show_year && !$nested ) {
					$text = sprintf(__('%1$s %2$d'), $wp_locale->get_month($result->month), $result->year);
				} else {
					$text = sprintf(__('%1$s'), $wp_locale->get_month($result->month));
				}
				if ( $nested ) {
					$output[$result->year][] = $this->get_archives_link($url, $text, $result->posts);
				} else {
					$output[] = $this->get_archives_link($url, $text, $result->posts);
				}
			}
		}
		if ( $nested ) {
			$out2 = array();
			foreach ( $output as $year => $months ) {
				$out2[] = array('name' => $year, 'children' => $months, 'post_count' => array_sum(array_column($months, 'post_count')));
			}
			return $out2;
		}
		return $output;
	}

	/**
	 * Gets archive items.
	 *
	 * @api
	 * @deprecated 2.0.0, use `{{ archives.items }}` instead.
	 * @see \Timber\Archives::items()
	 * @return array|string
	 */
	public function get_items( $args = null ) {
		Helper::warn( '{{ archives.get_items }} is deprecated. Use {{ archives.items }} instead.' );

		return $this->items($args);
	}

	/**
	 * @api
	 * @param array|string $args Optional. Array of arguments.
	 * @return array|string
	 */
	public function items( $args = null ) {
		global $wpdb;

		$defaults = array(
			'type' => 'monthly-nested',
			'limit' => '',
			'show_post_count' => false,
			'order' => 'DESC',
			'post_type' => 'post',
			'show_year' => false,
			'nested' => false
		);

		if ( $args === null ) {
			$args = $this->args;
		}

		$args = wp_parse_args($args, $defaults);
		$post_type = $args['post_type'];
		$order = $args['order'];
		$nested = $args['nested'];
		$type = $args['type'];
		$limit = '';
		if ( $type == 'yearlymonthly' || $type == 'yearmonth' ) {
			$type = 'monthly-nested';
		}
		if ( $type == 'monthly-nested' ) {
			$nested = true;
		}

		if ( !empty($args['limit']) ) {
			$limit = absint($args['limit']);
			$limit = ' LIMIT '.$limit;
		}
		$order = strtoupper($order);
		if ( $order !== 'ASC' ) {
			$order = 'DESC';
		}

		// this is what will separate dates on weekly archive links
		$archive_week_separator = '&#8211;';

		// over-ride general date format ? 0 = no: use the date format set in Options, 1 = yes: over-ride
		$archive_date_format_over_ride = 0;

		// options for daily archive (only if you over-ride the general date format)
		$archive_day_date_format = 'Y/m/d';

		// options for weekly archive (only if you over-ride the general date format)
		$archive_week_start_date_format = 'Y/m/d';
		$archive_week_end_date_format = 'Y/m/d';

		if ( !$archive_date_format_over_ride ) {
			$archive_day_date_format = get_option('date_format');
			$archive_week_start_date_format = get_option('date_format');
			$archive_week_end_date_format = get_option('date_format');
		}

		$where = $wpdb->prepare('WHERE post_type = "%s" AND post_status = "publish"', $post_type);

		/**
		 * @link https://developer.wordpress.org/reference/hooks/getarchives_where/
		 */
		$where = apply_filters('getarchives_where', $where, $args);

		/**
		 * @link https://developer.wordpress.org/reference/hooks/getarchives_join/
		 */
		$join = apply_filters('getarchives_join', '', $args);

		$output = array();
		$last_changed = wp_cache_get('last_changed', 'posts');
		if ( !$last_changed ) {
			$last_changed = microtime();
			wp_cache_set('last_changed', $last_changed, 'posts');
		}
		if ( 'monthly' == $type ) {
			$output = $this->get_items_monthly($args, $last_changed, $join, $where, $order, $limit, $nested);
		} elseif ( 'yearly' == $type ) {
			$output = $this->get_items_yearly($args, $last_changed, $join, $where, $order, $limit);
		} elseif ( 'monthly-nested' == $type ) {
			$output = $this->get_items_monthly($args, $last_changed, $join, $where, $order, $limit);
		} elseif ( 'daily' == $type ) {
			$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAYOFMONTH(post_date) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) ORDER BY post_date $order $limit";
			$key = md5($query);
			$key = "wp_get_archives:$key:$last_changed";
			if ( !$results = wp_cache_get($key, 'posts') ) {
				$results = $wpdb->get_results($query);
				$cache = array();
				$cache[$key] = $results;
				wp_cache_set($key, $results, 'posts');
			}
			if ( $results ) {
				foreach ( (array) $results as $result ) {
					$url = get_day_link($result->year, $result->month, $result->dayofmonth);
					$date = sprintf('%1$d-%2$02d-%3$02d 00:00:00', $result->year, $result->month, $result->dayofmonth);
					$text = mysql2date($archive_day_date_format, $date);
					$output[] = $this->get_archives_link($url, $text, $result->posts);
				}
			}
		} elseif ( 'weekly' == $type ) {
			$week = _wp_mysql_week('`post_date`');
			$query = "SELECT DISTINCT $week AS `week`, YEAR( `post_date` ) AS `yr`, DATE_FORMAT( `post_date`, '%Y-%m-%d' ) AS `yyyymmdd`, "
				. "count( `ID` ) AS `posts` FROM `$wpdb->posts` $join $where GROUP BY $week, YEAR( `post_date` ) ORDER BY `post_date` $order $limit";
			$key = md5($query);
			$key = "wp_get_archives:$key:$last_changed";
			if ( !$results = wp_cache_get($key, 'posts') ) {
				$results = $wpdb->get_results($query);
				wp_cache_set($key, $results, 'posts');
			}
			$arc_w_last = '';
			if ( $results ) {
				foreach ( (array) $results as $result ) {
					if ( $result->week != $arc_w_last ) {
						$arc_year = $result->yr;
						$arc_w_last = $result->week;
						$arc_week = get_weekstartend($result->yyyymmdd, get_option('start_of_week'));
						$arc_week_start = date_i18n($archive_week_start_date_format, $arc_week['start']);
						$arc_week_end = date_i18n($archive_week_end_date_format, $arc_week['end']);
						$url = sprintf('%1$s/%2$s%3$sm%4$s%5$s%6$sw%7$s%8$d', home_url(), '', '?', '=', $arc_year, '&amp;', '=', $result->week);
						$text = $arc_week_start.$archive_week_separator.$arc_week_end;
						$output[] = $this->get_archives_link($url, $text, $result->posts);
					}
				}
			}
		} elseif ( 'postbypost' == $type || 'alpha' == $type ) {
			$orderby = 'alpha' == $type ? 'post_title ASC ' : 'post_date DESC ';
			$query = "SELECT * FROM $wpdb->posts $join $where ORDER BY $orderby $limit";
			$key = md5($query);
			$key = "wp_get_archives:$key:$last_changed";
			if ( !$results = wp_cache_get($key, 'posts') ) {
				$results = $wpdb->get_results($query);
				wp_cache_set($key, $results, 'posts');
			}
			if ( $results ) {
				foreach ( (array) $results as $result ) {
					if ( $result->post_date != '0000-00-00 00:00:00' ) {
						$url = get_permalink($result);
						$text = $result->ID;
						if ( $result->post_title ) {
							/** This filter is documented in wp-includes/post-template.php */
							$text = strip_tags(apply_filters('the_title', $result->post_title, $result->ID));
						}
						$output[] = $this->get_archives_link($url, $text);
					}
				}
			}
		}
		return $output;
	}

}
