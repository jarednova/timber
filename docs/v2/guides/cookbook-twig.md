---
title: "Twig Cookbook"
order: "200"
---

## Using Twig vars in live type

Imagine this scenario, I let the users set this in the Admin panel:

```
Copyright {{year}} by Upstatement, LLC. All Rights Reserved
```

But on the site I want it to render as:

```
Copyright 2020 by Upstatement, LLC. All Rights Reserved
```

Ready? There are a bunch of ways, but one helpful example is:

**In your PHP file**

```php
$data = [
    'year' => wp_date( 'Y' ),
    // "Copyright {{year}} by Upstatement, LLC. All Rights Reserved"
    'copyright' => get_option( 'footer_message' );
];

Timber::render( 'footer.twig', $data );
```

**In your HTML file (let's say **footer.twig**)**

```twig
{% include template_from_string(copyright) %}
```

## Includes

### Simple include

```twig
{% include "footer.twig" %}
```

#### Notes

* Make sure your file actually exists or you're going to have a bad time
* Timber will look in your `child-theme/views` directory first, then `timber/views` directory
* Don't forget the quote marks!

### Dynamic includes

Use a variable to determine the included file!

```twig
{% include ['blocks/block-'~block.slug~'.twig', 'blocks/blog.twig'] ignore missing %}
```

**Huh?**

* You're telling Twig to include an array of files
* Same rules as above
* ~ (tilde) is what twig uses to concatenate a string with your variable

## Twig tools

### Text editor add-ons

* Text Mate & Sublime text bundle – [Anomareh's PHP-Twig](https://github.com/Anomareh/PHP-Twig.tmbundle)
* Emacs – [Web Mode](http://web-mode.org/)
* Geany – Add [Twig/Symfony2 detection and highlighting](https://wiki.geany.org/howtos/geany_and_django#twigsymfony2_support)
* PhpStorm – Built in coloring and code hinting. The Twig extension is recognized and has been for some time. [Twig Details for PhpStorm](http://blog.jetbrains.com/phpstorm/2013/06/twig-support-in-phpstorm/).
* Atom – Syntax highlighting with the [Atom Component](https://atom.io/packages/php-twig).

### WordPress tools

* [Lisa Templates](https://github.com/pierreminik/lisa-templates/) – allows you to write Twig-templates in the WordPress Admin that renders through a shortcode, widget or on the_content hook.

### Other

* [Watson-Ruby](http://nhmood.github.io/watson-ruby/) – An inline issue manager. Put tags like `[todo]` in a Twig comment and find it easily later. Watson supports Twig as of version 1.6.3.

### JavaScript

* [Twig.js](https://github.com/justjohn/twig.js) – Use those `.twig` files in the Javascript and AJAX components of your site.
* [Nunjucks](http://mozilla.github.io/nunjucks/) – Another JS template language that is also based on [Jinja2](http://jinja.pocoo.org/docs/)
