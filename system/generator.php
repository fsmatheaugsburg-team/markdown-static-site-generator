<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_PARSE);

/**
 *  This is the main file, it holds most of the generating logic
 *  if called with ?build it will start a build (if authenticated)
 */

require_once('config.php');
require_once('auth.php');
require_once('complete_doc.php');
require_once('date_time.php');
require_once('plugins.php');
require_once('lib/Michelf/MarkdownExtra.inc.php');
require_once('sitemap.php');
require_once('dropbox.php');

use Michelf\MarkdownExtra;

function downloadFile($source, $target) {
  $data = file_get_contents($source);
  $directory = dirname($target);

  if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
  }

  $file = fopen($target, "w+");
  fputs($file, $data);
  fclose($file);
}



// build route specified in config.json/routes
function create_for_path($target_path, $cfg) {
  global $CONFIG, $PLUGINS;

  // a function we can pass along to render markdown with our parsedown parser
  $parse_func = function ($markdown) {return MarkdownExtra::defaultTransform($markdown);};


  // test for illegal paths
  if (preg_match('/\\.\\./', $target_path)) {
    throw new Error("[create_for_path] Path can't contain ..");
  }

  // defaults
  if (!isset($cfg['use'])) $cfg['use'] = $target_path;
  if (!isset($cfg['title'])) $cfg['title'] = $CONFIG['title'];
  $cfg['url'] = $target_path;

  custom_log("\n## Rendering contents of `$cfg[use]`");

  // build plugin library
  $plugins = [];
  if (isset($cfg['plugin'])) {
    // if it's not an arrray of objects, but an associative array
    // turn it into an array pf associative arrays
    if (!isset($cfg['plugin'][0])) $cfg['plugin'] = [$cfg['plugin']];

    $log_loaded_plugins = [];
    foreach ($cfg['plugin'] as $plugin_cfg) {
      $plugin_cfg['..'] = &$cfg;
      if (isset($PLUGINS[$plugin_cfg['name']]))  {
        $plugins[] = [
          "methods" => $PLUGINS[$plugin_cfg['name']],
          "config" => $plugin_cfg
        ];
        $log_loaded_plugins[] = $plugin_cfg['name'];
      } else {
        custom_log('**Error: Plugin '.$plugin_cfg['name'].' could not be found!**');
      }
    }
    custom_log("Using plugns: " . implode(", ", $log_loaded_plugins));
  }

  // call before_route
  call_plugin_function($plugins, 'before_route', [$target_path]);

  // choose a layout
  $layout = $CONFIG['layout'];
  if (isset($cfg['layout'])) $layout = $cfg['layout'];

  // get recursive flag (default false)
  $recursive = isset($cfg['recursive']) ? $cfg['recursive'] : false;

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
  // list of relative paths
  $files = scanndir_recursive($folder, $recursive);

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
    $content_body = MarkdownExtra::defaultTransform($raw_content);

    // assemble html file
    $content = complete_doc($content_body, $title, $layout, $headers);

    // apply plugin
    call_plugin_function($plugins, 'after_parse', $content, $file, $metadata);

    $target_file = in_project_root($target_path . preg_replace('/\\.md$/', '.html', $file));

    // create path to file (if necessary)
    mkdir(dirname($target_file), 0777, true);

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
  call_plugin_function($plugins, 'after_route', [
    $rendered_pages,
    function ($file, $content, $title, $appendToOriginalContent = false) use ($target_path, $layout, $headers, $rendered_pages) {
      $url = $target_path . $file;
      if ($appendToOriginalContent) {
        $contentFromPagesWithSameName = implode(
          "\n\n",
          array_map(
            function ($page) {
              return $page["content"];
            },
            array_filter(
              $rendered_pages,
              function ($page) use ($target_path, $file, $url) {
                return $page["url"] == $url;
              }
            )
          )
        );

        $content = $contentFromPagesWithSameName . "\n\n" . $content;
      }

      $rendered_pages[] = [
        'metadata' => [],
        'title' => $title,
        'url' => $url,
        'file' => null,
        'content' => $content
      ];
      $filename = in_project_root($target_path . '/' . $file);
      file_put_contents($filename, complete_doc($content, $title, $layout, $headers));
      return $filename;
    },
    $parse_func,
    function ($file, $content) use ($target_path) {
      $filename = in_project_root($target_path . '/' . $file);
      file_put_contents($filename, $content);
      return $filename;
    }
  ]);
  return $rendered_pages;
}


// replace %s in the title string with $title
function create_title($template, $title) {
  return str_replace("%s", $title, $template);
}

/**
 *  Extract title from file
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
  if (!preg_match("/^---\s*\n/", $raw_content)) {
    return $raw_content;
  }
  $raw_content = substr($raw_content, strpos($raw_content, "\n") + 1);

  while (strlen($raw_content) > 1) {
    $metadata_line = substr($raw_content, 0, strpos($raw_content, "\n"));
    if (preg_match("/^---\s*/", $metadata_line)) {
      $raw_content = substr($raw_content, strpos($raw_content, "\n") + 1);
      break;
    }
    preg_match("/^([^:]+):\s?([^\n]+)/", $metadata_line, $matches);
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

  custom_log("* linking `$path => $target`");

  if (file_exists($path)) {
    return symlink($path, in_project_root($target));
  }
}

function scanndir_recursive($dir, $recursive = true) {
  $result = [];
  foreach(scandir($dir) as $filename) {
    if ($filename[0] === '.') continue;

    $filePath = $dir . '/' . $filename;

    if (is_dir($filePath)) {
      if ($recursive) {
        foreach (scanndir_recursive($filePath) as $childFilename) {
          $result[] = $filename . '/' . $childFilename;
        }
      }
      // ignore if directories if not recursive
    } else {
      $result[] = $filename;
    }
  }
  return $result;
}

// build entire project
function build() {
  global $CONFIG;

  try {
    custom_log("\n# Syncing files from Dropbox");

    $dropboxLoginFile = realpath("../source/")."/.dropbox-login.json";
    if(file_exists($dropboxLoginFile)) {
      $dropboxConfig = json_decode(file_get_contents($dropboxLoginFile), true);

      syncFromDropbox(
        $dropboxConfig['clientId'],
        $dropboxConfig['clientSecret'],
        $dropboxConfig['accessToken'],
        $dropboxConfig['rootFolder'],
        realpath("../source/")."/"
      );
    } else {
      custom_log("\"".$dropboxLoginFile."\" does not exist, so Dropbox-Sync was skipped.");
    }

    custom_log("\n# Fetching additional files");
    array_map(
      function ($object) {
        custom_log("\nDownloading \"".$object["source"]."\" to \"".$object["target"]."\"");
        downloadFile($object["source"], realpath("../source/").$object["target"]);
      },
      $CONFIG["fetch"]
    );

    custom_log("\n# Loading Plugins:");
    load_plugins(in_project_root('system/plugins'));

    if (isset($CONFIG['external_plugins']) && $CONFIG['external_plugins']) {
      custom_log("\n## Loading external plugins");
      load_plugins(in_source_folder("plugins/"));
    }

    custom_log("\n# Rendering:");
    $rendered_pages = [];
    foreach ($CONFIG['routes'] as $target_path => $cfg) {
      $rendered_pages = array_merge($rendered_pages, create_for_path($target_path, $cfg));
    }

    custom_log("\n# Miscellaneous:");
    $sitemap = generate_sitemap($rendered_pages);
    file_put_contents(in_project_root('sitemap.xml'), $sitemap);
    custom_log("\n## Sitemap generated");

    custom_log("\n## Linking resources:");

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
    custom_log("```" . $e->getTraceAsString() . "```");
  }
  flush_log();
}


/*
 *  logs the given message according to log-level (todo: implement!);
 *
 */

$LOG = "";
function custom_log($msg) {
  global $LOG;
  $LOG .= $msg . "\n";
}

function flush_log() {
  global $LOG;
  $accept_header = getallheaders()['Accept'];
  $html_accepted = preg_match('/(^|,)\s*text\/html\s*($|,)/', $accept_header);
  if (!$html_accepted) {
    echo $LOG;
  } else {
    echo MarkdownExtra::defaultTransform($LOG);
  }
  $LOG = "";
}

if (isset($_GET['build'])) {
  if (!session_is_authorized()) die("# Error: You are not authorized!");
  build();
}


?>
