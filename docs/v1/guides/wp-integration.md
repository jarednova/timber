---
title: "WordPress Integration"
---

Timber plays nicely with your existing WordPress setup. You can still use other plugins, etc.

## the_content

You’re probably used to call `the_content()` in your theme file. This is good. Before outputting, WordPress will run all the filters and actions that your plugins and themes are using. If you want to get this into your new Timber theme (and you probably do), call it like this:

```twig
<div class="my-article">
    {{ post.content }}
</div>
```

This differs from `{{ post.post_content }}`, which will display the raw text stored in the database.

## Hooks

Timber hooks to interact with WordPress use `this/style/of_hooks` instead of `this_style_of_hooks`. This matches the same methodology as [Advanced Custom Fields](http://www.advancedcustomfields.com/resources/#actions).

Full documentation to come.

## Actions

Call them in your Twig template...

```twig
{% do action('my_action') %}
{% do action('my_action_with_args', 'foo', 'bar') %}
```

... in your `functions.php` file:

```php
<?php
add_action( 'my_action', 'my_function' );

function my_function( $context ) {
    // $context stores the template context in case you need to reference it

    // Outputs title of your post
    echo $context['post']->post_title;
}
```

```php
<?php
add_action( 'my_action_with_args', 'my_function_with_args', 10, 2 );

function my_function_with_args( $foo, $bar ){
    echo 'I say ' . $foo . ' and ' . $bar;
}
```

You can still get the context object when passing args, it’s always the _last_ argument...

```php
<?php
add_action( 'my_action_with_args', 'my_function_with_args', 10, 3 );

function my_function_with_args( $foo, $bar, $context ){
    echo 'I say ' . $foo . ' and ' . $bar;
    echo 'For the post with title ' . $context['post']->post_title;
}
```

Please note the argument count that WordPress requires for `add_action`.

## Filters

Timber already comes with a [set of useful filters](/docs/guides/filters/). If you have your own WordPress filters that you want to easily apply in Twig, you can use `apply_filters`.

```twig
{{ post.content|apply_filters('default_message') }}
{{ "my custom string"|apply_filters('default_message', param1, param2, ...) }}
```

You can use your filter with a [Twig filter tag](https://twig.symfony.com/doc/2.x/tags/filter.html).

```twig
{% filter apply_filters( 'default_message') %}
        {{ post.content }}
{% endfilter %}

{% filter apply_filters('default_message', 'foo', 'bar, 'baz' ) %}
        I love pizza
{% endfilter %}
```

In __PHP__, you can get the content of the block with the first parameter and the rest of parameters like that.

```php
add_filter( 'default_message', 'my_default_message', 10, 4 );

function my_default_message( $tag, $param1, $param2, $param3 ) {
       var_dump( $tag, $param1, $param2, $param3 ); // 'I love pizza', 'foo', 'bar, 'baz'
              
       echo 'I have a message: ' . $tag; // I have a message: I love pizza
}
```

### Real world example with WooCommerce

Sometimes in __WooCommerce__ we found very long line of code:

```php
echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ) . '</li>';
```

In __Twig__:

```twig
<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">
    {{ customer.get_billing_country() ? __( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : __( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) | apply_filters( 'woocommerce_no_available_payment_methods_message' ) }}
</li>
```

And with `filter` tag:

```twig
<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">
    {% filter apply_filters( 'woocommerce_no_available_payment_methods_message' ) %}
        {% if customer.get_billing_country() %}
            {{ __( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) }}
        {% else %}
            {{ __( 'Please fill in your details above to see available payment methods.', 'woocommerce' )  }}
        {% endif %}
    {% endfilter %}
</li>
```

## Widgets

Everyone loves widgets! Of course they do...

```php
<?php
$data['footer_widgets'] = Timber::get_widgets( 'footer_widgets' );
```

...where `footer_widgets` is the registered name of the widgets you want to get (in twentythirteen these are called `sidebar-1` and `sidebar-2`).

Then use it in your template:

**base.twig**

```twig
<footer>
    {{ footer_widgets }}
</footer>
```

### Using Timber inside your own widgets

You can also use twig templates for your widgets! Let’s imagine we want a widget that shows a random number each time it is rendered.

Inside the widget class, the widget function is used to show the widget:

```php
<?php
public function widget( $args, $instance ) {
    $number = rand();

    Timber::render( 'random-widget.twig', array(
        'args' => $args,
        'instance' => $instance,
        'number' => $number
    ) );
}
```

The corresponding template file `random-widget.twig` looks like this:

```twig
{{ args.before_widget|raw }}
{{ args.before_title|raw }}{{ instance.title|apply_filters('widget_title') }}{{ args.after_title|raw }}

<p>Your magic number is: <strong>{{ number }}</strong></p>

{{ args.after_widget|raw }}
```
The raw filter is needed here to embed the widget properly.

You may also want to check if the Timber plugin was loaded before using it:

```php
<?php
public function widget( $args, $instance ) {
    if ( ! class_exists( 'Timber' ) ) {
        // if you want to show some error message, this is the right place
        return;
    }

    $number = rand();

    Timber::render( 'random-widget.twig', array(
        'args' => $args,
        'instance' => $instance,
        'number' => $number
    ) );
}
```

## Shortcodes

Well, if it works for widgets, why shouldn't it work for shortcodes? Of course it does!

Let’s implement a `[youtube]` shortcode which embeds a youtube video.
For the desired usage of `[youtube id=xxxx]`, we only need a few lines of code:

```php
<?php
// Should be called from within an init action hook
add_shortcode( 'youtube', 'youtube_shortcode' );

function youtube_shortcode( $atts ) {
    if( isset( $atts['id'] ) ) {
        $id = sanitize_text_field( $atts['id'] );
    } else {
        $id = false;
    }

    // This time we use Timber::compile since shortcodes should return the code
    return Timber::compile( 'youtube-short.twig', array( 'id' => $id ) );
}
```

In `youtube-short.twig` we have the following template:

```twig
{% if id %}
	<iframe width="560" height="315" src="//www.youtube.com/embed/{{ id }}" frameborder="0" allowfullscreen></iframe>
{% endif %}
```

Now, when the YouTube embed code changes, we only need to edit the `youtube-short.twig` template. No need to search your PHP files for this one particular line.

### Layouts with Shortcodes

Timber and Twig can process your shortcodes by using the `{% filter shortcodes %}` tag. Let’s say you're using a `[tab]` shortcode, for example:

```twig
{% filter shortcodes %}
    [tabs tab1="Tab 1 title" tab2="Tab 2 title" layout="horizontal" backgroundcolor="" inactivecolor=""]
        [tab id=1]
            Something something something
        [/tab]

        [tab id=2]
            Tab 2 content here
        [/tab]
    [/tabs]
{% endfilter %}
```

## Password protected posts

It’s recommended to use the [`post_password_required()`](https://developer.wordpress.org/reference/functions/post_password_required/) function to check if a post requires a password. You can add this check in all your single PHP template files

**single.php**

```php
$context = Timber::context();
$post = Timber::query_post();
$context['post'] = $post;
if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
```

**single-password.twig**

```twig
{% extends "base.twig" %}

{% block content %}
    {{ function('get_the_password_form') }}
{% endblock %}
```


#### Using a Filter
With a WordPress filter, you can use a specific PHP template for all your password protected posts. Note: this is accomplished using only standard WordPress functions. This is nothing special to Timber

**functions.php**

```php
/**
 * Use specific template for password protected posts.
 *
 * By default, this will use the `password-protected.php` template file. If you want password
 * templates specific to a post type, use `password-protected-$posttype.php`.
 */
add_filter( 'template_include', 'get_password_protected_template', 99 );

function get_password_protected_template( $template ) {
    global $post;

    if ( ! empty( $post ) && post_password_required( $post->ID ) ) {
        $template = locate_template( [
            'password-protected.php',
            "password-protected-{$post->post_type}.php",
        ] ) ?: $template;
    }

    return $template;
};
```

With this filter, you can use a **password-protected.php** template file with the following contents:

```php
<?php

$context                  = Timber::context();
$context['post']          = new Timber\Post();
$context['password_form'] = get_the_password_form();

Timber::render( 'password-protected.twig', $context );
```

To display the password on the page, you could then use `{{ password_form }}` in your Twig file.
