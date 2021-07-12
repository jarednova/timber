<?php

namespace Timber;

use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Error\Error;

use Timber\Factory\PostFactory;

/**
 * Class Twig
 */
class Twig {
	public static $dir_name;

	/**
	 * @codeCoverageIgnore
	 */
	public static function init() {
		$self = new self();

		add_filter( 'timber/twig', [ $self, 'add_timber_functions' ] );
		add_filter( 'timber/twig', [ $self, 'add_timber_filters' ] );
		add_filter( 'timber/twig', [ $self, 'add_timber_escapers' ] );

        add_filter( 'timber/loader/twig', [ $self, 'set_defaults' ] );
	}

	/**
	 * Get Timber default functions
	 *
	 * @return array Default Timber functions
	 */
	public function get_timber_functions() {
		$post_factory = new PostFactory();

		$functions = [
			'action' => [
				'callable' => function( $action_name, ...$args ) {
					do_action_ref_array( $action_name, $args );
				}
			],
			'function' => [
				'callable' => [$this, 'exec_function'],
			],
			'fn' => [
				'callable' => [$this, 'exec_function'],
			],
			'get_post' => [
				'callable' => [Timber::class, 'get_post'],
			],
			'get_image' => [
				'callable' => [Timber::class, 'get_image'],
			],
			'get_attachment' => [
				'callable' => [Timber::class, 'get_attachment'],
			],
			'get_posts' => [
				'callable' => [Timber::class, 'get_posts'],
			],
			'get_attachment_by' => [
				'callable' => [Timber::class, 'get_attachment_by'],
			],
			'get_term' => [
				'callable' => [Timber::class, 'get_term'],
			],
			'get_terms' => [
				'callable' => [Timber::class, 'get_terms'],
			],
			'get_user' => [
				'callable' => [Timber::class, 'get_user'],
			],
			'get_users' => [
				'callable' => [Timber::class, 'get_users'],
			],
			'get_comment' => [
				'callable' => [Timber::class, 'get_comment'],
			],
			'get_comments' => [
				'callable' => [Timber::class, 'get_comments'],
			],
			'Post' => [
				'callable' => function ($post_id) use ($post_factory) {
					Helper::deprecated('{{ Post() }}', '{{ get_post() }} or {{ get_posts() }}', '2.0.0');
					return $post_factory->from($post_id);
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'TimberPost' => [
				'callable' => function ($post_id) use ($post_factory) {
					Helper::deprecated('{{ TimberPost() }}', '{{ get_post() }} or {{ get_posts() }}', '2.0.0');
					return $post_factory->from($post_id);
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'Image' => [
				'callable' => function ($post_id) use ($post_factory) {
					Helper::deprecated('{{ Image() }}', '{{ get_post() }} or {{ get_attachment_by() }}', '2.0.0');
					return $post_factory->from($post_id);
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'TimberImage' => [
				'callable' => function ($post_id) use ($post_factory) {
					Helper::deprecated('{{ TimberImage() }}', '{{ get_post() }} or {{ get_posts() }}', '2.0.0');
					return $post_factory->from($post_id);
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'Term' => [
				'callable' => function ($term_id) {
					Helper::deprecated('{{ Term() }}', '{{ get_term() }} or {{ get_terms() }}', '2.0.0');
					return Timber::get_term( $term_id );
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'TimberTerm' => [
				'callable' => function ($term_id) {
					Helper::deprecated('{{ TimberTerm() }}', '{{ get_term() }} or {{ get_terms() }}', '2.0.0');
					return Timber::get_term( $term_id );
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'User' => [
				'callable' => function ($user_id) {
					Helper::deprecated('{{ User() }}', '{{ get_user() }} or {{ get_users() }}', '2.0.0');
					return Timber::get_user( $user_id );
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'TimberUser' => [
				'callable' => function ($user_id) {
					Helper::deprecated('{{ TimberUser() }}', '{{ get_user() }} or {{ get_users() }}', '2.0.0');
					return Timber::get_user( $user_id );
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'shortcode' => [
				'callable' => 'do_shortcode',
			],
			'bloginfo' => [
				'callable' => 'bloginfo',
			],

			// Translation functions.
			'__' => [
				'callable' => '__',
			],
			'translate' => [
				'callable' => 'translate',
			],
			'_e' => [
				'callable' => '_e',
			],
			'_n' => [
				'callable' => '_n',
			],
			'_x' => [
				'callable' => '_x',
			],
			'_ex' => [
				'callable' => '_ex',
			],
			'_nx' => [
				'callable' => '_nx',
			],
			'_n_noop' => [
				'callable' => '_n_noop',
			],
			'_nx_noop' => [
				'callable' => '_nx_noop',
			],
			'translate_nooped_plural' => [
				'callable' => 'translate_nooped_plural',
			],
		];

		/**
		 * Filters the functions that are added to Twig.
		 *
		 * The `$functions` array is an associative array with the filter name as a key and an
		 * arguments array as the value. In the arguments array, you pass the function to call with
		 * a `callable` entry.
		 *
		 * This is an alternative filter that you can use instead of adding your function in the
		 * `timber/twig` filter.
		 *
		 * @api
		 * @since 2.0.0
		 * @example
		 * ```php
		 * add_filter( 'timber/twig/functions', function( $functions ) {
		 *     // Add your own function.
		 *     $functions['url_to_domain'] = [
		 *         'callable' => 'url_to_domain',
	     *     ];
		 *
		 *     // Replace a function.
		 *     $functions['get_image'] = [
		 *         'callable' => 'custom_image_get',
		 *     ];
		 *
		 *     // Remove a function.
		 *     unset( $functions['bloginfo'] );
		 *
		 *     return $functions;
		 * } );
		 * ```
		 *
		 * @param array $functions
		 */
		$functions = apply_filters( 'timber/twig/functions', $functions );

		return $functions;
	}

	/**
	 * Adds Timber-specific functions to Twig.
	 *
	 * @param \Twig\Environment $twig The Twig Environment.
	 *
	 * @return \Twig\Environment
	 */
	public function add_timber_functions( $twig ) {
		foreach ( $this->get_timber_functions() as $name => $function ) {
			$twig->addFunction(
				new TwigFunction(
					$name,
					$function['callable'],
					$function['options'] ?? []
				)
			);
		}

		return $twig;
	}

	/**
	 * Get Timber default filters
	 *
	 * @return array Default Timber filters
	 */
	public function get_timber_filters() {
		$filters = [
			/* image filters */
			'resize' => [
				'callable' => ['Timber\ImageHelper', 'resize'],
			],
			'retina' => [
				'callable' => ['Timber\ImageHelper', 'retina_resize'],
			],
			'letterbox' => [
				'callable' => ['Timber\ImageHelper', 'letterbox'],
			],
			'tojpg' => [
				'callable' => ['Timber\ImageHelper', 'img_to_jpg'],
			],
			'towebp' => [
				'callable' => ['Timber\ImageHelper', 'img_to_webp'],
			],

			// Debugging filters.
			'get_class' => [
				'callable' => function( $obj ) {
					Helper::deprecated( '{{ my_object | get_class }}', "{{ function('get_class', my_object) }}", '2.0.0' );
					return get_class( $obj );
				},
				'options' => [
					'deprecated' => true,
				],
			],
			'print_r' => [
				'callable' => function( $arr ) {
					Helper::deprecated( '{{ my_object | print_r }}', '{{ dump(my_object) }}', '2.0.0' );
					return print_r($arr, true);
				},
				'options' => [
					'deprecated' => true,
				],
			],

			// Other filters.
			'stripshortcodes' => [
				'callable' => 'strip_shortcodes',
			],
			'array' => [
				'callable' => [$this, 'to_array'],
			],
			'excerpt' => [
				'callable' => 'wp_trim_words',
			],
			'excerpt_chars' => [
				'callable' => ['Timber\TextHelper', 'trim_characters'],
			],
			'function' => [
				'callable' => [$this, 'exec_function'],
			],
			'pretags' => [
				'callable' => [$this, 'twig_pretags'],
			],
			'sanitize' => [
				'callable' => 'sanitize_title',
			],
			'shortcodes' => [
				'callable' => 'do_shortcode',
			],
			'wpautop' => [
				'callable' => 'wpautop',
			],
			'list' => [
				'callable' => [$this, 'add_list_separators'],
			],
			'pluck' => [
				'callable' => ['Timber\Helper', 'pluck'],
			],
			'wp_list_filter' => [
				'callable' => ['Timber\Helper', 'wp_list_filter'],
			],

			'relative' => [
				'callable' => function( $link ) {
					return URLHelper::get_rel_url($link, true);
				},
			],

			/**
			 * Date and Time filters.
			 *
			 * @todo copy this formatting to other functions
			 */
			'date' => [
				'callable' => [ $this, 'twig_date_format_filter' ],
				'options' => [ 'needs_environment' => true ],
			],
			'time_ago' => [
				'callable' => ['Timber\DateTimeHelper', 'time_ago'],
			],
			'truncate' => [
				'callable' => function( $text, $len ) {
					return TextHelper::trim_words($text, $len);
				},
			],

			// Actions and filters.
			'apply_filters' => [
				'callable' => function() {
					$args = func_get_args();
					$tag = current(array_splice($args, 1, 1));

					return apply_filters_ref_array($tag, $args);
				},
			],
		];

		/**
		 * Filters the filters that are added to Twig.
		 *
		 * The `$filters` array is an associative array with the filter name as a key and an
		 * arguments array as the value. In the arguments array, you pass the function to call with
		 * a `callable` entry.
		 *
		 * This is an alternative filter that you can use instead of adding your filter in the
		 * `timber/twig` filter.
		 *
		 * @api
		 * @since 2.0.0
		 * @example
		 * ```php
		 * add_filter( 'timber/twig/default_filters', function( $filters ) {
		 *     // Add your own filter.
		 *     $filters['price'] = [
		 *         'callable' => 'format_price',
	     *     ];
		 *
		 *     // Replace a filter.
		 *     $filters['list'] = [
		 *         'callable' => 'custom_list_filter',
		 *     ];
		 *
		 *     // Remove a filter.
		 *     unset( $filters['list'] );
		 *
		 *     return $filters;
		 * } );
		 * ```
		 *
		 * @param array $filters
		 */
		$filters = apply_filters( 'timber/twig/filters', $filters );

		return $filters;
	}

	/**
	 * Adds filters to Twig.
	 *
	 * @param \Twig\Environment $twig The Twig Environment.
	 *
	 * @return \Twig\Environment
	 */
	public function add_timber_filters( $twig ) {
		foreach ( $this->get_timber_filters() as $name => $function ) {
			$twig->addFilter(
				new TwigFilter(
					$name,
					$function['callable'],
					$function['options'] ?? []
				)
			);
		}

		return $twig;
	}

	/**
	 * Adds escapers.
	 *
	 * @param \Twig\Environment $twig The Twig Environment.
	 * @return \Twig\Environment
	 */
	public function add_timber_escapers( $twig ) {
		$esc_url = function( \Twig\Environment $env, $string ) {
			return esc_url( $string );
		};

		$wp_kses_post = function( \Twig\Environment $env, $string ) {
			return wp_kses_post( $string );
		};

		$esc_html = function( \Twig\Environment $env, $string ) {
			return esc_html( $string );
		};

		$esc_js = function( \Twig\Environment $env, $string ) {
			return esc_js( $string );
		};

		if ( class_exists( 'Twig\Extension\EscaperExtension' ) ) {
			$escaper_extension = $twig->getExtension('Twig\Extension\EscaperExtension');
			$escaper_extension->setEscaper('esc_url', $esc_url);
			$escaper_extension->setEscaper('wp_kses_post', $wp_kses_post);
			$escaper_extension->setEscaper('esc_html', $esc_html);
			$escaper_extension->setEscaper('esc_js', $esc_js);
		}
		return $twig;
	}

	/**
	 * Overwrite Twig defaults.
	 *
	 * Makes Twig compatible with how WordPress handles dates, timezones, numbers and perhaps other items in
	 * the future
	 *
	 * @since 2.0.0
	 *
	 * @throws \Twig_Error_Runtime
	 * @param \Twig\Environment $twig Twig Environment.
	 *
	 * @return \Twig\Environment
	 */
	public function set_defaults( Environment $twig ) {
		$twig->getExtension( CoreExtension::class )->setDateFormat( get_option( 'date_format' ), '%d days' );
		$twig->getExtension( CoreExtension::class )->setTimezone( wp_timezone_string() );

		/** @see https://developer.wordpress.org/reference/functions/number_format_i18n/ */
		global $wp_locale;
		if ( isset( $wp_locale ) ) {
			$twig->getExtension( CoreExtension::class )->setNumberFormat( 0, $wp_locale->number_format['decimal_point'], $wp_locale->number_format['thousands_sep'] );
		}

		return $twig;
	}

	/**
	 * Converts a date to the given format.
	 *
	 * @internal
	 * @since 2.0.0
	 * @see  twig_date_format_filter()
	 * @link https://twig.symfony.com/doc/2.x/filters/date.html
	 *
	 * @throws \Exception
	 *
	 * @param \Twig\Environment         $env      Twig Environment.
	 * @param null|string|int|\DateTime $date     A date.
	 * @param null|string               $format   Optional. PHP date format. Will return the
	 *                                            current date as a DateTimeImmutable object by
	 *                                            default.
	 * @param null                      $timezone Optional. The target timezone. Use `null` to use
	 *                                            the default or
	 *                                            `false` to leave the timezone unchanged.
	 *
	 * @return false|string A formatted date.
	 */
	public function twig_date_format_filter( Environment $env, $date = null, $format = null, $timezone = null ) {
		// Support for DateInterval.
		if ( $date instanceof \DateInterval ) {
			if ( null === $format ) {
				$format = $env->getExtension( CoreExtension::class )->getDateFormat()[1];
			}

			return $date->format( $format );
		}

		if ( null === $date || 'now' === $date ) {
			return DateTimeHelper::wp_date( $format, null );
		}

		/**
		 * If a string is given and it’s not a timestamp (e.g. "2010-01-28T15:00:00+04:00", try creating a DateTime
		 * object and read the timezone from that string.
		 */
		if ( is_string( $date ) && ! ctype_digit( $date ) ) {
			$date_obj = date_create( $date );

			if ( $date_obj ) {
				$date = $date_obj;
			}
		}

		/**
		 * Check for `false` parameter in |date filter in Twig
		 *
		 * @link https://twig.symfony.com/doc/2.x/filters/date.html#timezone
		 */
		if ( false === $timezone && $date instanceof \DateTimeInterface ) {
			$timezone = $date->getTimezone();
		}

		return DateTimeHelper::wp_date( $format, $date, $timezone );
	}

	/**
	 *
	 *
	 * @param mixed   $arr
	 * @return array
	 */
	public function to_array( $arr ) {
		if ( is_array($arr) ) {
			return $arr;
		}
		$arr = array($arr);
		return $arr;
	}

	/**
	 *
	 *
	 * @param string  $function_name
	 * @return mixed
	 */
	public function exec_function( $function_name ) {
		$args = func_get_args();
		array_shift($args);
		if ( is_string($function_name) ) {
			$function_name = trim($function_name);
		}
		return call_user_func_array($function_name, ($args));
	}

	/**
	 *
	 *
	 * @param string  $content
	 * @return string
	 */
	public function twig_pretags( $content ) {
		return preg_replace_callback('|<pre.*>(.*)</pre|isU', array(&$this, 'convert_pre_entities'), $content);
	}

	/**
	 *
	 *
	 * @param array   $matches
	 * @return string
	 */
	public function convert_pre_entities( $matches ) {
		return str_replace($matches[1], htmlentities($matches[1]), $matches[0]);
	}

	/**
	 * Formats a date.
	 *
	 * @deprecated 2.0.0
	 *
	 * @param null|string|false    $format Optional. PHP date format. Will use the `date_format`
	 *                                     option as a default.
	 * @param string|int|\DateTime $date   A date.
	 *
	 * @return string
	 */
	public function intl_date( $date, $format = null ) {
		Helper::deprecated( 'intl_date', 'DateTimeHelper::wp_date', '2.0.0' );

		return DateTimeHelper::wp_date( $format, $date );
	}

	/**
	 *
	 * @deprecated 2.0.0
	 *
	 * Returns the difference between two times in a human readable format.
	 *
	 * Differentiates between past and future dates.
	 *
	 * @see \human_time_diff()
	 *
	 * @param int|string $from          Base date as a timestamp or a date string.
	 * @param int|string $to            Optional. Date to calculate difference to as a timestamp or
	 *                                  a date string. Default to current time.
	 * @param string     $format_past   Optional. String to use for past dates. To be used with
	 *                                  `sprintf()`. Default `%s ago`.
	 * @param string     $format_future Optional. String to use for future dates. To be used with
	 *                                  `sprintf()`. Default `%s from now`.
	 *
	 * @return string
	 */
	public static function time_ago( $from, $to = null, $format_past = null, $format_future = null ) {
		Helper::deprecated( 'time_ago', 'DateTimeHelper::time_ago', '2.0.0' );

		return DateTimeHelper::time_ago( $from, $to, $format_past, $format_future );
	}

	/**
	 * @param array $arr
	 * @param string $first_delimiter
	 * @param string $second_delimiter
	 * @return string
	 */
	public function add_list_separators( $arr, $first_delimiter = ',', $second_delimiter = ' and' ) {
		$length = count($arr);
		$list = '';
		foreach ( $arr as $index => $item ) {
			if ( $index < $length - 2 ) {
				$delimiter = $first_delimiter.' ';
			} elseif ( $index == $length - 2 ) {
				$delimiter = $second_delimiter.' ';
			} else {
				$delimiter = '';
			}
			$list = $list.$item.$delimiter;
		}
		return $list;
	}

}
