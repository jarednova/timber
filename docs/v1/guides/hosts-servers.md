---
title: "Hosts & Servers"
---

This guide serves as reference for any host or server-specific information we gather. If you have experience hosting WordPress with a particular host, stack, or service (AWS, Azure, etc.) please add that information here so it can be shared.

## WordPress VIP

Automattic offers a paid service called [WordPress VIP](https://wpvip.com/) for enterprise customers. To get Timber to play nice with their stack, we need to disable functionality related to caching and writes to the filesystem:

**functions.php**

```php
add_filter('timber/cache/mode', function() {
	return 'none';
});
```

```php
add_filter( 'timber/allow_fs_write', '__return_false' );
```

This means you will not be able to use on-the-fly image resizing through Timber. Don't despair! You can set custom image sizes for WordPress to use:

**functions.php**
```php
add_image_size( 'my_custom_size', 220, 220, array( 'left', 'top' ) );
```

**single.twig**
```twig
<img src="{{ post.thumbnail.src('my_custom_size') }}" alt="{{ post.thumbnail.alt() }}">
```

WordPress VIP has its own caching mechanisms. So when we disable caching, we're only disabling Timber and Twig's caching — not other layers that WP VIP applies.
