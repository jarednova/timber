---
title: "Custom Page Templates"
---

Imagine that you’ve created a page called **«About Us»** and WordPress has given it the slug `about-us`. How would you create a custom template just for this page?

There are a few ways to manage custom pages in WordPress and Timber, in order from simple to complex.

## Custom Twig File

If you're using the [Timber Starter Theme](https://github.com/timber/starter-theme) you can 

* Create a file called `page-about-us.twig` inside your `views` and go crazy.
* Copy and paste the contents of [`page.twig`](https://github.com/timber/starter-theme/blob/master/templates/page.twig) so you have something to work from.

**How does this work?**

In the `page.php` file you'll find this code:

```php
<?php
Timber::render( array(
    'page-' . $post->post_name . '.twig',
    'page.twig'
), $context );
```

This is telling PHP to first look for a Twig file named `page-{{slug}}.twig` and then fall back to `page.twig` if that doesn't exist. With the array notation, you can add as many fallbacks as you need.

## Custom PHP File

If you need to do something special for a page in PHP, you can use the standard WordPress [template hierarchy](http://codex.wordpress.org/Template_Hierarchy) to gather and manipulate data for this page. In the example above, you would create a file

`/wp-content/themes/my-theme/page-about-us.php`

and populate it with the necessary PHP. You can use the contents of the starter theme’s [`page.php`](https://github.com/timber/starter-theme/blob/master/page.php) file as a guide.

## Custom Page Template

Create a file with the following comment header:

```php
<?php
/**
 * Template Name: My Custom Page
 * Description: A Page Template with a darker design.
 */

// Code to display Page goes here...
```

In the WordPress admin, a new entry will be added in your page’s list of available templates like so:

![](http://codex.wordpress.org/images/thumb/a/a3/page-templates-pulldown-screenshot.png/180px-page-templates-pulldown-screenshot.png)

* Name it something like `/wp-content/themes/my-theme/template-my-custom-page.php`.
* Do **NOT** name it something beginning with `page-` or [WordPress will get very confused](http://jespervanengelen.com/page-templates-in-wordpress-template-hierarchy/).
