---
title: "Sqaure Payment Processor Integration"
menu:
  main:
    parent: "guides"
---

Integration of Square Payment Processing

## Why this integration

Square integration is already availalbe for WooCommerce. For the project this code was written for, integrating with WooCommerce for purchasing the item was overkill and to resource intensive.  Also, WooCommerce would have required integration into another post type for tracking.

The project was for a support ticket system in WordPress. This integration was based upon a support site that sells time packages for support access.  Once the user purchased support time, the time spent working on a ticket was deducted from the user's pre-purchased time.

The client at any time could log into the ticket system to see how much time was remaining with a detailed breakdown of time purchased and on which tickets time was used. 

ACF was used to create the fields for the custom post type which integrates the billing system. Without going into the ACF Setup or CPT in detail, the fields required for this integration are as follows:

1. idempotency (text field)
2. purchased (select field - hourly support package purchased 1,2,4 or 8 hours)
3. description (text)
4. price (number)
5. transaction_date (text)
6. client (user)
7. pwa_oreder_id (text)
8. time_adjust (number)
9. sq_transaction (text)
10. sq_checkout (text)
11. sq_status (text)

I decided on Square Payment Integration due to the excellent documentation and support availalbe. As with any API integration, the SDK was used (in this case PHP) to handle the main functions and integration to the payment processor.   These files were not touched, as to keep the upgrade path simple if the API changed.

So most of the integration in PHP, but the templates that the user sees are Timber and the variables are passed to the API using Timber/Twig.

For the purposes of this document, Bootstrap 4.x is being used.

## Requirements
1. You must have a Square processing account which is easy to set up. If you do not have one, [you can sign up here](https://squareup.com/dashboard/).
2. Create access credentials in the [Application Dashboard](https://connect.squareup.com/apps).
3. We will be using the [Square Connect APIs](https://squareup.com/developers).  You will need to download and install the [PHP client library for the Square Connect APIs](https://github.com/square/connect-php-sdk#without-command-line-access).  I chose the installation method of "without command line access", which the link will take you to. We will be integrating with Checkout API. I suggest you read that [Setup Guide](https://docs.connect.squareup.com/payments/checkout/setup). Make note of the directory that you install the PHP SDK package into.  You will need to change your paths in the code to follow accordingly.  For the purposes of this documentation we will use `/your_template/php_sdk/`.


## Creating the order

The easiest way to set this up is to use a pricing page, this page will contain buttons, once click on by the user it will create the order. In this document, we will use the 1-hour package.

The user clicks to purchase a one hour package and is taken to `page-purchase-1-hour-package.php`
```php
<?php

$largs = array(
    'echo'           => true,
    'form_id'        => 'loginform',
  );
$context = Timber::get_context();
$context['current_user'] = new Timber\User();

$content['largs'] = $largs;
$post = new TimberPost();
$context['post'] = $post;
Timber::render( 'pages/purchase-1-hour-package.twig', $context );
```

`pages/purchase-1-hour-package.twig`

```php

{% extends 'layouts/boot-base.twig' %}

{% block content %}
  <div class="content-wrapper">
    <article class="post-type-{{post.post_type}}" id="post-{{post.ID}}">
      <section class="article-content">
        <div class="article-body">
          {% if not user %}
          <div class ="d-flex justify-content-center">

          <div class="col-md-6">
                        <div class="card mt-2 mb-2">
                          <p class="card-header"><strong>Please login to purchase a support package.  This allows us to track time against your account.</strong></p>
                <div class = "card-body">

              {{ function('wp_login_form', {object: largs}) }}

            <p class ="card-text"><a href="{{ function('wp_lostpassword_url') }}" title="Lost Password">Lost Password</a></p></div>
            <p class="card-footer"> Not Registered?  <a href="{{ function('wp_registration_url')}}"> Register Now</a></p>

            </div>
          </div>
</div>
              {% else %}
  {% set idempotency = function('uniqid', current_user.user_login ) %}
  {% set assoc = {'post_author': 1 , 'post_content': idempotency, 'post_title': idempotency , 'post_status': 'publish','post_type': 'hourypakage'} %}
  {% set refid = function('wp_insert_post', assoc, wp_error ) %}
  {% set packid = function('update_post_meta', refid, 'idempotency', idempotency ) %}
  {% set packageid = function('update_post_meta', refid, 'purchased', '1') %}
  {% set package = function('update_post_meta', refid, 'decsription', post.onehourcatno ) %}
  {% set price = function('update_post_meta', refid, 'price', post.onehourprice ) %}
  {% set client = function('update_post_meta', refid, 'client', current_user.id ) %}
  {% set order = function('update_post_meta', refid, 'pwa_order_id', refid ) %}
  {% set order = function('update_post_meta', refid, 'sq_status', 'Creating' ) %}
            <div class = "card pb-5">
            <div class = "card-header"><center><h4>Please review the information below:</h4></center></div>

    <div class="card-body ">
    <center>
    <p>Your user name: {{ current_user.user_login}}</p>
    <p>Your email address: {{ current_user.user_email }} </p>
    <p>Package -{{ post.onehourcatno }}</p>
    <p>Description - {{ post.onehourcard }}</p>
    <p>Price - {{"$"}}{{ "%.0f"|format(post.onehourprice) }}</p>
    <p> PW.A order number:  {{refid}} {{"-"}} {{idempotency}}</p>
    <p>If everything is correct, please click on the "Continue" button below.</p>
	</center>

    </div>
	<center><a class="btn btn-primary" href="{{ function('get_permalink', refid) }}" role="button">Continue</a>
	</center>

  	</div>
 	</div>


  {% endif %}
 	</section>
 </article>


 <!-- /content-wrapper -->
  {% endblock %}
```
The code above sets up the order/invoice for processing when the user clicks on the button to purchase the 1 hour package. 

First, we check to see if the user is logged in, if not we direct to the login.  This way we have a user to tie the order to.

Next, we assign an idempotency to the order, which is required by Square.  We then assign the other variables needed to send the invoice for checkout.

The user is presented with a confirmation and is required to confirm the purchase details.  Upon confirming said details, the user is taken to checkout.

## Checkout

The user, once confirming the purchase details, is redirected to `single-hourypakage.php`

```php
<?php

$id = get_the_ID();

if ( !empty( get_post_field('sq_transaction') ) ) { 

$context = Timber::get_context();
$post = Timber::query_post($id);
$context['post'] = $post;
Timber::render ( 'pages/single-hourypakage.twig' , $context);
} else {
global $post;
$currOrder = (get_the_ID());

// Include the Square Connect API resources, store config, and helper functions
require_once '/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/your_template/php_sdk/autoload.php';
require_once '/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/your_template/php_sdk/store-config.php';
require_once '/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/your_template/php_sdk/sc-helper-func.php';

// HELPER FUNCTION: Repackage the order information as an array
$orderArray = getOrderAsArray($currOrder);
echo '<pre>';
print_r($orderArray);
echo '</pre>';
// CONFIG FUNCTION: Create a Square Checkout API client if needed
initApiClient();

// Create a new API object to send order information to Square Checkout
$checkoutClient = new \SquareConnect\Api\CheckoutApi();

try {

  // Send the order array to Square Checkout
  $apiResponse = $checkoutClient->createCheckout(
    $GLOBALS['LOCATION_ID'],
    $orderArray
  );

  // Grab the redirect url and checkout ID sent back
  $checkoutUrl = $apiResponse['checkout']['checkout_page_url'];
  $checkoutID = $apiResponse['checkout']['id'];

  // HELPER FUNCTION: save the checkoutID so it can be used to confirm the
  // transaction after payment processing
  saveCheckoutId($orderArray['order']['reference_id'], $checkoutID);

} catch (Exception $e) {
  echo "The SquareConnect\Configuration object threw an exception while " .
       "calling CheckoutApi->createCheckout: ", $e->getMessage(), PHP_EOL;
  exit();
}

// Redirect the customer to Square Checkout
header("Location: $checkoutUrl");
}
```
The code above pulls up the post id of the order created.  It checks to see if the field `sq_transaction` is empty.  If it is the data is sent to the square checkout.

If the field `sq_transaction` is not empty, the code renders the information for the purchase for the user (purchase successful or failure details) using `pages/single-hourypakage.twig`.

```php
{% extends 'layouts/base.twig' %}

{% block content %}
  <div class="content-wrapper">
    <article class="post-type-{{post.post_type}}" id="post-{{post.ID}}">
      <section class="article-content">
        <div class="article-body">
          {% if not user %}
            <div class="d-flex justify-content-center">

              <div class="col-md-6">
                <div class="card mt-2 ">
                  <p class="card-header">
                    <strong>Please login to view your trnsaction.</strong>
                      <div class="card-body">

                        {{ function('wp_login_form', {object: largs}) }}
                        <p class="card-text">
                          <a href="{{ function('wp_lostpassword_url') }}" title="Lost Password">Lost Password</a>
                        </$ <p class="card-footer">
                        Not Registered?
                        <a href="{{ function('wp_registration_url')}}">
                          Register Now</a>
                      </p>

                    </div>
                  </div>
                </div>
              {% endif %}
		{% set vclient = post.client|number_format %}
		{% set vuser =  user.id|number_format %}
		{% if vclient != vuser %}
			{{"You are not authorized to view this transaction."}}
		{% else %}
		{% if post.sq_status == 'CAPTURED' %}
		<div class="card ">
                  <div class="card-header">
                    <center>
                      <h4>Purchase Confirmation</h4>
                    </center>
                  </div>
                  <div class="card-body ">
                    <p>Your user name:
                      {{ user.user_login}}</p>
                    <p>Your email address:
                      {{ user.user_email }}
                    </p>
                    <p>Package -{{ post.decsription }}</p>
                    <p>Price -
                      {{"$"}}{{ "%.0f"|format(post.price) }}</p>
                    <p>
                      PW.A order number:
                      {{post.id}}
                      {{"-"}}
                      {{post.idempotency}}</p>
                    <p>
                      Paid on:
                      {{ post.transaction_date }}</p>
                    <p>
                      Time added to account:
                      {{post.time_adjust }}</p>
                  </div>
                </div>
		{% endif %}
              {% if post.sq_status == "ERROR" %}
                <div class="card pb-1">
                  <div class="card-header">
                    <center>
                      <h4>Error on Transaction</h4>
                    </center>
                  </div>
                  <div class="card-body ">
                    <p>There was an error processign your payment. We show that your card was not charged. You can try creating a new purchase</p>
                    </div>
                  </div>
                {% endif %}		
		{% endif %}
		</div>
		</article>
		</section>
{% endblock content %}


```

The reason for this is to use the same custom post type to create the checkout and also serve as a receipt/confirmed purchase page.

As can be seen from the code above, we perform a check to make sure the user the transaction is for is the only user that can view the transaction. 

If the transaction is being sent to checkout, integration to the Square PHP SDK is required.

`/your_template/php_sdk/autoload.php` (No Changes to file required)


`/your_template/php_sdk/store-config.php`

Change the first 5 lines to read as follows:

```php
<?php
require_once '/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/your_template/php_sdk/autoload.php';
$GLOBALS['ACCESS_TOKEN'] = "your_access token";
$GLOBALS['STORE_NAME'] = "your_store_name";
$GLOBALS['LOCATION_ID'] = "your_location_id";
$GLOBALS['API_CLIENT_SET'] = false;
```
For testing, use the sandbox credentials which you can get instructions for from the API documents refrenced in the beginning of this document. Remember to change to live when ready to start processing transactions. 

`/your_template/php_sdk/sc-helper-func.php`

```php
<?php
function saveCheckoutId($currOrder, $checkoutId) {
$success = update_post_meta( $currOrder, 'sq_checkout', $checkoutId );
$statusnow = update_post_meta( $currOrder, 'sq_status', 'Checkout' );
  return $success;
}
function getCheckoutId($currOrder) {
 $checkoutId = get_field( 'sq_checkout', $currOrder );
  return $checkoutId;
}
function getOrderTotal($currOrder) {
 $orderTotal = get_field( 'price', $currOrder );
    return $orderTotal;
}
function getShippingCost($currOrder) {
$shippingAmount = 0;
    return $shippingAmount;
}
function verifyTransaction(
  $getResponse,  $apiResponse,  $savedCheckoutId,  $savedOrderTotal) {
  $savedCheckoutId = get_field( 'pwa_order_id', $returnedOrderId);
  $savedOrderTotal = get_field( 'price', $returnedOrderId) *100;
  $calculatedOrderTotal = 0;
  $cardCaptured = false;
  $totalMatch = false;
  $checkoutIdMatch = false;
  foreach ($apiResponse['transaction']['tenders'] as $tender) {
    $calculatedOrderTotal += $tender['amount_money']['amount'];
    if ($tender['type'] == "CARD") {
      $cardCaptured = ($tender['card_details']['status'] == "CAPTURED");
      if (!cardCaptured) { return false; }
    }
  }
  $totalMatch = ($calculatedOrderTotal == $savedOrderTotal);
  $checkoutIdMatch = ($returnedCheckoutId == $savedCheckoutId);
  return ($totalMatch && $cardCaptured && $checkoutIdMatch);
}
function getOrderAsArray($currOrder) {
  $idempotency_key = get_field( 'idempotency', $currOrder );
  $reference_id = get_field( 'pwa_order_id', $currOrder );
  $name = get_field ( 'decsription' ,$currOrder);
  $quantity = "1";
  $amount = get_field( 'price', $currOrder ) * 100;
  $currency = "USD";
  $merchant_support_email = "your_square_merchant_email";
  $client_info = get_userdata(get_field( 'client', $currOrder ));
  $pre_populate_buyer_email = $client_info->user_email;
  $orderArray =array(
	"redirect_url" => "https://your_url_to/make-a-payemnt",
      "idempotency_key" => $idempotency_key,
      "order" => array(
        "reference_id" => $reference_id,
        "line_items" => array(
          array(
            "name" => $name,
            "quantity" => "1",
            "base_price_money" => array(
              "amount" => $amount,
              "currency" => $currency
            ),
          ),
        ),
      "merchant_support_email" => $merchant_support_email,
      "pre_populate_buyer_email" => $pre_populate_buyer_email,
    )
  );
  return $orderArray;
}

```
Remember to replace:

`$merchant_support_email = "your_square_merchant_email";` with your Square email account.

`"redirect_url" => "https://your_url_to/make-a-payemnt",` with the URL for the user to be sent to after checkout.  I will be the URL that your WordPress creates for `page-make-a-payment.php`.

##Verify Transaction after checkout

`page-make-a-payment.php`

```php
<?php
$returnedTransactionId = $_GET["transactionId"];
$returnedOrderId = $_GET["referenceId"];
$returnedCheckoutId =  $_GET["checkoutId"];
$transID = $returnedTransactionId;
require_once '/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/your_template/php_sdk/autoload.php';
require_once '/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/your_template/php_sdk/store-config.php';
require_once '/opt/bitnami/apps/wordpress/htdocs/wp-content/themes/your_template/php_sdk/sc-helper-func.php';
// CONFIG FUNCTION: Create a Square Checkout API client if needed
initApiClient();

// Create a new API object to verify the transaction
$transactionsClient = new \SquareConnect\Api\TransactionsApi();

  // Get transaction details for this order from the Transactions API endpoint
// Ping the Transactions API endpoint for transaction details
try {

  // Get transaction details for this order from the Transactions API endpoint
  $apiResponse = $transactionsClient->retrieveTransaction(
    $GLOBALS['LOCATION_ID'],
    $returnedTransactionId
  );

} catch (Exception $e) {
  echo "The SquareConnect\Configuration object threw an exception while " .
       "calling TransactionApi->retrieveTransaction: ",
       $e->getMessage(), PHP_EOL;
  exit;
}
echo '<pre>';
print_r($apiResponse);
echo '</pre>';

// HELPER FUNCTION: verify the order information
//$validTransaction = verifyTransaction($_GET, $apiResponse, $savedCheckoutId, $savedOrderTotal);
$savedCheckoutId = get_field( 'sq_checkout', $returnedOrderId);
$savedOrderTotal = get_field( 'price', $returnedOrderId) *100;
  foreach ($apiResponse['transaction']['tenders'] as $tender) {


    $calculatedOrderTotal += $tender['amount_money']['amount'];

    if ($tender['type'] == "CARD") {
      $cardCaptured = ($tender['card_details']['status'] == "CAPTURED");
      if (!cardCaptured) { return false; }
    }
  }
$time_adjust = 0;
$cardCaptured = ($tender['card_details']['status'] == "CAPTURED");
  $totalMatch = ($calculatedOrderTotal == $savedOrderTotal);
  $checkoutIdMatch = ($returnedCheckoutId == $savedCheckoutId);
if ($totalMatch && $cardCaptured && $checkoutIdMatch) {

$statusnow = update_post_meta( $returnedOrderId, 'sq_status', 'CAPTURED' );
$transaction_date = update_post_meta( $returnedOrderId, 'transaction_date', date("y-m-d") );
$transID = update_post_meta( $returnedOrderId, 'sq_transaction', $transID );
if (get_field('purchased' , $returnedOrderId) == '1') {
$time_adjust = 60;
}
if (get_field('purchased' , $returnedOrderId) == '2') {
$time_adjust = 120;
}
if (get_field('purchased' , $returnedOrderId) == '4') {
$time_adjust = 240;
}

if (get_field('purchased' , $returnedOrderId) == 'S') {
$time_adjust = 240;
}
if (get_field('purchased' , $returnedOrderId) == '8') {
$time_adjust = 480;
}

$timeadjap = update_post_meta( $returnedOrderId, 'time_adjust', $time_adjust );
$url = get_permalink($returnedOrderId) ;
wp_redirect( $url );
exit;

} else {

$timeadjap = update_post_meta( $returnedOrderId, 'time_adjust', $time_adjust );
$statusnow = update_post_meta( $returnedOrderId, 'sq_status', 'ERROR' );
$transaction_date = update_post_meta( $returnedOrderId, 'transaction_date', date("y-m-d") );
$transID = update_post_meta( $returnedOrderId, 'sq_transaction', $transID );
$url = get_permalink($returnedOrderId) ;
wp_redirect( $url );
exit; 
}
```
This code captures the response from Square checkout and then validates the response with Square.  If it validates it updates the custom post type accordingly.  It then redirects the user to the existing confirmation page (Transaction shows paid).
