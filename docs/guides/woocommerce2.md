---
title: "WooCommerce 2"
menu:
  main:
    parent: "guides"
---

## Why 2 WooCommerce Guides?
There are different ways to integrate WooCommerce with Timber.  Although the basics are the same, there are different ways and methods to achieve your desired results.  This guide is based upon using 4 other guides that are out there and some of my own coding from researching the integration. 

For the purposes of this document, I will be using Bootstrap 4.x. 

## Keeping upgrade path simple
The objective of this integration is to keep the upgrade path simple, requiring little or no intervention on your part when WooCommerce releases upgrades/updates. 

By using WooCommerce's default hooks, you can keep your upgrade path simple. Just use functions to add or remove the relevant functions.  WooCommerce has good documentation on this process located here: [WooCommerce Hooks: Actions and filters](https://docs.woocommerce.com/document/introduction-to-hooks-actions-and-filters/). 

For example, when creating the twig template for viewing a single product, I wanted to remove the product description from the tabs below the product. The code for doing this is as follows:

```php
/**
 * Remove product data tabs
 */
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );

function woo_remove_product_tabs( $tabs ) {

    unset( $tabs['description'] );          // Remove the description tab
    return $tabs;
}

```
You can call these changes in your template's `functions.php` file directly, though an include file containing all of your specific WooCommerce changes, or use a plugin like [Code Snippets](https://wordpress.org/plugins/code-snippets/). If you are new to adding such functions, I personally recommend Code Snippets, as it will not allow you to save the code if it creates a fatal error.

##Starter Theme Files Needed
In the [Timber Starter Theme](https://github.com/timber/starter-theme) there are some files that you need to make sure are in the template you are using.  If you are using the starter theme itself, no need to do anything, if you are using a custom template, make sure they are there.

These files are: `header.php`, `footer.php`, and `page-plugin.twig`. These are basically used in conjunction with plugins, such as WooCommerce, that render their own output to the template without the use of shortcodes, such as the store page. Without these files, you will get the dreaded "white screen of death" when accessing the store page.

##File Hierarchy
The Timber Starter theme has its own file hierarchy as do many other templates that are out there.  For the purposes of this document, I will be using this hierarchy for template files, please change references to files to fit your template's structure.

```
{Your Timber Template}
|Files: php template files such as `page.php`
|-Folder: views
|--Folder: layouts
|----Files: base layout files such as `base.twig`
|--Folder: pages
|---Files: twig page files such as `single-product.twig`
|--Folder: partials
|---Files: partial twig files that are called from page templates such as `footer.twig`
|--Folder: woo
|---Files: woocommerce specific twig page files such as `single-product.twig`
|---Folder: partials
|----Files: partial twig files that are called from woocommerce twig pages such as `cart.twig`
|-Folder: woocommerce
|--Files: woocommerce template files that are replaced by twig or may need to be modified via php

```

In the folder woocommerce, I just copied over `archive-product.php` , `single-product.php` and the folder and contents of the `cart` folder from the woocommerce template directory from the plugin.  This is the procedure WooCommerce tells you to do in order to do template overrides.  Although no overrides where done directly in php, having the files located here makes access for reference easier.

##Declaring WooCommerce support
You need to tell WooCommerce that your Timber theme will be overriding the standard WooCommerce templates by [declaring WooCommerce support](https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes).  This is done with the following code:
```php
function pwa_add_woocommerce_support() {
    add_theme_support( 'woocommerce', array(
        'thumbnail_image_width' => 150,
        'single_image_width'    => 300,

        'product_grid'          => array(
            'default_rows'    => 3,
            'min_rows'        => 2,
            'max_rows'        => 8,
            'default_columns' => 4,
            'min_columns'     => 2,
            'max_columns'     => 5,
        ),
    ) );
}
add_action( 'after_setup_theme', 'pwa_add_woocommerce_support' );

```
As with other calls to WooCommerce, you can call these changes in your template's `functions.php` file directly, though an include file containing all of your specific WooCommerce changes, or use a plugin like Code Snippets.

##WooCommerce.php

In the root of your theme, the same location as `single.php` or `page.php` you need to create a file `woocommerce.php`.  Using this method, you are creating  a custom WooCommerce theme using a new WooCommerce PHP page in your theme file. With this method you are defining the default taxonomy pages (archives) and single product pages.  This is where we tell WooCommerce to use Timber. Here is my `woocommerce.php` file.

```php
<?php

use Timber\Timber;

$context            = Timber::get_context();

$context['dynamic_sidebar1'] = Timber::get_widgets('footersidebar1');
$context['dynamic_sidebar2'] = Timber::get_widgets('footersidebar2');
$context['dynamic_sidebar3'] = Timber::get_widgets('footersidebar3');
$context['dynamic_sidebar4'] = Timber::get_widgets('footersidebar4');
$context['dynamic_sidebar5'] = Timber::get_widgets('topsidebar1');
$context['dynamic_sidebar6'] = Timber::get_widgets('topsidebar2');
$context['dynamic_sidebar7'] = Timber::get_widgets('topsidebar3');
$context['dynamic_sidebar8'] = Timber::get_widgets('topsidebar4');
$context['dynamic_sidebarc1'] = Timber::get_widgets('topsidebarc1');
$context['dynamic_sidebarc2'] = Timber::get_widgets('topsidebarc2');
$woocommerce_context['cart'] = WC()->cart;

if ( is_singular( 'product' ) ) {
    $context['post']    = Timber::get_post();
    $product            = wc_get_product( $context['post']->ID );
    $context['product'] = $product;

    Timber::render( 'woo/single-product.twig', $context );
} else {
    $posts = Timber::get_posts();
    $context['products'] = $posts;
    $woocommerce_context['post'] = new \Timber\Post( wc_get_page_id( 'shop' ) );
    

    if ( is_product_category() ) {

        $queried_object = get_queried_object();
        $term_id = $queried_object->term_id;
        $context['cateid'] = $term_id;
        $context['category'] = get_term( $term_id, 'product_cat' );
        $context['title'] = single_term_title( '', false );
    }

    Timber::render( 'woo/archive-product.twig', $context );
}
```
In the template I use, I already have defined 10 widget areas that use WordPress's built-in dynamic sidebars.  Since I am using Bootstrap 4.x and design "mobile-first", I opted not to use the default WooCommerce side-bar.  Just a matter of choice. 

If you want to use the 'shop-sidebar', your `woocommerce.php` file would be as follows:
```php
<?php

use Timber\Timber;

$context            = Timber::get_context();

$context['sidebar'] = Timber::get_widgets( 'shop-sidebar' );
$woocommerce_context['cart'] = WC()->cart;

if ( is_singular( 'product' ) ) {
    $context['post']    = Timber::get_post();
    $product            = wc_get_product( $context['post']->ID );
    $context['product'] = $product;

    Timber::render( 'woo/single-product.twig', $context );
} else {
    $posts = Timber::get_posts();
    $context['products'] = $posts;
    $woocommerce_context['post'] = new \Timber\Post( wc_get_page_id( 'shop' ) );
    

    if ( is_product_category() ) {

        $queried_object = get_queried_object();
        $term_id = $queried_object->term_id;
        $context['cateid'] = $term_id;
        $context['category'] = get_term( $term_id, 'product_cat' );
        $context['title'] = single_term_title( '', false );
    }

    Timber::render( 'woo/archive-product.twig', $context );
}
```
##Base.twig
With my structure the base.twig file has a few different block sections and is located here `layouts/base.twig`

```php
{% block html_head_container %}
  {% include 'partials/html-header.twig' %}
{% endblock html_head_container %}
<header id="masthead" class="site-header">
  <body class="{{body_class}}" data-template="base.twig">
      {% block nav %}
      {% include 'partials/nav.twig' %}
    {% endblock  nav %}
</header>

{% block headbarcontent %}
{% endblock headbarcontent %}

{% block carouselcontent %}
{% endblock carouselcontent %}

{% block parallaxcontent %}
{% endblock parallaxcontent %}

{% block parallax2content %}
{% endblock parallax2content %}

{% block jumboherocontent %}
{% endblock jumboherocontent %}



<main role="main" class="container pl-0 pr-0">


  {% block productextcontent %}
  {% endblock productextcontent %}

    {% block productcontent %}
    {% endblock productcontent %}

    {% block featuredcontent %}
    {% endblock featuredcontent %}

    {# controlling the display of widget area via a ACF override #}
    {% if post.enable_top_bar %}
      {% block topbar %}
        {% include 'partials/widget-areas/top-bar.twig' %}
      {% endblock topbar %}
    {% endif %}

    {# controlling the display of widget area via a ACF override #}
    {% if post.enable_top_bar %}
      {% block topbar2 %}
        {% include 'partials/widget-areas/top2-bar.twig' %}
      {% endblock topbar2 %}
    {% endif %}

    {# controlling the display of widget area via a ACF override #}
    {% if post.enable_custom_top_bar %}
      {% block customtopbar %}
        {% include 'partials/widget-areas/top-bar-custom.twig' %}
      {% endblock customtopbar %}
    {% endif %}

    {% block wcfeaturedextcontent %}
    {% endblock wcfeaturedextcontent %}

    {# controlling the display of the usual WP content area via a ACF override #}
    {%if post.disable_wordpress_content == false %}
      {% block content %}
      {% endblock content %}
    {% endif %}

  

    {% block carddecks %}
    {% endblock carddecks %}

    {% block supportcontent %}
    {% endblock supportcontent %}

    {# controlling the display of widget area via a ACF override #}
    {% if post.enable_bottom_bar or fn('is_archive') %}
      {% block bottombar %}
        {% include 'partials/widget-areas/bottom-bar.twig' %}
      {% endblock bottombar %}
    {% endif %}

    {# controlling the display of widget area via a ACF override #}
    {% if post.enable_bottom_bar_2 %}
      {% block bottombar2 %}
        {% include 'partials/widget-areas/bottom2-bar.twig' %}
      {% endblock bottombar2 %}
    {% endif %}

    {% block buttongroup %}
    {% endblock buttongroup %}

  </main>

{% block footer %}
  {% include 'partials/footer.twig' %}
{% endblock footer %}


</body>
</html>

```
As you can see there are a few blocks.  I do this so that when building a template I have various areas to put content into.  Once again, this is just a preference. I also commented the widget areas where they are toggled to display via ACF overrides.

##Single Product 
We need to create a twig file to show a single product. With my structure, it was created here: `woo/singleproduct.twig`

```php
{% extends 'layouts/base.twig' %}

{% block headbarcontent %}
    {# Puts the store name at the top of the page #}
     <div class="mb-2 mt-5 pt-3 pb-2">
        <h2<center>{{fn('get_the_title',fn('wc_get_page_id', 'shop' ))}}</center></h2>
    </div>

{% endblock headbarcontent %}

{% block featuredcontent %}
    {% include 'woo/partials/cart.twig'%}
{% endblock featuredcontent %}


{% block content %}

<article itemscope itemtype="http://schema.org/Product" class="single-product-details {{ post.class }}">

    <div class="container pt-5">
        <div id="product-{{ post.id }}" {{ fn('post_class', ['product', 'product--' ~ post.product.get_type()]) }}>
            {##
             # woocommerce_before_single_product hook.
             #
             # @hooked wc_print_notices - 10
             #}
            {% do action('woocommerce_before_single_product') %}

            <div class="row">
                <div class = "col-sm-6">
                    {##
                     # woocommerce_before_single_product_summary hook.
                     #
                     # @hooked woocommerce_show_product_sale_flash - 10
                     # @hooked woocommerce_show_product_images - 20
                     #}
                    
                    {% do action('woocommerce_before_single_product_summary') %}

                </div>
                <div class="col-sm-6">
                    <div class="product-summary">
                        {##
                         # woocommerce_single_product_summary hook.
                         #
                         # @hooked woocommerce_template_single_title - 5
                         # @hooked woocommerce_template_single_rating - 10
                         # @hooked woocommerce_template_single_price - 10
                         # @hooked woocommerce_template_single_excerpt - 20
                         # @hooked woocommerce_template_single_add_to_cart - 30
                         # @hooked woocommerce_template_single_meta - 40
                         # @hooked woocommerce_template_single_sharing - 50
                         # @hooked WC_Structured_Data::generate_product_data() - 60
                         #}
                        {% do action( 'woocommerce_single_product_summary' ) %}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <h3>Description</h3>
                    <div class="content">{{ post.content }}</div>
                    {##
                     # woocommerce_after_single_product_summary hook.
                     #
                     # @hooked woocommerce_output_product_data_tabs - 10
                     # @hooked woocommerce_upsell_display - 15
                     # @hooked woocommerce_output_related_products - 20
                     #}
                    {% do action('woocommerce_after_single_product_summary') %}
                </div>
            </div>
        {% do action('woocommerce_after_single_product') %}
        </div>
    </div>
</article>
{% endblock content %}

```
I commented the code with the hooks being called from the `do action()`.  For more information on each hook look to the WooCommerce [Hook Reference](https://docs.woocommerce.com/wc-apidocs/hook-docs.html).

You can see that my base template includes more blocks than just content. This is just my preference, so I can put the store title at the top of each page and my own cart function.

I also chose to keep the built-in image function used in WooCommerce for single products, this way any modifications chosen via product attributes, the image would be updated accordingly.

##Archive Product
We need to create a twig file to show archive products. With my structure, it was created here: `woo/archive-product.twig`
```php

{% extends 'layouts/base.twig' %}



{% block headbarcontent %}
     <div class="mb-2 mt-5 pt-3 pb-2">
        <h2><center>{{fn('get_the_title',fn('wc_get_page_id', 'shop' ))}}</center></h2>
    </div>

{% endblock headbarcontent %}

{% block featuredcontent %}
    {% include 'woo/partials/cart.twig'%}
{% endblock featuredcontent %}

{% block wcfeaturedextcontent %}
 {% include 'partials/widget-areas/top-bar.twig' %}
 <div class="container"> 
    <div><h1 class="heading-1" id="main-title">{{ title }}</h1></div>

    {#
     # woocommerce_archive_description hook.
     #
     # @hooked woocommerce_taxonomy_archive_description - 10
     # @hooked woocommerce_product_archive_description - 10
     #}
    <div class="clearfix">{% do action('woocommerce_archive_description') %}</div>

    {#
     # woocommerce_before_shop_loop hook.
     #
     # @hooked wc_print_notices - 10
     # @hooked woocommerce_result_count - 20
     # @hooked woocommerce_catalog_ordering - 30
     #}
    <div class ="clearfix">{% do action('woocommerce_before_shop_loop') %} </div>
  </div>  
   
    {% if posts %}
        <div class="container products text-center"> 
            <div class="row">
                {% for post in posts if fn('have_posts') %}
                <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3 product-items mt-3 mb-3">
                    {% include 'partials/tease-product.twig' %}
                </div>
                {% endfor %}
            </div>
        </div>

    {% elseif not fn('woocommerce_product_subcategories', [{
        before: fn('woocommerce_product_loop_start', false),
        after: fn('woocommerce_product_loop_end', false)
    }]) %}
        {##
         # woocommerce_no_products_found hook.
         #
         # @hooked wc_no_products_found - 10
         #}
        {% do action('woocommerce_no_products_found') %}

    {% endif %}

    {##
     # woocommerce_after_shop_loop hook.
     #
     # @hooked woocommerce_pagination - 10
     #}
    {% do action('woocommerce_after_shop_loop') %}

{% endblock wcfeaturedextcontent %}

{% block content %}

{% endblock content %}

{% block carddecks %}

    {% include 'woo/partials/prod-cats.twig'%}

{% endblock carddecks %}
```

##Tease Product

For the loop, my teaser used is here: `partials/tease-product.twig`

```php
<div {{ fn('post_class', ['$classes', 'entry'] ) }}  style="height: 100%;"  >{{ fn('timber_set_product', post) }}
    <div class="card bg-light border-primary" style="height: 100%;">
        <div class="card-img-top {% if not post.thumbnail %}placeholder{% endif %}">
            <a href="{{ post.link }}">
                {% if post.thumbnail %}
                   <center> <img src="{{ post.thumbnail.src('f-med') }}" /></center>
                {% else %}
                    <span class="thumb-placeholder"><i class="icon-camera"></i></span>
                {% endif %}
            </a>
        </div>
        <div class="card-header">          

            {% if post.title %}
                <h3 class="entry-title"><a href="{{ post.link }}">{{ post.title }}</a></h3>
            {% else %}
                <h3 class="entry-title"><a href="{{ post.link }}">{{ fn('the_title') }}</a></h3>
            {% endif %}
            
        </div>
        <div class="card-body">
             {% do action( 'woocommerce_after_shop_loop_item_title' ) %}
        </div>
        <div class="card-footer">
            {% do action( 'woocommerce_after_shop_loop_item' ) %}
        </div>
    </div>
</div>
```
As can be seen from the code, I built my own loop only using 2 hooks from WooCommerce. I utlized the "card" component of Bootstrap 4.x.

To make sure that products in the loop get the right context add the following function to your template's `functions.php` file directly, through an include file containing all of your specific WooCommerce changes, or use a plugin like Code Snippets. 

```php
function timber_set_product( $post ) {
    global $product;
    
    if ( is_woocommerce() ) {
        $product = wc_get_product( $post->ID );
    }
}
```

##Product Categories

I created a twig file which show the product categories or subcategories on an archive page.  It is called in the Archive Product.  It is located here: `woo/partials/prod-cats.twig`

```php
{% if cateid %}
    {% set cats = function('woocommerce_get_product_subcategories', cateid) %}
{% else %}        
    {% set cats = function('woocommerce_get_product_subcategories', 0) %}
{% endif %}

{% if cats %}
    <div class="archive cateories "> 
        <center><h3>Product Categories</h3></center>
        <div class="row">
          {% for cat in cats %}
                <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 category-items mt-3 mb-3">
                    {# include 'partials/tease-product.twig' #}
                    <div class="card bg-light border-primary" style="height: 100%;">
                        {% do action( 'woocommerce_before_subcategory',cat ) %}
                        <div class="card-header"> 
                            <center>  
                            {% do action( 'woocommerce_shop_loop_subcategory_title',cat ) %}
                            {% do action( 'woocommerce_after_subcategory_title',cat ) %}
                            </center>
                        </div>
                        <div class="card-body">
                            <center> {% do action( 'woocommerce_before_subcategory_title',cat ) %}</center>
                        </div>
                        <div class="card-footer">{% do action( 'woocommerce_after_subcategory', cat ) %}<center><strong>Category</strong></center></div>
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>
{% endif %}
```
This file, like others in this documentation use Bootstrap 4.x.

##Functions and Modifications

So far in this documentation, we have made 2 changes called through php.  These are `Declaring WooCommerce Support` and `Timber Set Product`. 

The following are additional functions added. As with other calls to WooCommerce,you can call these changes in your template's `functions.php` file directly, though an include file containing all of your specific WooCommerce changes, or use a plugin like Code Snippets.

###Change Number of Related Products
```php
/**
 * Change number of related products output
 */ 
function woo_related_products_limit() {
  global $product;
    
    $args['posts_per_page'] = 6;
    return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'pwa_related_products_args' );
  function pwa_related_products_args( $args ) {
    $args['posts_per_page'] = 4; // 4 related products
    $args['columns'] = 4; // arranged in 2 columns
    return $args;
}
```

### Remove Description Tab
```php
/**
 * Remove product data tabs
 */
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );

function woo_remove_product_tabs( $tabs ) {

    unset( $tabs['description'] );          // Remove the description tab
    return $tabs;
}
```
### Rename Product Data Tab
```php
/**
 * Rename product data tabs
 */
add_filter( 'woocommerce_product_tabs', 'woo_rename_tabs', 98 );
function woo_rename_tabs( $tabs ) {


    $tabs['additional_information']['title'] = __( 'Specs' );   // Rename the additional information tab

    return $tabs;

}
```
### Featured Products
To save resources, featured products was re-written as a function to be called. 
```php
function pwa_fprods() {
  global $woocommerce;
   
   
    $fproducts = wc_get_featured_product_ids();
    $context['fproducts'] = $fproducts;

    return Timber::compile( 'woo/partials/fprods.twig', $context );
} 
```
Create this twig file: `woo/partials/fprods.twig`
```php
{% if fproducts %}
    <div class="container products "> <center><h3>Featured Products</h3></center>
    <div class="row">

    {% for fproduct in fproducts %}
        {% if loop.index <= 4 %}
         <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 category-items mt-3 mb-3">
            <div class="card bg-light border-primary " style="height: 100%;">



                <div class="card-header">
                    <center><a href="{{function('get_the_permalink', fproduct) }}">         
                    <h3 class="entry-title">{{function('get_the_title', fproduct) }}</h3>
                    </a>
                </center>
                </div>
                    

                <div class="card-body">
                    <center><a href="{{function('get_the_permalink', fproduct) }}">
                    {% if function('get_post_thumbnail_id', fproduct) %}
                    <img class= "img-fluid" src="{{ Image(function('get_post_thumbnail_id', fproduct)).src('shop_catalog') }}" />
                    {% else %}
                    <span class="thumb-placeholder"><i class="icon-camera"></i></span>
                    {% endif %}
                    </a></center>
                    <center>{% if fn('get_post_meta', fproduct, '_price', true) > 0  %}
                        {{fn('wc_price', fn('get_post_meta', fproduct, '_price', true))}}
                    {% endif %}
                    
                </center>

             </div>
             

                <div class="card-footer">
                    <center><h3><span class="badge badge-primary">Featured</span></h3></center>
                </div>
            </div>
        </div>
    {% endif %}
        {% endfor %}
</div>
</div>
{% endif %}
```
You can now call the featured products display with the function `{{fn('pwa_fprods')}}`

### Sale Products
To save resources, sale products was re-written as a function to be called. 
```php
function pwa_sprods() {
  global $woocommerce;
   

    $sproducts = wc_get_product_ids_on_sale();
    $context['sproducts'] = $sproducts;

    return Timber::compile( 'woo/partials/sprods.twig', $context );
} 
```
Create this twig file: `woo/partials/sprods.twig`
```php
    {% if sproducts %}
    <div class="container products "> <center> <h3>On Sale</h3></center>
    <div class="row">

    {% for sproduct in sproducts %}
        {% if loop.index <= 4 %}
         <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 category-items mt-3 mb-3">
            <div class="card bg-light border-primary " style="height: 100%;">



                <div class="card-header">
                    <center><a href="{{function('get_the_permalink', sproduct) }}">         
                    <h3 class="entry-title">{{function('get_the_title', sproduct) }}</h3>
                    </a>
                </center>
                </div>
                    

                <div class="card-body">
                    <center><a href="{{function('get_the_permalink', sproduct) }}">
                    {% if function('get_post_thumbnail_id', sproduct) %}
                    <img class= "img-fluid" src="{{ Image(function('get_post_thumbnail_id', sproduct)).src('shop_catalog') }}" />
                    {% else %}
                    <span class="thumb-placeholder"><i class="icon-camera"></i></span>
                    {% endif %}
                    </a></center>
                    <center><p class="price"><del><span class="woocommerce-Price-amount amount">{{fn('wc_price', fn('get_post_meta', sproduct, '_regular_price', true))}}</span></del> <ins><span class="woocommerce-Price-amount amount">{{fn('wc_price', fn('get_post_meta', sproduct, '_sale_price', true))}}</span></ins></p>
                </center>
             </div>
             

                <div class="card-footer">
                    <center><h3><span class="badge badge-success">On Sale</span></h3></center>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
</div>
</div>
{% endif %}
```
You can now call the featured products display with the function `{{fn('pwa_sprods')}}`

### Top Selling Products
To save resources, top selling products was re-written as a function to be called. 
```php
function pwa_tprods() {
  global $woocommerce;
    $args = array(
      'post_type' => 'product',
      'fields'        => 'ids',
      'meta_key' => 'total_sales',
      'orderby' => 'meta_value_num',
      'posts_per_page' => 4,
    );
    $result_query = new WP_Query( $args );
    $tproducts =  $result_query->posts;
    $context['tproducts'] = $tproducts;
    wp_reset_postdata();
    return Timber::compile( 'woo/partials/tprods.twig', $context );
} 
```
Create this twig file: `woo/partials/tprods.twig`
```php
    {% if tproducts %}
    <div class="container products "> <center> <h3>Top Sellers</h3></center>
    <div class="row">

    {% for tproduct in tproducts %}
        {% if loop.index <= 4 %}
         <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 category-items mt-3 mb-3">
            <div class="card bg-light border-primary " style="height: 100%;">



                <div class="card-header">
                    <center><a href="{{function('get_the_permalink', tproduct) }}">         
                    <h3 class="entry-title">{{function('get_the_title', tproduct) }}</h3>
                    </a>
                </center>
                </div>
                    

                <div class="card-body">
                    <center><a href="{{function('get_the_permalink', tproduct) }}">
                    {% if function('get_post_thumbnail_id', tproduct) %}
                    <img class= "img-fluid" src="{{ Image(function('get_post_thumbnail_id', tproduct)).src('shop_catalog') }}" />
                    {% else %}
                    <span class="thumb-placeholder"><i class="icon-camera"></i></span>
                    {% endif %}
                    </a></center>
                    <center>{% if fn('get_post_meta', tproduct, '_price', true) > 0  %}
                        {{fn('wc_price', fn('get_post_meta', tproduct, '_price', true))}}
                    {% endif %}
                </center>
             </div>
             

                <div class="card-footer">
                    <center><h3><span class="badge badge-info">Top Seller</span></h3></center>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
</div>
</div>
{% endif %}
```
You can now call the top selling products display with the function `{{fn('pwa_tprods')}}`

### New Products
To save resources, new products was re-written as a function to be called. 
```php
function pwa_nprods() {
  global $woocommerce;
$args = array(
    'post_type' => 'product',
    'fields'        => 'ids',
    'stock' => 1,
    'orderby' =>'date',
    'order' => 'DESC',
    'posts_per_page' => 4,
);
    $result_query = new WP_Query( $args );
    $nproducts =  $result_query->posts;
    $context['nproducts'] = $nproducts;
    wp_reset_postdata();
    return Timber::compile( 'woo/partials/nprods.twig', $context );
}

```
Create this twig file: `woo/partials/nprods.twig`
```php
    {% if nproducts %}
    <div class="container products "> <center> <h3>New Product</h3></center>
    <div class="row">

    {% for nproduct in nproducts %}
        {% if loop.index <= 4 %}
         <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3 category-items mt-3 mb-3">
            <div class="card bg-light border-primary " style="height: 100%;">



                <div class="card-header">
                    <center><a href="{{function('get_the_permalink', nproduct) }}">         
                    <h3 class="entry-title">{{function('get_the_title', nproduct) }}</h3>
                    </a>
                </center>
                </div>
                    

                <div class="card-body">
                    <center><a href="{{function('get_the_permalink', nproduct) }}">
                    {% if function('get_post_thumbnail_id', nproduct) %}
                    <img class= "img-fluid" src="{{ Image(function('get_post_thumbnail_id', nproduct)).src('shop_catalog') }}" />
                    {% else %}
                    <span class="thumb-placeholder"><i class="icon-camera"></i></span>
                    {% endif %}
                    </a></center>
                    <center>{% if fn('get_post_meta', nproduct, '_price', true) > 0  %}
                        {{fn('wc_price', fn('get_post_meta', nproduct, '_price', true))}}
                    {% endif %}</center>
             </div>
             

                <div class="card-footer">
                    <center><h3><span class="badge badge-warning">New Product</span></h3></center>
                </div>
            </div>
        </div>
    {% endif %}
{% endfor %}
</div>
</div>
{% endif %}
```
You can now call the tnew products display with the function `{{fn('pwa_nprods')}}`

## Mini Cart Them
This is the code to add your own mini cart theme that will update automatically when an item is added to the cart. Add this code in your template's `functions.php` file directly or use a plugin like Code Snippets.
```php
require get_template_directory() . '/inc/minicarttheme.php';
```
The code for `/inc/minicarttheme.php`
```php
<?php

use Timber\Timber;

class Theme_Mini_Cart {
    public function __construct() {
        add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'cart_link_fragment' ] );
    }

    /**
     * Cart Fragments.
     *
     * Ensure cart contents update when products are added to the cart via AJAX.
     *
     * @param  array $fragments Fragments to refresh via AJAX.
     * @return array            Fragments to refresh via AJAX.
     */
    public function cart_link_fragment( $fragments ) {
        global $woocommerce;

        $fragments['a.cart-mini-contents'] = Timber::compile(
            'woo/fragment-link.twig',
            [ 'cart' => WC()->cart ]
        );

        return $fragments;
    }
}

new Theme_Mini_Cart();
```

The code for `woo/fragment-link.twig`
```php

{% do action( 'woocommerce_cart_totals_before_shipping' ) %}
<a
class="cart-mini-contents"
href="{{ fn('wc_get_cart_url') }}"
title="{{'View Cart'}}"
>
 <span class="amount">{{ cart.get_cart_subtotal }}</span>{{'/'}}
    <span class="count">{{ cart.get_cart_contents_count }}</span>{{' items'}}
</a>
```
Then create this file `woo/partials/cart.twig`
```php
    <div class="container-fluid  text-center "> 
    <img src = '/wp-content/uploads/2018/06/cart.png'>
       {% include 'woo/fragment-link.twig' %}
    </div>

```
The image above will change depending on the card image you use.  Then in your template, where you want the minicart to be, use this code:
`{% include 'woo/partials/cart.twig'%}`


