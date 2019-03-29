<?php
/**
 * This file handles authentication
 *
 */

// apache request headers polyfil from https://www.php.net/manual/en/function.apache-request-headers.php#70810
 if( !function_exists('apache_request_headers') ) {
 ///
   function apache_request_headers() {
     $arh = array();
     $rx_http = '/\AHTTP_/';
     foreach($_SERVER as $key => $val) {
       if( preg_match($rx_http, $key) ) {
         $arh_key = preg_replace($rx_http, '', $key);
         $rx_matches = array();
         // do some nasty string manipulations to restore the original letter case
         // this should work in most cases
         $rx_matches = explode('_', $arh_key);
         if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
           foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
           $arh_key = implode('-', $rx_matches);
         }
         $arh[$arh_key] = $val;
       }
     }
     return( $arh );
   }
 ///
 }

if (!isset($CONFIG)) die("config required");

function is_authorized($token) {
  global $CONFIG;

  if (!isset($CONFIG['authtokens'])) {
    if (function_exists('custom_log')) {
      custom_log('# Critical security error: No Authorization set! Allowing everything!');
    }
    return true;
  }


  return in_array($token, $CONFIG['authtokens']);
}

// check for authorization on every instance
function session_is_authorized() {
  // try GET, POST, Cookie, then headers
  if (isset($_GET['auth'])) {
    return is_authorized($_GET['auth']);
  } else if (isset($_POST['auth'])) {
    return is_authorized($_POST['auth']);
  } else if (isset($_COOKIE['auth'])) {
    return is_authorized($_COOKIE['auth']);
  }

  $headers = apache_request_headers();
  if (isset($headers['Authorization'])) {
    preg_match('/Bearer ([^\s]+)/', $headers['Authorization'], $matches);
    if(isset($matches[1])){
      return is_authorized($matches[1]);
    }
  }

  return is_authorized(null);
}
