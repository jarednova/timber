---
title: "Image Cookbook"
---

Timber makes it damn easy to use an image in a tag.

Automatically, Timber will interpret images attached to a post’s thumbnail field ("Featured Image" in the admin) and treat them as instances of [Timber\Image](https://timber.github.io/docs/reference/timber-image/). Then, in your Twig templates, you can access them via `{{ post.thumbnail }}`.

## Basic image stuff

Again, pretty damn easy:

```twig
<img src="{{ post.thumbnail.src }}" class="my-thumb-class" alt="Image for {{ post.title }}" />
```

## Use a WordPress image size

You can use WordPress image sizes (including ones you register with your theme/plugin) by passing the name of the size to `src` like so:

```twig
<img src="{{ post.thumbnail.src('medium') }}" class="my-thumb-class" alt="Image for {{ post.title }}" />
```

Note: If the WordPress size (e.g `medium`) has not been generated, it will return an empty string.

## Arbitrary resizing of images

Want to resize an image? Here we’re going to use [Twig Filters](http://twig.sensiolabs.org/doc/filters/index.html).

```twig
<img src="{{ post.thumbnail.src|resize(300, 200) }}" />
```

The first parameter is `width`, the second is `height` (optional). So if you don’t know the height of the image, but still want to scale it proportionally, you can do:

```twig
<img src="{{ post.thumbnail.src|resize(640) }}" />
```

All of these filters are written specifically to interact with WordPress’s image API. So don’t worry, no weird TimThumb stuff going on—this is all using WordPress’s internal image sizing stuff.

Be aware of the limitations of this function [when working with a CDN](https://timber.github.io/docs/guides/cookbook-images/#limitations-when-working-with-a-cdn).

## Letterboxing images

Let’s say you have an image that you want to contain to a certain size without any cropping. If the proportions don’t fit you’ll letterbox the extra space. I find this is really useful when getting logos to all appear next to each other. You can do this with:

```twig
<img src="{{ post.thumbnail.src|letterbox(400, 400, '#FFFFFF') }}" />
```

Here `width` and `height` are required. The third argument is the background color in hex format (default is `#000000`).

## Converting images

Let’s say your client or editor is a bit lazy and they resort to PNGs where only JPGs are required. We have all seen this a lot. People will just upload screenshots that are saved by default as PNGs. No problemo!

```twig
<img src="{{ post.thumbnail.src|tojpg }}" />
```

You can use this in conjunction with other filters.

```twig
<img src="{{ post.thumbnail.src|tojpg|resize(300, 300) }}" />
```

Filters are executed from left to right. You’ll probably want to convert to JPG before running the resizing, etc.

### Using WebP images

Similar to the `|tojpg` filter, there’s a `|towebp` filter.

```twig
<picture>
   <source srcset="{{ post.thumbnail.src|towebp }}" type="image/webp">
   <source srcset="{{ post.thumbnail.src|tojpg }}" type="image/jpeg">
   <img src="{{ post.thumbnail.src|tojpg }}" alt="{{ post.title }}">
</picture>
```

## Generating retina sizes

You can use Timber to generate @2x image sizes for retina devices. For example, using `srcset`:

```twig
<img src="{{ post.thumbnail.src }}" srcset="{{ post.thumbnail.src|retina(1) }} 1x,
    {{ post.thumbnail.src|retina(2) }} 2x,
    {{ post.thumbnail.src|retina(3) }} 3x,
    {{ post.thumbnail.src|retina(4) }} 4x">
```

Unfortunately, it’s not possible to use the `|retina()` filter in combination with `|resize()`, because it would create an upscaled image. We are working on making this work eventually.

## Using images in custom fields

Let’s say you're using a custom field plugin (like the amazing [Advanced Custom Fields](http://www.advancedcustomfields.com/)). You can use the resulting images in your Twig templates very easily.

When setting up your custom fields you’ll want to save the `image_id` to the field. The image object, url, etc. _will_ work but it’s not as fool-proof.

### The quick way (for most situations)

```twig
{% set image = Image(post.hero_image) %}

<img src="{{ image.src }}" alt="{{ image.alt }}" />
```

### The long way (for some special situations)

This is where we’ll start in PHP.

**single.php**

```php
<?php
$post = Timber::get_post();

if ( isset( $post->hero_image ) && strlen( $post->hero_image ) ) {
    $post->hero_image = new Timber\Image( $post->hero_image );
}

$data = Timber::context();
$data['post'] = $post;
Timber::render( 'single.twig', $data );
```

`Timber\Image` should be initialized using a WordPress image ID. It can also take URLs and image objects, but that requires extra processing.

You can now use all the above functions to transform your custom images in the same way. The format will be:

```twig
<img src="{{ post.hero_image.src|resize(500, 300) }}" />
```

## Limitations when working with a CDN

Timber’s image functions may be somewhat limited when using a CDN.
There are [differences between Pull CDN and Push CDN](https://cdn.net/push-vs-pull-cdn/).

### Using a Pull CDN

You should be **fine when you use a Pull CDN**, because there, you pass in which directories of your website you want to map to your CDN. The links to assets in those directories will be automatically replaced with the links to the CDN in the resulting HTML. The Pull CDN will «pull» these files to the server, if they don’t exist yet.

### Using a Push CDN

When you use a Push CDN, you need to be aware of the following limitation:

**When you use a filter that creates a new image**, like `|resize()`, `|letterbox()` or **a filter that converts your image to another format**, like `|tojpg` or `|towebp`, it probably won’t work. With these filters, Timber creates a new file locally. But WordPress doesn’t know about that file, because it’s not associated with a WordPress image size registered through `add_image_size()`. Because of that, some CDN plugins will not be able to upload/push that image to the CDN server, because they don’t know the file exists, either.

**You are safe if you use** `{{ image.src }}` or if you get the source of a WordPress image size, like `{{ image.src('large') }}`.

Examples for plugins that work with Push CDN:

- [WP Offload Media Lite](https://wordpress.org/plugins/amazon-s3-and-cloudfront/)
