---
title: "Timber Docs"
layout: "page"
---

## What is Timber?

Timber helps you create fully-customized WordPress themes faster with more sustainable code.

### Simplifies the way you write your templates

With Timber, you write your HTML with the [Twig Template Engine](https://twig.symfony.com/). This cleans up your theme code. Your PHP file can focus on providing the data or logic and your Twig files can focus 100% on the HTML and display.

Let’s look at a simple example. First, we prepare the data in our PHP file.

**single.php**

```php
<?php

use Timber\Timber;

$context = Timber::context();

Timber::render( 'single.twig', $context );
```

And then we render that data in your Twig template.

**single.twig**

```twig
{% extends 'base.twig' %}

{% block content %}
	<article>
		<h1>{{ post.title }}</h1>

		<img
            src="{{ post.thumbnail.src }}"
            alt="{{ post.thumbnail.alt }}"
        >

		<div class="post-content">
			{{ post.content }}
		</div>
	</article>
{% endblock %}
```

### Unifies interacting with WordPress data

Timber makes Posts, Terms, Users, Comments and Menus more object-oriented. You can use the common WordPress objects in a way that makes sense.

For example, have you ever tried to get the thumbnail of a post? Here’s how that looks like in traditional WordPress themes:

```php
<?php

$thumb_id = get_post_thumbnail_id( $post->ID );
$url      = wp_get_attachment_url( $thumb_id );

?>

<img
    src="<?php echo $url; ?>"
    alt="Thumbnail for <?php echo $post->post_title; ?>"
/>
```

And here’s how it looks like with Timber and Twig:

```twig
<img
    src="{{ post.thumbnail.src }}"
    alt="Thumbnail for {{ post.title }}"
/>
```

### Use it a little, use it a lot

Timber works with your existing themes to unlock new power. You don’t have to throw out everything you already have working. You can replace parts of your theme with Timber bit by bit. Say you want to just include an HTML snippet with a variable from your database:

**home.php**

```php
$data['welcome_message'] = get_option( 'welcome_message' );

Timber::render( 'welcome.twig',  $data );
```

**welcome.twig**

```twig
<p class="intro">{{ welcome_message }}</p>
```

## Who is Timber for?

Timber is for both WordPress pros and rookies:

- **Developers** who want to write their WordPress theme in a way that’s common in the PHP industry.
- **Developers** who are new to WordPress and don’t want to dive deep into the the WordPress way of writing themes.
- **WordPress professionals** who want to take advantage of object-oriented patterns that adhere to [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself) and [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) principles.
- **Agencies and studios** that want to make it easier for teams to develop WordPress themes collaboratively. With Timber, your best WordPress engineer can focus on building the `.php` files with requests from WordPress and pass the data into `.twig` files. Once there, your frontend developer can mark-up data and build out a site’s look-and-feel.
- **Agencies and studios** that want to build their own framework for building WordPress websites. [Lumberjack](https://lumberjack.rareloop.com/) and [Conifer](https://coniferplug.in/) are just two examples.

### When is Timber not the right tool for the job?

Timber does things differently than many WordPress themes and plugins. Naturally, this can create compatibility problems. There are ways to work around these problems, but sometimes they are not really worth it and might feel like you have to use a lot of hacks to make it work. Because of this, we believe that **Timber might not the best tool for every job** when

- you want to connect a lot of existing WordPress plugins and functionality.
- a WordPress plugin is the primary driver of your project like WooCommerce, or Easy Digital Downloads or Events Calendar Pro, just to name some examples.

It’s still possible to make Timber work with pretty much everything, and there are existing integrations for popular plugins (like Advanced Custom Fields). We want to stress that it’s still possible to use Timber only for certain parts of your theme; you can still mix it with traditional WordPress code.
