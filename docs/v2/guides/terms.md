---
title: "Terms"
order: "120"
---

## Get a term

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

You can convert terms IDs to term objects in Twig using the `get_term()` function.

```twig
{% set term = get_term(term_id) %}
```

If you have an array of terms IDs that you want to convert to `Timber\Term` objects, you can use `get_terms()`.

```twig
{% for term in get_terms(term_ids) %}

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

If you want to get an array of terms, you can use `Timber::get_terms()`.

```php
$terms = Timber::get_terms();
```

If you don’t pass in any argument, Timber will use [`get_taxonomies()`](https://developer.wordpress.org/reference/functions/get_taxonomies/) to get a list of terms from all taxonomies.

You can pass the same arguments to this function that you know from using [`WP_Term_Query`](https://developer.wordpress.org/reference/classes/wp_term_query/).

```php
// Using the WP_Term_Query argument format.
$terms = Timber::get_terms( [
    'taxonomy' => 'book_genre',
    'count'    => true,
 ] );
```

Also check out the documentation for [`Timber::get_terms()`](https://timber.github.io/docs/v2/reference/timber-timber/#get_terms).

You get array of terms as a return value that you can loop over.

```php
foreach ( $terms as $term ) {
    echo $term->title();
}
```

In Twig, you can do the same.

```twig
{% for term in terms %}
    {{ term.title }}
{% endfor %}
```

When you query for terms, Timber will use the [Term Class Map](https://timber.github.io/docs/v2/guides/class-maps/#the-term-class-map) to check which class it should use to instantiate your terms.

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
