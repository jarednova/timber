---
title: "Class Maps"
order: "1650"
---

The Class Map is the central hub for Timber to select the right PHP class for post or term objects. Whenever you want to extend existing Timber classes with your custom classes, you’ll have to register them through a Class Map so that Timber will know when to use it.

 There are several different Class Maps in Timber:

- `timber/post/classmap` for posts
- `timber/term/classmap` for terms
- `timber/comment/classmap` for comments
- `timber/menu/classmap` for menus
- `timber/menuitem/classmap` for menus
- `timber/user/classmap` for users

## The Post Class Map

When you extend `Timber\Post`, the logic you add is usually for a certain post type. With the `timber/post/classmap` filter, you can tell Timber which class it should use for certain post types.

The Post Class Map is used:

- When you get a post through `Timber::get_post()`.
- When you get a collection of posts through `Timber::get_posts()`.
- When you use a function that returns other posts, like `$post->children()` or `$post->thumbnail()`.

**functions.php**

```php
use Book;
use Page;

add_filter( 'timber/post/classmap', function( $classmap ) {
    $custom_classmap = [
        'page' => Page::class,
        'book' => Book::class,
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

Post types that you don’t list in the Post Class Map will take the default `Timber\Post` class.

In case you need more fine-grained control over which class is used for your post object, you can use a callback function:

```php
use Book;
use Page;
use PreciousBook;

add_filter( 'timber/post/classmap', function( $classmap ) {
    $custom_classmap = [
        'book' => function( \WP_Post $post ) {
            if ( $post->id === 3 ) {
                return PreciousBook::class;
            }

            return Book::class;
        },
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

The callback function receives a `WP_Post` object and should return the name of the class to use.

Here’s another example, where you could use a different attachment class for a featured image that belongs to a certain post type.

```php
use BookAttachment;
use Timber\Attachment;

add_filter( 'timber/post/classmap', function( $classmap ) {
    $custom_classmap = [
        'attachment' => function( \WP_Post $post ) {
            if ( 'book' === get_post_type( $post->post_parent ) {
                return BookAttachment::class;
            }

            return Attachment::class;
        },
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

## The Term Class Map

When you extend `Timber\Term`, the logic you add is usually for a certain taxonomy. With the `timber/term/classmap` filter, you can tell Timber which class it should use for certain taxonomies.

The Term Class Map is used:

- When you get a post through `Timber::get_term()`.
- When you get a collection of posts through `Timber::get_terms()`.
- When you use a function that returns other terms, like `$post->terms()`.

**functions.php**

```php
use Genre;

add_filter( 'timber/term/classmap', function( $classmap ) {
    $custom_classmap = [
        'genre' => Genre::class,
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

Taxonomies that you don’t list in the Term Class Map will take the default `Timber\Term` class.

When you need more fine-grained control over which class is used for your term object, you can use a callback function:

```php
use ComedyGenre;
use Genre;

add_filter( 'timber/term/classmap', function( $classmap ) {
    $custom_classmap = [
        'genre' => function( \WP_Term $term ) {
            if ( $term->term_id === 2 ) {
                return ComedyGenre::class;
            }

            return Genre::class;
        },
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

The callback function receives a `WP_Term` object and should return the name of the class to use.

## The Comment Class Map

When you extend `Timber\Comment`, the logic you add is usually for comments related to a certain post type. With the `timber/comment/classmap` filter, you can tell Timber which class it should use for comments that belong to a certain post type.

The Comment Class Map is used:

- When you get a single comment through `Timber::get_comment()`.
- When you get the comments for a post through `$post->comments()`.

**functions.php**

```php
use CommentPost;
use CommentBook;

add_filter( 'timber/comment/classmap', function( $classmap ) {
    $custom_classmap = [
        'post' => CommentPost::class,
        'book' => CommentBook::class,
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

Comments for post types that you don’t list in the Comment Class Map will take the default `Timber\Comment` class name as an argument.

When you need more fine-grained control over which class is used for your comment object, you can use a callback function:

```php
use BookComment;
use BookChildComment;

add_filter( 'timber/comment/classmap', function( $classmap ) {
    $custom_classmap = [
        'book' => function( \WP_Comment $comment ) {
            $post = get_post( $comment->comment_post_ID );

            if ( 0 !== $post->post_parent ) {
                return BookChildComment::class;
            }

            return BookComment::class;
        },
    ];

    return array_merge( $classmap, $custom_classmap );
} );
```

The callback function receives a `WP_Comment` object and should return the name of the class to use. If you need the post ID a comment is associated with, you can get that through `$comment->comment_post_ID`.

## The Menu Class Map

With the `timber/menu/classmap` filter, you can tell Timber which class it should use for menu objects.

The Menu Class Map is used:

- When you get a menu through `Timber::get_menu()`.

Here’s an a example for a basic filter where you would always return your custom `ExtendedMenu` class.

**functions.php**

```php
use ExtendedMenu;

add_filter( 'timber/menu/classmap', function( $class, $menu_object ) {
    return ExtendedMenu::class;
}, 10, 2 );
```

The Menu Class Map receives the default `Timber\Menu` class name and a menu object (which is a `WP_Term`) as arguments. You should be able to decide which class to use based on these parameters.

The following example demonstrates how you can use a custom class (`MenuPrimary`) that you want to use for the menu assigned to the `primary` menu location.

```php
use MenuPrimary;

add_filter( 'timber/menu/classmap', function( $class, $menu_object ) {
    $locations       = get_nav_menu_locations();
    $primary_menu_id = $locations['primary'];

    if ( $primary_menu_id === $menu_object->term_id ) {
        return MenuPrimary::class;
    }

    return $class;
}, 10, 2 );
```

## The MenuItem Class Map

With the `timber/menuitem/classmap` filter, you can tell Timber which class it should use for menu item objects (that is, the actual items within the menu).

The MenuItem Class Map is used:

- When you get a menu through `Timber::get_menu()`.

**functions.php**

```php
use MenuPrimary;
use MenuItemPrimary;

add_filter( 'timber/menuitem/classmap', function( $class, $menu ) {
    if ( $menu instanceof MenuPrimary ) {
        return MenuItemPrimary::class;
    }

    return $class;
}, 10, 2 );
```

In the above example, the MenuItem Class Map receives the default `Timber\MenuItem` class name, the registered menu location and the `Timber\Menu` it’s assigned to as arguments. You should be able to decide which class to use based on these parameters. This example demonstrates how you can use a custom class (`MenuItemPrimary`) when the parent menu has a (custom) class of `MenuPrimary`.

Here’s another example where you would use a different class if the menu item is in a menu assigned to the `secondary` menu location.

```php
use MenuItemSecondary;

add_filter( 'timber/menuitem/classmap', function( $class, $menu ) {
    if ( 'secondary' === $menu->theme_location ) {
        return MenuItemPrimary::class;
    }

    return $class;
}, 10, 2 );
```

## The Pages Menu Class Map

With the `timber/pages_menu/classmap` filter, you can tell Timber which class it should use for pages menu object.

The Pages Menu Class Map is used:

- When you get a menu through `Timber::get_pages_menu()`.

Here’s an a example for a basic filter where you would always return your custom `ExtendedMenu` class.

**functions.php**

```php
use ExtendedPagesMenu;

add_filter( 'timber/pages_menu/classmap', function( $class ) {
    return ExtendedPagesMenu::class;
} );
```

The Pages Menu Class Map receives the default `Timber\PagesMenu` class name as an argument.

## The User Class Map

With the `timber/user/classmap` filter, you can tell Timber which class it should use for user objects.

The User Class Map is used:

- When you get the currently logged-in user through `Timber::get_user()`.
- When you get a user by ID through `Timber::get_user( $user_id )`.
- When you get a post’s author (or authors) through `$post->author()` or `$post->authors()`.

**functions.php**

```php
use UserExtended;

add_filter( 'timber/user/classmap', function( $class, \WP_User $user ) {
    return UserExtended::class;
}, 10, 2 );
```

In the above example, the User Class Map receives the default User Class Map and a `WP_User` object as arguments. You should be able to decide which class to use based on that user object.

In case you need a different User class based on the current template you’re displaying, you can use [Conditional Tags](https://developer.wordpress.org/themes/references/list-of-conditional-tags/).

```php
use Author;

add_filter( 'timber/user/classmap', function( $class, \WP_User $user ) {
    // Use Author class for single post template.
    if ( is_singular( 'post' ) ) {
        return Author::class;
    }

    return $class;
}, 10, 2 );
```

If you need to have a special class based on the capabilities a user has, work with `$user->has_cap()`.

```php
use Administrator;
use Editor;

add_filter( 'timber/user/classmap', function( $class, \WP_User $user ) {
    if ( $user->has_cap( 'manage_options' ) ) {
        return Administrator::class;
    } elseif ( $user->has_cap( 'edit_pages' ) ) {
        return Editor::class;
    }

    return $class;
}, 10, 2 );
```

If you need to check for user roles, check the `$user->roles` array. Don’t work with `$user->has_cap()` to check for roles, because it may lead to unreliable results.


```php
use Administrator;
use Editor;

add_filter( 'timber/user/classmap', function( $class, \WP_User $user ) {
    if ( in_array( 'editor', $user->roles, true ) ) {
        return Editor::class;
    } elseif ( in_array( 'author', $user->roles, true ) ) {
        return Author::class;
    }

    return $class;
}, 10, 2 );
```
