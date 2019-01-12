<?php

$CONFIG = json_decode(file_get_contents("../source/config.json"), true);

if ($CONFIG == null) throw new Error("Invalid JSON config! " + json_last_error_msg());

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

if (!isset($CONFIG['layout'])) {
  $CONFIG['layout'] = 'layout.html';
}
if (!isset($CONFIG['title'])) {
  $CONFIG['layout'] = '%s';
}
if (isset($CONFIG['headers'])) {
  if (!is_array($CONFIG['headers'])) $CONFIG['headers'] = [$CONFIG['headers']];
} else {
  $CONFIG['headers'] = [];
}

function in_source_folder($path) {
  return $_SERVER['DOCUMENT_ROOT'] . '/source/' . $path;
}

?>
