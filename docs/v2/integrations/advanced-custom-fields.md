---
title: "Advanced Custom Fields"
---

Timber is designed to play nicely with the amazing [Advanced Custom Fields](http://www.advancedcustomfields.com/) plugin.

## Getting data from ACF

If you’ve worked with ACF before, you’re use to use `get_field( 'my_acf_field' )` all the time. In Timber, getting data from ACF works the same way as getting WordPress’s native meta data in general:

**Twig**

```twig
{{ post.meta('my_acf_field') }}
```

**PHP**

```php
$meta = $post->meta( 'my_acf_field' );
```

### Unformatted values

In ACF, all values are filtered. If you want to use unfiltered, raw values from the database, you’re probably used to using the third parameter for `get_field()`, which is called `$format_value`. This defaults to true. In Timber, you’d pass it like so:

**Twig**

```twig
{{ post.meta('my_acf_field', { format_value: false }) }}
```

**PHP**

```php
$meta = $post->meta( 'my_acf_field', [
    'format_value' => false,
] );
```

You can also use the **faster `raw_meta()` method**, which accesses values directly from the database and bypasses any ACF filters:

**Twig**

```twig
{{ post.raw_meta('my_acf_field') }}
```

## WYSIWYG Field (and other requiring text)

```twig
<h3>{{ post.title }}</h3>

<div class="intro-text">
    {{ post.meta('my_wysiwyg_field') }}
</div>
```

This will apply your expected paragraph breaks and other pre-processing to the text.

## Image Field

You can retrieve an image from a custom field, then use it in a Twig template. The most reliable approach is this: When setting up your custom fields, you’ll want to use `Image ID` as the field's return value. The image object, URL, etc. _should_ work, but it’s not as fool-proof.

### The quick way (for most situations)

```twig
<img src="{{ get_image(post.meta('hero_image')).src }}" />
```

### The long way (for some special situations)

This is where we’ll start in PHP.

**single.php**

```php
$post = Timber::get_post();

if (isset($post->hero_image) && strlen($post->hero_image)){
    $post->hero_image = Timber::get_image( $post->hero_image );
}

$context = Timber::context( [
    'post' => $post,
] );

Timber::render('single.twig', $context);
```

`Timber\Image` should be initialized using a WordPress image ID. It can also take URLs and image objects, but that requires extra processing.

You can now use all the above functions to transform your custom images in the same way, the format will be:

```twig
<img src="{{ post.hero_image.src | resize(500, 300) }}" />
```

## Gallery Field

```twig
{% for image in post.meta('gallery') %}
    <img src="{{ get_image(image) }}" />
{% endfor %}
```

## Group Field

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

## Relationship field

The post data returned from a relationship field will not contain the Timber methods needed for easy handling inside of your Twig file. To get these, you’ll need to convert them into proper `Timber\Post` objects using `get_posts()`:

```twig
{% for item in get_posts(post.relationship_field) %}
   {{ item.title }}
   {# Do something with item #}
{% endfor %}
```

## Repeater Field

You can access repeater fields within Twig files:

**single.twig**

```twig
<h2>{{ post.title }}</h2>
<div class="my-list">
    {% for item in post.meta('my_repeater') %}
        <div class="item">
            <h4>{{ item.name }}</h4>
            <h6>{{ item.info }}</h6>
            <img src="{{ get_image(item.picture).src }}" />
        </div>
    {% endfor %}
</div>
```

### Nested Repeater fields

When you run `meta` on an outer ACF field, everything inside is ready to be traversed. You can refer to nested fields via item_outer.inner_repeater

```twig
{% for item_outer in post.meta('outer') %}
     {{ item_outer.title }}

     {% for item_inner in item_outer.inner_repeater %}
          {{ item_inner.title }}
     {% endfor %}

{% endfor %}
```

### Troubleshooting Repeaters

A common problem in working with repeaters is that you should only call the `meta` method **once** on an item. In other words if you have a field inside a field (for example, a relationship inside a repeater or a repeater inside a repeater, **do not** call `meta` on the inner field). More:

**DON’T DO THIS: (Bad)**

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

## Flexible Content Field

Similar to repeaters, get the field by the name of the flexible content field:

```twig
{% for media_item in post.meta('media_set') %}
    {% if media_item.acf_fc_layout == 'image_set' %}
        <img src="{{ get_image(media_item.image).src }}" />
        <p class="caption">{{ get_image(media_item.image).caption }}</p>
        <aside class="notes">{{ media_item.notes }}</aside>
    {% elseif media_item.acf_fc_layout == 'video_set' %}
        <iframe width="560" height="315" src="http://www.youtube.com/embed/{{media_item.youtube_id}}" frameborder="0" allowfullscreen></iframe>
        <p class="caption">{{ media_item.caption }}</p>
    {% endif %}
{% endfor %}
```

### Repeater in Flexible Content Field

Similar to nested repeaters, you should only call the `meta` method once when you use a repeater field inside a flexible content field:

```twig
{% for media_item in post.meta('media_set') %}
    {% if media_item.acf_fc_layout == 'image_set' %}
        {% for image_item in media_item.image_set %}
            <img src="{{ get_image(image_item.image).src }}" />
            <p class="caption">{{ get_image(image_item.image).caption }}</p>
            <aside class="notes">{{ image_item.notes }}</aside>
        {% endfor %}
    {% endif %}
{% endfor %}
```

### Split Flexible Content Fields into includes / chunks

We can break Flexible Content Fields into small blocks with include files utilizing the `acf_fc_layout` value. This way you have more flexibility and reusability for included sections. For instance, this is a great way to build landing pages that re-use the same blocks in different configurations.

You could use a **blocks** subdirectory where you put all your Twig template files for your blocks. For example:
**wp-content/themes/example-theme/views/blocks**.

```twig
{% for block in post.meta( 'blocks' ) %}
    {{ include("blocks/#{ block.acf_fc_layout|sanitize ) }.twig", {
        block: block
    }) }}
{% endfor %}
```

The [sanitize](https://timber.github.io/docs/v2/guides/filters/#sanitize) filter will slugify the block name.

Consider this example for a Flexible Content Field named `Photo` with a text field named `credit`. You would create a **blocks/photo.twig** file which is automatically included:

```twig
<p>Photo by: {{ block.credit }}</p>
```

To prevent errors with include files that can’t be found, you can optionally use the `ignore_missing` parameter for `include()`:

```twig
{% for block in post.meta( 'blocks' ) %}
    {{ include("blocks/#{ block.acf_fc_layout|sanitize ) }.twig", {
        block: block
    }, ignore_missing = true) }}
{% endfor %}
```

## Options Page

**PHP**

```php
$context['site_copyright_info'] = get_field( 'copyright_info', 'options' );

Timber::render('index.twig', $context);
```

**Twig**

```twig
<footer>{{ site_copyright_info }}</footer>
```

### Get all info from your options page

```php
$context['options'] = get_fields( 'options' );

Timber::render( 'index.twig', $context );
```

ACF Pro has a built in options page, and changes the `get_fields( 'options' )` to `get_fields( 'option' )`.

```twig
<footer>{{ options.copyright_info }}</footer>
```

### Use options info site wide

To use any options fields site wide, add the `option` context to your **functions.php** file:

```php
add_filter( 'timber/context', 'global_timber_context' );

/**
 * Filters global context.
 *
 * @param array $context An array of existing context variables.
 * @return mixed
 */
function global_timber_context( $context ) {
    $context['options'] = get_fields( 'option' );

    return $context;
}
```

Now, you can use any of the option fields across the site instead of per template.

**footer.twig**

```twig
<footer>{{ options.copyright_info }}</footer>
```

## Getting ACF info

You can grab specific field label data like so:

**single.php**

```php
$context['acf'] = get_field_objects( $data['post']->ID );
```

```twig
{{ acf.your_field_name_here.label }}
```
