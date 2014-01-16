<?php

define('ANALYTICS_HASH_IDS', true);
require('universal-analytics.php');

$t = new Tracker(/* tracking id */ 'UA-XXXXX-Y', /* client id */ 'abc', /* user id */ null, /* debug */ true);

$t->set('dimension1', 'tested');


if(true) $t->send('event', array(
  'eventAction' => 'testing',
  'eventCategory' => 'test events',
  'eventLabel' => '(test)'
));

?>
