---
title: "Term"
order: "120"
---

To get a term object in Timber, you use `Timber::get_term()` and pass the WordPress term ID as an argument.

```php
$term = Timber::get_term( $term_id );
```

This function is similar to [`get_term()`](https://developer.wordpress.org/reference/functions/get_term/) and accepts one argument: a term ID. If you don’t pass in any argument, Timber will use `get_queried_object()` to try and work with the currently queried term.

```php
$term = Timber::get_term();

// Is the same as…

$term = Timber::get_term( get_queried_object_id() );
```

What you get in return is a [`Timber\Term`](https://timber.github.io/docs/v2/reference/timber-term/) object, which is similar to `WP_Term`.

## Get term by field

If you don’t have a term ID, you can also get a term by other fields, like `slug` or `name` through `Timber::get_term_by()`.

```php
// Get a term by slug.
$term = Timber::get_term_by( 'slug', 'news', 'category' );

// Get a term by name.
$term = Timber::get_term_by( 'name', 'News', 'category' );
```

## Twig

You can convert terms IDs to term objects in Twig using the `Term()` function.

```twig
{% set term = get_term(term_id) %}
```

It also works if you have an array of terms IDs that you want to convert to `Timber\Term` objects.

```twig
{% for term in get_term(term_ids) %}

{% endfor %}
```

## Invalid terms

If no valid term can be found with the term ID you provided, the `Timber::get_term()` function will return `null`. With this, you can always check for a valid term with a simple if statement.

```php
$term = Timber::get_term( $term_id );

if ( $term ) {

}
```

Or in Twig:

```twig
{% if term %}
    {{ term.title }}
{% endif %}
```

## Extending `Timber\Term`

If you need additional functionality that the `Timber\Term` class doesn’t provide or if you want to have cleaner Twig templates, you can extend the `Timber\Term` class with your own classes:

```php
class BookGenre extends Timber\Term {

}
```

To initiate your new `BookGenre` term, you also use `Timber::get_term()`.

```php
$term = Timber::get_term( $term_id );
```

In the same way that you [can’t instantiate post objects directly](https://timber.github.io/docs/v2/guides/posts/#extending-timber-post), you **can’t** instantiate a `Timber\Term` object or an object that extends this class with a constructor. Timber will use the [Term Class Map](https://timber.github.io/docs/v2/guides/class-maps/#the-term-class-map) to sort out which class it should use.

## Querying Terms

If you want to get a collection of terms, you can use `Timber::get_terms()`.

```php
$terms = Timber::get_terms( [
    'query' => $query,
] );
```

You can use this function in a similar way to how you use [`WP_Term_Query`](https://developer.wordpress.org/reference/classes/wp_term_query/). If you don’t pass in any argument, Timber will use the global query.

```php
// Using the WP_Term_Query argument format.
$term_query = Timber::get_terms( [
    'query' => [
        'taxonomy' => 'book_genre',
        'count'    => true,
    ],
 ] );
```

Be aware that you need to pass an array to `Timber::get_terms()` with a `query` parameter. This array can have additional arguments that you can check out in the documentation for [`Timber::get_terms()`](https://timber.github.io/docs/v2/reference/timber/#get-terms).

What you get as a return value is not a pure array of posts, but a `Timber\TermCollection` object, which is an `ArrayObject` that is very similar to an array as you know it. To loop over the terms collection in PHP, you first need to convert it to an array with `$terms->get_terms()`.

```php
foreach ( $terms_query->get_terms() as $term ) {
    echo $term->title();
}
```

In Twig, you can directly loop over it.

```twig
{% for term in terms %}
    {{ term.title }}
{% endfor %}
```

When you query for certain post types, Timber will use the [Term Class Map](https://timber.github.io/docs/v2/guides/class-maps/#the-term-class-map) to check which class it should use to instantiate your posts.

### Listing terms in Twig

When you want to display your terms in a textual list, you can make use of Twig filters:

```twig
{# A comma separated list #}
{{ terms|join(', ') }}
```

You don’t have to provide `{{ term.title }}`, because when you use `{{ term }}`, Timber will automatically return a term’s title.

If you want to separate the last item in your list with something else than a comma, use the second argument in Twig’s [join](https://twig.symfony.com/doc/2.x/filters/join.html) filter.

```twig
{{ terms|join(', ', 'and') }}
```

And now, you probably also want to link these terms as well. You can make use of Twig’s [map](https://twig.symfony.com/doc/2.x/filters/map.html) filter. While we use a dot (`.`) in PHP to concatenate strings, we use the tilde (`~`) in Twig.

```twig
{{ terms|map(
    term => '<a href="' ~ term.link ~ '">' ~ term.title ~ '</a>'
)|join(', ', ' and ') }}
```

Or you can use a for loop:

```twig
{% for term in terms -%}
    <a href="{{ term.link }}">{{ term.title }}</a>
    {{ not loop.last ? 1 == loop.revindex0 ? ' and ' : ', ' }}
{%- endfor %}
```

We make use of the `loop` variable in Twig to either display an *and* or a comma.

See how we end the opening tag of the for-loop with `-%}` and start the closing tag with `{%-`? These are [Whitespace Controls](https://twig.symfony.com/doc/2.x/templates.html#whitespace-control) and can come in quite handy. Here, we use them to remove all the superfluous markup whitespace we don’t need.
