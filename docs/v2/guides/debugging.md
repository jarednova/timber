---
title: "Debugging"
order: "1800"
---

## Enable debugging

To use debugging, the constant `WP_DEBUG` needs to be set to `true`.

**wp-config.php**

```php
define( 'WP_DEBUG', true );
```

## Using Twig’s native functions

Twig includes a `dump` function that can output the properties of an object. 

**Twig**

```twig
{{ dump(post) }}
```

Which will give you:

![](https://i.imgur.com/5Xu53Fk.png)

You can also dump _everything_ sent to your template (all the contents of `$context` that were passed to the Twig file) via:

```twig
{{ dump() }}
```

This will give you something like:

![](https://i.imgur.com/5ZD8VDd.png)

## Formatted output

For a highlighted output like you see it above, you need to have [xDebug](https://xdebug.org/) enabled in your local development environment. With some environments like MAMP, enabling it is as easy as ticking a checkbox and restarting the server. Other times, it might be more complex.

An easier solution is to use the [Timber Dump Extension](https://github.com/nlemoine/timber-dump-extension), which will make use of the Symfony VarDumper component to generate output like this when using `{{ dump() }}` in Twig:

![](https://user-images.githubusercontent.com/2084481/31230351-116569a8-a9e4-11e7-8310-48b7f679892b.png)

It also works in PHP. Instead of using `var_dump` or `print_r`, you will use `dump()` as well:

```php
dump( $post );
```

## Commented Twig includes

Sometimes it’s difficult to know which Twig file generated a certain output. Thankfully, there’s the [Timber Commented Include](https://github.com/djboris88/timber-commented-include) extension. It will generate HTML comments that indicate where a template starts and where it ends:

```html
<!-- Begin output of "partials/navigation.twig" -->
<nav class="navigation">...</nav>
<!-- / End output of "partials/navigation.twig" -->).
```

The extension is only active when `WP_DEBUG` is set to `true`.

## Set breakpoints in Twig

### Twig breakpoints in PhpStorm

With PhpStorm, you can [set breakpoints right in your Twig files](https://blog.jetbrains.com/phpstorm/2019/05/twig-and-blade-templates-debugging-2/). To make it work, you need to enable the caching of Twig files:

**functions.php**

```php
Timber::$twig_cache = true;
```

Then, you need to reference the path to the cached files in ***Settings/Preferences*** &rarr; ***Languages & Frameworks*** &rarr; ***PHP*** &rarr; ***Debug*** &rarr; ***Templates***. If you’ve installed Timber through Composer, the path will be `vendor/timber/timber/cache/`.

Remember that you can set the location of the cache files through the `timber/cache/location` filter:

```php
add_filter( 'timber/cache/location', function() {
    return '/absolute/path/to/your/cached/twig/files';
} );
```

### Twig breakpoints in other IDEs

Other IDEs don’t allow you to set breakpoints in your PHP code. You can try out the [AjglBreakpointTwigExtension](https://github.com/ajgarlag/AjglBreakpointTwigExtension) extension, that allows you to set breakpoints and inspect environment and context variables.

Install it as a dev-dependency:

```
composer require ajgl/breakpoint-twig-extension --dev
```

And then add it to Timber’s Twig environment:

**functions.php**

```php
add_filter( 'timber/twig', function( \Twig\Environment $twig ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG
        && class_exists( 'Ajgl\Twig\Extension\BreakpointExtension' )
    ) {
        $twig->addExtension( new Ajgl\Twig\Extension\BreakpointExtension() );
    }

    return $twig;
} );
```

Finally, you can set a breakpoint anywhere in your Twig file:

```twig
<nav>
    {{ breakpoint() }}
</nav>
```

## Timber Debugger

The [**Timber Debugger**](https://github.com/djboris88/timber-debugger) package includes all three extensions mentioned above:  the [Timber Dump](https://github.com/nlemoine/timber-dump-extension) extension, the [Timber Commented Include](https://github.com/djboris88/timber-commented-include) extension and the [Twig Breakpoints](https://github.com/ajgarlag/AjglBreakpointTwigExtension) extension.

## Using Timber Debug Bar plugin

There’s a [Timber add-on](http://wordpress.org/plugins/debug-bar-timber/) for the [WordPress debug bar](https://wordpress.org/plugins/debug-bar/).  
**Warning:** this currently requires PHP 5.4.
