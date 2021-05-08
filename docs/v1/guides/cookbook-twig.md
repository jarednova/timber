---
title: "Twig Cookbook"
---

## Using Twig vars in live type

Imagine this scenario, I let the users set this in the Admin panel:

```
Copyright {{year}} by Upstatement, LLC. All Rights Reserved
```

But on the site I want it to render as:

```
Copyright 2013 by Upstatement, LLC. All Rights Reserved
```

Ready? There are a bunch of ways, but my favorite is:

**In your PHP file**

```php
<?php
$data['year'] = date('Y');
$data['copyright'] = get_option("footer_message"); //"Copyright {{year}} by Upstatement, LLC. All Rights Reserved"
render_twig('footer.twig', $data);
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
* Timber will look in your ```child-theme/views``` directory first, then ```timber/views``` directory
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
