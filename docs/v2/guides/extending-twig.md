---
title: "Extending Timber"
order: "1650"
---

## Twig Environment

Timber abstracts some of that functionality for you, so you can add functions or filters more easily.

## Functions

If you have functions that you use a lot and want to improve readability of your code, you can make them available in Twig.

By default, you’ll have to use `{{ fn('function_name') }}` to call a function in Twig. To use a function directly without using `fn()`, you can use the `timber/twig/functions` filter.

**functions.php**

```php
add_filter( 'timber/twig/functions', function( $functions ) {
    $functions['edit_post_link'] = [
        'callable' => 'edit_post_link',
    ];

    return $functions;
} );
```

The `$functions` variable is an array of functions that Timber already adds by default.

In the example above, we add a `edit_post_link` function by defining  an array with a `callable` key that contains the name of the PHP function we want to call. In this case: [`edit_post_link()`](https://developer.wordpress.org/reference/functions/edit_post_link/).

In Twig, we can then use it like this:

**single.twig**

```twig
{# Calls edit_post_link using default arguments #}
<div class="admin-tools">{{ edit_post_link() }}</div>

{# Calls edit_post_link with all defaults, except for second argument #}
<div class="admin-tools">
    {{ edit_post_link(null, '<span class="edit-my-post-type-link">') }}
</div>
```

### Functions that Timber provides

Timber already comes with a list of functions it adds by default. If you want to check out the functions that Timber provides, you can do a debug dump using `var_dump()`.

**functions.php**

```php
add_filter( 'timber/twig/functions', function( $functions ) {
    var_dump( $functions );

    return $functions;
} );
```

### Changing functions

You can replace a function with your own function or even remove a function by updating the array items in `$functions`.

```php
add_filter( 'timber/twig/filters', function( $functions ) {
    // Replace a function.
    $functions['get_image'] = [
        'callable' => 'custom_get_image',
    ];

    // Remove a function.
    unset( $filters['get_image'] );

    return $filters;
} );
```

### function_wrapper

In Timber versions lower than 1.3, you could use `function_wrapper` to make functions available in Twig. This method is now deprecated. Instead, use the method above.

## Filters

To extend Twig with your own filters, you can use the `timber/twig/filters` filter in PHP. The benefit of filters is that they make your code somewhat more readable, because you chain filters with pipes (`|`) instead of nesting a value inside function calls.

Here’s an example where we add our own `|price` filter. We pass the name of the PHP that should be called – in this case it’s `format_price()` – with a `callable` key.

**functions.php**

```php
add_filter( 'timber/twig/filters', function( $filters ) {
    // Add your own filters.
    $filters['price'] = [
        'callable' => 'format_price',
    ];

    $filters['slugify'] = [
        'callable' => 'sanitize_title',
    ];

    return $filters;
} );
```

In Twig, we can then use it like this:

```twig
<h2 id="{{ post.title|slugify }}">{{ post.title }}</h2>

<span class="price">{{ post.meta('price')|price }}</span>
```

### Filters provided by Timber

You could use the same filter to dump a list of all available filters for debugging:

```php
add_filter( 'timber/twig/filters', function( $filters ) {
    var_dump( $filters );

    return $filters;
} );
```

### Changing filters

You can replace a filter with your own function or even remove a filter by updating the array items in `$filters`.

```php
add_filter( 'timber/twig/filters', function( $filters ) {
    // Replace a filter.
    $filters['list'] = [
        'callable' => 'custom_list_filter',
    ];

    // Remove a filter.
    unset( $filters['list'] );

    return $filters;
} );
```

## Adding functionality with the Twig Environment filter

You can still extend the [Twig Environment](https://twig.symfony.com/doc/3.x/advanced.html) directly with the `timber/twig` filter. The only disadvantage of this is that once functions or filters are added to Twig, the can’t be removed anymore.

Here’s the same functions and filters that we add above. But instead of using `timber/twig/functions` and `timber/twig/filters`, we use `timber/twig` directly.

```php
add_filter( 'timber/twig', function( \Twig\Environment $twig ) {
    $twig->addFunction(
        new TwigFunction( 'edit_post_link', 'edit_post_link' )
    );

    $twig->addFilter(
        new TwigFilter( 'price', 'format_price' )
    );

    $twig->addFilter(
        new TwigFilter( 'slugify', 'sanitize_title' )
    ];

    return $twig;
} );
```
