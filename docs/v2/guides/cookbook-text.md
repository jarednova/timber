---
title: "Text Cookbook"
order: "300"
---

There’s tons of stuff you can do with Twig and Timber filters to make complex transformations easy (and fun).

## Dates

### Example 1: Bylines

Timber does bylines like a boss:

```twig
<p class="byline">
    <span class="name">By {{ post.author.name }}</span>
    <span class="date">{{ post.post_date|date('F j, Y') }}</span>
</p>
```

**Renders**

```html
<p class="byline"><span class="name">By Mr. WordPress</span><span class="date">September 28, 2013</span></p>
```

### Example 2: Copyright year

Nothing is worse than an out-of-date copyright year in the footer. Nothing.

```twig
<footer>
	<p class="copyright">&copy; {{ now|date('Y') }} by {{ bloginfo('name') }}</p>
</footer>
```

**Renders**

```html
<footer><p class="copyright">&copy; 2015 by The Daily Orange</p></footer>
```

## Standard transforms

### Run WordPress’ auto-paragraph filter

```twig
<p class="content">{{ post.my_custom_text|wpautop }}</p>
```

### Run WordPress shortcodes over a block of text

```twig
<p class="content">{{ post.my_custom_text|shortcodes }}</p>
```

### Code samples

Code Samples? Lord knows I’ve got ’em:

```twig
<div class="code-sample">{{ post.code_samples|pretags }}</div>
```

### Calling PHP functions inside of your templates

WordPress template:

```html
<p class="entry-meta"><?php twentytwelve_entry_meta(); ?></p>
```

Twig template:

```twig
<p class="entry-meta">{{ function('twentytwelve_entry_meta') }}</p>
```

You can read more about using functions in the [Functions](https://timber.github.io/docs/v2/guides/functions/) guide.
