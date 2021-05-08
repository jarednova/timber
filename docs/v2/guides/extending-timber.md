---
title: "Extending Timber"
order: "1600"
---

There’s a myth that Timber is for making simple themes, but in fact it’s for making incredibly complex themes _look_ easy. But yes, you can also make simple sites from it.

The beauty of Timber is that its object-oriented nature lets you extend it to match the exact requirements of your theme.

## Extending Timber classes

One of the main concepts for extending Timber is to extend its classes. You can add your custom methods and properties to Posts, Terms and other objects. This way, you can reduce the amount of logic in your template files as well as your Twig files.

Let’s look at how we could add a method to **calculate the reading time for a blog post.** First, let’s create a `BlogPost` class with a method that calculates the reading time.

**src/BlogPost.php**

```php
<?php

/**
 * Class BlogPost
 */
class BlogPost extends \Timber\Post {
   /**
    * Estimates time required to read a post.
    *
    * The words per minute are based on the English language, which e.g. is much
    * faster than German or French.
    *
    * @link https://www.irisreading.com/average-reading-speed-in-various-languages/
    *
    * @return string
    */
    public function reading_time() {
        $words_per_minute = 228;

        $words   = str_word_count( wp_strip_all_tags( $this->content() ) );
        $minutes = round( $words / $words_per_minute );

        /* translators: %s: Time duration in minute or minutes. */
        return sprintf( _n( '%s minute', '%s minutes', $minutes ), (int) $minutes );
    }
}
```

By extending `Timber\Post`, we have all the methods from that class available to us by using `$this->method_name()`. In the example, we get the post’s content through `$this->content()`.

### Use Class Maps

To register your own classes with Timber, you use Class Maps. Refer to the [Class Maps Guide](/docs/v2/guides/class-maps/) for a detailed explanation for how they work. For the `BlogPost` class, it would work like this:

**functions.php**

```php
require_once( 'src/BlogPost.php' );

add_filter( 'timber/post/classmap', function( $classmap ) {
    $custom_classmap = [
        'post' => BlogPost::class,
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

With that, Timber will use the `BlogPost` class for all your posts with the post type `post`, whenever you use a Timber function that returns a `Timber\Post`.

### Use it in Twig

Now, in Twig, you can use this new method using `{{ post.reading_time }}`.

```twig
<header>
    <h1>{{ post.title }}</h1>

    <p>
        {{ post.reading_time }} to read <span>&bull;</span>

        Written by {{ post.author.name }} <span>&bull;</span>

        <time
            datetime="{{ post.date('Y-m-d H:i:s') }}"
        >{{ post.date }}</time>
    <p>
</header>
```

Your custom methods can get pretty complex. And that’s the beauty. The complexity lives inside the context of the object, but looks very simple when it comes to your Twig templates.

### Register a custom namespace in Composer

To make it a little easier to load your custom classes, you could either require that class in your **functions.php** to make it available for Timber to use:

```php
require_once 'inc/BlogPost.php';
```

Or, because you already use Composer to handle dependencies, you can register your own [namespace](https://www.php.net/manual/en/language.namespaces.php) to be autoloaded. For example, you could add a **src** folder to your project and declare it as the `Theme` namespace.

**composer.json**

```json
"autoload": {
  "psr-4": {
    "Theme\\": "src/"
  }
},
```

Then, you would use that namespace for your `BlogPost` class.


**src/BlogPost.php**

```php
<?php

namespace Theme;

class BlogPost {
    // ...
}
```

And in your Class Map, you could reference that class with the `use` statement.

```php
use Theme\BlogPost;

add_filter( 'timber/post/classmap', function( $classmap ) {
    $custom_classmap = [
        'post' => BlogPost::class,
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

## Move your logic from your template files to your classes

In the Getting Started Guide for [Archive Pages](/docs/v2/getting-started/a-post-archive/), we looked at how you can load related posts and add them to the context.

```php
<?php

$context = Timber::context();

$post = $context['post'];

$context['related_posts'] = Timber::get_posts( [
	'post_type'      => 'post',
    'posts_per_page' => 3,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post__not_in'   => [ $post->ID ],
    'category__in'   => $post->terms( [
        'taxonomy' => 'category',
        'fields'   => 'ids',
    ] ),
] );

Timber::render( 'single.twig', $context );
```

We can move this function over to the `BlogPost` class. The only difference is that instead of using `$post`, you would use `$this` to reference properties and methods.

```php
<?php

class MySitePost extends \Timber\Post {

/**
 * Class BlogPost
 */
class BlogPost extends \Timber\Post {
    /**
     * Gets related posts for that post object.
     *
     * @return \Timber\PostCollection
     */
    public function related_posts() {
        return Timber::get_posts( [
            'post_type'      => $this->post_type,
            'posts_per_page' => 3,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => [ $this->ID ],
            'category__in'   => $this->terms( [
                'taxonomy' => 'category',
                'fields'   => 'ids',
            ] ),
        ] );
    }
}
```

Now, you can still loop over your related posts in your Twig template:

**single.twig**

```twig
{% if post.related_posts is not empty %}
    <ul>
        {% for post in post.related_posts %}
            <li>{{ include('teaser.twig') }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

However, because of the if-statement, we call the `related_posts()` method twice. This might be bad for performance, because we call `Timber::get_posts()` twice. So let’s add a small cache via a `related_posts` property. This way, we can call `related_posts()` as many times as we need.

```php
<?php

/**
 * Class BlogPost
 */
class BlogPost extends \Timber\Post {
    /**
     * Related posts cache.
     *
     * @var \Timber\PostCollection|null
     */
    protected $related_posts;

    /**
     * Gets related posts for that post object.
     *
     * @return \Timber\PostCollection
     */
    public function related_posts() {
        // Return related posts early if we already loaded them.
        if ( ! empty( $this->related_posts ) ) {
            return $this->related_posts;
        }

        $this->related_posts = Timber::get_posts( [
            'post_type'      => $this->post_type,
            'posts_per_page' => 3,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => [ $this->ID ],
            'category__in'   => $this->terms( [
                'taxonomy' => 'category',
                'fields'   => 'ids',
            ] ),
        ] );

        return $this->related_posts;
    }
}
```

## Magazine example

Let’s look at an example where you work with a post’s relationship to other WordPress objects. In the following example, each post is a part of an "issue" of a magazine. We want an easy way to reference the issue in the Twig file:

**single-post.twig**

```twig
<header>
    <h1>{{ post.title }}</h1>
    <p>From the {{ post.issue.title }} issue</p>
</header>
```

Of course, `Timber\Post` has no built-in concept of an issue. Imagine there’s a custom taxonomy called `issue`. So we're going to extend `Timber\Post` to give it an `issue()` method.

```php
<?php

class MySitePost extends \Timber\Post {

class MagazinePost extends \Timber\Post {
    /**
     * Issue cache.
     *
     * @var \Timber\Term
     */
	protected $issue;

    /**
     * Gets a magazine’s issue.
     *
     * @return \Timber|Term|false;
     */
	public function issue() {
        if ( ! empty( $this->_issue ) ) {
            return $this->issue;
        }

        $issues = $this->terms( [
            'taxonomy' => 'issues'
        ] );

		if ( is_array( $issues ) && ! empty( $issues ) ) {
			$this->issue = $issues[0];
        }

        return $this->issue;
	}
}
```

So now we’ve got an easy way to refer to `{{ post.issue }}` in our Twig templates.

## Working with meta fields

A very common use case is to use custom methods for handling meta values. Also refer to the [Custom Fields Guide](/docs/v2/guides/custom-fields/) for more information.

Here, we take an custom post type `event` an example.

### Keep your logic in PHP

An event probably has a date that you define in the meta field `date_start`. You could get the date and manipulate the output format through Twig’s `date` filter.

```twig
{{ post.meta('date_start')|date('F j Y') }}
```

But you could also define your own `date_display` method to display that date.

```php
/**
 * Class Event
 */
class Event extends \Timber\Post {
    /**
	 * Gets display date.
	 *
	 * @return string
	 */
    public function date_display( $date_format = 'F j Y' ) {
        $date_start = DateTimeImmutable::createFromFormat( 'Ymd', $this->meta( 'date_start' ) );

        if ( empty( $date_start ) ) {
			return '';
		}

        return wp_date( $date_format, $date_start->getTimestamp() );
    }
}
```

And use it in Twig:

```twig
{# With your default date format #}
{{ post.date_display }}

{# With a different format #}
{{ post.date_display('F j') }}
```

This is better, because

1. You now have a clear name for your functionality.
2. You can use it everywhere where an `Event` post is used.

Additionally, if there’s more logic, you probably shouldn’t write it in Twig anyway. Twig is powerful, but it shouldn’t replace PHP.

Consider that you might also have an end date for the event that you save in a meta field `date_end`. Suddenly, you have many more cases to handle. You might have to account for empty end dates or maybe display the date a little different if there’s an end date present. Maybe you would want to say `May 30 – June 4 2020`, but `4 – 6 June 2020` instead of `June 4 – June 6 2020` if the month of the two dates is the same.

You can also make the date formats a little more dynamic here by using an arguments array. By using [`wp_parse_args()`](https://developer.wordpress.org/reference/functions/wp_parse_args/), you can make sure that all arguments you need are there, even when you only want to overwrite one of the date formats.

```php
/**
 * Class Event
 */
class Event extends \Timber\Post {
    /**
     * Gets display date.
     *
     * @param array $formats An array of date formats.
	 *
	 * @return string
	 */
	public function date_display( $formats = [] ) {
        $formats = wp_parse_args( $formats, [
            'single'           => 'F j Y',
            'yearless'         => 'F j',
            'same_month_start' => 'j',
            'same_month_end'   => 'j F Y'
        ] );

		$date_start = DateTimeImmutable::createFromFormat(
            'Ymd',
            $this->meta( 'date_start' )
        );
		$date_end = DateTimeImmutable::createFromFormat(
            'Ymd',
            $this->meta( 'date_end' )
        );

		if ( empty( $date_start ) ) {
			return '';
		}

		if ( empty( $date_end ) ) {
            // There’s only a start date.
			$date_string = wp_date( $formats['single'], $date_start->getTimestamp() );
		} else {
			// Different format if month is the same.
			if ( $date_start->format( 'm' ) === $date_end->format( 'm' ) ) {
				$date_string = sprintf(
                    '%1$s &ndash %2$s',
                    wp_date( $formats['same_month_start'], $date_start->getTimestamp() ),
                    wp_date( $formats['same_month_end'], $date_end->getTimestamp() )
                );
			} else {
                $date_string = sprintf(
                    '%1$s &ndash %2$s',
                    wp_date( $formats['yearless'], $date_start->getTimestamp() ),
                    wp_date( $formats['single'], $date_end->getTimestamp() )
                );
			}
		}

		return $date_string;
	}
}
```

In Twig, you could use it like this:

```twig
{# With your default date format #}
{{ post.date_display }}

{# With customized date formats #}
{{ post.date_display({
    same_month_end: 'j M Y'
}) }}
```

You could make it even more dynamic and use the default date format you set in your WordPress settings.

```php
$formats = wp_parse_args( $formats, [
    'single'           => get_option( 'date_format' ),
    'yearless'         => trim(
        preg_replace( '/[Yy]/', '', get_option( 'date_format' ) )
    ),
    'same_month_start' => 'j',
    'same_month_end'   => 'j F Y'
] );
```

Or you could add a filter to update your date formats globally.

```php
$formats = wp_parse_args( $formats, [
    'single'           => 'F j Y',
    'yearless'         => 'F j',
    'same_month_start' => 'j',
    'same_month_end'   => 'j F Y'
] );

$formats = apply_filters( 'theme/event/date_formats', $formats );
```

You would use that filter like this.

```php
add_filter( 'theme/event/date_formats', function( $formats ) {
    $formats['same_month_end'] = 'j M Y';

    return $formats;
} );
```

And with this, we have a method or even a class that we can reuse in other projects.

### Extending IDs to Timber objects

Imagine that each `event` has a meta field that contains an array of post IDs of posts of the post type `sponsor`, with which you can select all the organizations that make your event possible with their donations.

You could do this in Twig and use `get_posts()` to convert your IDs to `Timber\Post` objects:

```twig
{% set sponsors = get_posts(post.meta('sponsors')) %}

{% if sponsors %}
    <h2>Many thanks to our generous sponsors</h2>

    <ul>
        {% for post in sponsors %}
            <li>{{ sponsor.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

But you could also reduce the logic you have in Twig an move it your custom class.

```php
/**
 * Class Event
 */
class Event extends \Timber\Post {
    /**
     * Sponsors cache.
     *
     * @var \Timber\PostCollection|null
     */
    protected $sponsors;

    /**
     * Gets event sponsors.
     *
     * @return \Timber\PostCollection
     */
    public function sponsors() {
        if ( empty( $this->sponsors ) ) {
            return $this->sponsors;
        }

        $this->sponsors = Timber::get_posts( $this->meta( 'sponsors' ) )

        return $this->sponsors;
    }
}
```

In Twig:

```twig
{% if post.sponsors %}
    <h2>Many thanks to our generous sponsors</h2>

    <ul>
        {% for post in post.sponsors %}
            <li>{{ sponsor.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

## Extending Twig

You can also extend Twig by adding custom functionality like functions or filters.

**functions.php**

```php
add_filter( 'timber/twig', 'add_to_twig' );

/**
 * Adds functionality to Twig.
 *
 * @param \Twig\Environment $twig The Twig environment.
 * @return \Twig\Environment
 */
function add_to_twig( $twig ) {
    // Adding a function.
    $twig->addFunction( new \Twig\TwigFunction( 'edit_post_link', 'edit_post_link' ) );

    // Adding functions as filters.
    $twig->addFilter( new \Twig\TwigFilter( 'whateverify', 'whateverify' ) );
    $twig->addFilter( new \Twig\TwigFilter( 'slugify', function( $title ) {
        return sanitize_title( $title );
    } ) );

    return $twig;
}

function whateverify( $text ) {
    $text .= ' or whatever';

    return $text;
}
```

This can now be called in your twig files with:

```twig
<h2 id="{{ post.title|slugify }}">{{ post.title|whateverify }}</h2>
```

Which will output:

```twig
<h2 id="hello-world">Hello World! or whatever</h2>
```
