<?php

namespace Timber;

use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

use Timber\Factory\PostFactory;
use Timber\Factory\TermFactory;

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

        add_action( 'timber/twig/filters', array( $self, 'add_timber_filters' ) );
		add_action( 'timber/twig/functions', array( $self, 'add_timber_functions' ) );
		add_action( 'timber/twig/escapers', array( $self, 'add_timber_escapers' ) );

        add_filter( 'timber/loader/twig', [ $self, 'set_defaults' ] );
  }

	/**
	 * Adds Timber-specific functions to Twig.
	 *
	 * @param \Twig\Environment $twig The Twig Environment.
	 *
	 * @return \Twig\Environment
	 */
	public function add_timber_functions( $twig ) {
		$twig->addFunction( new TwigFunction( 'action', function( $action_name, ...$args ) {
			do_action_ref_array( $action_name, $args );
		} ) );

		$twig->addFunction(new TwigFunction('function', array(&$this, 'exec_function')));
		$twig->addFunction(new TwigFunction('fn', array(&$this, 'exec_function')));

		$twig->addFunction(new TwigFunction('shortcode', 'do_shortcode'));

		/**
		 * Timber object functions.
		 */

		// Posts
		$twig->addFunction( new TwigFunction( 'get_post', [ Timber::class, 'get_post' ] ) );
		$twig->addFunction( new TwigFunction( 'get_image', [ Timber::class, 'get_image' ] ) );
		$twig->addFunction( new TwigFunction( 'get_attachment', [ Timber::class, 'get_attachment' ] ) );
		$twig->addFunction( new TwigFunction( 'get_posts', [ Timber::class, 'get_posts' ] ) );
		$twig->addFunction( new TwigFunction( 'get_attachment_by', [ Timber::class, 'get_attachment_by' ] ) );

		// Terms
		$twig->addFunction( new TwigFunction( 'get_term', [ Timber::class, 'get_term' ] ) );
		$twig->addFunction( new TwigFunction( 'get_terms', [ Timber::class, 'get_terms' ] ) );

		// Users
		$twig->addFunction( new TwigFunction( 'get_user', [ Timber::class, 'get_user' ] ) );
		$twig->addFunction( new TwigFunction( 'get_users', [ Timber::class, 'get_users' ] ) );

		// Comments
		$twig->addFunction( new TwigFunction( 'get_comment', [ Timber::class, 'get_comment' ] ) );
		$twig->addFunction( new TwigFunction( 'get_comments', [ Timber::class, 'get_comments' ] ) );

		/**
		 * Deprecated Timber object functions.
		 */

		$postFactory = new PostFactory();

		$twig->addFunction(new TwigFunction('Post', function( $post_id ) use ($postFactory) {
			Helper::deprecated( '{{ Post() }}', '{{ get_post() }} or {{ get_posts() }}', '2.0.0' );
			return $postFactory->from( $post_id );
		} ) );
		$twig->addFunction( new TwigFunction(
			'TimberPost',
			function( $post_id ) use ($postFactory) {
				Helper::deprecated( '{{ TimberPost() }}', '{{ get_post() }} or {{ get_posts() }}', '2.0.0' );
				return $postFactory->from( $post_id );
			}
		) );

		$twig->addFunction(new TwigFunction('Image', function( $post_id ) use ($postFactory) {
			Helper::deprecated( '{{ Image() }}', '{{ get_post() }} or {{ get_attachment_by() }}', '2.0.0' );
			return $postFactory->from( $post_id );
		} ) );
		$twig->addFunction( new TwigFunction(
			'TimberImage',
			function( $post_id = false ) use ($postFactory) {
				Helper::deprecated( '{{ TimberImage() }}', '{{ get_post() }} or {{ get_posts() }}', '2.0.0' );
				return $postFactory->from( $post_id );
			}
		) );

		$termFactory = new TermFactory();

		$twig->addFunction( new TwigFunction(
			'Term',
			function( $term_id ) use ( $termFactory ) {
				Helper::deprecated( '{{ Term() }}', '{{ get_term() }} or {{ get_terms() }}', '2.0.0' );
				return $termFactory->from( $term_id );
			}
		) );
		$twig->addFunction( new TwigFunction(
			'TimberTerm',
			function( $term_id ) use ( $termFactory ) {
				Helper::deprecated( '{{ TimberTerm() }}', '{{ get_term() }} or {{ get_terms() }}', '2.0.0' );
				return $termFactory->from( $term_id );
			}
		) );

		$twig->addFunction(new TwigFunction('User', function( $post_id ) {
			Helper::deprecated( '{{ User() }}', '{{ get_user() }} or {{ get_users() }}', '2.0.0' );
			return Timber::get_user( $post_id );
		} ) );
		$twig->addFunction( new TwigFunction(
			'TimberUser',
			function( $user_id ) {
				Helper::deprecated( '{{ TimberUser() }}', '{{ User() }}', '2.0.0' );
				return Timber::get_user( $user_id );
			}
		) );

		/* bloginfo and translate */
		$twig->addFunction(new TwigFunction('bloginfo', 'bloginfo'));
		$twig->addFunction(new TwigFunction('__', '__'));
		$twig->addFunction(new TwigFunction('translate', 'translate'));
		$twig->addFunction(new TwigFunction('_e', '_e'));
		$twig->addFunction(new TwigFunction('_n', '_n'));
		$twig->addFunction(new TwigFunction('_x', '_x'));
		$twig->addFunction(new TwigFunction('_ex', '_ex'));
		$twig->addFunction(new TwigFunction('_nx', '_nx'));
		$twig->addFunction(new TwigFunction('_n_noop', '_n_noop'));
		$twig->addFunction(new TwigFunction('_nx_noop', '_nx_noop'));
		$twig->addFunction(new TwigFunction('translate_nooped_plural', 'translate_nooped_plural'));

		return $twig;
	}

	/**
	 * Adds filters to Twig.
	 *
	 * @param \Twig\Environment $twig The Twig Environment.
	 * @return \Twig\Environment
	 */
	public function add_timber_filters( $twig ) {
		/* image filters */
		$twig->addFilter(new TwigFilter('resize', array('Timber\ImageHelper', 'resize')));
		$twig->addFilter(new TwigFilter('retina', array('Timber\ImageHelper', 'retina_resize')));
		$twig->addFilter(new TwigFilter('letterbox', array('Timber\ImageHelper', 'letterbox')));
		$twig->addFilter(new TwigFilter('tojpg', array('Timber\ImageHelper', 'img_to_jpg')));
		$twig->addFilter(new TwigFilter('towebp', array('Timber\ImageHelper', 'img_to_webp')));

		/* debugging filters */
		$twig->addFilter(new TwigFilter('get_class', function( $obj ) {
			Helper::deprecated( '{{ my_object | get_class }}', "{{ function('get_class', my_object) }}", '2.0.0' );
			return get_class( $obj );
		} ));
		$twig->addFilter(new TwigFilter('print_r', function( $arr ) {
			Helper::deprecated( '{{ my_object | print_r }}', '{{ dump(my_object) }}', '2.0.0' );
			return print_r($arr, true);
		} ));

		/* other filters */
		$twig->addFilter(new TwigFilter('stripshortcodes', 'strip_shortcodes'));
		$twig->addFilter(new TwigFilter('array', array($this, 'to_array')));
		$twig->addFilter(new TwigFilter('excerpt', 'wp_trim_words'));
		$twig->addFilter(new TwigFilter('excerpt_chars', array('Timber\TextHelper', 'trim_characters')));
		$twig->addFilter(new TwigFilter('function', array($this, 'exec_function')));
		$twig->addFilter(new TwigFilter('pretags', array($this, 'twig_pretags')));
		$twig->addFilter(new TwigFilter('sanitize', 'sanitize_title'));
		$twig->addFilter(new TwigFilter('shortcodes', 'do_shortcode'));
		$twig->addFilter(new TwigFilter('wpautop', 'wpautop'));
		$twig->addFilter(new TwigFilter('list', array($this, 'add_list_separators')));

		$twig->addFilter(new TwigFilter('pluck', array('Timber\Helper', 'pluck')));

		$twig->addFilter(new TwigFilter('wp_list_filter', array('Timber\Helper', 'wp_list_filter')));

		$twig->addFilter(new TwigFilter('relative', function( $link ) {
					return URLHelper::get_rel_url($link, true);
				} ));

		/**
		 * Date and Time filters.
		 *
		 * @todo copy this formatting to other functions
		 */
		$twig->addFilter(new TwigFilter(
			'date',
			[ $this, 'twig_date_format_filter' ],
			[ 'needs_environment' => true ]
		) );
		$twig->addFilter(new TwigFilter('time_ago', array('Timber\DateTimeHelper', 'time_ago')));

		$twig->addFilter(new TwigFilter('truncate', function( $text, $len ) {
					return TextHelper::trim_words($text, $len);
				} ));

		/* actions and filters */
		$twig->addFilter(new TwigFilter('apply_filters', function() {
					$args = func_get_args();
					$tag = current(array_splice($args, 1, 1));

					return apply_filters_ref_array($tag, $args);
				} ));

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
