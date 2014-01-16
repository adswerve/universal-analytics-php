<?php

define('ANALYTICS_HASH_IDS', true);
require('universal-analytics.php');

$t = new Tracker('UA-XXXXX-Y', 'abc', null);

$t->set('dimension1', 'tested');

$t->debug = true;

if(true) $t->send('event', array(
  'eventAction' => 'testing',
  'eventCategory' => 'test events',
  'eventLabel' => '(test)'
));

?>
