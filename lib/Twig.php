<?php

namespace Timber;

use Timber\URLHelper;
use Timber\Helper;

use Timber\Post;
use Timber\Term;
use Timber\Image;
use Timber\User;

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
	}

	/**
	 * Adds functions to Twig.
	 *
	 * @param \Twig\Environment $twig The Twig Environment.
	 * @return \Twig\Environment
	 */
	public function add_timber_functions( $twig ) {
		/* actions and filters */
		$twig->addFunction(new Twig_Function('action', function( $context ) {
					$args = func_get_args();
					array_shift($args);
					$args[] = $context;
					call_user_func_array('do_action', $args);
				}, array('needs_context' => true)));

		$twig->addFunction(new Twig_Function('function', array(&$this, 'exec_function')));
		$twig->addFunction(new Twig_Function('fn', array(&$this, 'exec_function')));

		$twig->addFunction(new Twig_Function('shortcode', 'do_shortcode'));

		/* TimberObjects */
		$twig->addFunction(new Twig_Function('TimberPost', function( $pid, $PostClass = 'Timber\Post' ) {
					if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
						foreach ( $pid as &$p ) {
							$p = new $PostClass($p);
						}
						return $pid;
					}
					return new $PostClass($pid);
				} ));
		$twig->addFunction(new Twig_Function('TimberImage', function( $pid = false, $ImageClass = 'Timber\Image' ) {
					if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
						foreach ( $pid as &$p ) {
							$p = new $ImageClass($p);
						}
						return $pid;
					}
					return new $ImageClass($pid);
				} ));

		$twig->addFunction(new Twig_Function('TimberTerm', array($this, 'handle_term_object')));

		$twig->addFunction(new Twig_Function('TimberUser', function( $pid, $UserClass = 'Timber\User' ) {
					if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
						foreach ( $pid as &$p ) {
							$p = new $UserClass($p);
						}
						return $pid;
					}
					return new $UserClass($pid);
				} ));

		/* TimberObjects Alias */
		$twig->addFunction(new Twig_Function('Post', function( $pid, $PostClass = 'Timber\Post' ) {
					if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
						foreach ( $pid as &$p ) {
							$p = new $PostClass($p);
						}
						return $pid;
					}
					return new $PostClass($pid);
				} ));
		$twig->addFunction(new Twig_Function('Image', function( $pid, $ImageClass = 'Timber\Image' ) {
					if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
						foreach ( $pid as &$p ) {
							$p = new $ImageClass($p);
						}
						return $pid;
					}
					return new $ImageClass($pid);
				} ));
		$twig->addFunction(new Twig_Function('Term', array($this, 'handle_term_object')));
		$twig->addFunction(new Twig_Function('User', function( $pid, $UserClass = 'Timber\User' ) {
					if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
						foreach ( $pid as &$p ) {
							$p = new $UserClass($p);
						}
						return $pid;
					}
					return new $UserClass($pid);
				} ));

		/* bloginfo and translate */
		$twig->addFunction(new Twig_Function('bloginfo', 'bloginfo'));
		$twig->addFunction(new Twig_Function('__', '__'));
		$twig->addFunction(new Twig_Function('translate', 'translate'));
		$twig->addFunction(new Twig_Function('_e', '_e'));
		$twig->addFunction(new Twig_Function('_n', '_n'));
		$twig->addFunction(new Twig_Function('_x', '_x'));
		$twig->addFunction(new Twig_Function('_ex', '_ex'));
		$twig->addFunction(new Twig_Function('_nx', '_nx'));
		$twig->addFunction(new Twig_Function('_n_noop', '_n_noop'));
		$twig->addFunction(new Twig_Function('_nx_noop', '_nx_noop'));
		$twig->addFunction(new Twig_Function('translate_nooped_plural', 'translate_nooped_plural'));

		return $twig;
	}

	/**
	 * Function for Term or TimberTerm() within Twig
	 * @since 1.5.1
	 * @author @jarednova
	 * @param integer $tid the term ID to search for
	 * @param string $taxonomy the taxonomy to search inside of. If sent a class name, it will use that class to support backwards compatibility
	 * @param string $TermClass the class to use for processing the term
	 * @return Term|array
	 */
	function handle_term_object( $tid, $taxonomy = '', $TermClass = 'Timber\Term' ) {
		if ( $taxonomy != $TermClass ) {
			// user has sent any additonal parameters, process
			$processed_args = self::process_term_args($taxonomy, $TermClass);
			$taxonomy = $processed_args['taxonomy'];
			$TermClass = $processed_args['TermClass'];
		}
		if ( is_array($tid) && !Helper::is_array_assoc($tid) ) {
			foreach ( $tid as &$p ) {
				$p = new $TermClass($p, $taxonomy);
			}
			return $tid;
		}
		return new $TermClass($tid, $taxonomy);
	}

	/**
	 * Process the arguments for handle_term_object to determine what arguments the user is sending
	 * @since 1.5.1
	 * @author @jarednova
	 * @param string $maybe_taxonomy probably a taxonomy, but it could be a Timber\Term subclass
	 * @param string $TermClass a string for the Timber\Term subclass
	 * @return array of processed arguments
	 */
	protected static function process_term_args( $maybe_taxonomy, $TermClass ) {
		// A user could be sending a TermClass in the first arg, let's test for that ...
		if ( class_exists($maybe_taxonomy) ) {
			$tc = new $maybe_taxonomy;
			if ( is_subclass_of($tc, 'Timber\Term') ) {
				return array('taxonomy' => '', 'TermClass' => $maybe_taxonomy);
			}
		}
		return array('taxonomy' => $maybe_taxonomy, 'TermClass' => $TermClass);
	}

	/**
	 * Adds filters to Twig.
	 *
	 * @param \Twig\Environment $twig The Twig Environment.
	 * @return \Twig\Environment
	 */
	public function add_timber_filters( $twig ) {
		$twig->addFilter(new Twig_Filter('resize', array('Timber\ImageHelper', 'resize')));
		$twig->addFilter(new Twig_Filter('retina', array('Timber\ImageHelper', 'retina_resize')));
		$twig->addFilter(new Twig_Filter('letterbox', array('Timber\ImageHelper', 'letterbox')));
		$twig->addFilter(new Twig_Filter('tojpg', array('Timber\ImageHelper', 'img_to_jpg')));
		$twig->addFilter(new Twig_Filter('towebp', array('Timber\ImageHelper', 'img_to_webp')));
    $twig->addFilter(new Twig_Filter('grayscale', array('Timber\ImageHelper', 'img_grayscale')));

		/* debugging filters */
		$twig->addFilter(new Twig_Filter('get_class', 'get_class'));
		$twig->addFilter(new Twig_Filter('get_type', 'get_type'));
		$twig->addFilter(new Twig_Filter('print_r', function( $arr ) {
					return print_r($arr, true);
				} ));

		/* other filters */
		$twig->addFilter(new Twig_Filter('stripshortcodes', 'strip_shortcodes'));
		$twig->addFilter(new Twig_Filter('array', array($this, 'to_array')));
		$twig->addFilter(new Twig_Filter('excerpt', 'wp_trim_words'));
		$twig->addFilter(new Twig_Filter('excerpt_chars', array('Timber\TextHelper', 'trim_characters')));
		$twig->addFilter(new Twig_Filter('function', array($this, 'exec_function')));
		$twig->addFilter(new Twig_Filter('pretags', array($this, 'twig_pretags')));
		$twig->addFilter(new Twig_Filter('sanitize', 'sanitize_title'));
		$twig->addFilter(new Twig_Filter('shortcodes', 'do_shortcode'));
		$twig->addFilter(new Twig_Filter('time_ago', array($this, 'time_ago')));
		$twig->addFilter(new Twig_Filter('wpautop', 'wpautop'));
		$twig->addFilter(new Twig_Filter('list', array($this, 'add_list_separators')));

		$twig->addFilter(new Twig_Filter('pluck', array('Timber\Helper', 'pluck')));

		/**
		 * @deprecated since 1.13 (to be removed in 2.0). Use Twig's native filter filter instead
		 * @todo remove this in 2.x so that filter merely passes to Twig's filter without any modification
		 * @ticket #1594 #2120
		 */
		$twig->addFilter(new Twig_Filter('filter', array('Timber\Helper', 'filter_array')));
		$twig->addFilter(new Twig_Filter('wp_list_filter', array('Timber\Helper', 'wp_list_filter')));

		$twig->addFilter(new Twig_Filter('relative', function( $link ) {
					return URLHelper::get_rel_url($link, true);
				} ));

		$twig->addFilter(new Twig_Filter('date', array($this, 'intl_date')));

		$twig->addFilter(new Twig_Filter('truncate', function( $text, $len ) {
					return TextHelper::trim_words($text, $len);
				} ));

		/* actions and filters */
		$twig->addFilter(new Twig_Filter('apply_filters', function() {
					$args = func_get_args();
					$tag = current(array_splice($args, 1, 1));

					return apply_filters_ref_array($tag, $args);
				} ));

		return $twig;
	}

	/**
	 * Adds escapers to Twig.
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
			if ( method_exists($escaper_extension, 'setEscaper') ) {
				$escaper_extension->setEscaper('esc_url', $esc_url);
				$escaper_extension->setEscaper('wp_kses_post', $wp_kses_post);
				$escaper_extension->setEscaper('esc_html', $esc_html);
				$escaper_extension->setEscaper('esc_js', $esc_js);
				return $twig;
			}
		}
		$escaper_extension = $twig->getExtension('Twig\Extension\CoreExtension');
		$escaper_extension->setEscaper('esc_url', $esc_url);
		$escaper_extension->setEscaper('wp_kses_post', $wp_kses_post);
		$escaper_extension->setEscaper('esc_html', $esc_html);
		$escaper_extension->setEscaper('esc_js', $esc_js);
		return $twig;
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
	 *
	 *
	 * @param string  $date
	 * @param string  $format (optional)
	 * @return string
	 */
	public function intl_date( $date, $format = null ) {
		if ( $format === null ) {
			$format = get_option('date_format');
		}

		if ( $date instanceof \DateTime ) {
			$timestamp = $date->getTimestamp() + $date->getOffset();
		} else if ( is_numeric($date) && (strtotime($date) === false || strlen($date) !== 8) ) {
			$timestamp = intval($date);
		} else {
			$timestamp = strtotime($date);
		}

		return date_i18n($format, $timestamp);
	}

	/**
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
		if ( null === $format_past ) {
			/* translators: %s: Human-readable time difference. */
			$format_past = __( '%s ago' );
		}

		if ( null === $format_future ) {
			/* translators: %s: Human-readable time difference. */
			$format_future = __( '%s from now' );
		}

		$to = $to === null ? time() : $to;
		$to = is_int($to) ? $to : strtotime($to);
		$from = is_int($from) ? $from : strtotime($from);

		if ( $from < $to ) {
			return sprintf($format_past, human_time_diff($from, $to));
		} else {
			return sprintf($format_future, human_time_diff($to, $from));
		}
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
