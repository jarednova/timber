---
title: "A post archive"
order: "400"
---

Here’s how a PHP template file for a WordPress archive looks like.

**index.php**

```php
<?php

$context = Timber::context();

Timber::render( 'index.twig', $context );
```

For basic archives, that’s all you need. Behind the scenes, Timber already prepared a `posts` variable for you that holds all the posts that you would normally find in [The Loop](https://developer.wordpress.org/themes/basics/the-loop/).

You can then loop over your posts with a [for-loop in Twig](https://twig.symfony.com/doc/tags/for.html). Here’s what your archive page could look like.

**index.twig**

```twig
{% extends "base.twig" %}

{% block content %}
    {% if posts is not empty %}
        <ul>
            {% for post in posts %}
                <li>{{ include('teaser.twig') }}</li>
            {% endfor %}
        <ul>
    {% endif %}

	{{ include('pagination.twig') }}
{% endblock %}
```

## A post teaser

Your teaser could look like this. We used the markup for an [Inclusive Card](https://inclusive-components.design/cards/) as an example.

**teaser.twig**

```twig
<img
    src="{{ post.thumbnail.src('medium') }}"
    alt="{{ post.thumbnail.alt }}"
>

<h2>
    <a
        href="{{ post.link }}"
        aria-describedby="desc-{{ post.slug }}"
    >{{ post.title }}</a>
</h2>

<p>{{ post.excerpt }}</p>

<span
    id="desc-{{ post.slug }}"
    aria-hidden="true"
>Read more</span>

<small>By {{ post.author }}</small>
```

There are two new things that you see here:

- `{{ post.slug }}` – This property is the same as `post.post_name`, which is the version of a post’s title that is safe to use in URLs and will be used for permalinks.
- `{{ post.excerpt }}` – This is an advanced function that pulls in the excerpt of a post, if it exists. Otherwise, it will generate an excerpt from your post’s content. Check out the documentation for [`Timber\Post::excerpt()`](https://timber.github.io/docs/reference/v2/timber-post/#excerpt) to learn more about the parameters you can control the output with.

## Using custom queries

Sometimes you’ll want to use your own queries for archive pages or to display a list of posts in other places. For that, you can use `Timber::get_posts()`. Here’s an example for a more complex query, that selects posts that have certain movie genre and actor terms assigned. The parameters you use are the same as those for [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/).

```php
<?php

$args = [
    'post_type' => 'post',
    'tax_query' => [
        'relation' => 'AND',
        [
            'taxonomy' => 'movie_genre',
            'field'    => 'slug',
            'terms'    => [ 'action', 'comedy' ]
        ],
        [
            'taxonomy' => 'actor',
            'field'    => 'id',
            'terms'    => [ 103, 115, 206 ],
            'operator' => 'NOT IN'
        ]
    ]
];

$context['posts'] = Timber::get_posts( $args );
```

### An example: related posts

You might want to show teasers for other posts from the same category at the bottom of a singular post. How would we do that?

First, you would prepare the data in your PHP template. For this, we add `related_posts` to `$context`.

**single.php**

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

And then, in your singular view, you would loop over them. We can also reuse the **teaser.twig** view, that we introduced earlier.

**single.twig**

```twig
{% if related_posts is not empty %}
    <ul>
        {% for post in related_posts %}
            <li>{{ include('teaser.twig') }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

You can read more about custom queries in the [Posts Guide](https://timber.github.io/docs/v2/guides/posts/#querying-posts).
