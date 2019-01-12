<?php

require_once('config.php');
require_once('complete_doc.php');
require_once('date_time.php');
require_once('plugins.php');
require_once('Parsedown.php');

$Parsedown = new Parsedown();

// build route specified in config.json/routes
function create_for_path($target_path, $cfg) {
  global $Parsedown, $CONFIG, $PLUGINS;

  // a function we can pass along to render markdown with our parsedown parser
  $parse_func = function ($markdown) use ($Parsedown) {return $Parsedown->text($markdown);};

  // test for illegal paths
  if (preg_match('/\\.\\./', $target_path)) {
    throw new Error("[create_for_path] Path can't contain ..");
  }

  // defaults
  if (!isset($cfg['use'])) $cfg['use'] = $target_path;
  if (!isset($cfg['title'])) $cfg['title'] = $CONFIG['title'];

  // choose a layout
  $layout = $CONFIG['layout'];
  if (isset($cfg['layout'])) $layout = $cfg['layout'];

  // make header list
  $headers = [];
  if (isset($cfg['headers'])) $headers = $cfg['headers'];
  if (!is_array($headers)) $headers = [$headers];

  // merge in global headers
  $headers = array_merge($headers, $CONFIG['headers']);

  // add css to headers
  if (isset($cfg['css'])) {
    $css = $cfg['css'];
    if (!is_array($css)) $css = [$css];
    foreach($css as $cssurl) {
      $headers[] = "<link rel=\"stylesheet\" href=\"/css/$cssurl\"/>";
    }
  }

  // make headers into a string
  $headers = implode("\n", $headers);

  // list of files in Directory
  $folder = in_source_folder($cfg['use'] . "/");
  $files = scandir($folder);

  // get plugin
  $plugin = [];
  if (isset($cfg['plugin'])) {
    if (isset($PLUGINS[$cfg['plugin']['name']]))  {
      $plugin = $PLUGINS[$cfg['plugin']['name']];
    } else {
      custom_log('Error: Plugin '.$cfg['plugin']['name'].' could not be found.');
    }
  }


  $rendered_pages = [];

  // create path to files:
  mkdir($_SERVER['DOCUMENT_ROOT'] . '/' . $target_path, 0777, true);

  foreach ($files as $file) {
    // ignore non markdown files
    if (preg_match('/\\.md$/', $file) != 1) continue;

    // get file contents (markdown)
    $raw_content = file_get_contents($folder . $file);

    custom_log("$file");
    custom_log("body len b4: " . strlen($raw_content));

    // remove metadata from file and save in array $metadata
    $raw_content = extract_metadata($raw_content, $metadata);

    custom_log("metadata: ".json_encode($metadata, JSON_PRETTY_PRINT));

    // extract title from metadata (or raw content, if no title is set in metadata)
    $title = create_title($cfg['title'], extract_title($metadata, $raw_content));
    custom_log("body len no meta: " . strlen($raw_content));

    // apply plugin, if available
    if (isset($plugin['before_parse'])) {
      $raw_content = $plugin['before_parse']($cfg['plugin'], $raw_content, $metadata);
    }

    // parse markdown
    $content_body = $Parsedown->text($raw_content);

    custom_log("body len after: " . strlen($content_body));

    // assemble html file
    $content = complete_doc($content_body, $title, $layout, $headers);

    $target_file = $_SERVER['DOCUMENT_ROOT'] . '/' . $target_path . preg_replace('/\\.md$/', '.html', $file);

    // write generated file
    file_put_contents($target_file, $content);

    // save some details about the generated page
    $rendered_pages[] = [
      'metadata' => $metadata,
      'title' => $title,
      'url' => preg_replace('/^\\/*/', '/', '/' . $target_path . preg_replace('/\\.md$/', '.html', $file)),
      'file' => $file,
      'content' => $content_body
    ];
  }

  // if the plugin wants to generate an index, let it do it's job
  if (isset($plugin['index'])) {
    $plugin['index']($cfg['plugin'], $rendered_pages, function ($content, $title) use ($target_path, $layout, $headers) {
      file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/' . $target_path . 'index.html',
        complete_doc($content, $title, $layout, $headers)
      );
    }, $parse_func);
  }
}


// replace %s in the title string with $title
function create_title($template, $title) {
  return str_replace("%s", $title, $template);
}

/**
 *  Extsract title from file
 *  first looks for a title in the metadata
 *  if nothing was found, return the first heading
 */
function extract_title($metadata, $raw_content) {
  if (isset($metadata['title'])) return $metadata['title'];

  preg_match('/#\\s?([^\\n]+)/', $raw_content, $matches, PREG_UNMATCHED_AS_NULL);
  if (isset($matches)  && $matches[1] != null) return $matches[1];
  return "";
}


/*
 *  Extracts all metadata at the beginning of a file (format ~key: value)
 *  returns content without metadata, can return metadata array
 */
function extract_metadata($raw_content, &$metadata) {
  $metadata = [];

  while (strlen($raw_content) > 1 && $raw_content[0] == '~') {
    $metadata_line = substr($raw_content, 0, strpos($raw_content, "\n"));
    preg_match("/~([^:]+):\s?([^\n]+)/", $metadata_line, $matches);
    $metadata[$matches[1]] = $matches[2];
    $raw_content = substr($raw_content, strpos($raw_content, "\n") + 1);
  }

  return $raw_content;
}

// build entire project
function build() {
  global $CONFIG;

  try {
    foreach ($CONFIG['routes'] as $target_path => $cfg) {
      create_for_path($target_path, $cfg);
    }

    // link th public folder to the source, as we don't want to duplicate potentially larger files
    if (file_exists(in_source_folder('public'))) {
      symlink(in_source_folder('public'), $_SERVER['DOCUMENT_ROOT'] . '/public');
    }

    // link the base css file
    if (file_exists(in_source_folder('css/base.css'))) {
      symlink(in_source_folder('css/base.css'), $_SERVER['DOCUMENT_ROOT'] . '/css/base.css');
    }
  } catch (Exception | Error $e) {
    custom_log("Caught error: " . $e->getMessage());
  }

}

/*
 *  logs the given message according to log-level (todo: implement!);
 *
 */
function custom_log($msg) {
  echo $msg . "\n";
}

if (isset($_GET['build'])) build();

custom_log(json_encode($CONFIG, JSON_PRETTY_PRINT));

?>
