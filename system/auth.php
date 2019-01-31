<?php
/**
 * This file handles authentication
 *
 */

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
