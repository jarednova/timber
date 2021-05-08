---
title: "Template Locations"
order: "75"
---

```php
Timber::render( 'teaser.twig' );
```

When you use `Timber::render()` or `Timber::compile()` to render a Twig template file, Timber will look for that template in different directories. It will first look in the child theme and then falls back to the parent theme (it’s the same logic as in WordPress).

The default load order is:

1. **User-defined locations** – See filters below.
2. **Directory of calling PHP script** (if not in the theme). If you’re using Timber in a plugin it will use the twig files in the plugin’s directory.
3. **Child theme**
4. **Parent theme**
5. **Directory of calling PHP script** (including the theme)

## Changing the default folder for Twig files

By default, Timber looks in your child and parent theme’s **views** directory to pull **.twig** files. If you don't like the default **views** directory (which by default resides in your theme folder), you can change that.

Example: If you want to use **/wp-content/themes/my-theme/twigs** as your default folder, you can either configure it with a string or use an array with fallbacks.

### Configure with a string

**functions.php**

```php
Timber::$dirname = 'twigs';
```

### Use an array with fallbacks

This is an alternative to configuring `$dirnames` with a string.

**functions.php**

```php
Timber::$dirname = [
    [
        'templates',
        'templates/shared/mods',
        'twigs',
        'views'
    ],
];
```

## Subdirectories

You can always reference **subdirectories** in your template folders relatively. For example:

```php
Timber::render( 'shared/headers/header-home.twig' );
```

... might correspond to a file in
`/wp-content/themes/my-theme/views/shared/headers/header-home.twig`.

## Add your own locations

You can set your own locations for your twig files with...

**functions.php**

```php
add_filter( 'timber/locations', function($paths) {
	$paths[] = array('/Users/lukas/Sandbox/templates');

	return $paths;
});
```

Use the full file path to make sure Timber knows what you're trying to draw from. You can also send an array for multiple locations:

**functions.php**

```php
add_filter( 'timber/locations', function($paths) {
	$paths[] = array(
		'/Users/lukas/Sandbox/templates',
		'~/Sites/timber-templates/',
		ABSPATH.'/wp-content/templates'
	);

	return $paths;
});
```

## Register your own namespaces

You can use [namespaces](https://symfony.com/doc/current/templating/namespaced_paths.html) in your locations, too. Namespaces allow you to create a shortcut to a particular location. Just define it as the value next to a path, for example:

**functions.php**

```php
add_filter( 'timber/locations', function($paths) {
	$paths['styleguide'] = [
		ABSPATH . '/wp-content/styleguide'
	];

	return $paths;
});
```

In the example above the namespace is called `styleguide`. You must prefix this with `@` when using it in templates (that's how Twig can differentiate namespaces from regular paths).
Assuming you have a template called **menu.twig** within that namespace, you would call it like so:

```twig
{{ include('@styleguide/menu.twig') }}
```

You can also register multiple paths for the same namespace. Order is important as it will look top to bottom and return the first one it encounters, for example:

**functions.php**

```php
add_filter( 'timber/locations', function($paths) {
	$paths['styleguide'] = array(
		ABSPATH.'/wp-content/styleguide',
		'/Users/lukas/Sandbox/styleguide'
	);

	return $paths;
});
```

You only need to do this once in your project (in **functions.php** of your theme). When you call one of the render or compile functions from a PHP file (say **single.php**), Timber will look for Twig files in these locations before it checks the child or parent theme.
