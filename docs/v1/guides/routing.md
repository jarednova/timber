---
title: "Routing"
---

Among its other special powers, Timber includes modern routing in the Express.js/Ruby on Rails mold, making it easy for you to implement custom pagination--and anything else you might imagine in your wildest dreams of URLs and parameters. OMG so easy!

## Some examples
In your functions.php file, this can be called anywhere (don't hook it to init or another action or it might be called too late)

```php
<?php
Routes::map('blog/:name', function($params){
    $query = 'posts_per_page=3&post_type='.$params['name'];
    Routes::load('archive.php', null, $query, 200);
});

Routes::map('blog/:name/page/:pg', function($params){
    $query = 'posts_per_page=3&post_type='.$params['name'].'&paged='.$params['pg'];
    $params = array('thing' => 'foo', 'bar' => 'I dont even know');
    Routes::load('archive.php', $params, $query);
});
```

## map

`Routes::map($pattern, $callback)`

### Usage

A `functions.php` where I want to display custom paginated content:

```php
<?php
Routes::map('info/:name/page/:pg', function($params){
	//make a custom query based on incoming path and run it...
	$query = 'posts_per_page=3&post_type='.$params['name'].'&paged='.intval($params['pg']);

	//load up a template which will use that query
	Routes::load('archive.php', null, $query);
});
```

### Arguments

`$pattern` (required)
Set a pattern for Timber to match on, by default everything is handled as a string. Any segment that begins with a `:` is handled as a variable, for example:

**To paginate:**

```
page/:pagenum
```

**To edit a user:**

```
my-users/:userid/edit
```

`$callback`
A function that should fire when the pattern matches the request. Callback takes one argument which is an array of the parameters passed in the URL.

So in this example: `'info/:name/page/:pg'`, $params would have data for:
* `$data['name']`
* `$data['pg']`

... which you can use in the callback function as a part of your query

* * *

## load

`Routes::load($php_file, $args, $query = null, $status_code = 200)`

### Arguments

`$php_file` (required)
A PHP file to load, in my experience this is usually your archive.php or a generic listing page (but don't worry it can be anything!)

`$template_params`
Any data you want to send to the resulting view. Example:

```php
<?php
/* functions.php */

Routes::map('info/:name/page/:pg', function($params){
    //make a custom query based on incoming path and run it...
    $query = 'posts_per_page=3&post_type='.$params['name'].'&paged='.intval($params['pg']);

    //load up a template which will use that query
    $params = array();
    $params['my_title'] = 'This is my custom title';
    Routes::load('archive.php', $params, $query, 200);
});
```

```php
<?php
/* archive.php */

global $params;
$context['wp_title'] = $params['my_title']; // "This is my custom title"
/* the rest as normal... */
Timber::render('archive.twig', $context)
```

`$query`
The query you want to use, it can accept a string or array just like `Timber::get_posts` -- use the standard WP_Query syntax (or a WP_Query object too)

`$status_code`
Send an optional status code. Defaults to 200 for 'Success/OK'
