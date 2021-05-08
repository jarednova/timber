---
title: "Block Editor (Gutenberg)"
order: "1550"
---

## Using the Block Editor with Timber

Timber works with the Block Editor (also called Gutenberg) out of the box. If you use `{{ post.content }}`, Timber will render all the Gutenberg blocks.

## ACF Blocks

### What are ACF Blocks?

ACF Blocks are an alternative way to create content blocks without advanced JavaScript knowledge. If you want to learn more about them, read the article on [advancedcustomfields.com](https://www.advancedcustomfields.com/blog/acf-5-8-introducing-acf-blocks-for-gutenberg/).

### How to use ACF Blocks with Timber

Before you can start using ACF Blocks, you must install the Advanced Custom Fields 5.8.0-beta version or later.

To create a content block, you first have to register it in **functions.php** or in a separate plugin:

```php
add_action( 'acf/init', 'my_acf_init' );

function my_acf_init() {
    // Bail out if function doesn’t exist.
    if ( ! function_exists( 'acf_register_block' ) ) {
        return;
    }

    // Register a new block.
    acf_register_block( array(
        'name'            => 'example_block',
        'title'           => __( 'Example Block', 'your-text-domain' ),
        'description'     => __( 'A custom example block.', 'your-text-domain' ),
        'render_callback' => 'my_acf_block_render_callback',
        'category'        => 'formatting',
        'icon'            => 'admin-comments',
        'keywords'        => array( 'example' ),
    ) );
}
```

Next, you you have to create your `render_callback()` function:

```php
/**
 *  This is the callback that displays the block.
 *
 * @param   array  $block      The block settings and attributes.
 * @param   string $content    The block content (emtpy string).
 * @param   bool   $is_preview True during AJAX preview.
 */
function my_acf_block_render_callback( $block, $content = '', $is_preview = false ) {
    $context = Timber::context( [
        // Store block values.
        'block'      => $block,
        // Store field values.
        'fields'     => get_fields(),
        // Store $is_preview value.
        'is_preview' => $is_preview,
    ] );

    // Render the block.
    Timber::render( 'block/example-block.twig', $context );
}
```

You create an extra array called `$context` with three values:
- **block** - with all data like block title, alignment etc
- **fields** - all custom fields - also all the fields created in **ACF**
- **is_preview** - returns true during AJAX preview

Finally, you can create the template **block/example-block.twig**:

```twig
{#
/**
 * Block Name: Example block
 *
 * This is the template that displays the example block.
 */
#}

{% if is_preview %}
    <p>I will only appear in the editor.</p>
{% endif %}

<div id="example-{{ block.id }}" class="wrapper">
    <h1>{{ fields.title }}</h1>
    <p>{{ fields.description }}</p>
</div>
<style type="text/css">
    #testimonial-{{ block.id }} {
        background: {{ fields.background_color }};
        color: {{ fields.text_color }};
    }
</style>
```

If you would like to use an external stylesheet both inside of the block editor and the frontend you should add:

```php
function my_acf_block_editor_style() {
    wp_enqueue_style(
        'example_block_css',
        get_template_directory_uri() . '/assets/example-block.css'
    );
}

add_action( 'enqueue_block_assets', 'my_acf_block_editor_style' );
```

For more details about enqueueing assets read the [Block Editor Handbook](https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/#enqueueing-editor-only-block-assets).

### Using repeaters

```
{% for field in fields.repeater %}
    Title: {{ field.title }} <br/>
    Url: {{ field.url }}
{% endfor %}
```

### Using groups

```
Title: {{ fields.group.title }} <br/>
Url: {{ fields.group.url }}
```
