---
title: "Escapers"
---

## Universal Escaping

By default, Timber does *not* escape the output of standard tags (i.e. `{{ post.field }}`). If you want to enable `autoescape` behavior simply add these lines to `functions.php`:

```php
if ( class_exists('Timber') ) {
	Timber::$autoescape = 'html';
}
```

## General Escapers

Twig offers a variety of [escapers](http://twig.sensiolabs.org/doc/filters/escape.html) out of the box. These are intended to escape a string for safe insertion into the final output and there are multiple functions to conform to the strategy dependant on the context. In addition, Timber has added some valuable custom escapers for your WP theme. To use the escaper (see documentation link above) you use pipe your content through a function `e` if you want to use a custom escaper you would supply an argument to the function, e.g. `e('wp_kses_post')`

This all follows the WordPress (and greater development philosophy) to:

1. Never trust user input.
2. Escape as late as possible.
3. Escape everything from untrusted sources (like databases and users), third-parties (like Twitter), etc.
4. Never assume anything.
5. Never trust user input.
6. Sanitation is okay, but validation/rejection is better.
7. Never trust user input.

[Relevant Documentation](https://vip.wordpress.com/documentation/vip/best-practices/security/validating-sanitizing-escaping/)

## wp_kses_post

Background on KSES. KSES is a recursive acronym for `KSES Kills Evil Scripts`. It's goal is to ensure only  "allowed" HTML element names, attribute names and attribute values plus only sane HTML entities in the string. Allowed is based on a configuration.

This uses ths internal WordPress method that sanitize content for allowed HTML tags for post content. The configuration used can be found by running ` wp_kses_allowed_html( 'post' );` [WordPress Documentation](https://codex.wordpress.org/Function_Reference/wp_kses_post)

**Twig**

`<p class="intro">{{post.post_content|e('wp_kses_post')}}</p>`

In this example, `post.post_content` is:

`<div foo="bar" src="bum">Foo</div><script>DoEvilThing();</script>`

**Output**

`<div>Foo</div>DoEvilThing();`

* * *

## esc_url
Uses WordPress' internal `esc_url` function on text. This should be used to sanitize URLs. [WordPress Documentation](https://codex.wordpress.org/Function_Reference/esc_url)

**Twig**

`<a href="{{ post.get_field('custom_link')|e('esc_url') }}"></a>`

**Output**

`<a href="http://google.com"></a>`

* * *

## esc_html

Escaping for HTML blocks. It converts any potentially conflicting HTML entities to their encoded equivalent to prevent them from being rendered as markup by the browser, e.g. converts "<" to "&lt;" and double quotes " to "$quot;"

This is for plain old text. If your content has HTML markup you should not use `esc_html` which will render the HTML as it looks in your code editor -- to preserve the HTML you will want to use `wp_kses_post`

**Twig**

`<div class="equation">{{ post.get_field('equation')|e('esc_html') }}</div>`

**Output**

`<div class="equation">is x &lt; y?</div>`

* * *

## esc_js

Escapes text strings for echoing in JS. It is intended to be used for inline JS (in a tag attribute, for example onclick=”…”). Note that the strings have to be in single quotes. The filter ‘js_escape’ is also applied.

**Twig**

`<script>var bar = '{{ post.get_field('name') }}';</script>`

**Output**

`<script>var bar = 'Gabrielle';</script>`
