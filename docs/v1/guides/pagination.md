---
title: "Pagination"
---

Do you like pagination? Stupid question, of course you do. Well, here's how you do it.

## Default pagination

This will only work in a php file with an active query (like `archive.php` or `home.php`):

```php
	<?php
	$context = Timber::context();
	$context['posts'] = new Timber\PostQuery();
	Timber::render('archive.twig', $context);
```

You can then markup the output like so  (of course, the exact markup is up to YOU):

```twig
<div class="tool-pagination">
	{% if posts.pagination.prev %}
		<a href="{{posts.pagination.prev.link}}" class="prev {{posts.pagination.prev.link|length ? '' : 'invisible'}}">Prev</a>
	{% endif %}
	<ul class="pages">
		{% for page in posts.pagination.pages %}
			<li>
				{% if page.link %}
					<a href="{{page.link}}" class="{{page.class}}">{{page.title}}</a>
				{% else %}
					<span class="{{page.class}}">{{page.title}}</span>
				{% endif %}
			</li>
		{% endfor %}
	</ul>
	{% if posts.pagination.next %}
		<a href="{{posts.pagination.next.link}}" class="next {{posts.pagination.next.link|length ? '' : 'invisible'}}">Next</a>
	{% endif %}
</div>
```

## What if I'm not using the default query?

```php
	<?php
	global $paged;
	if (!isset($paged) || !$paged){
		$paged = 1;
	}
	$context = Timber::context();
	$args = array(
		'post_type' => 'event',
		'posts_per_page' => 5,
		'paged' => $paged
	);
	$context['posts'] = new Timber\PostQuery($args);
	Timber::render('page-events.twig', $context);
```

## Pagination with pre_get_posts

Custom `query_posts` sometimes shows 404 on example.com/page/2. In that case you can also use `pre_get_posts` in your functions.php file:

```php
	<?php
	function my_home_query( $query ) {
	  if ( $query->is_main_query() && !is_admin() ) {
		$query->set( 'post_type', array( 'movie', 'post' ));
	  }
	}
	add_action( 'pre_get_posts', 'my_home_query' );
```
In your archive.php or home.php template:

```php
	<?php
	$context = Timber::context();
	$context['posts'] = new Timber\PostQuery();
	Timber::render('archive.twig', $context);
```
