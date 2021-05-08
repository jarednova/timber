---
title: "Comments"
---

## Simple Comment Implementation
Looking to do non-threaded comments? This pattern can be expanded upon. If you're looking to customize the HTML of the comment form, you can get the necessary HTML from the starter theme's [comment-form.twig](https://github.com/timber/starter-theme/blob/master/templates/comment-form.twig) file.

**single.twig**
```twig
<section class="post-{{ post.id }}">
  <h1>{{ post.title }}</h1>
  <div class="content">
    {{ post.content }}
  </div>
  <div class="comments">
    {% for comment in post.comments %}
      <article class="comment" id="comment-{{ comment.id }}">
      	<h5 class="comment-author">{{ comment.author.name }} says</h5>
        {{ comment.content }}
      </article>
    {% endfor %}
    {{ function('comment_form') }}
  </div>
</section>
```

## Threaded Comments (Method 1)

You can implement threaded comments this way (if you don't mind using WordPress’s comment markup).

**single.twig**
```twig
<section class="post-{{ post.id }}">
  <h1>{{ post.title }}</h1>
  <div class="content">
    {{ post.content }}
  </div>
  <div class="comments">
    {{ function('comments_template') }}
  </div>
</section>
```

**functions.php**
```php
//Include the comment reply Javascript
add_action('wp_print_scripts', function(){
  if ( (!is_admin()) && is_singular() && comments_open() && get_option('thread_comments') ) wp_enqueue_script( 'comment-reply' );
});
```

## Threaded Comments (Method 2)

Timber contains the `CommentThread` class to help manage comment threads. If you need to build something custom, you can implement it like so:

**single.twig**
```twig
{# single.twig #}
<div id="post-comments">
  <h4>Comments on {{ post.title }}</h4>
  <ul>
    {% for comment in post.comments %}
      {% include 'comment.twig' %}
    {% endfor %}
  </ul>
  <div class="comment-form">
    {{ function('comment_form') }}
  </div>
</div>
```

```twig
{# comment.twig #}
<li>
  <div>{{ comment.content }}</div>
  <p class="comment-author">{{ comment.author.name }}</p>
  {{ function('comment_form') }}
  <!-- nested comments here -->
  {% if comment.children %}
    <div class="replies">
      {% for child_comment in comment.children %}
        {% include 'comment.twig' with { comment:child_comment } %}
      {% endfor %}
    </div>
  {% endif %}
</li>
```
