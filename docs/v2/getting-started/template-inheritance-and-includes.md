---
title: "Template inheritance and includes"
order: "200"
---

## Template inheritance

You might have realized that there’s no HTML skeleton around the HTML for a post yet. We’re missing a `<head>` and a `<body>` and the navigation and the footer section. Let’s get to that.

First, create a basic post template.

**single.twig**

```twig
{% extends 'base.twig' %}

{% block content %}

    <article>

        <header>
            <h1>{{ post.title }}</h1>
        </header>

        <section>
            {{ post.content }}
        </section>

    </article>

{% endblock %}
```

The part that you should take note of here is this one:

```twig
{% extends 'base.twig' %}
```

You use the [extend](https://twig.symfony.com/doc/tags/extends.html) tag to tell the template engine that this template *extends* another template. This means that **single.twig** is using **base.twig** as its parent template. Here’s what your **base.twig** could look like:

```twig
<!DOCTYPE html>
<html>
    <head>
        {% block head %}
            <title>{{ site.title }}</title>
        {% endblock %}
    </head>

    <body>
        <main>
            {% block content %}
            {% endblock %}
        </main>

        <footer>
            {% block footer %}
                <p>Brought to you with ❤ by the Timber.</p>
            {% endblock %}
        </footer>
    </body>
</html>
```

If you look at **single.twig** again, you see opening and closing block declarations that surround the main page contents.

```twig
{% extends 'base.twig' %}

{% block content %}

    {# Other stuff here ... #}

{% endblock %}
```

Twig will take the content inside the `{% block %}` tag and put it where you defined your block in **base.twig**. Everything in between `{% block content %}` and `{% endblock %}` in your **single.twig** will go into your `<main>` tag in your **base.twig**.

Blocks are one of the most important and powerful concepts in managing your templates. The official [Twig Documentation](http://twig.sensiolabs.org/doc/templates.html#template-inheritance) has more details.

While you can define your own custom number of blocks, you can also create any number of base files to extend from (we use the name "base" as a naming convention, but it’s not required).

## Nesting Blocks and Multiple Inheritance

Here is where things get really cool. While most people use a lot of [PHP includes](https://www.php.net/manual/en/function.include.php) after each other, in Twig you can create infinite levels of nested blocks to control your page templates. For example, let’s say you occasionally want to replace the title/headline on your **single.twig** template with a custom image or typography.

For this introduction, let’s assume that the name of the page is "All about Jared" (making the slug of the page `all-about-jared`). First, we’re going to surround the part of the template we want to control with block declarations:

**single.twig**

```twig
{% extends 'base.twig' %}

{% block content %}
    <article
        class="article post-type-{{ post.post_type }}"
        id="post-{{ post.ID }}"
    >
        <section class="article-content">
            {% block headline %}
                <header>
                    <h1 class="article-title">{{ post.title }}</h1>
                    <p role="doc-subtitle">{{ post.subtitle }}</h2>
                </header>
            {% endblock %}

            <p class="article-author"><span>By</span> {{ post.author.name }} <span>&bull;</span> {{ post.post_date }}</p>

            {{ post.content }}
        </section>
    </article>
{% endblock %}
```

Compared to the earlier example, we now have the `{% block headline %}` bit surrounding the `<header>` tag of the post.

To inject a custom bit of markup, we’re going to create a file called **single-all-about-jared.twig** in the **views** directory. The logic for which template should be selected is controlled in **single.php**, but generally follows WordPress conventions on [Template Hierarchy](https://wphierarchy.com/).

Now, to replace the `headline` block **single.twig**, we define it in our new file.

**single-all-about-jared.twig**

```twig
{% extends 'single.twig' %}

{% block headline %}
    <header>
        <h1>Jared and his mug</h1>

        <img
            src="/wp-content/uploads/2014/05/jareds-face.jpg"
            alt="Jared’s Mug"
        />
    </header>
{% endblock %}
```

So there are two big concepts going on here:

1. **Multiple Inheritance:** We’re extending **single.twig**, which itself extends **base.twig**. Thus we stay true to the [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself) principle and don’t have very similar code between our two templates hanging around.
2. **Nested Blocks:** `{% block headline %}` is located inside `{% block content %}`. So while we’re replacing the headline, we get to keep all the other markup and variables found in the parent template.

What if you wanted to **add** something to the block as opposed to replace? In this case you can [`{{ parent() }}`](https://twig.symfony.com/doc/functions/parent.html) where the parent block’s content should go.

```twig
{% extends "single.twig" %}

{% block headline %}
    {{ parent() }}

    <p class="reading-time">Reading time: 15 min</p>
{% endblock %}
```

## Includes

Another very useful functionality is Twig’s [include](https://twig.symfony.com/doc/functions/include.html) function. You can use it to extract reusable parts of your theme and build your own component library.

Let’s the example of the headline again.

**single.twig**

```twig
{% extends 'base.twig' %}

{% block content %}
    <article
        class="article post-type-{{ post.post_type }}"
        id="post-{{ post.ID }}"
    >
        <section class="article-content">
            {% block headline %}
                {{ include('header.twig') }}
            {% endblock %}

            {{ include('article-author.twig') }}

            {{ post.content }}
        </section>
    </article>
{% endblock %}
```

With the include function, you don’t have to pass down all the variables you want to use, they will be available automatically in the included template.

You’re free to choose what folder structure you’ll want to use for your views. You can place all in the same folder, or you can load them from nested subfolders.

The following are all fine.

```twig
{{ include('header.twig') }}
{{ include('partials/header.twig') }}
{{ include('components/post/header.twig') }}
```

There’s a couple of neat functionalities when using `include()`, so it’s definitely worth reading through its [documentation](https://twig.symfony.com/doc/functions/include.html).

## Use it a little, use it a lot

We want to mention here that if you want to use Timber, you don’t have to rewrite your whole theme with Timber. You can start replacing different parts of your theme with Timber. Instead of using [`get_template_part()`](https://developer.wordpress.org/reference/functions/get_template_part/) or [`get_extended_template_part()`](https://github.com/johnbillion/extended-template-parts), you can use `Timber::render()`.

Here’s an example from the [Twenty Twenty theme](https://github.com/WordPress/twentytwenty).

**singular.php**

```php
<?php get_header(); ?>

<main id="site-content" role="main">

	<?php

	if ( have_posts() ) {

		while ( have_posts() ) {
			the_post();

			get_template_part( 'template-parts/content', get_post_type() );
		}
	}

	?>

</main>

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>

<?php get_footer(); ?>
```

If you wanted to introduce Timber, you could start replacing different parts of the content with `Timber::render()`. In the following example, we display a post’s content with Timber.

```php
<?php get_header(); ?>

<main id="site-content" role="main">

    <?php
        $template = sprintf( 'content-%s.twig', get_post_type() );
        $post     = Timber::get_post();

        $post->setup();

        Timber::render( $template, [
            'post' => $post,
        ] );
    ?>

</main>

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>

<?php get_footer(); ?>
```

---

Read more about what else you can do in [singular post templates](https://timber.github.io/docs/v2/getting-started/a-single-post/).
