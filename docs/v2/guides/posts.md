---
title: "Posts"
order: "110"
---

To get a post object in Timber, you use `Timber::get_post()` and pass the WordPress post ID as an argument.

```php
$post = Timber::get_post( $post_id );
```

This function is similar to [`get_post()`](https://developer.wordpress.org/reference/functions/get_post/) and accepts one argument: a post ID. If you don’t pass in any argument, Timber will use `get_queried_object()` to try and work with the currently queried post.

```php
$post = Timber::get_post();

// Is the same as…

$post = Timber::get_post( get_queried_object_id() );
```

What you get in return is a [`Timber\Post`](https://timber.github.io/docs/v2/reference/timber-post/) object, which is similar to `WP_Post`. This object provides you with functions and properties for pretty much everything you need for developing theme templates.

Here’s a Twig template that received the post above in a `$post` variable.

```twig
<article class="article post-type-{{ post.type }}" id="post-{{ post.ID }}">
    <section class="article-content">
        <h1 class="article-h1">{{ post.title }}</h1>
        <h2 class="article-h2">{{ post.meta('subtitle') }}</h2>

        <p class="article-author">
            <span>By</span> {{ post.author.name }} <span>&bull;</span> {{ post.date }}
        </p>

        {{ post.content }}
    </section>
</article>
```

## Twig

You can convert post IDs to post objects in Twig using the `Post()` function.

```twig
{% set post = Post(post_id) %}
```

This is especially helpful if you only have an image ID and want to convert it to an image:

```twig
<img src="{{ Image(attachment_id).src }}">
```

It also works if you have an array of post IDs that you want to convert to `Timber\Post` objects.

```twig
{% for post in Post(post_ids) %}

{% endfor %}
```

## Invalid posts

If no valid post can be found with the post ID you provided, the `Timber::get_post()` function will return `false`. With this, you can always check for valid posts with a simple if statement.

```php
$post = Timber::get_post( $post_id );

if ( $post ) {
    // Handle post.
}
```

Or in Twig:

```twig
{% if post %}
    {{ post.title }}
{% endif %}
```

## Extending `Timber\Post`

If you need additional functionality that the `Timber\Post` class doesn’t provide or if you want to have cleaner Twig templates, you can [extend the `Timber\Post` class](/docs/v2/guides/extending-timber/) with your own classes:

```php
class Book extends Timber\Post {

}
```

To initiate your new `Book` post, you also use `Timber::get_post()`.

```php
$book = Timber::get_post( $post_id );
```

You **can’t** instantiate a `Timber\Post` object or an object that extends this class with a constructor – you can’t use `$post = new Book( $post_id )`. In Timber, we’ve chosen to go a different way to prevent a lot of problems that would come with direct instantiation.

So, how does Timber know about your `Book` class? Timber will use the [Post Class Map](https://timber.github.io/docs/v2/guides/class-maps/#the-post-class-map) to sort out which class it should use.

## Querying Posts

If you want to get a collection of posts, you can use `Timber::get_posts()`.

```php
$posts = Timber::get_posts( $query );
```

You can use this function similarly to how you use [`WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/). If you don’t pass in any argument, Timber will use the global query.

```php
// Use the global query.
$posts = Timber::get_posts();

// Using the WP_Query argument format.
$posts = Timber::get_posts( [
    'post_type'     => 'article',
    'category_name' => 'sports',
] );
```

The `Timber::get_posts()` function accepts a second parameter with options for the query. For example, with the `merge_default` option you can tell Timber that it should merge your query parameters with the default query parameters of the current template. You can check out `merge_default` and all the other options in the documentation for [`Timber::get_posts()`](https://timber.github.io/docs/v2/reference/timber/#get-posts).

```php
$posts = Timber::get_posts( $query, $options );
```

### The default query

In archive templates like **archive.php** or **category.php**, Timber will already fetch the default query when you call `Timber::context()` and make it available under the `posts` entry. Read more about this in the [Context Guide](https://timber.github.io/docs/v2/guides/context).

### Class Map

When you query for certain post types, Timber will use the [Post Class Map](https://timber.github.io/docs/v2/guides/class-maps/#the-post-class-map) to check which class it should use to instantiate your posts.

## Post Collections

The analogous `Timber` methods for getting Users, Terms, and Comments (`Timber::get_users()`, `Timber::get_terms()`, and `Timber::get_comments()`) all return arrays. But to make pagination work and making Timber compatible with The Loop, we treat Posts with special care.

What you get as a **return value** when running `Timber::get_posts()` is not a pure array of posts, but an instance of `Timber\PostCollectionInterface`, an [`ArrayObject`](https://www.php.net/manual/en/class.arrayobject.php) that is very similar to an array as you know it. That means you can still loop over a `PostCollectionInterface` directly:

```php
$posts = Timber::get_posts( /* optional args */ );

foreach ( $posts as $post ) {
    echo $post->title();
}
```

You can also directly access an index, like a normal array:

```php
$first = $posts[0]; // Timber\Post instance
```

In Twig, you can also loop over the collection.

```twig
{% for post in posts %}
    {{ post.title }}
{% endfor %}
```

What **doesn’t work** with `Timber\PostCollection` objects are PHP’s [Array functions](https://www.php.net/manual/en/ref.array.php) like `array_filter()` or WordPress helper functions like [`wp_list_filter()`](https://developer.wordpress.org/reference/functions/wp_list_filter/). If you want to work with those, you can turn a `Timber\PostCollectionInterface` instance into a pure array with `to_array()`. But be aware that when you do that, you lose the pagination functionality and compatibility optimizations with The Loop.

```php
$filtered = wp_list_filter( $posts->to_array(), [
    'comment_status' => 'open'
] );
```

### Types of Post Collections

Like every other [PHP interface](https://www.php.net/manual/en/language.oop5.interfaces.php), `PostCollectionInterface` cannot be instantiated directly, i.e. `new PostCollectionInterface()` will not work. You can only create instances of concrete *classes* that *implement* the interface.

Timber offers two implementations of `PostCollectionInterface`: the `Timber\PostQuery` and `Timber\PostArrayObject` classes. Both extend PHP’s `ArrayObject` class, which means you can loop over and index them directly (see examples above).

### The common case: PostQuery

`Timber\PostQuery` is what you will likely deal with most of the time. It is what is returned when:

* calling `Timber::get_posts()` with no arguments
* passing an associative array, e.g. `Timber::get_posts( [ 'post_type' => 'post' ] )`
* passing a `WP_Query` object, e.g. `Timber::get_posts( new WP_Query( [ 'post_type=post' ] ) )`
* calling `$term->posts()` on a  `Timber\Term` object
* calling `$user->posts()` on a `Timber\User` object
* calling `$post->children()` on a `Timber\Post` object

The `Timber\PostQuery` class contains some optimization for tight integration with The Loop, including support for advanced [pagination](/docs/v2/guides/pagination). To get a `Pagination` object you can use for rendering a pagination navigation component, call `$post_query->pagination()`.

### The fallback case: PostArrayObject

Sometimes you may not have direct access to a `WP_Query` instance; you may only have an array of `WP_Post` objects, e.g. when calling a helper function defined in a plugin:

```php
$wp_posts = fancy_plugin_get_custom_posts(); // -> an array of WP_Posts
```

In this scenario, you can still easily map each of these posts to instances of `Timber\Post` or the appropriate subclass, according to the [Class Map](/docs/v2/guides/class-maps/#the-post-class-map) you’ve defined:

```php
$timber_posts = Timber::get_posts( $wp_posts ); // -> Timber\PostArrayObject
```

Here’s an example:

```php
use MyProject\MyPage;
use MyProject\CustomPost;

// Define your Post Class Map.
add_filter( 'timber/post/classmap', function( $classmap ) {
    return array_merge( [
        'page'   => MyPage,
        'custom' => CustomPost,
      	// Omitting an entry for "post" here means it defaults to Timber\Post.
    ] );
});

$timber_posts = Timber::get_posts( $wp_posts );

array_map( function( $p ) { return $p->post_type; }, $wp_posts );
// -> ["post", "page", "custom"]
array_map( 'get_class', $timber_posts );
// -> ["\Timber\Post", "\MyProject\MyPage", "\MyProject\MyCustom"]
```

In short, `Timber\Post` instances are always created *polymorphically*, whether they are inside some kind of Post Collection or not.

### Debugging Post Collections

At query time and directly afterward, Post Collections do not necessarily contain instances of `Timber\Post`. Instead, `Post` objects are created *lazily*, meaning only when explicitly requested, such as by iterating over a Post Collection or accessing an index:

```php
$posts = Timber::get_posts(); // -> PostQuery containing only raw WP_Post instances

$first = $posts[0]; // -> Timber\Post (or subclass) instance ON DEMAND!

foreach ( $posts as $post ) {
    // $post is a Timber\Post instance, again created ON DEMAND.
}
```

To force eager instantiation, you can call the `realize()` method on a Post Collection:

```php
$posts = Timber::get_posts()->realize();
// -> PostQuery containing realized (eagerly instantiated) Timber\Post instances
```

This is mostly an implementation detail for performance reasons, with an important caveat: Due to a [bug in PHP <= 7.3](https://bugs.php.net/bug.php?id=69264), dumping the contents of a Post Collection does *not* realize the posts within it:

```php
$posts = Timber::get_posts();

/**
 * Before PHP 7.4, will dump a bunch of PostQuery internals,
 * but no Timber\Post instances!
 */
var_dump( $posts );

// Do this instead:
var_dump( $posts->realize() );
```

See [Laziness and Caching](#laziness-and-caching) for details about how this interacts with caching.

## Differences from WP core’s `get_posts()`

It might seem like `Timber::get_posts()` is the same as [`get_posts()`](https://developer.wordpress.org/reference/functions/get_posts/) in WordPress. But it isn’t. It’s more similar to using [`WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/). WP core’s `get_posts()` function applies different default parameters and performs the same database query as it would when calling `Timber::get_posts()` like this:

```php
$posts = Timber::get_posts( [
    'ignore_sticky_posts' => true,
    'suppress_filters'    => true,
    'no_found_rows'       => true,
] );
```

If you’re used to using `get_posts()` instead of `WP_Query`, you will have to set these parameters separately in your queries.

Of course, the other main difference is that instead of returning plain `WP_Post` objects, `Timber::get_posts()` returns instances of `Timber\Post`.

## Serialization

When you want to work with post data in JavaScript, you will want to convert it to JSON first.

Under normal circumstances, this wouldn't be a problem. However, Timber posts are instantiated lazily. This means that most methods will only calculate and return a value the first time you call them. Take the `Timber\Post::link()` method for example. You use it to get the permalink for a post.

```php
$permalink = $post->link();
```

Now let’s say you need to access that link in JavaScript, so you would convert your post data to JSON:

```php
$post = Timber::get_post( 84 );
$json = wp_json_encode( $post );
```

Because `link` is a method of your post object, you wouldn’t have access to it in JavaScript, because when you JSON-encode a post, you will only get its properties.

```js
console.log(post.link); // undefined
```

Luckily, support for serialization is baked into Timber queries when you implement PHP’s [JsonSerializable](https://www.php.net/manual/en/class.jsonserializable.php) interface.

Say you create a `Book` class that [extends](/docs/v2/guides/extending-timber/) `Timber\Post`. You define a `jsonSerialize()` method for that class. This method returns an array with all the data you want to use in JavaScript.

```php
<?php

use Timber\Post;

/**
 * Class Book
 *
 * Implements custom JSON serialization.
 */
class Book extends Post implements JsonSerializable {
    /**
     * Defines data that is used when post is converted to JSON.
     *
     * @return array
     */
	public function jsonSerialize() {
		return [
            'title'     => $this->title(),
            'link'      => $this->link(),
            'thumbnail' => $this->thumbnail()->src( 'thumbnail' ),
            'price'     => $this->meta( 'price' ),
		];
	}
}
```

Once you define with [Class Maps](/docs/v2/guides/class-maps/#the-post-class-map) that all `book` post types should be instantiated with your `Book` class, you can directly convert your posts query to JSON:

```php
$posts = Timber::get_posts( [
    'post_type' => 'book',
] );

$posts_json = wp_json_encode( $posts_json );
```

Now, when you access your posts in JavaScript, you will have all the data you defined in your `Book::jsonSerialize()` method as object properties of your post.

```js
console.log(post);

{
    title: 'The magic serialization of posts',
    link: 'https://example.org/book/the-magic-serialization-of-posts',
    thumbnail: 'https://example.org/wp-content/uploads/the-magic-serializaton-of-posts-150x150.jpg',
    price: 100
}
```

Now you might think: Why do I have to add all the data manually? Could we not just add all the data from all the methods of a post? Well, technically we could. But all that data would end up in your HTML output, which might not be a good idea:

- There could be sensitive data that you don’t want to have publicly available in your HTML.
- All the data you add to the HTML will make your page size bigger. For performance reasons, it makes sense to only load the data you need.

## Performance

### Consider using the `pre_get_posts` action

Data will always be fetched from the database if you

- Use `Timber::get_posts()` with a `query` argument
- Use `Timber::get_post()` with an argument.

These two cases are always a performance hit. As long as you don’t change any query parameters, Timber will use the default query that is already set up and no additional database query will be run.

If you care about performance and want to change the query that WordPress runs before deciding which template file it will use, you need to use the [`pre_get_posts`](https://developer.wordpress.org/reference/hooks/pre_get_posts/) action.

### Count rows only if needed

Whenever you query for a collection of posts, but you don’t need pagination for them, you should set `no_found_rows` to `true`.

```php
$posts = Timber::get_posts( array(
    'no_found_rows' => true,
) );
```

In the back, the query will not count the number of found rows. This can result in [better performance](https://kinsta.com/blog/wp-query/) if you have a large number of posts.

### Laziness and Caching

`Timber\Post` instances inside Post Collections are created [lazily](https://en.wikipedia.org/wiki/Lazy_evaluation). That is, until it is accessed directly, each index inside a Post Collection is just a raw `WP_Post` object. We do it this way because creating a `Timber\Post` object may require additional database round trips or other expensive operations to import a post’s data.

Here is a timeline of what normally happens when you call `Timber::get_posts()`:

1. Timber decides what kind of Post Collection it will return (or returns `false` on error).
2. It populates the new Post Collection, either with `WP_Post` objects it queries from WordPress core or with ones it was already passed, and returns the Collection object.
3. The Collection (say, `$coll`) now contains zero or more raw `WP_Post` instances passed in/returned from the query.
4. Calling `$coll[ $numeric_index ]` *realizes* a `Timber\Post` object from the `WP_Post` at `$numeric_index`, running it through any Post Class Map you've defined, and *replaces* the object at that index internally, so it doesn't have to repeat that work in the future.
5. Looping over `$coll` repeats the above step for each index that has not been realized. For indexes that have been realized, it simply returns the realized `Timber\Post` object that already lives at that index.

This is usually what you want. But sometimes, you may want to force Timber to realize a collection up front, such as if you are caching an expensive post query:

```php
$eager_posts = \Timber\Helper::transient( 'my_posts', function() {
    $query = \Timber\Timber::get_posts( [
        'post_type' => 'some_post_type',
    ] );

    // Run Post::setup() up front.
    return $query->realize();
}, HOUR_IN_SECONDS );

foreach ( $eager_posts as $post ) {
    // No additional overhead here.
}

// Later...
foreach ( get_transient( 'my_posts' ) as $post ) {
    /**
     * Same deal!
     *
     * No repeated overhead here because the realized Posts are already cached.
     */
}
```

## Using posts or post collections in the context

Timber will automatically set the `post` or `posts` variable for you in the context depending on the template file you’re using. Read more about this in the [Context Guide](https://timber.github.io/docs/v2/guides/context/#template-contexts).
