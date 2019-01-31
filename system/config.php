<?php error_reporting(E_ERROR | E_PARSE);
/**
 *  This file handles loading the config and setting up defaults
 *  it also provides some handy functions to get relative paths in project root
 *  or in the source folder
 */

// load config.json, set some flags
if (file_exists("../source/config.json")) {
  $CONFIG = json_decode(file_get_contents("../source/config.json"), true);
  if ($CONFIG == null) throw new Error("Invalid JSON config! " + json_last_error_msg());
  define('NO_CONFIG', false);
} else {
  define('NO_CONFIG', true);
  $CONFIG = [
    "routes" => []
  ];
}

// configure defaults:
// formatting
if (!isset($CONFIG['formatting'])) {
  $CONFIG['formatting'] = [
    'date' => 'd.m.Y',
    'time' => 'H:i',
    'datetime' => 'd.m.Y - H:i'
  ];
}

if (!isset($CONFIG['formatting']['date'])) {
  $CONFIG['formatting']['date'] = 'd.m.Y';
}
if (!isset($CONFIG['formatting']['time'])) {
  $CONFIG['formatting']['time'] = 'H:i';
}
if (!isset($CONFIG['formatting']['datetime'])) {
  $CONFIG['formatting']['datetime'] = 'd.m.Y - H:i';
}

// layout
if (!isset($CONFIG['layout'])) {
  $CONFIG['layout'] = 'layout.html';
}
// title
if (!isset($CONFIG['title'])) {
  $CONFIG['title'] = '%s';
}
// make sure global headers are an array
if (isset($CONFIG['headers'])) {
  if (!is_array($CONFIG['headers'])) $CONFIG['headers'] = [$CONFIG['headers']];
} else {
  $CONFIG['headers'] = [];
}

// easy path handling:
// project root path
$PROJECT_ROOT = realpath(dirname(__FILE__) . '/../');

// relative in source folder
function in_source_folder($path) {
  return in_project_root('source/' . $path);
}
// relative in project folder
function in_project_root($path) {
  global $PROJECT_ROOT;
  return $PROJECT_ROOT . '/' . $path;
}

?>
