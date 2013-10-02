# Universal Analytics for PHP 

This library provides a PHP interface for the Universal Analytics Measurement Protocol, with an interface modeled (loosely) after Google's `analytics.js`.
Future releases will support an interface similar to `ga.js`, for legacy implementations which already integrate our [legacy library](https://github.com/analytics-pros/google-analytics-php-legacy).

**NOTE** that this project is still _beta_; some features of the Measurement Protocol aren't fully represented, and new features will be added in the (hopefully) nearer future. Please feel free to file issues for feature requests.

# Contact
Email: `opensource@analyticspros.com`

# Usage

For the most accurate data in your reports, Analytics Pros recommends establishing a distinct ID for each of your users, and integrating that ID on your front-end web tracking, as well as back-end tracking calls. This provides for a consistent, correct representation of user engagement, without skewing overall visit metrics (and others).

A simple example:

```php
<?php

require('universal-analytics.php');

$t = new Tracker(/* web property id */ 'UA-XXXXX-Y', /* client id */ 'abc', /* user id */ null);

$t->set('dimension1', 'pizza');

$t->send(/* hit type */ 'event', /* hit properties */ array(
  'eventCategory' => 'test events',
  'eventAction' => 'testing',
  'eventLabel' => '(test)'
));

?>
```

Currently all tracking hits (using `send`) require an array (dictionary) of properties related to the hit type.


# Features not implemented

* Throttling 
* GA Classic interface

We're particularly interested in the scope of throttling for back-end tracking for users who have a defined use-case for it. Please [contact us](mailto:opensource@analyticspros.com) if you have such a use-case.


# License

universal-analytics-php is licensed under the [BSD license](./LICENSE)
