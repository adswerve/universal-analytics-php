# Universal Analytics for PHP 

This library provides a PHP interface to Google Analytics, supporting the Universal Analytics Measurement Protocol, with an interface modeled (loosely) after Google's `analytics.js`.
Future releases will support an interface similar to `ga.js`, for legacy implementations which already integrate our [legacy library](https://github.com/analytics-pros/google-analytics-php-legacy).

**NOTE** that this project is still _beta_; some features of the Measurement Protocol aren't fully represented, and new features will be added in the (hopefully) nearer future. Please feel free to file issues for feature requests.

# Contact
Email: `opensource@analyticspros.com`

# Usage

For the most accurate data in your reports, Analytics Pros recommends establishing a distinct ID for each of your users, and integrating that ID on your front-end web tracking, as well as back-end tracking calls. This provides for a consistent, correct representation of user engagement, without skewing overall visit metrics (and others).

A few simple examples:

```php
<?php

require('universal-analytics.php');

$t = new Tracker(/* web property id */ 'UA-XXXXX-Y', /* client id */ 'abc', /* user id */ null);

// Set a custom dimension
$t->set('dimension1', 'pizza');

// Send an event
$t->send(/* hit type */ 'event', /* hit properties */ array(
  'eventCategory' => 'test events',
  'eventAction' => 'testing',
  'eventLabel' => '(test)'
));


// Send a transaction
$tracker->send('transaction', array(
  'transactionId' => $transaction_id,
  'transactionAffiliation' => $affiliate,
  'transactionRevenue' => $total_revenue, // not including tax or shipping
  'transactionShipping' => $total_shipping,
  'transactionTax' => $total_tax
));

// Send an item record related to the preceding transaction
$tracker->send('item', array(
  'transactionId' => $transaction_id,
  'itemName' => $item_name,
  'itemCode' => $item_sku,
  'itemCategory' => $item_variation,
  'itemPrice' => $item_unit_price,
  'itemQuantity' => 1
));


?>
```

All messages will be flushed when the tracker object is destroyed.

Currently all tracking hits (using `send`) require an array (dictionary) of properties related to the hit type.


# Features not implemented

* Throttling 
* GA Classic interface

We're particularly interested in the scope of throttling for back-end tracking for users who have a defined use-case for it. Please [contact us](mailto:opensource@analyticspros.com) if you have such a use-case.


# License

universal-analytics-php is licensed under the [BSD license](./LICENSE)
