<?php

namespace Timber\TwigExtension;

use Timber\URLHelper;
use Timber\Helper;

use Timber\Post;
use Timber\Term;
use Timber\Image;
use Timber\User;

use Timber\Twig_Function;

/**
 *
 */
class CoreExtension implements \Twig_ExtensionInterface {

	/**
	 *
	 */
	public static function activateWordpressHook($priority = 5) {
		\add_action('timber/twig', array(__CLASS__, 'loadIntoEnvironment'), $priority);
	}

	/**
	 *
	 */
	public static function deactivateWordpressHook($priority = 5) {
		\remove_action('timber/twig', array(__CLASS__, 'loadIntoEnvironment'), $priority);
	}

	/**
	 *
	 *
	 * @param Twig_Environment $twig
	 */
	public static function loadIntoEnvironment(\Twig_Environment $twig) {
		static $instance = null;
		if ($instance === null) {
			$instance = new static();
		}
		$twig->addExtension($instance);

		$instance->addEscapers($twig);

// TODO: Remove when 'timber/twig' is changed into an action...
		return $twig;
	}

	/**
	 *
	 *
	 * @param Twig_Environment $twig
	 */
	protected function addEscapers( \Twig_Environment $twig ) {

		$twig->getExtension('Twig_Extension_Core')->setEscaper('esc_url', function( \Twig_Environment $env, $string ) {
			return esc_url( $string );
		});
		$twig->getExtension('Twig_Extension_Core')->setEscaper('wp_kses_post', function( \Twig_Environment $env, $string ) {
			return wp_kses_post( $string );
		});

		$twig->getExtension('Twig_Extension_Core')->setEscaper('esc_html', function( \Twig_Environment $env, $string ) {
			return esc_html( $string );
		});

		$twig->getExtension('Twig_Extension_Core')->setEscaper('esc_js', function( \Twig_Environment $env, $string ) {
			return esc_js( $string );
		});
	}

	/**
     * This is not used by Twig v2.x, but existence is required to maintain compatibility with Twig v1.x
     *
     * @deprecated since Twig v1.23 (to be removed in v2.0)
     * @ignore
     */
    final public function initRuntime(\Twig_Environment $environment) {
	}

	/**
     * Returns the token parser instances to add to the existing list.
     *
     * @return Twig_TokenParserInterface[]
     */
	public function getTokenParsers() {
		
		static $token_parsers = array();

//		if (empty($token_parsers)) {
//			// nothing yet...
//		}

		return apply_filters('timber/twig/extension/token_parsers', $token_parsers, $this);
	}
	
	/**
     * Returns the node visitor instances to add to the existing list.
     *
     * @return Twig_NodeVisitorInterface[]
     */
	public function getNodeVisitors() {

		static $node_visitors = array();

//		if (empty($node_visitors)) {
//			// nothing yet...
//		}

		return apply_filters('timber/twig/extension/node_visitors', $node_visitors, $this);
	}
	
	/**
     * Returns a list of filters to add to the existing list.
     *
     * @return Twig_Filter[]
     */
	public function getFilters() {

		static $filters = array();

		if (empty($filters)) {
			$filters = array(
				/* image filters */
				'resize'          => new \Twig_SimpleFilter('resize', array('Timber\ImageHelper', 'resize')),
				'retina'          => new \Twig_SimpleFilter('retina', array('Timber\ImageHelper', 'retina_resize')),
				'letterbox'       => new \Twig_SimpleFilter('letterbox', array('Timber\ImageHelper', 'letterbox')),
				'tojpg'           => new \Twig_SimpleFilter('tojpg', array('Timber\ImageHelper', 'img_to_jpg')),

				/* debugging filters */
				'get_class'       => new \Twig_SimpleFilter('get_class', 'get_class'),
				'get_type'        => new \Twig_SimpleFilter('get_type', 'get_type'),
				'print_r'         => new \Twig_SimpleFilter('print_r', function( $arr ) {
							    	      return print_r($arr, true);
							          }),

				/* other filters */
				'stripshortcodes' => new \Twig_SimpleFilter('stripshortcodes', 'strip_shortcodes'),
				'array'           => new \Twig_SimpleFilter('array', array($this, 'to_array')),
				'excerpt'         => new \Twig_SimpleFilter('excerpt', 'wp_trim_words'),
				'excerpt_chars'   => new \Twig_SimpleFilter('excerpt_chars', array('Timber\TextHelper','trim_characters')),
				'function'        => new \Twig_SimpleFilter('function', array($this, 'exec_function')),
				'pretags'         => new \Twig_SimpleFilter('pretags', array($this, 'twig_pretags')),
				'sanitize'        => new \Twig_SimpleFilter('sanitize', 'sanitize_title'),
				'shortcodes'      => new \Twig_SimpleFilter('shortcodes', 'do_shortcode'),
				'time_ago'        => new \Twig_SimpleFilter('time_ago', array($this, 'time_ago')),
				'wpautop'         => new \Twig_SimpleFilter('wpautop', 'wpautop'),
				'list'            => new \Twig_SimpleFilter('list', array($this, 'add_list_separators')),

				'pluck'           => new \Twig_SimpleFilter('pluck', array('Timber\Helper', 'pluck')),

				'relative'        => new \Twig_SimpleFilter('relative', function( $link ) {
							    	     return URLHelper::get_rel_url($link, true);
							          }),
				'date'            => new \Twig_SimpleFilter('date', array($this, 'intl_date')),

				'truncate'        => new \Twig_SimpleFilter('truncate', function( $text, $len ) {
							             return TextHelper::trim_words($text, $len);
							          }),

				/* actions and filters */
				'apply_filters'   => new \Twig_SimpleFilter('apply_filters', function() {
							    	      $args = func_get_args();
							    	      $tag = current(array_splice($args, 1, 1));
							    	      return apply_filters_ref_array($tag, $args);
							    	  }),
			);
		}

		return apply_filters('timber/twig/extension/filters', $filters, $this);
	}

	/**
     * Returns a list of tests to add to the existing list.
     *
     * @return Twig_Test[]
     */
	public function getTests() {

		static $tests = array();

//		if (empty($tests)) {
//			// nothing yet...
//		}

		return apply_filters('timber/twig/extension/operators', $tests, $this);
	}
	
	/**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_Function[]
     */
	public function getFunctions() {
		
		static $functions = array();

		if (empty($functions)) {
			$functions = array(
				/* actions and filters */
				'action' => new Twig_Function('action', function( $context ) {
						$args = func_get_args();
						array_shift($args);
						$args[] = $context;
						call_user_func_array('do_action', $args);
					}, array('needs_context' => true)),

				'function' => new Twig_Function('function', array(&$this, 'exec_function')),
				'fn' => new Twig_Function('fn', array(&$this, 'exec_function')),

				'shortcode' => new Twig_Function('shortcode', 'do_shortcode'),

				/* TimberObjects */
				'TimberPost' => new Twig_Function('TimberPost', function( $pid, $PostClass = 'Timber\Post' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $PostClass($p);
							}
							return $pid;
						}
						return new $PostClass($pid);
					}),
				'TimberImage' => new Twig_Function('TimberImage', function( $pid = false, $ImageClass = 'Timber\Image' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $ImageClass($p);
							}
							return $pid;
						}
						return new $ImageClass($pid);
					}),

				'TimberTerm' => new Twig_Function('TimberTerm', function( $pid, $TermClass = 'Timber\Term' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $TermClass($p);
							}
							return $pid;
						}
						return new $TermClass($pid);
					}),
				'TimberUser' => new Twig_Function('TimberUser', function( $pid, $UserClass = 'Timber\User' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $UserClass($p);
							}
							return $pid;
						}
						return new $UserClass($pid);
					}),

				/* TimberObjects Alias */
				'Post' => new Twig_Function('Post', function( $pid, $PostClass = 'Timber\Post' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $PostClass($p);
							}
							return $pid;
						}
						return new $PostClass($pid);
					}),
				'Image' => new Twig_Function('Image', function( $pid, $ImageClass = 'Timber\Image' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $ImageClass($p);
							}
							return $pid;
						}
						return new $ImageClass($pid);
					}),
				'Term' => new Twig_Function('Term', function( $pid, $TermClass = 'Timber\Term' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $TermClass($p);
							}
							return $pid;
						}
						return new $TermClass($pid);
					}),
				'User' => new Twig_Function('User', function( $pid, $UserClass = 'Timber\User' ) {
						if ( is_array($pid) && !Helper::is_array_assoc($pid) ) {
							foreach ( $pid as &$p ) {
								$p = new $UserClass($p);
							}
							return $pid;
						}
						return new $UserClass($pid);
					}),

				/* bloginfo and translate */
				'bloginfo' => new Twig_Function('bloginfo', function( $show = '', $filter = 'raw' ) {
						return get_bloginfo($show, $filter);
					}),
				'__' => new Twig_Function('__', function( $text, $domain = 'default' ) {
						return __($text, $domain);
					}),
				'translate' => new Twig_Function('translate', function( $text, $domain = 'default' ) {
						return translate($text, $domain);
					}),
				'_e' => new Twig_Function('_e', function( $text, $domain = 'default' ) {
						return _e($text, $domain);
					}),
				'_n' => new Twig_Function('_n', function( $single, $plural, $number, $domain = 'default' ) {
						return _n($single, $plural, $number, $domain);
					}),
				'_x' => new Twig_Function('_x', function( $text, $context, $domain = 'default' ) {
						return _x($text, $context, $domain);
					}),
				'_ex' => new Twig_Function('_ex', function( $text, $context, $domain = 'default' ) {
						return _ex($text, $context, $domain);
					}),
				'_nx' => new Twig_Function('_nx', function( $single, $plural, $number, $context, $domain = 'default' ) {
						return _nx($single, $plural, $number, $context, $domain);
					}),
				'_n_noop' => new Twig_Function('_n_noop', function( $singular, $plural, $domain = 'default' ) {
						return _n_noop($singular, $plural, $domain);
					}),
				'_nx_noop' => new Twig_Function('_nx_noop', function( $singular, $plural, $context, $domain = 'default' ) {
						return _nx_noop($singular, $plural, $context, $domain);
					}),
				'translate_nooped_plural' => new Twig_Function('translate_nooped_plural', function( $nooped_plural, $count, $domain = 'default' ) {
						return translate_nooped_plural($nooped_plural, $count, $domain);
					}),
			);
		}
		
		return apply_filters('timber/twig/extension/functions', $functions, $this);
	}

	/**
     * Returns a list of operators to add to the existing list.
     *
     * @return array<array> First array of unary operators, second array of binary operators
     */
	public function getOperators() {
		
		static $operators = array();

//		if (empty($operators)) {
//			// nothing yet...
//		}

		return apply_filters('timber/twig/extension/operators', $operators, $this);
	}

	/**
     * This is not used by Twig v2.x, but existence is required to maintain compatibility with Twig v1.x
     *
     * @return array An empty array
     * @deprecated since Twig v1.23 (to be removed in v2.0)
     * @ignore
     */
    final public function getGlobals() {
		return array();
	}
	
    /**
     * This is not used by Twig v2.x, but existence is required to maintain compatibility with Twig v1.x
	 *
	 * Returns the name of the extension.
     *
     * @return string The extension name
	 * @deprecated since Twig v1.26 (to be removed in Twig v2.0), not used anymore internally
     * @ignore
     */
    final public function getName() {
		return 'TimberExtension';
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
	 * @param int|string $from
	 * @param int|string $to
	 * @param string $format_past
	 * @param string $format_future
	 * @return string
	 */
	public static function time_ago( $from, $to = null, $format_past = '%s ago', $format_future = '%s from now' ) {
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
	public function add_list_separators( $arr, $first_delimiter = ',', $second_delimiter = 'and' ) {
		$length = count($arr);
		$list = '';
		foreach ( $arr as $index => $item ) {
			if ( $index < $length - 2 ) {
				$delimiter = $first_delimiter.' ';
			} elseif ( $index == $length - 2 ) {
				$delimiter = ' '.$second_delimiter.' ';
			} else {
				$delimiter = '';
			}
			$list = $list.$item.$delimiter;
		}
		return $list;
	}
}
