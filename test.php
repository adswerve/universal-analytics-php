<?php

require('universal-analytics.php');

$t = new Tracker('UA-XXXXX-Y', 'abc', null);

$t->set('dimension1', 'tested');


if(false) print_r($t->hitdata('event', array(
  'eventAction' => 'testing',
  'eventCategory' => 'test events',
  'eventLabel' => '(test)'
)));


if(true) $t->send('event', array(
  'eventAction' => 'testing',
  'eventCategory' => 'test events',
  'eventLabel' => '(test)'
));

?>
