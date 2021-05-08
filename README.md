<div style="text-align:center">
<a href="https://upstatement.com/timber/"><img src="http://i.imgur.com/PbEwvZ9.png" style="display:block; margin:auto; width:100%; max-width:100%"/></a>
</div>

By
[Jared Novack](https://github.com/jarednova) ([@jarednova](https://twitter.com/jarednova)),
[Lukas Gächter](https://github.com/gchtr) ([@lgaechter](https://twitter.com/lgaechter)),
[Pascal Knecht](https://github.com/pascalknecht) ([@pascalknecht](https://twitter.com/revenwo)),
[Maciej Palmowski](https://github.com/palmiak) ([@palmiak_fp](https://twitter.com/palmiak_fp)),
[Coby Tamayo](https://github.com/acobster) ([@cobytamayo](https://keybase.io/acobster)),
[Upstatement](https://twitter.com/upstatement) and [hundreds of other GitHub contributors](https://github.com/timber/timber/graphs/contributors)

[![Build Status](https://img.shields.io/travis/timber/timber/master.svg?style=flat-square)](https://travis-ci.com/github/timber/timber)
[![Coverage Status](https://img.shields.io/coveralls/timber/timber.svg?style=flat-square)](https://coveralls.io/github/timber/timber)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/timber/timber.svg?style=flat-square)](https://scrutinizer-ci.com/g/timber/timber/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/timber/timber.svg?style=flat-square)](https://packagist.org/packages/timber/timber)
[![WordPress Download Count](https://img.shields.io/wordpress/plugin/dt/timber-library.svg?style=flat-square)](https://wordpress.org/plugins/timber-library/)
[![WordPress Rating](https://img.shields.io/wordpress/plugin/r/timber-library.svg?style=flat-square)](https://wordpress.org/support/plugin/timber-library/reviews/)


> Because WordPress is awesome, but the_loop isn’t.

Timber helps you create fully-customized WordPress themes faster with more sustainable code. With Timber, you write your HTML using the [Twig Template Engine](http://twig.sensiolabs.org/) separate from your PHP files.

This cleans up your theme code so, for example, your PHP file can focus on being the data/logic, while your Twig file can focus 100% on the HTML and display.

This is what Timber's `.twig` files look like (from this [Hello World example](https://gist.github.com/jarednova/dc75030fd2c7dd6fe52a6fef459c450e))

```twig
{% extends "base.twig" %}
{% block content %}
  <h1 class="big-title">{{ foo }}</h1>
  <h2 class="post-title">{{ post.title }}</h2>

  <img src="{{ post.thumbnail.src }}" />

  <div class="body">
	{{ post.content }}
  </div>
{% endblock %}
```

Once Timber is installed, it gives any WordPress theme the ability to take advantage of the power of Twig and other Timber features.

### Looking for docs?
* [Timber Documentation](https://timber.github.io/docs/)
* [Twig Reference](http://twig.sensiolabs.org/doc/templates.html)
* [Overview / Getting Started Guide](https://timber.github.io/docs/getting-started/)
* [Video Tutorials](https://timber.github.io/docs/getting-started/video-tutorials/)

* * *

### Installation

The GitHub version of Timber requires [Composer](https://getcomposer.org/download/) and is setup for inclusion _within_ a theme or plugin. If you'd prefer one-click installation for your site, you should use the [WordPress.org](https://wordpress.org/plugins/timber-library/) version.

```shell
cd ~/wp-content/themes/my-theme
composer require timber/timber
```

If your theme/plugin is not setup to pull in Composer's autoload file, you will need to

```php
/* functions.php */
require_once(__DIR__ . '/vendor/autoload.php');
```

After this line, initialize Timber with
```php
$timber = new \Timber\Timber();
```

### What Now?

Setup the [Timber Starter Theme](https://timber.github.io/docs/getting-started/setup/#use-the-starter-theme). Once you have that installed in your WordPress setup, continue reading the [Getting Started guide to Themeing](https://timber.github.io/docs/getting-started/theming/).

* * *

### Mission Statement

Timber is a tool for developers who want to translate their HTML into high-quality WordPress themes through an intuitive, consistent and fully-accessible interface.
* **Intuitive**: The API is written to be user-centric around a programmer's expectations.
* **Consistent**: WordPress objects can be accessed through common polymorphic properties like slug, ID and name.
* **Accessible**: No black boxes. Every effort is made so the developer has access to 100% of their HTML.

#### What does it look like?
Nothing. Timber is meant for you to build a theme on. Like [underscores](https://github.com/Automattic/_s) it comes style-free, because you're the style expert. Instead, Timber handles the logic you need to make a kick-ass looking site.

#### Who is it good for?
Timber is great for any WordPress developer who cares about writing good, maintainable code. It helps teams of designers and developers working together. At [Upstatement](http://upstatement.com) we made Timber because while our entire team needs to participate in building WordPress sites, not everyone knows the ins-and-outs of the_loop(),  codex and PHP (nor should they). With Timber your best WordPress engineer can focus on building the `.php` files with requests from WordPress and pass the data into `.twig` files. Once there, designers can easily mark-up data and build out a site's look-and-feel.

#### Related & Official Projects
* [**Twig**](https://github.com/twigphp/Twig) The template language used by Timber.
* [**Timber Starter Theme**](https://github.com/timber/starter-theme) The "_s" of Timber to give you an easy start to the most basic theme you can build upon and customize.
* [**Timber Debug Bar**](https://github.com/timber/debug-bar-timber) Adds a debug bar panel that will show you which template is in-use and the data sent to your twig file.

#### Related Timber Projects
* [**Editor for Timber**](https://wordpress.org/plugins/editor-for-timber/) Edit your Twig files from the WordPress admin
* [**Pine**](https://github.com/azeemhassni/pine) A CLI _installer_ for Timber
* [**Query Monitor Twig Profile**](https://github.com/NielsdeBlaauw/query-monitor-twig-profile) An add-on for [Query Monitor](https://wordpress.org/plugins/query-monitor/) to see what's going on inside your Twig files (automatically works with Timber!)
* [**Timber ACF WP Blocks**](https://github.com/palmiak/timber-acf-wp-blocks) Easy ACF Gutenberg blocks creation
* [**Timber CLI**](https://github.com/nclud/wp-timber-cli) A CLI for Timber
* [**Timber Commented Include**](https://github.com/djboris88/timber-commented-include) Debug output via HTML comments before and after each include statement in Twig
* [**Timber Debugger**](https://github.com/djboris88/timber-debugger) Package that provides extra debugging options for Timber
* [**Timber Dump Extension**](https://github.com/nlemoine/timber-dump-extension) Debug output with nice formatting
* [**Timber Photon**](https://github.com/slimndap/TimberPhoton) Plug-in to use JetPack's free Photon image manipulation and CDN with Timber
* [**Timber VS Code Extension**](https://github.com/JDevx97/Timber-Snippets) Snippets for Timber in Visual Studio Code
* [**Timber Sugar**](https://github.com/timber/sugar) A catch-all for goodies to use w Timber
* [**Timber WebLink Extension**](https://github.com/nlemoine/timber-weblink-extension) Provides Twig functions to manage the Link HTTP header needed for Web Linking when using HTTP/2 Server Push as well as Resource Hints
* [**Timmy**](https://github.com/MINDKomm/Timmy) Advanced image manipulation for Timber



#### Projects that use Timber
* [**Branch**](https://github.com/JeyKeu/branch/) Bootstrap 3 + Timber = Branch starter theme!
* [**Flynt**](https://flyntwp.com/) a component based WordPress starter theme built on Timber and ACF Pro
* [**Gantry5**](https://wordpress.org/plugins/gantry5/) a framework for theme development
* [**Hozokit**](https://github.com/csalmeida/hozokit) a component based starter theme
* [**Seedling**](https://github.com/maxdmyers/seedling) a starter theme using Bootstrap 4

#### Helpful Links
* [**CSS Tricks**](https://css-tricks.com/timber-and-twig-reignited-my-love-for-wordpress/) introduction to Timber by [@tjFogarty](https://github.com/tjFogarty)
* [**Twig for Timber Cheatsheet**](http://notlaura.com/the-twig-for-timber-cheatsheet/) by [@laras126](https://github.com/laras126)
* [**TutsPlus**](http://code.tutsplus.com/articles/kick-start-wordpress-development-with-twig-introduction--cms-24781) A guide to getting started by [@ahmadawais](https://github.com/ahmadawais)

#### Support
Please post on [StackOverflow under the "Timber" tag](http://stackoverflow.com/questions/tagged/timber). Please use GitHub issues _only_ for specific bugs, feature requests and other types of issues.

#### Should I use it?
It's MIT-licensed, so please use in personal or commercial work. Just don't re-sell it. Timber is used on [tens of thousands of sites](https://www.upstatement.com/timber/#showcase) (and tons more we don't know about)

#### Contributing & Community
We love PRs! Read the [Contributor Guidelines](https://github.com/timber/timber/blob/master/CONTRIBUTING.md) for more info. Say hello, share your tips/work, and spread the love on Twitter at [@TimberWP](https://twitter.com/TimberWP).

## Documentation

The Official [Documentation for Timber](https://timber.github.io/docs/) is generated from the contents of this repository:

* Documentation for classes and functions is [auto generated](https://github.com/timber/docs). Any changes to the [Reference section](https://timber.github.io/docs/reference/) of the docs should be made by editing the function’s DocBlock. For inline documentation, we follow the [WordPress PHP Documentation Standards](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/php/).
* To make a change to one of the guides, edit the relevant file in the [`docs` directory](https://github.com/timber/timber/tree/master/docs).
