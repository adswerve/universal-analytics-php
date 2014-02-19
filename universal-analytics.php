<?php
###############################################################################
# Universal Analytics for PHP 
# Copyright (c) 2013, Analytics Pros
# 
# This project is free software, distributed under the BSD license. 
# Analytics Pros offers consulting and integration services if your firm needs 
# assistance in strategy, implementation, or auditing existing work.
###############################################################################

defined('ANALYTICS_HASH_IDS') || define('ANALYTICS_HASH_IDS', false);

class UniversalBeacon {
  /* Implements CURL POST to Google Analytics */
  private $endpoint = 'https://www.google-analytics.com/collect';
  private $data = null;
  private $user_agent = null;
  
  public function __construct($data, $user_agent = null){
    $this->data = $data;
    $this->user_agent = $user_agent;
  }

  public function handle($data_update = null, $debug = false){
    $data = array_merge($this->data, (array)$data_update);
    return self::curl($this->endpoint, $data, $this->user_agent, $debug);
  }

  // Issue an HTTP request via CURL
  public static function & curl($url, $data, $ua = null, $debug = true){
    $h = curl_init($url);
    $payload = self::combine($data);
    curl_setopt($h, CURLOPT_AUTOREFERER, true);
    curl_setopt($h, CURLOPT_NOPROGRESS, true);
    if($debug){
      print_r($data);
      print_r($payload);
      print "\n"; # readability
    }
    if(is_string($ua)){
      curl_setopt($h, CURLOPT_USERAGENT, $ua);
    }
    curl_setopt($h, CURLOPT_VERBOSE, $debug);
    curl_setopt($h, CURLOPT_POST, count($data));
    curl_setopt($h, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($h, CURLOPT_HEADER, 0);
    return $h;
    // $v = curl_exec($h);
    // curl_close($h);
    // return $v;
  }

  // A simpler parameter joining method
  public static function combine($params, $pair = '=', $sep = '&'){
    $c = count($params);
    return $c ? implode($sep, array_map(
      'sprintf', // NOTE: even built-in functions require names given as strings when mapping 
      array_fill(0, $c, '%s%s%s'), // format string 
      array_keys($params), // keys
      array_fill(0, $c, $pair),  // pairing (=)
      array_map('urlencode', array_values($params)) // values
    )) : '';
  }

}


class UniversalBeaconPool {
  const MAXIMUM_REQUEST_QUEUE = 10;
  private $user_agent = 'user_agent';
  private $debug = false;
  private $handler = null;
  private $request_queue = array();

  public function __construct($user_agent = null, $debug = false){
    $this->handler = curl_multi_init();
    
    // This option isn't supported before PHP 5.5
    // curl_multi_setopt($this->handler, CURLMOPT_PIPELINING, 1);

    if(is_string($user_agent)){
      $this->user_agent = $user_agent;
    }
    if($debug){
      $this->debug = true;
    }
  }


  public function addRequest($data, $user_agent = null){
    $user_agent = (is_string($user_agent) ? $user_agent : $this->user_agent);
    $request = new UniversalBeacon($data, $user_agent);
    $handle = $request->handle(null, $this->debug);
    array_push($this->request_queue, $handle);
    curl_multi_add_handle($this->handler, $handle);
    if(count($this->request_queue) >= self::MAXIMUM_REQUEST_QUEUE){
      self::process($this->handler, $this->request_queue);
    }
  }

  public static function process(& $handler, & $request_queue){
    do {
      curl_multi_exec($handler, $running);
    } while($running > 0);
    while($handle = array_pop($request_queue)){
      curl_multi_remove_handle($handler, $handle);
    }
  }


  public function __destruct(){
    self::process($this->handler, $this->request_queue);
    curl_multi_close($this->handler);
  }

}


class Tracker {
  const VERSION = 1;
  const USER_AGENT = 'Analytics Pros - Universal Analytics (PHP)';
  private $account = null;
  private $state = null;
  private $user_agent = null;
  private $pool = null;
  public $debug = false;

  public function __construct($account, $client_id = null, $user_id = null, $debug = false){
    $this->account = $account;
    $this->debug = (bool) $debug;
    $this->pool = new UniversalBeaconPool(self::USER_AGENT, $this->debug);

    if(!is_null($client_id) && constant('ANALYTICS_HASH_IDS'))
      $client_id = self::hash_uuid($client_id);
    elseif(is_null($client_id))
      $client_id = self::generateUUID4();

    $this->state = array(
      'v' => self::VERSION,
      'tid' => $account,
      'cid' => $client_id
    );
    
    if(!is_null($user_id)){
      if(constant('ANALYTICS_HASH_IDS'))
        $user_id = self::hash_uuid($user_id);
      $this->state[ 'uid' ] = $user_id;
    }
  }

  /* Return an MD5 checksum spaced in UUD4-format */
  public static function hash_uuid($value){ 
    $checksum = md5($value);
    return sprintf('%8s-%4s-%4s-%4s-%12s', 
      substr($checksum, 0, 8), 
      substr($checksum, 8, 4), 
      substr($checksum, 12, 4),
      substr($checksum, 16, 4),
      substr($checksum, 20, 12)
    );
  }


  public function setUserAgent($ua){
    if(is_string($ua)){
      $this->user_agent = $ua;
    }
  }

  public function send($hit_type, $attribs = null, $ua = null){
    $agent = (is_string($ua) 
      ? $ua
      : (is_string($this->user_agent) 
            ? $this->user_agent 
            : self::USER_AGENT
        )
    );

    return $this->pool->addRequest($this->hitdata($hit_type, $attribs), $agent);
  }

  public function set($name, $value){
    $this->state[ $name ] = $value;
  }

  public function get($name){
    if(array_key_exists($name, $this->state)){
      return $this->state[ $name ];
    } else {
      return null;
    }
  }

  public function hitdata($type, $attribs = null){
    return self::params($type, array_merge($this->state, (array)$attribs));
  }

  public static function & params($type, $data){
    $result_data = array();
    $result_keys_in = array_keys($data);
    $result_keys = str_replace(array_keys(self::$name_map), array_values(self::$name_map), $result_keys_in);
    $result_keys = preg_replace(array_keys(self::$name_map_re), array_values(self::$name_map_re), $result_keys);
    for($i = 0; $i < count($result_keys_in); $i++){
      $result_data[ $result_keys[ $i ] ] = $data[ $result_keys_in[ $i ] ];
    }
    $result_data[ 't' ] = $type;
    return $result_data;
  }

  public static $name_map = array(
    'clientId' => 'cid',
    'userId' => 'uid',
    'eventCategory' => 'ec',
    'eventAction' => 'ea',
    'eventLabel' => 'el',
    'eventValue' => 'ev',
    'nonInteraction' => 'ni',
    'nonInteractive' => 'ni',
    'documentPath' => 'dp',
    'documentTitle' => 'dt',
    'title' => 'dt',
    'path' => 'dp',
    'page' => 'dp',
    'location' => 'dl',
    'documentLocation' => 'dl',
    'hostname' => 'dh',
    'documentHostname' => 'dh',
    'sessionControl' => 'sc',
    'referrer' => 'dr',
    'documentReferrer' => 'dr',
    'queueTime' => 'qt',
    'campaignName' => 'cn',
    'campaignSource' => 'cs',
    'campaignMedium' => 'cm',
    'campaignKeyword' => 'ck',
    'campaignContent' => 'cc',
    'campaignId' => 'ci',
    'screenResolution' => 'sr',
    'viewportSize' => 'vp',
    'documentEncoding' => 'de',
    'screenColors' => 'sd',
    'userLanguage' => 'ul',
    'appName' => 'an',
    'contentDescription' => 'cd',
    'appVersion' => 'av',
    'transactionAffiliation' => 'ta',
    'transactionId' => 'ti',
    'transactionRevenue' => 'tr',
    'transactionShipping' => 'ts',
    'transactionTax' => 'tt',
    'transactionCurrency' => 'cu',
    'itemName' => 'in',
    'itemPrice' => 'ip',
    'itemQuantity' => 'iq',
    'itemCode' => 'ic',
    'itemVariation' => 'iv',
    'itemCategory' => 'iv',
    'socialAction' => 'sa',
    'socialNetwork' => 'sn',
    'socialTarget' => 'st',
    'exceptionDescription' => 'exd',
    'exceptionFatal' => 'exf',
    'timingCategory' => 'utc',
    'timingVariable' => 'utv',
    'timingTime' => 'utt',
    'timingLabel' => 'utl',
    'timingDNS' => 'dns',
    'timingPageLoad' => 'pdt',
    'timingRedirect' => 'rrt',
    'timingTCPConnect' => 'tcp',
    'timingServerResponse' => 'srt'
  );

  public static $name_map_re = array(
    '@^dimension([0-9]+)$@' => 'cd$1',
    '@^metric([0-9]+)$@' => 'cm$1'
  );

  public static function generateUUID4(){
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
  }

}

?>
