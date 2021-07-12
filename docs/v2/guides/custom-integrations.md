---
title: "Custom Integrations"
---

The new Integrations API (available from Timber 2.0) provides a generalized way to integrate with third-party plugins.

It’s how, for example, the built-in [Advanced Custom Fields](/docs/v2/integrations/advanced-custom-fields/) integration is implemented: There is nothing in the Timber core (i.e. `Timber\Timber`) that "knows" about ACF. All the convenient `meta` mapping and stuff gets hooked in by a [single class](https://github.com/timber/timber/blob/2.x/lib/Integration/AcfIntegration.php) (`Timber\Integration\AcfIntegration`).

To achieve this, that class implements an interface: `Timber\Integrations\IntegrationInterface`:

```php
namespace Timber\Integration;

interface IntegrationInterface {
    public function should_init() : bool;
    public function init() : void;
}
```

## A simple example

There are two main steps to a custom Timber integration:

1. Define a class that implements `Timber\Integration\IntegrationInterface`.
2. Add your class’s name to the list of integrations using the `timber/integrations` filter.

This is the simplest way to run your integration code when the plugin is activated _and_ provide a safe fallback to core Timber behavior when it’s not.

### Implementing IntegrationInterface

Here’s a simplified example of the ACF integration. Timber defines and adds an `AcfIntegration` automatically, but for illustration purposes, let’s rename it `MyAcfIntegration`.

```php
namespace MyProject;

use ACF;

/**
 * Class used to handle integration with Advanced Custom Fields
 */
class MyAcfIntegration implements IntegrationInterface {
    public function should_init() : bool {
        return class_exists( ACF::class );
    }

    public function init() : void {
        // Hook into Timber’s post meta logic.
        add_filter( 'timber/post/pre_meta', [ $this, 'post_get_meta_field' ], 10, 5 );
    }

    public static function post_get_meta_field( $value, $post_id, $field_name, $post, $args ) {
      $args = wp_parse_args( $args, array(
          // Apply formatting logic (defined when configuring the field).
          'format_value' => true,
      ) );

      // NOTE: get_field() is defined by ACF itself. We’re simply delegating.
      return get_field( $field_name, $post_id, $args['format_value'] );
    }
}
```

This tells Timber two important things:

First, the `should_init()` method tells Timber to initialize this integration (i.e. call `init()`) if Advanced Custom Fields is activated (in which case the `ACF` class will exist). In your own integration, this should return `true` _if and only if_ the plugin of choice is activated. Choosing a reasonable check to make is up to you, and will of course depend on the plugin.

Second, `init()` extends Timber’s core logic, in this case the `timber/post/pre_meta` hook, which is called internally in `Timber\Post` _before_ the core WordPress function `get_post_meta()` is called. This ensures that Timber will always prefer ACF’s behavior over the normal WP way. 

There are many [actions](/docs/v2/hooks/actions) and [filters](/docs/v2/hooks/filters) to hook into: For your integration, find the ones you need to override and call them from your `init()` method.

Note this is the _simplified_ version, favoring brevity over completeness. The actual ACF integration has to handle more than this.

### Adding your integration

The hard part’s over. Now you need to tell Timber about your class:

**functions.php**

```php
use MyProject\MyAcfIntegration;

add_filter( 'timber/integrations', function( array $classes ) : array {
    $classes[] = MyAcfIntegration::class;

    return $classes;
} );
```

Timber will call the method(s) you defined and initialize your integration if applicable.
