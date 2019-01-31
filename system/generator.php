<?php

require_once('config.php');
require_once('auth.php');
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

  custom_log("\n## Rendering contents of $cfg[use]");

  // build plugin library
  $plugins = [];
  if (isset($cfg['plugin'])) {
    // if it's not an arrray of objects, but an associative array
    // turn it into an array pf associative arrays
    if (!isset($cfg['plugin'][0])) $cfg['plugin'] = [$cfg['plugin']];

    foreach ($cfg['plugin'] as $plugin_cfg) {
      $plugin_cfg['..'] = $cfg;
      if (isset($PLUGINS[$plugin_cfg['name']]))  {
        $plugins[] = [
          "methods" => $PLUGINS[$plugin_cfg['name']],
          "config" => $plugin_cfg
        ];
        custom_log("loaded plugin: $plugin_cfg[name]");
      } else {
        custom_log('# Error: Plugin '.$plugin_cfg['name'].' could not be found.');
      }
    }
  }

  // call before_route
  call_plugin_function($plugins, 'before_route', $target_path);

  // choose a layout
  $layout = $CONFIG['layout'];
  if (isset($cfg['layout'])) $layout = $cfg['layout'];

  // make header list
  // order is global[headers], route[headers], config[css]
  $headers = array_merge([], $CONFIG['headers']);

  if (isset($cfg['headers'])) {
    $headers = array_merge($headers, is_array($cfg['headers']) ? $cfg['headers'] : [$cfg['headers']]);
  }

  // add css to headers
  $headers = array_merge($headers, [
    '<link rel="stylesheet" href="{{root}}/css/base.css"/>',
    '<link rel="stylesheet" href="{{root}}/css/current_theme.php"/>'
  ]);

  if (isset($cfg['css'])) {
    $css = $cfg['css'];
    if (!is_array($css)) $css = [$css];
    foreach($css as $cssurl) {
      $headers[] = "<link rel=\"stylesheet\" href=\"{{root}}/css/$cssurl\"/>";
    }
  }

  // make headers into a string
  $headers = implode("\n", $headers);

  // list of files in Directory
  $folder = in_source_folder($cfg['use'] . "/");
  $files = scandir($folder);

  $rendered_pages = [];

  // create path to files:
  mkdir(in_project_root($target_path), 0777, true);

  foreach ($files as $file) {
    // ignore non markdown files
    if (preg_match('/\\.md$/', $file) != 1) continue;

    // get file contents (markdown)
    $raw_content = file_get_contents($folder . $file);

    custom_log("\n### $file");

    // remove metadata from file and save in array $metadata
    $raw_content = extract_metadata($raw_content, $metadata);

    custom_log("metadata: `" . json_encode($metadata) . '`');

    // extract title from metadata (or raw content, if no title is set in metadata)
    $title = create_title($cfg['title'], extract_title($metadata, $raw_content));

    // apply plugin
    call_plugin_function($plugins, 'before_parse', $raw_content, $file, $metadata);

    // parse markdown
    $content_body = $Parsedown->text($raw_content);

    // assemble html file
    $content = complete_doc($content_body, $title, $layout, $headers);

    // apply plugin
    call_plugin_function($plugins, 'after_parse', $content, $file, $metadata);

    $target_file = in_project_root($target_path . preg_replace('/\\.md$/', '.html', $file));

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
  call_plugin_function($plugins, 'index', [
    $rendered_pages,
    function ($content, $title) use ($target_path, $layout, $headers) {
      file_put_contents(
        in_project_root($target_path . '/index.html'),
        complete_doc($content, $title, $layout, $headers)
      );
    },
    $parse_func
  ]);
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

// links /source/$name to /$name
// it checks if the files exists in the source folder, but it is up to you to
// cover any other checks
// if $absolute == true, assume $name is an absolute path
function link_from_source($name, $absolute = false) {
  $basepath = in_source_folder('');
  if ($absolute) {
    if (substr($name, 0, strlen($basepath)) != $basepath) {
      throw new Exception("Can't link from source if path is not in source! (given: $name, not in: $basepath) [\$absolute=true]");
    }
  }
  // determine source and target (source absolute, target relative to the project root)
  $path = $absolute ? $name : in_source_folder($name);
  $target = $absolute ? substr($name, strlen($basepath)) : $name;

  custom_log("linking $path => $target");

  if (file_exists($path)) {
    return symlink($path, in_project_root($target));
  }
}

// build entire project
function build() {
  global $CONFIG;

  try {
    custom_log("# Rendering:");
    foreach ($CONFIG['routes'] as $target_path => $cfg) {
      create_for_path($target_path, $cfg);
    }

    custom_log("\n# Linking resources:");

    // link the public folder to the source, as we don't want to duplicate potentially larger files
    link_from_source('public');
    // link favicon
    link_from_source('favicon.ico');

    // link the css files
    foreach (glob(in_source_folder('css/') . '*.{css}', GLOB_BRACE) as $file) {
      link_from_source($file, true);
      symlink($file, in_project_root('/css/' . basename($file)));
    }

    custom_log("\n# Config: \n\n````");
    custom_log(json_encode($CONFIG, JSON_PRETTY_PRINT) . "\n````");
  } catch (Exception | Error $e) {
    custom_log("# Caught error: " . $e->getMessage());
  }

}

/*
 *  logs the given message according to log-level (todo: implement!);
 *
 */
function custom_log($msg) {
  echo $msg . "\n";
}

if (isset($_GET['build'])) {
  if (!session_is_authorized()) die("# Error: You are not authorized!");
  build();
}


?>
