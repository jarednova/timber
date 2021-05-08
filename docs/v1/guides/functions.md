---
title: "Functions"
---

My theme/plugin has some functions I need! Do I really have to re-write all of them?  
No, you don’t.

## function()

You can call all PHP functions through `function()` in Twig. For example, if you need to call `wp_head()` and `wp_footer()`, you’d do it like this:

```twig
{# single.twig #}
<html>
	<head>
	<!-- Add whatever you need in the head, and then...-->
	{{ function('wp_head') }}
	</head>

	<!-- etc... -->

	<footer>
		Copyright &copy; {{ "now"|date('Y') }}
	</footer>
	{{ function('wp_footer') }}
	</body>
</html>
```

You can also use `fn('my_function')` as an alias for `function('my_function')`.

### function() with arguments

What if you need to pass arguments to a function? Easy, add them as additional arguments (the first argument will always be the name of the function to call):

```twig
{# single.twig #}
<div class="admin-tools">
	{{ function('edit_post_link', 'Edit', '<span class="edit-link">', '</span>') }}
</div>
```

Nice! Any gotchas? Unfortunately yes. While the above example will totally work in a single.twig file it will not in The Loop. Why? Single.twig/single.php retain the context of the current post. A function like `edit_post_link` will try to guess the ID of the post you want to edit from the current post in The Loop. the same function requires some modification in a file like `archive.twig` or `index.twig`. There, you will need to explicitly pass the post ID:

```twig
{# index.twig #}
<div class="admin-tools">
	{{ function('edit_post_link', 'Edit', '<span class="edit-link">', '</span>', post.ID) }}
</div>
```

## Make functions available in Twig

If you have functions that you use a lot and want to improve readability of your code, you can make a function available in Twig by using `Timber\Twig_Function` inside the `timber/twig` filter.

```php
add_filter( 'timber/twig', 'add_to_twig' );

/**
 * My custom Twig functionality.
 *
 * @param \Twig\Environment $twig
 * @return \Twig\Environment
 */
function add_to_twig( $twig ) {
    // Adding a function.
    $twig->addFunction( new Timber\Twig_Function( 'edit_post_link', 'edit_post_link' ) );
    
    return $twig;
} );
```

Now you can use it like a "normal" function:

```twig
{# single.twig #}
<div class="admin-tools">
    {{ edit_post_link() }}
</div>
{# Calls edit_post_link using default arguments #}

{# single-my-post-type.twig #}
<div class="admin-tools">
    {{ edit_post_link(null, '<span class="edit-my-post-type-link">') }}
</div>
{# Calls edit_post_link with all defaults, except for second argument #}
```

### function_wrapper

In Timber versions lower than 1.3, you could use `function_wrapper` to make functions available in Twig. This method is now deprecated. Instead, use one of the methods above.

### Functions that echo output

The concept of Timber (and templating engines like Twig in general) is to prepare all the data before you pass it to a template. Some functions in WordPress echo their output directly. We don’t want this, because the output of this function would be echoed before we call `Timber:render()` and appear before every else on your website. There are two ways to work around this:

- If you have a function where you want to bypass the output and instead save it as a string, so that you can add it to your context, use [`Helper::ob_function`](https://timber.github.io/docs/reference/timber-helper/#ob-function).
- If you have a function that needs to be called exactly where you use it in your template (e.g. because it depends on certain global values) you can use `FunctionWrapper`:

```php
$context['my_custom_function'] = new FunctionWrapper( 'my_custom_function', $array_of_arguments );
```
