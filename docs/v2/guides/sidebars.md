---
title: "Sidebars"
order: "1200"
---

So you want a sidebar?

## Method 1: PHP file

Let's say every page on the site has the same content going into its sidebar. If so, you would:
Create a **sidebar.php** file in your theme directory (so **wp-content/themes/mytheme/sidebar.php**)

**sidebar.php**

```php
<?php

$context = array();
$context['widget'] = my_function_to_get_widget();
$context['ad'] = my_function_to_get_an_ad();
Timber::render('sidebar.twig', $context);
```

Use that php file within your main PHP file (home.php, single.php, archive.php, etc):

**single.php**

```php
<?php

$context = Timber::context( [
    'sidebar' => Timber::get_sidebar( 'sidebar.php' ),
] );

Timber::render( 'single.twig', $context );
```

In the final twig file make sure you reserve a spot for your sidebar:

**single.twig**

```twig
<aside class="sidebar">
	{{ sidebar }}
</aside>
```

## Method 2: Twig file

In this example, you would populate your sidebar from your main PHP file (home.php, single.php, archive.php, etc).

Make a Twig file for what your sidebar should be:

**views/sidebar-related.twig**

```twig
<h3>Related Stories</h3>

{% for post in related %}
	<h4><a href="{{ post.get_path }}">{{ post.post_title }}</a></h4>
{% endfor %}
```

Send data to it via your main PHP file:

**single.php**

```php
$post     = Timber::get_post();
$post_cat = $post->get_terms( 'category' );

$post_cat = $post_cat[0]->ID;

$sidebar_context = array(
	'related' => Timber::get_posts( [
	    'cat' => $post_cat
    ] ),
);

$context = Timber::context( [
    'post'    => $post,
    'sidebar' => Timber::get_sidebar(
        'sidebar-related.twig',
        $sidebar_context
    ),
] );

Timber::render( 'single.twig', $context );
```

In the final twig file, make sure you have spot for your sidebar:

**single.twig**

```twig
<aside class="sidebar">
	{{ sidebar }}
</aside>
```

## Method 3: Dynamic

This is using WordPress's built-in dynamic_sidebar tools (which, confusingly, are referred to as "Widgets" in the interface). Since sidebar is already used; I used widgets in the code to describe these:

```php
<?php
$context = [
    'dynamic_sidebar' => Timber::get_widgets( 'dynamic_sidebar' ),
];

Timber::render('sidebar.twig', $context);
```

```twig
<aside class="my-sidebar">
    {{ dynamic_sidebar }}
</aside>
```
