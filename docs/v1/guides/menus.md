---
title: "Menus"
---

In Timber, you can use `Timber\Menu` to make a standard WordPress menu available to the Twig template as an object you can loop through.

Once the menu becomes available to the context, you can get items from it in a way that is a little smoother and more versatile than WordPress’s `wp_nav_menu()` function. (And you never again need to rely on a crazy [Walker Function](https://codex.wordpress.org/Class_Reference/Walker)).

## Initializing a menu

`Timber\Menu` can be initialized with different values:

You can pass the slug of the menu you want to use:

```php
$menu = new Timber\Menu( 'site-tools' );
```

Or the ID number of the menu:

```php
$menu = new Timber\Menu( 3 );
```

Or the proper name from the admin:

```php
$menu = new Timber\Menu( 'Primary Navigation' );
```

Or the slug of the registered location:

```php
$menu = new Timber\Menu( 'primary' );
```

Or pass nothing. This is good if you have only one menu. In that case Timber will just grab what you got.

```php
$menu = new Timber\Menu();
```

## Options

Optionally, you can send additional options to `Timber\Menu`. Current only `depth` is supported (see https://developer.wordpress.org/reference/functions/wp_nav_menu/ for reference)

```php
$args = array(
	'depth' => 2,
);
$menu = new Timber\Menu( 'primary', $args );
```

## Setting up a menu globally

The first thing to do is to initialize your menu using `Timber\Menu`. This will make the menu available as an object to work with in the context. Because we need the menu on every page, we can add it to the global context through the `timber/context` filter:

**functions.php**

```php
<?php

add_filter( 'timber/context', 'add_to_context' );

function add_to_context( $context ) {
    // So here you are adding data to Timber's context object, i.e...
    $context['foo'] = 'I am some other typical value set in your functions.php file, unrelated to the menu';

    // Now, in similar fashion, you add a Timber Menu and send it along to the context.
    $context['menu'] = new \Timber\Menu( 'primary-menu' );

    return $context;
}
```

Now, when you call `Timber::context()`, your menu will already be set in the context. You don’t need to initialize the menu in all your template files.

**index.php**

```php
<?php

$context = Timber::context();

Timber::render( 'index.twig', $context );
```

When adding multiple menus to the global `$context`, declare unique names for each menu in the `add_to_context` function.

**functions.php**

```php
public function add_to_context( $context ) {
    $context['primary_menu']  = new Timber\Menu('Primary Navigation');
    $context['footer_menu'] = new Timber\Menu('Footer Navigation');
    // ...
    return $context;
}
```

## Displaying the menu items

In your Twig file, you can loop over the menu items like normal arrays. You’re in complete control of the markup.

**index.twig**

```twig
<nav>
    <ul class="nav-main">
        {% for item in menu.items %}
            <li class="nav-main-item {{ item.classes|join(' ') }}">
                <a class="nav-main-link" href="{{ item.link }}">{{ item.title }}</a>
                {% if item.children %}
                    <ul class="nav-drop">
                        {% for child in item.children %}
                            <li class="nav-drop-item">
                                <a href="{{ child.link }}">{{ child.title }}</a>
                            </li>
                        {% endfor %}
                    </ul>
                {% endif %}
            </li>
        {% endfor %}
    </ul>
</nav>
```

When using multiple menus, use the unique name in your Twig markup. Notice `primary_menu.items` instead of `menu.items`.

**index.twig**

```twig
<nav>
  <ul class="nav-main">
      {% for item in primary_menu.items %}
          <li class="nav-main-item {{ item.classes|join(' ') }}">
              <a class="nav-main-link" href="{{ item.link }}">{{ item.title }}</a>
              <!-- ... -->
          </li>
      {% endfor %}
  </ul>
</nav>
```

## The current menu item

When you need to check whether a menu item is the current menu item, you can use the `current` property.
Here’s an example to display child menu items only if it’s the menu item is the currently visited page:

**Twig**

```twig
{% if item.current and item.children %}
    <ul class="nav-child">
        {% for child in item.children %}
            <li class="nav-child-item">
                <a class="nav-child-link" href="{{ child.link }}">{{ child.title }}</a>
            </li>
        {% endfor %}
    </ul>
{% endif %}
```

Other properties that are available are `current_item_parent` for direct parents of a menu item and `current_item_ancestor` for when you have deeper nesting.

## Tips

- [Add items dynamically](https://github.com/jarednova/timber/issues/200)
