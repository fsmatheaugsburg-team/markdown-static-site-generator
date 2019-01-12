<?php

function assert_theme_name($theme) {
  if (preg_match('/^[A-z0-9_\\-]{1,20}$/', $theme) != 1 || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/source/css/' . $theme . '.css')) {
    die("Invalid theme specified");
  }
}

function load_theme($theme) {
  assert_theme_name($theme);

  header("Content-type: text/css; charset: UTF-8");
  echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/source/css/' . $theme . '.css');
}

function set_theme($theme) {
  assert_theme_name($theme);

  setcookie("theme", $theme, 0, "/", "", false, true);
}

if (isset($_GET['theme'])) {
  $theme = $_GET['theme'];
  set_theme($theme);
  load_theme($theme);
} else if (isset($_COOKIE["theme"])) {
  load_theme($_COOKIE["theme"]);
} else {
  require_once('../system/config.php');
  $theme = $CONFIG['themes'][0];
  set_theme($theme);
  load_theme($theme);
}

?>
