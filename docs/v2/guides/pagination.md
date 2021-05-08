---
title: "Pagination"
order: "1100"
---

There are three types of pagination, which all work differently, but can easily be confused with each other, because they use similar terms:

1. Pagination for archive templates
2. Adjacent post pagination for singular templates
3. Paged content within a post

Let’s look at them in more detail.

## Pagination for archive templates

The pagination for archive pages applies to template files with an active query of multiple posts, like **archive.php** or **home.php**. When you have a lot of posts, you wouldn’t list all of the posts on one page, because that would slow down your site too much. Instead, you would only show a defined number of results and use other pages that show the next set of results. Here’s an example:

- `posts/` – Shows first 10 posts.
- `posts/page/2` – Shows posts 11-20.

### Default pagination

**archive.php**

```php
<?php

$context = Timber::context();

Timber::render( 'archive.twig', $context );
```

Because we’re on an archive page, Timber already prepared a `posts` variable in the context for us. You could then markup the output like so:

**archive.twig**

```twig
<div class="tool-pagination">
	{% if posts.pagination.prev %}
		<a
            href="{{ posts.pagination.prev.link }}"
            class="prev {{ posts.pagination.prev.link|length ? '' : 'invisible' }}"
        >Previous</a>
	{% endif %}

	<ul class="pages">
		{% for page in posts.pagination.pages %}
			<li>
				{% if page.link %}
					<a
                        href="{{ page.link }}"
                        class="{{ page.class }}"
                    >{{ page.title }}</a>
				{% else %}
					<span class="{{ page.class }}">{{ page.title }}</span>
				{% endif %}
			</li>
		{% endfor %}
	</ul>

	{% if posts.pagination.next %}
		<a
            href="{{ posts.pagination.next.link }}"
            class="next {{ posts.pagination.next.link|length ? '' : 'invisible' }}"
        >Next</a>
	{% endif %}
</div>
```

### What if I’m not using the default query?

If you want to overwrite the default query, you can do that by overwriting `posts` in the context with a new query. Make sure to include the `paged` parameter in the query.

**archive-event.php**

```php
<?php

global $paged;

if ( ! isset( $paged ) || ! $paged ) {
    $paged = 1;
}

$context = Timber::context( [
    'posts' => Timber::get_posts( [
        'post_type'      => 'event',
        'posts_per_page' => 5,
        'paged'          => $paged
    ] ),
] );

Timber::render( 'archive-event.twig', $context );
```

### Pagination with `pre_get_posts`

Custom `query_posts` sometimes shows 404 on example.com/page/2. In that case you can also use `pre_get_posts` in your **functions.php** file:

```php
function my_home_query( $query ) {
    if ( $query->is_main_query() && ! is_admin() ) {
        $query->set( 'post_type', [ 'movie', 'post' ] );
    }
}

add_action( 'pre_get_posts', 'my_home_query' );
```

Your **archive.php** or **home.php** template wouldn’t change:

```php
<?php

$context = Timber::context();

Timber::render( 'archive.twig', $context );
```

## Adjacent post pagination for singular templates

A pagination for singular templates works different, because unlike for archive pages, you don’t have a collection of posts. But you can show links to the previous and next posts – they are called **adjacent posts** – with `{{ post.next }}` and `{{ post.prev }}`. These two functions are available on every instance of `Timber\Post`.

**single.twig**

```twig
{% if post.prev %}
    <h3>Previous article</h3>

    <a href="{{ post.prev.link }}">{{ post.prev.title }}</a>
{% endif %}

{% if post.next %}
    <h3>Next article</h3>

    <a href="{{ post.next.link }}">{{ post.next.title }}</a>
{% endif %}
```

The posts are sorted by default. But if you use a plugin like [Simple Custom Post Order](https://wordpress.org/plugins/simple-custom-post-order/) to order posts manually, it will affect the order for `{{ post.next }}` and `{{ post.prev }}` as well.

## Paged content within a post

Paged content is yet another form of pagination that appears in WordPress. You can split the content of a single post into multiple pages and use a pagination to add links to the next and previous pages of the post.

- If you use the **Classic Editor**, you can insert `<!--nextpage-->` wherever you want to add a page break.
- If you use the Block Editor, you can’t use `<!--nextpage-->`. Instead, use the **Page Break** block.

Then, instead of using `{{ post.content }}`, use `{{ post.paged_content }}` to display only the content of the current page.

```twig
{{ post.paged_content }}
```

To display the links for the next and previous pages, you will use `{{ post.pagination }}`. Here’s an example where you would display the links to the next and previous pages.

```twig
{% if post.pagination.next is not empty %}
	<a href="{{ post.pagination.next.link|e('esc_url') }}">Go to next page</a>
{% endif %}

{% if post.pagination.prev is not empty %}
	<a href="{{ post.pagination.prev.link|e('esc_url') }}">Go to previous page</a>
{% endif %}
```

You can also display links to all pages, using an accessible pagination markup.

```twig
{% if post.pagination.pages is not empty %}
    <nav aria-label="pagination">
        <ul>
            {% for page in post.pagination.pages %}
                <li>
                    {% if page.current %}
                        <span aria-current="page">Page {{ page.title }}</span>
                    {% else %}
                        <a href="{{ page.link|e('esc_url') }}">Page {{ page.title }}</a>
                    {% endif %}
                </li>
            {% endfor %}
        </ul>
    </nav>
{% endif %}
```
