<?php
/**
 *  Contains code for generating a compllete html file from template, body, etc
 *  (does not render markdown)
 *
 */
$LAYOUT_CACHE = [];

function complete_doc($body, $title, $layout, $headers = "") {
  global $CONFIG;

  $layout = get_layout_cached($layout);

  // extend this dictionary if you want to add fields for templating
  $dict = default_dict([
    'body'  => $body,
    'title' => $title
  ]);

  $layout_filled = fill_template_string_dict($layout, $dict);

  return '<!DOCTYPE html>
  <html lang="de" dir="ltr">
    <head>
      <meta charset="utf-8">
      <title>'.$title.'</title>
      <link rel="stylesheet" href="/css/base.css"/>
      <link rel="stylesheet" href="/css/current_theme.php"/>
'.$headers.'
    </head>
    <body>
'.$layout_filled.'
    </body>
  </html>';
}

function get_layout_cached($name) {
  global $LAYOUT_CACHE;

  if (isset($LAYOUT_CACHE[$name])) return $LAYOUT_CACHE[$name];

  $layout = file_get_contents(in_source_folder($name));

  if ($layout == false) throw new Error("[get_layout_cached] Can't locate layout with $name!");

  $LAYOUT_CACHE[$name] = $layout;

  return $layout;
}

// replaces {{field}} with the contents of $dict[field]
function fill_template_string_dict($string, $dict) {
  return preg_replace_callback("/{{(\w*)}}/", function($matches) use ($dict) {
    if (isset($dict[$matches[1]])) {
      return $dict[$matches[1]];
    } else {
      return '<div class="error">Templating error: unkown field: "'.$matches[1].'"</div>';
    }
  }, $string);
}

// creates the default dctionary (date, time, etc)
function default_dict($with = [], $timestamp = false) {
  global $CONFIG;

  if ($timestamp == false || $timestamp == null) $timestamp = time();

  if (!isset($with['date'])) $with['date'] = date($CONFIG['formatting']['date'], $timestamp);
  if (!isset($with['time'])) $with['time'] = date($CONFIG['formatting']['time'], $timestamp);
  if (!isset($with['datetime'])) $with['datetime'] = date($CONFIG['formatting']['datetime'], $timestamp);

  return $with;
}

function simplify_text($text) {
  return preg_replace('/(\s\s+)/', ' ', $text);
}

?>
