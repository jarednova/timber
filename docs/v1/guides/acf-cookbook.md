---
title: "ACF Cookbook"
---

Timber is designed to play nicely with (the amazing) [Advanced Custom Fields](http://www.advancedcustomfields.com/). It's not a requirement, of course.

While data saved by ACF is available via `{{ post.my_acf_field }}` you will often need to do some additional work to get back the _kind_ of data you want. For example, images are stored as image ID#s which you might want to translate into a specific image object. Read on to learn more about those specific exceptions.

## WYSIWYG field (and other requiring text)

```twig
<h3>{{ post.title }}</h3>
<div class="intro-text">
     {{ post.meta('my_wysiwyg_field') }}
</div>
```
This will apply your expected paragraph breaks and other pre-processing to the text. In the past we used `{{ post.get_field('my_wysiwyg_field') }}`, but this is now deprecated. Use `{{ post.meta('my_wysiwyg_field') }}`.


## Image field

You can retrieve an image from a custom field, then use it in a Twig template. The most reliable approach is this: When setting up your custom fields you'll want to save the `image_id` to the field. The image object, url, etc. _will_ work but it's not as fool-proof.

### The quick way (for most situations)

```twig
<img src="{{ Image(post.meta('hero_image')).src }}" />
```

### The long way (for some special situations)

This is where we'll start in PHP.

```php
<?php
/* single.php */
$post = new Timber\Post();
if (isset($post->hero_image) && strlen($post->hero_image)){
	$post->hero_image = new Timber\Image($post->hero_image);
}
$data = Timber::context();
$data['post'] = $post;
Timber::render('single.twig', $data);
```

`Timber\Image` should be initialized using a WordPress image ID#. It can also take URLs and image objects, but that requires extra processing.

You can now use all the above functions to transform your custom images in the same way, the format will be:

```twig
<img src="{{ post.hero_image.src | resize(500, 300) }}" />
```

* * *

## Gallery field

```twig
{% for image in post.meta('gallery') %}
    <img src="{{ Image(image) }}" />
{% endfor %}
```

* * *

## Group field
```twig
{{ post.meta('group').first_field }}
{{ post.meta('group').second_field }}
```
or
```twig
{% set group = post.meta('group') %}
{{ group.first_field }}
{{ group.second_field }}
```
* * *

## Relationship field

The post data returned from a relationship field will not contain the Timber methods needed for easy handling inside of your Twig file. To get these, you'll need to convert them into a proper Timber `Post` object like so:

```twig
{% for item in Post(post.relationship_field) %}
   {{ item.title }}
   {# Do something with item #}
{% endfor %}
```

* * *

## Repeater field

You can access repeater fields within twig files:

```twig
{# single.twig #}
<h2>{{ post.title }}</h2>
<div class="my-list">
	{% for item in post.meta('my_repeater') %}
		<div class="item">
			<h4>{{ item.name }}</h4>
			<h6>{{ item.info }}</h6>
			<img src="{{ Image(item.picture).src }}" />
		</div>
	{% endfor %}
</div>
```

### Nested Repeater fields

When you run `get_field` on an outer ACF field, everything inside is ready to be traversed. You can refer to nested fields via item_outer.inner_repeater

```twig
{% for item_outer in post.meta('outer') %}
     {{item_outer.title}}

     {% for item_inner in item_outer.inner_repeater %}
          {{ item_inner.title }}
     {% endfor %}

{% endfor %}
```

### Troubleshooting Repeaters

A common problem in working with repeaters is that you should only call the `meta` method **once** on an item. In other words if you have a field inside a field (for example, a relationship inside a repeater or a repeater inside a repeater, **do not** call `meta` on the inner field). More:

**DON'T DO THIS: (Bad)**

```twig
{% for gear in post.meta('gear_items') %}
    <h3> {{ gear.brand_name }} </h3>
    {% for gear_feature in gear.meta('features') %}
        <li> {{ gear_feature }} </li>
    {% endfor %}
{% endfor %}
```

**DO THIS: (Good)**

```twig
{% for gear in post.meta('gear_items') %}
    <h3> {{ gear.brand_name }} </h3>
    {% for gear_feature in gear.features %}
        <li> {{ gear_feature }} </li>
    {% endfor %}
{% endfor %}
```

* * *

## Flexible Content field

Similar to repeaters, get the field by the name of the flexible content field:

```twig
{% for media_item in post.meta('media_set') %}
	{% if media_item.acf_fc_layout == 'image_set' %}
		<img src="{{ Image(media_item.image).src }}" />
		<p class="caption">{{ Image(media_item.image).caption }}</p>
		<aside class="notes">{{ media_item.notes }}</aside>
	{% elseif media_item.acf_fc_layout == 'video_set' %}
		<iframe width="560" height="315" src="http://www.youtube.com/embed/{{ media_item.youtube_id }}" frameborder="0" allowfullscreen></iframe>
		<p class="caption">{{ media_item.caption }}</p>
	{% endif %}
{% endfor %}
```

* * *

## Options Page

```php
	<?php
	$context['site_copyright_info'] = get_field('copyright_info', 'options');
	Timber::render('index.twig', $context);
```

```twig
	<footer>{{site_copyright_info}}</footer>
```

### Get all info from your options page

```php
	<?php
	$context['options'] = get_fields('options');
	Timber::render('index.twig', $context);
```

ACF Pro has a built in options page, and changes the `get_fields('options')` to `get_fields('option')`.

```twig
	<footer>{{ options.copyright_info }}</footer>
```

### Use options info site wide

To use any options fields site wide, add the `option` context to your functions.php file

```php
<?php
/* functions.php */
add_filter( 'timber_context', 'mytheme_timber_context'  );

function mytheme_timber_context( $context ) {
    $context['options'] = get_fields('option');
    return $context;
}
```

Now, you can use any of the option fields across the site instead of per template.

```twig
/* footer.twig */
<footer>{{ options.copyright_info }}</footer>
```

* * *

## Getting ACF info

You can grab specific field label data like so:

```php
<?php
/* single.php */
$context["acf"] = get_field_objects($data["post"]->ID);
```

```twig
{{ acf.your_field_name_here.label }}
```

* * *

## Query by custom field value

This example that uses a [WP_Query](http://codex.wordpress.org/Class_Reference/WP_Query) array shows the arguments to find all posts where a custom field called `color` has a value of `red`.

```php
<?php
$args = array(
    'numberposts' => -1,
    'post_type' => 'post',
    'meta_key' => 'color',
    'meta_value' => 'red'
);
$context['posts'] = Timber::get_posts($args);
```
* * *
