---
title: "Performance/Caching"
order: "1300"
---

Timber, especially in conjunction with WordPress and Twig, offers a variety of caching strategies to optimize performance. Here’s a quick rundown of some of the options, ranked in order of most-broad to most-focused.

## tl;dr

In my tests with Debug Bar, Timber has no measurable performance hit. Everything compiles to PHP. @fabpot has an [overview of the performance costs on his blog](http://fabien.potencier.org/article/34/templating-engines-in-php) (scroll down to the table).


## Cache Everything

You can still use plugins like [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/) in conjunction with Timber. In most settings, this will _skip_ the Twig/Timber layer of your files and serve static pages via whatever mechanism the plugin or settings dictate.

## Cache the Entire Twig File and Data

When rendering, use the `$expires` argument in [`Timber::render`](https://timber.github.io/docs/v2/reference/timber/#render). For example:

```php
<?php

$context['posts'] = Timber::get_posts();

Timber::render( 'index.twig', $context, 600 );
```

In this example, Timber will cache the template for 10 minutes (600 / 60 = 10). But here’s the cool part: Timber hashes the fields in the view context. This means that **as soon as the data changes, the cache is automatically invalidated**. Yay!

This method is very effective, but crude - the whole template is cached. So if you have any context dependent sub-views (eg. current user), this mode won’t do.

### Set cache mode

As a fourth parameter for [Timber::render()](https://timber.github.io/docs/v2/reference/timber/#render), you can set the `$cache_mode`.

```php
Timber::render( $filenames, $data, $expires, $cache_mode );
```

The following cache modes are available:

| Mode | Description |
| --- | --- |
| `Timber\Loader::CACHE_NONE` | Disable caching |
| `Timber\Loader::CACHE_OBJECT` | WP Object Cache |
| `Timber\Loader::CACHE_TRANSIENT` | Transients |
| `Timber\Loader::CACHE_SITE_TRANSIENT` | Network wide transients |
| `Timber\Loader::CACHE_USE_DEFAULT` | Use whatever caching mechanism is set as the default for `Timber\Loader`, the default is `CACHE_TRANSIENT`. |

## Cache _Parts_ of the Twig File and Data

This method implements the [Twig Cache Extension](https://github.com/twigphp/twig-cache-extension). It adds the cache tag, for use in templates. Best shown by example:

```twig
{% cache 'index/content' posts %}
    {% for post in posts %}
        {% include ['tease-'~post.post_type~'.twig', 'tease.twig'] %}
    {% endfor %}
{% endcache %}
```

`'index/content'` will be used as annotation (label) for the cache, while `posts` will be encoded with all its public fields. You can use anything for the label ("foo", "elephant", "single-posts", whatever).

The mechanism behind it is the same as with render - the cache key is determined based on a hash of the object/array passed in (in the above example posts).

The cache method used is always the default mode, set using the bespoke filter (by default, transient cache).

This method allows for very fine-grained control over what parts of templates are being cached and which are not. When applied on resource-intensive sections, the performance difference is huge.

In your cache, the eventual key will be:

```php
$annotation . '__GCS__' . $key
```

That is in this scenario:

```php
'index/content__GCS__' . md5( json_encode( $context['post'] ) )
```

### Extra: TimberKeyGeneratorInterface

Instead of hashing a whole object, you can specify the cache key in the object itself. If the object implements `TimberKeyGeneratorInterface`, it can pass a unique key through the method `get_cache_key`. That way a class can for example simply pass `'last_updated'` as the unique key.
If arrays contain the key `_cache_key`, that one is used as cache key.

This may save yet another few processor cycles.

## Cache the Twig File (but not the data)

Every time you render a `.twig` file, Twig compiles all the HTML tags and variables into a big, nasty blob of function calls and echo statements that actually gets run by PHP. In general, this is pretty efficient. However, you can cache the resulting PHP blob by turning on Twig’s cache via:

**functions.php**

```php
add_filter( 'timber/twig/environment/options', function( $options ) {
	$options['cache'] = true;

    return $options;
} );
```

You can look in your your `/vendor/timber/timber/cache` directory to see what these files look like.

If you want to change the path where Timber caches the Twig files, you can pass in an absolute path for the `cache` option:

```php
add_filter( 'timber/twig/environment/options', function( $options ) {
	$options['cache'] = '/absolute/path/to/twig_cache';

    return $options;
} );
```

This does not cache the _contents_ of the variables. But rather, the structure of the Twig files themselves (i.e. the HTML and where those variables appear in your template). Once enabled, any change you make to a `.twig` file (just tweaking the HTML for example) will not go live until the cache is flushed.

Thus, during development, you should enable the option for `auto_reload`:

```php
add_filter( 'timber/twig/environment/options', function( $options ) {
    $options['cache']       = true;
    $options['auto_reload'] = true;

    return $options;
});
```

Enabling `Timber::$cache` works best as a last step in the production process. Once enabled, any change you make to a `.twig` file (just tweaking the HTML for example) will not go live until the cache is flushed.

Note that when `WP_DEBUG` is set to `true`, changes you make to `.twig` files will be reflected on the site regardless of the `Timber::$cache` value.

To flush the Twig cache you can do this:

```php
$loader = new Timber\Loader();
$loader->clear_cache_twig();
```

## Cache the PHP data

Sometimes the most expensive parts of the operations are generating the data needed to populate the twig template. You can of course use WordPress’s default [Transient API](http://codex.wordpress.org/Transients_API) to store this data.

You can also use some [syntactic sugar](http://en.wikipedia.org/wiki/Syntactic_sugar) to make the checking/saving/retrieving of transient data a bit easier:

**home.php**

```php
<?php

$context = Timber::context();

$context['main_stories'] = TimberHelper::transient( 'main_stories', function(){
    $posts = Timber::get_posts();

    // As an example, do something expensive with these posts
    $extra_teases = get_field( 'my_extra_teases', 'options' );

    foreach( $extra_teases as &$tease ){
        $tease = Timber::get_post( $tease );
    }

    $main_stories = [
        'posts'        => $posts,
        'extra_teases' => $extra_teases,
    ];

    return $main_stories;
}, 600 );

Timber::render( 'home.twig', $context );
```

Here `main_stories` is a totally made-up variable. It could be called `foo`, `bar`, `elephant`, etc.

## Measuring Performance

Some tools like Debug Bar may not properly measure performance because its data (as in, the actual HTML it’s generating to tell you the timing, number of queries, etc.) is swept-up by the page’s cache.

Timber provides some quick shortcuts to measure page timing. Here’s an example of them in action:

**single.php**

```php
<?php

// This generates a starting time
$start = Timber\Helper::start_timer();

$context = Timber::context( [
    'whatever' => get_my_foo(),
] );

Timber::render( 'single.twig', $context, 600 );

// This reports the time diff by passing the $start time
echo Timber\Helper::stop_timer( $start);
```
