---
title: "Extending Timber"
---

Myth: Timber is for making simple themes. Fact: It's for making incredibly complex themes _look_ easy. But yes, you can also make simple sites from it.

The beauty of Timber is that the object-oriented nature lets you extend it to match the exact requirements of your theme.

## An example that extends TimberPost

Timber's objects like `Timber\Post`, `Timber\Term`, etc. are a great starting point to build your own subclass from. For example, on this project each post was a part of an "issue" of a magazine. I wanted an easy way to reference the issue in the twig file:


```twig
<h1>{{ post.title }}</h1>
<h3>From the {{ post.issue.title }} issue</h3>
```

Of course, `Timber\Post` has no built-in concept of an issue (which I've built as a custom taxonomy called "issues"). So we're going to extend TimberPost to give it one:

```php
<?php
class MySitePost extends Timber\Post {

	var $_issue;

	public function issue() {
		$issues = $this->get_terms('issues');
		if (is_array($issues) && count($issues)) {
			return $issues[0];
		}
	}
}
```

So now I've got an easy way to refer to the {{ post.issue }} in our twig templates. If you want to make this production-ready I recommend a bit of internal caching so that you don't re-query every time you need to get the
issue data:

```php
<?php
class MySitePost extends Timber\Post {

	var $_issue;

	public function issue() {
		if (!$this->_issue) {
			$issues = $this->get_terms('issues');
			if (is_array($issues) && count($issues)) {
				$this->_issue = $issues[0];
			}
		}
		return $this->_issue;
	}
}
```

Right now I'm in the midst of building a complex site for a hybrid foundation and publication. The posts on the site have some very specific requirements that requires a fair amount of logic. I can take the simple `Timber\Post` object and extend it to make it work perfectly for this theme.

For example, I have a plugin that let's people insert manually related posts, but if they don't, WordPress will pull some automatically based on how the post is tagged.

```php
	<?php
	class MySitePost extends Timber\Post {

		function get_related_auto() {
			$tags = $this->tags();
			if (is_array($tags) && count($tags)) {
				$search_tag = $tags[0];
				$related = Timber::get_posts('tag_id='.$search_tag->ID);
				return $related;
			} else {
				//not tagged, cant do related on it
				return false;
			}
		}

		function get_related_manual() {
			if (isset($this->related_manual) && is_array($this->related_manual)){
				foreach($this->related_manual as &$related){
					$related = new MySitePost($related);
				}
				return $this->related_manual;
			}
			return false;
		}

		function related($limit = 3) {
			$related = $this->get_related_manual();
			if (!$related){
				$related = $this->get_related_auto();
			}
			if (is_array($related)) {
				array_splice($related, 0, $limit);
			}
			return $related;
		}
	}
```

These can get pretty complex. And that's the beauty. The complexity lives inside the context of the object, but very simple when it comes to your templates.

## Adding functionality to Twig

You can extend Twig by adding custom functionality like functions or filters. Timber provides its own classes (`Timber\Twig_Function` and `Timber\Twig_Filter`) to provide better compatibility with different versions of Twig.

**functions.php**

```php
<?php

add_filter( 'timber/twig', 'add_to_twig' );

/**
 * Adds functionality to Twig.
 * 
 * @param \Twig\Environment $twig The Twig environment.
 * @return \Twig\Environment
 */
function add_to_twig( $twig ) {
    // Adding a function.
    $twig->addFunction( new Timber\Twig_Function( 'edit_post_link', 'edit_post_link' ) );
    
    // Adding functions as filters.
    $twig->addFilter( new Timber\Twig_Filter( 'whateverify', 'whateverify' ) );
    $twig->addFilter( new Timber\Twig_Filter( 'slugify', function( $title ) {
        return sanitize_title( $title );
    } ) );
    
    return $twig;
}

function whateverify( $text ) {
    $text .= ' or whatever';
    
    return $text;
}
```

This can now be called in your twig files with:

```twig
<h2 id="{{ post.title|slugify }}">{{ post.title|whateverify }}</h2>
```

Which will output:

```twig
<h2 id="hello-world">Hello World! or whatever</h2>
```
