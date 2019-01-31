<?php

// relative in source folder
// prevent namespace errors when importing config, as there the real in_source_folder is defined
function in_source_folder_($path) {
  return realpath(__FILE__ . '/../source/' . $path);
}


function assert_theme_name($theme) {
  if ($theme = "__no_theme") return;
  if (preg_match('/^[A-z0-9_\\-]{1,20}$/', $theme) != 1 || !file_exists(in_source_folder_('css/' . $theme . '.css'))) {
    die("Invalid theme specified: $theme");
  }
}

function load_theme($theme) {
  assert_theme_name($theme);

  header("Content-type: text/css; charset: UTF-8");
  // enable no-theme
  if ($theme = "__no_theme") return;

  echo file_get_contents(in_source_folder_('css/' . $theme . '.css'));
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
  if (isset($CONFIG['themes'])) {
    $theme = $CONFIG['themes'][0];
  } else {
    $theme = "__no_theme";
  }
  set_theme($theme);
  load_theme($theme);
}

?>
