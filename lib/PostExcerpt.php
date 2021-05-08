<?php

namespace Timber;

/**
 * The PostExcerpt class lets a user modify a post preview/excerpt to their liking.
 *
 * It’s designed to be used through the `Timber\Post::excerpt()` method. The public methods of this
 * class all return the object itself, which means that this is a **chainable object**. This means
 * that you could change the output of the excerpt by **adding more methods**. But you can also pass
 * in your arguments to the object constructor or to `Timber\Post::excerpt()`.
 *
 * By default, the excerpt will
 *
 * - have a length of 50 words, which will be forced, even if a longer excerpt is set on the post.
 * - be stripped of all HTML tags.
 * - have an ellipsis (…) as the end of the text.
 * - have a "Read More" link appended, if there’s more to read in the post content.
 *
 * One thing to note: If the excerpt already contains all of the text that can also be found in the
 * post’s content, then the read more link as well as the string to use as the end will not be
 * added.
 *
 * This class will also handle cases where you use the `<!-- more -->` tag inside your post content.
 * You can also change the text used for the read more link by adding your desired text to the
 * `<!-- more -->` tag. Here’s an example: `<!-- more Start your journey -->`.
 *
 * You can change the defaults that are used for excerpts through the
 * [`timber/post/excerpt/defaults`](https://timber.github.io/docs/v2/hooks/filters/#timber/post/excerpts/defaults)
 * filter.
 *
 * @api
 * @since 1.0.4
 * @see \Timber\Post::excerpt()
 * @example
 * ```twig
 * {# Use default excerpt #}
 * <p>{{ post.excerpt }}</p>
 *
 * {# Preferred method: Use hash notation to pass arguments. #}
 * <div>{{ post.excerpt({ words: 100, read_more: 'Keep reading' }) }}</div>
 *
 * {# Change the post excerpt text only #}
 * <p>{{ post.excerpt.read_more('Continue Reading') }}</p>
 *
 * {# Additionally restrict the length to 50 words #}
 * <p>{{ post.excerpt.length(50).read_more('Continue Reading') }}</p>
 * ```
 */
class PostExcerpt {
	/**
	 * Post.
	 *
	 * @var \Timber\Post
	 */
	protected $post;

	/**
	 * Excerpt end.
	 *
	 * @var string
	 */
	protected $end = '&hellip;';

	/**
	 * Force length.
	 *
	 * @var bool
	 */
	protected $force = false;

	/**
	 * Length in words.
	 *
	 * @var int
	 */
	protected $length = 50;

	/**
	 * Length in characters.
	 *
	 * @var int|bool
	 */
	protected $char_length = false;

	/**
	 * Read more text.
	 *
	 * @var string
	 */
	protected $read_more = 'Read More';

	/**
	 * HTML tag stripping behavior.
	 *
	 * @var string|bool
	 */
	protected $strip = true;

	/**
	 * Whether a read more link should be added even if the excerpt isn’t trimmed (when the excerpt
	 * isn’t shorter than the post’s content).
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	protected $always_add_read_more = false;

	/**
	 * Whether the end string should be added even if the excerpt isn’t trimmed (when the excerpt
	 * isn’t shorter than the post’s content).
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	protected $always_add_end = false;

	/**
	 * Destroy tags.
	 *
	 * @var array List of tags that should always be destroyed.
	 */
	protected $destroy_tags = array('script', 'style');

	/**
	 * PostExcerpt constructor.
	 *
	 * @api
	 *
	 * @param \Timber\Post $post The post to pull the excerpt from.
	 * @param array        $options {
	 *     An array of configuration options for generating the excerpt. Default empty.
	 *
	 *     @type int      $words     Number of words in the excerpt. Default `50`.
	 *     @type int|bool $chars     Number of characters in the excerpt. Default `false` (no
	 *                               character limit).
	 *     @type string   $end       String to append to the end of the excerpt. Default '&hellip;'
	 *                               (HTML ellipsis character).
	 *     @type bool     $force     Whether to shorten the excerpt to the length/word count
	 *                               specified, even if an editor wrote a manual excerpt longer
	 *                               than the set length. Default `false`.
	 *     @type bool     $strip     Whether to strip HTML tags. Default `true`.
	 *     @type string   $read_more String for what the "Read More" text should be. Default
	 *                               'Read More'.
	 *     @type bool     $always_add_read_more Whether a read more link should be added even if the
	 *                                          excerpt isn’t trimmed (when the excerpt isn’t
	 *                                          shorter than the post’s content). Default `false`.
	 *     @type bool     $always_add_end       Whether the end string should be added even if the
	 *                                          excerpt isn’t trimmed (when the excerpt isn’t
	 *                                          shorter than the post’s content). Default `false`.
	 * }
	 */
	public function __construct( $post, array $options = array() ) {
		$this->post = $post;

		$defaults = [
			'words'                => 50,
			'chars'                => false,
			'end'                  => '&hellip;',
			'force'                => false,
			'strip'                => true,
			'read_more'            => 'Read More',
			'always_add_read_more' => false,
			'always_add_end'       => false,
		];

		/**
		 * Filters the default options used for post excerpts.
		 *
		 * @since 2.0.0
		 * @example
		 * ```php
		 * add_filter( 'timber/post/excerpt/defaults', function( $defaults ) {
		 *     // Only add a read more link if the post content isn’t longer than the excerpt.
		 *     $defaults['always_add_read_more'] = false;
		 *
		 *     // Set a default character limit.
		 *     $defaults['words'] = 240;
		 *
		 *     return $defaults;
		 * } );
		 * ```
		 *
		 * @param array $defaults An array of default options. You can see which options you can use
		 *                         when you look at the `$options` parameter for
		 *                        [PostExcerpt::__construct()](https://timber.github.io/docs/v2/reference/timber-postexcerpt/#__construct).
		 */
		$defaults = apply_filters( 'timber/post/excerpt/defaults', $defaults );

		// Set up excerpt defaults.
		$options = wp_parse_args( $options, $defaults );

		// Set excerpt properties
		$this->length               = $options['words'];
		$this->char_length          = $options['chars'];
		$this->end                  = $options['end'];
		$this->force                = $options['force'];
		$this->strip                = $options['strip'];
		$this->read_more            = $options['read_more'];
		$this->always_add_read_more = $options['always_add_read_more'];
		$this->always_add_end       = $options['always_add_end'];
	}

	/**
	 * Returns the resulting excerpt.
	 *
	 * @api
	 * @return string
	 */
	public function __toString() {
		return $this->run();
	}

	/**
	 * Restricts the length of the excerpt to a certain amount of words.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <p>{{ post.excerpt.length(50) }}</p>
	 * ```
	 * @param int $length The maximum amount of words (not letters) for the excerpt. Default `50`.
	 * @return \Timber\PostExcerpt
	 */
	public function length( $length = 50 ) {
		$this->length = $length;
		return $this;
	}

	/**
	 * Restricts the length of the excerpt to a certain amount of characters.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <p>{{ post.excerpt.chars(180) }}</p>
	 * ```
	 * @param int|bool $char_length The maximum amount of characters for the excerpt. Default
	 *                              `false`.
	 * @return \Timber\PostExcerpt
	 */
	public function chars( $char_length = false ) {
		$this->char_length = $char_length;
		return $this;
	}

	/**
	 * Defines the text to end the excerpt with.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <p>{{ post.excerpt.end('… and much more!') }}</p>
	 * ```
	 * @param string $end The text for the end of the excerpt. Default `…`.
	 * @return \Timber\PostExcerpt
	 */
	public function end( $end = '&hellip;' ) {
		$this->end = $end;
		return $this;
	}

	/**
	 * Forces excerpt lengths.
	 *
	 * What happens if your custom post excerpt is longer than the length requested? By default, it
	 * will use the full `post_excerpt`. However, you can set this to `true` to *force* your excerpt
	 * to be of the desired length.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <p>{{ post.excerpt.length(20).force }}</p>
	 * ```
	 * @param bool $force Whether the length of the excerpt should be forced to the requested
	 *                    length, even if an editor wrote a manual excerpt that is longer than the
	 *                    set length. Default `true`.
	 * @return \Timber\PostExcerpt
	 */
	public function force( $force = true ) {
		$this->force = $force;
		return $this;
	}

	/**
	 * Defines the text to be used for the "Read More" link.
	 *
	 * Set this to `false` to not add a "Read More" link.
	 *
	 * @api
	 * ```twig
	 * <p>{{ post.excerpt.read_more('Learn more') }}</p>
	 * ```
	 *
	 * @param string $text Text for the link. Default 'Read More'.
	 *
	 * @return \Timber\PostExcerpt
	 */
	public function read_more( $text = 'Read More' ) {
		$this->read_more = $text;
		return $this;
	}

	/**
	 * Defines how HTML tags should be stripped from the excerpt.
	 *
	 * @api
	 * ```twig
	 * {# Strips all HTML tags, except for bold or emphasized text #}
	 * <p>{{ post.excerpt.length('50').strip('<strong><em>') }}</p>
	 * ```
	 * @param bool|string $strip Whether or how HTML tags in the excerpt should be stripped. Use
	 *                           `true` to strip all tags, `false` for no stripping, or a string for
	 *                           a list of allowed tags (e.g. '<p><a>'). Default `true`.
	 * @return \Timber\PostExcerpt
	 */
	public function strip( $strip = true ) {
		$this->strip = $strip;
		return $this;
	}

	/**
	 * Assembles excerpt.
	 *
	 * @internal
	 *
	 * @param string $text The text to use for the excerpt.
	 * @param array  $args An array of arguments for the assembly.
	 */
	protected function assemble( $text, $args = [] ) {
		$text = trim($text);
		$last = $text[strlen($text) - 1];
		$last_p_tag = null;
		if ( $last != '.' && ( $this->always_add_end || $args['add_end'] ) ) {
			$text .= $this->end;
		}
		if ( !$this->strip ) {
			$last_p_tag = strrpos($text, '</p>');
			if ( $last_p_tag !== false ) {
				$text = substr($text, 0, $last_p_tag);
			}
			if ( $last != '.' && ( $this->always_add_end || $args['add_end'] ) ) {
				$text .= $this->end.' ';
			}
		}

		// Maybe add read more link.
		if ( $this->read_more && ( $this->always_add_read_more || $args['add_read_more'] ) ) {
			/**
			 * Filters the CSS class used for excerpt links.
			 *
			 * @since 2.0.0
			 * @example
			 * ```php
			 * // Change the CSS class for excerpt links.
			 * add_filter( 'timber/post/excerpt/read_more_class', function( $class ) {
			 *     return 'read-more__link';
			 * } );
			 * ```
			 *
			 * @param string $class The CSS class to use for the excerpt link. Default `read-more`.
			 */
			$read_more_class = apply_filters( 'timber/post/excerpt/read_more_class', 'read-more' );

			/**
			 * Filters the CSS class used for excerpt links.
			 *
			 * @deprecated 2.0.0
			 * @since 1.0.4
			 */
			$read_more_class = apply_filters_deprecated(
				'timber/post/preview/read_more_class',
				[ $read_more_class ],
				'2.0.0',
				'timber/post/excerpt/read_more_class'
			);

			$linktext = trim( $this->read_more );

			$link = sprintf( ' <a href="%1$s" class="%2$s">%3$s</a>',
				$this->post->link(),
				$read_more_class,
				$linktext
			);

			/**
			 * Filters the link used for a read more text in an excerpt.
			 *
			 * @since 2.0.0
			 * @param string $link The HTML link.
			 * @param \Timber\Post $post Post instance.
			 */
			$link = apply_filters(
				'timber/post/excerpt/read_more_link',
				$link,
				$this->post,
				$linktext,
				$read_more_class
			);

			/**
			 * Filters the link used for a read more text in an excerpt.
			 *
			 * @deprecated 2.0.0
			 * @since 1.1.3
			 * @ticket #1142
			 */
			$link = apply_filters_deprecated(
				'timber/post/get_preview/read_more_link',
				[ $link ],
				'2.0.0',
				'timber/post/excerpt/read_more_link'
			);

			$text .= $link;
		}

		if ( !$this->strip && $last_p_tag && (strpos($text, '<p>') > -1 || strpos($text, '<p ')) ) {
			$text .= '</p>';
		}
		return trim($text);
	}

	protected function run() {
		$allowable_tags   = ( $this->strip && is_string( $this->strip ) ) ? $this->strip : false;
		$readmore_matches = array();
		$text             = '';
		$add_read_more    = false;
		$add_end          = false;

		// A user-specified excerpt is authoritative, so check that first.
		if ( isset($this->post->post_excerpt) && strlen($this->post->post_excerpt) ) {
			$text = $this->post->post_excerpt;
			if ( $this->force ) {

				if ( $allowable_tags ) {
					$text = TextHelper::trim_words($text, $this->length, false, strtr($allowable_tags, '<>', '  '));
				} else {
					$text = TextHelper::trim_words($text, $this->length, false);
				}
				if ( $this->char_length !== false ) {
					$text = TextHelper::trim_characters($text, $this->char_length, false);
				}

				$add_end = true;
			}

			$add_read_more = true;
		}

		// Check for <!-- more --> tag in post content.
		if ( empty( $text ) && preg_match('/<!--\s?more(.*?)?-->/', $this->post->post_content, $readmore_matches) ) {
			$pieces = explode($readmore_matches[0], $this->post->post_content);
			$text = $pieces[0];

			$add_read_more = true;

			/**
			 * Custom read more text.
			 *
			 * The following post content example will result in the read more text to become "But
			 * what is Elaina?": Eric is a polar bear <!-- more But what is Elaina? --> Lauren is
			 * not a duck.
			 */
			if ( ! empty( $readmore_matches[1] ) ) {
				$this->read_more = trim( $readmore_matches[1] );
			}

			if ( $this->force ) {
				if ( $allowable_tags ) {
					$text = TextHelper::trim_words($text, $this->length, false, strtr($allowable_tags, '<>', '  '));
				} else {
					$text = TextHelper::trim_words($text, $this->length, false);
				}
				if ( $this->char_length !== false ) {
					$text = TextHelper::trim_characters($text, $this->char_length, false);
				}

				$add_end = true;
			}

			$text = do_shortcode($text);
		}

		// Build an excerpt text from the post’s content.
		if ( empty( $text ) ) {
			$text = $this->post->content();
			$text = TextHelper::remove_tags($text, $this->destroy_tags);
			$text_before_trim      = trim( $text );
			$text_before_char_trim = '';

			if ( $allowable_tags ) {
				$text = TextHelper::trim_words($text, $this->length, false, strtr($allowable_tags, '<>', '  '));
			} else {
				$text = TextHelper::trim_words($text, $this->length, false);
			}

			if ( $this->char_length !== false ) {
				$text_before_char_trim = trim( $text );
				$text = TextHelper::trim_characters($text, $this->char_length, false);
			}

			$has_trimmed_words = strlen( $text ) < strlen( $text_before_trim );
			$has_trimmed_chars = ! empty( $text_before_char_trim )
				&& strlen( $text ) < strlen( $text_before_char_trim );

			if ( $has_trimmed_words || $has_trimmed_chars ) {
				$add_end       = true;
				$add_read_more = true;
			}
		}
		if ( empty( trim( $text ) ) ) {
			return trim($text);
		}
		if ( $this->strip ) {
			$text = trim(strip_tags($text, $allowable_tags));
		}
		if ( ! empty( $text ) ) {
			return $this->assemble( $text, [
				'add_end'       => $add_end,
				'add_read_more' => $add_read_more,
			] );
		}

		return trim($text);
	}
}
