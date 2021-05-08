---
title: "A single post"
order: "300"
---

Let’s look at a singular post template in more detail, because echoing a post’s title with `{{ post.title }}` is not the only thing you can do.

In this guide, we’re looking at a typical blog post. You can use most of the things we show here for other post types, too.

**single.twig**

```twig
<article>

    <header>
        <h1>{{ post.title }}</h1>

        <p>
            Posted by {{ post.author.name }} <span>&bull;</span>
            <time
                datetime="{{ post.date('Y-m-d H:i:s') }}"
            >{{ post.date }}</time>
        <p>
    </header>

    <section>
        {{ post.content }}
    </section>

</article>
```

In this example, you see that we can also use `post.author`, `post.date` and of course, `post.content` to actually display a post’s content.

The `{{ post }}` variable in Twig is not an array, it’s a PHP object. More specifically, it’s an instance of [`Timber\Post`](https://timber.github.io/docs/reference/v2/timber-post/). When you look at the documentation for `Timber\Post`, you see what else you can access.

If you look for `title` in that post object in PHP, you will see that it’s actually not a property, but a function. In Twig, we don’t have to write `{{ post.title() }}` including the parentheses, we can simply use `{{ post.title }}` to call the function and echo out what it returns. If this is still all confusing to you, then don’t worry. You don’t have to completely understand this just yet.

Apart from the post title, we can also access the assigned author of a post through `{{ post.author }}`. The `author` is another Timber object. It’s not a post object though, but a [`Timber\User`](https://timber.github.io/docs/v2/reference/timber-user/) object. We can get the result of the `name` method through `{{ post.author.name }}`. This displays the name of the author.

There’s also `{{ post.date }}`, but we won’t go into details about it here, because that’s a whole other chapter. You can read more about it in the [Date/Time Guide](https://timber.github.io/docs/guides/v2/date-time/).

## The featured image

If you want to display a post’s image, you can access it through `{{ post.thumbnail }}`. The `thumbnail` is yet another Timber object, a [`Timber\Image`](https://timber.github.io/docs/v2/reference/timber-image/) object.

**single.twig**

```twig
<img
    src="{{ post.thumbnail.src }}"
    alt="{{ post.thumbnail.alt }}"
>
```

But you probably don’t want to use the full size of an image. Use any registered size for your image by passing a parameter to the `src()` function:

```twig
<img
    src="{{ post.thumbnail.src('large') }}"
    alt="{{ post.thumbnail.alt }}"
>
```

There’s much more to images. Learn everything you need to know in the [Images Guide](https://timber.github.io/docs/v2/guides/cookbook-images/).

## Terms and categories

When you want to list all the categories that the blog post is assigned to, then you can use the `post.terms()` function in Twig:

```twig
<ul>
    {% for term in post.terms({ taxonomy: 'category' }) %}
        <li>
            <a href="{{ term.link }}">{{ term.title }}</a>
        <li>
    {% endfor %}
</ul>
```

We just used a [for-loop](https://twig.symfony.com/doc/tags/for.html) here. This works a little different than `foreach` in PHP.

And here, we have yet another Timber object. Each `term` is a [`Timber\Term`](https://timber.github.io/docs/v2/reference/timber-term/) object. For terms, you also have methods like `title` or `link`. To learn more about how terms are handled in Timber, refer to the [Terms Guide](https://timber.github.io/docs/v2/guides/terms/).

## Add your own data

This is what a PHP template for a singular post in Timber looks like:

**single.php**

```php
<?php

$context = Timber::context();

Timber::render( 'single.twig', $context );
```

For very basic themes, this is all you need to start working. But for more advanced functionality, you might have to add your own data. For that, you can add to `$context`.

Here’s an example where you would call a function to calculate the reading time for a post.

```php
<?php

$context = Timber::context();
$post    = $context['post'];

$context['reading_time'] = reading_time( $post );

Timber::render( 'single.twig', $context );
```

Now, you can head on to the next chapter about [Post Archives](https://timber.github.io/docs/v2/getting-started/a-post-archive/).

If you want to know more about advanced methods to extend Timber, you can learn about it in the [Extending Timber Guide](https://timber.github.io/docs/guides/v2/extending-timber/).
