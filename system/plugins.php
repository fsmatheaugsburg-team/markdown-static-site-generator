<?php
require_once(__DIR__.'/vendor/autoload.php');
use ICal\ICal;

/**
 *  calls method $name for all plugins in the $plugin array
 *  $plugins  is an array of plugin methods and config pairs. It's structured like this {methods: $PLUGIN[name], config: <plugin-config>}
 *  $name     is the name of the method that should be called
 *  $args     array of arguments for the plugin method
 */
function call_plugin_function(&$plugins, $name, $args) {
  if (sizeof($plugins) == 0) return;
  foreach ($plugins as &$plugin) {
    if (isset($plugin['methods'][$name])) {
      custom_log("applying plugin " . $plugin['config']['name'] . ".$name");
      try {
        $plugin['methods'][$name]($plugin['config'], ...$args);
      } catch (Exception | Error $e) {
        custom_log("Error applying plugin " . $plugin['config']['name'] . ".$name: " . $e->getMessage());
      }
    }
  }
}

// define a plugin with $name and $methods
function define_plugin($name, $methods) {
  global $PLUGINS;

  if (isset($PLUGINS[$name])) {
    custom_log("# Error: there already is a plugin loaded with name $name!");
    return;
  }

  $PLUGINS[$name] = $methods;
}

function load_plugins() {
  $plugins = scandir(in_source_folder("plugins/"));
  foreach ($plugins as $plugin) {
    $plugin_abs = in_source_folder("plugins/" . $plugin);
    if (is_file($plugin_abs) && preg_match("/\.php$/", $plugin_abs) !== false) {
      custom_log("* Loading $plugin");
      require_once($plugin_abs);
    }
  }
}

$PLUGINS = [
  'bloglike' => [
    // generates the file with a list of all blogposts
    'after_route' => function ($config, $pages, $write_to_file, $parse, $write_raw) {
      global $CONFIG;

      // manage defaults
      if (!isset($config['previewlen'])) $config['previewlen'] = 200;
      if (!isset($config['title'])) $config['title'] = 'Blog entries';

      // insert title, if a title is set
      if ($config['title'] != null) {
        $markdown = "# " . $config['title'] . "\n";
      } else {
        $markdown = "";
      }

      // article := a list entry of the blogposts
      // get the article layout, or use default one
      $article_layout = isset($config['layout']) ? $config['layout'] : "## #{{index}}: [{{title}}]({{root}}{{url}})\n*Written on the {{date}}*\n\n{{preview}} [more]({{root}}{{url}})";

      // save all generated articles, so we can sort them
      $articles = [];

      // enable numbering articles
      $index = 0;
      foreach ($pages as $post) {
        // get the metadata
        $metadata = $post['metadata'];
        if (!isset($metadata['date'])) {
          throw new Error("[plugin:bloglike] all blog entries must have a date in their metadata!");
        }
        // get the publish date
        $timestamp = timestamp_from_date($metadata['date'], $CONFIG['formatting']['date']);

        // create dictionary and merge metadata into it
        $dict = array_merge(default_dict([
          'index' => sizeof($pages) - $index,
          'preview' => substr(simplify_text(strip_tags($post['content'])), 0, $config['previewlen']) . '...',
          'url' => get_webroot_offset() . $post['url']
        ], 0), $metadata);

        // put article in the list
        $articles[] = [
          'metadata' => $dict,
          'content' => "\n" . fill_template_string_dict($article_layout, $dict) . "\n",
          'date' => $timestamp
        ];

        $index++;
      }

      // sort articles by date
      usort($articles, function ($a, $b) {
          return $b['date'] - $a['date'];
      });

      // append articles to markdown
      foreach($articles as $page) {
        $markdown .= $page['content'];
      }

      if ($config['rss']) {
        // add link to rss feed
        $markdown .= "\n\n[rss](" . get_webroot_offset() . $config['..']['url'] . 'feed.rss)';
      }

      $rendered = $parse($markdown);

      // append tagging logic, if tagging is enabled (disabled by default)
      if ($config['tagged']) {
        $rendered .= "\n<script id='tagging-script'>
          const taggedArticles = {
            add(tag, node) {
              if (this[tag]) {
                this[tag].push(node)
              } else {
                this[tag] = [node]
              }
            },
            highlight(tag) {
              if (!this[tag]) return false;
              this['*'].forEach(node => node.style.display = 'none');
              this[tag].forEach(node => node.style.display = '');
              if (tag == '*') {
                document.getElementById('tag-highliht-output').innerHTML = '';
                  location.hash = '';
              } else {
                location.hash = tag;
                document.getElementById('tag-highliht-output').innerHTML = 'Filtered for \"' + tag + '\" - <a href=\"javascript:taggedArticles.highlight(\'*\')\">clear filter</a>';
              }
            }
          };
          document.querySelectorAll('article').forEach(article => {
            if (article.attributes.getNamedItem('tags') == undefined) return false;
            let tags = article.attributes.getNamedItem('tags').value.split(',').map(t => t.trim()),
                tagsContainer = article.querySelector('.blog-tags');
            // empty container
            while (tagsContainer.firstChild) {
                tagsContainer.removeChild(tagsContainer.firstChild);
            }
            // add to global tags
            taggedArticles.add('*', article);
            tags.forEach(tag => {
              taggedArticles.add(tag, article);
              let a = document.createElement('a');
              a.innerText = tag;
              a.classList.add('blog-tag');
              a.addEventListener('click', e => taggedArticles.highlight(tag));
              tagsContainer.appendChild(a);
            })
          });
          // ensure there is a #tag-highliht-output element
          if (document.getElementById('tag-highliht-output') == undefined) {
            // insert after first h1
            let out = document.createElement('p');
            out.innerHTML = '<em id=\"tag-highliht-output\"></em>';
            let parent = document.getElementById('tagging-script').parentNode;
            parent.insertBefore(out, parent.firstChild);
          }
          if (location.hash.length > 1) {
            let tag = location.hash.substr(1);
            taggedArticles.highlight(tag);
          }
        </script>";
      }

      // write index to file
      $write_to_file('index.html', $rendered, $config['title']);

      // create rss feed
      if ($config['rss']) {
        $rss_rendered = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss_rendered .= '<rss version="2.0"><channel>';
        $rss_rendered .= '<title>'. $config['title'] .'</title>';
        $rss_rendered .= '<link>' . absolute_url($config['..']['url']) . '</link>';
        foreach ($articles as $page) {

          $rss_rendered .= '<item>';
          $rss_rendered .= '<title>'. $page['metadata']['title'] .'</title>';
          $rss_rendered .= '<link>' . absolute_url($page['metadata']['url']) . '</link>';
          $rss_rendered .= '<guid>' . absolute_url($page['metadata']['url']) . '</guid>';
          $rss_rendered .= '<description>' . $page['metadata']['preview'] . '</description>';
          $rss_rendered .= '<pubDate>' . date(DATE_RSS, $page['date']) . '</pubDate>';
          $rss_rendered .= '</item>';
        }
        $rss_rendered .= '</channel></rss>';

        // write rss feed to file
        $write_raw('feed.rss', $rss_rendered);
      }
    }
  ],
  "calendar" => [
    'after_route' => function ($config, $pages, $write_to_file, $parse) {
      global $CONFIG;

      if (!isset($config['forceTimeZone'])) $config['forceTimeZone'] = true;
      if (!isset($config['mainPageInterval'])) $config['mainPageInterval'] = "P6M";
      if (!isset($config['contentPreviewDay'])) $config['contentPreviewDay'] = "## {{date}}\n{{renderedEvents}}\n";
      if (!isset($config['contentPreviewEvent'])) $config['contentPreviewEvent'] = " * **{{startTime}}** [{{summary}}]({{filename}}.html) *{{location}}*  \n";
      if (!isset($config['contentEventPage'])) $config['contentEventPage'] = "# {{summary}}\n\n*Start:* {{startDate}} {{startTime}}  \n*Ende:* {{endDate}} {{endTime}}  \n*Ort:* {{location}}\n\n{{description}}\n";
      if (!isset($config['defaultTimeZone'])) $config['defaultTimeZone'] = "DE";

      try {
        $ical = new ICal("../source/".$config['calendarPath'], ['defaultTimeZone'=> $config['defaultTimeZone']]);
      } catch(Exception $e) {
        throw new Error("[plugin:calendar] It wasn't possible to open the calendar at \"".$config['calendarPath']."\"");
      }
      $events = $ical->sortEventsWithOrder($ical->events());

      $groupByDate = function ($array) use ($config, $CONFIG) {
        $group = array();
        foreach ($array as $event) {
            $date = $event["startDateObject"];
            $dateString = $date->format("Ymd");

            if(!isset($group[$dateString]["events"]))
              $group[$dateString]["events"] = [];

            $group[$dateString]["events"][] = $event;
            $group[$dateString]["startDateObject"] = $date;
            $group[$dateString]["date"] = $date->format($CONFIG['formatting']['date']);
        }
        return $group;
      };

      $getDateRange = function ($events, $begin = null, $end = null) {
        return array_filter(
          $events,
          function ($event) use ($begin, $end) {
            return $begin <= $event["startDateObject"] && $event["startDateObject"] <= $end;
          }
        );
      };

      $eventToInformationArray = function($event) use ($ical, $config, $CONFIG) {
        $dtstart = $ical->iCalDateToDateTime($event->dtstart_array[3], $config["forceTimeZone"]);
        $dtend = $ical->iCalDateToDateTime($event->dtend_array[3], $config["forceTimeZone"]);

        return [
          "filename" => $dtstart->format("Ymd")."_".preg_replace("/[^A-Za-z0-9]/", '', $event->summary),
          "startDateObject" => $dtstart,
          "endDateObject" => $dtend,
          "startTime" => $dtstart->format($CONFIG['formatting']['time']),
          "endTime" => $dtend->format($CONFIG['formatting']['time']),
          "startDate" => $dtstart->format($CONFIG['formatting']['date']),
          "endDate" => $dtend->format($CONFIG['formatting']['date']),
          "summary" => strip_tags($event->summary),
          "description" => strip_tags($event->description),
          "location" => strip_tags($event->location)
        ];
      };

      $renderEvents = function ($eventList) use ($config) {
        return implode(
          "\n",
          array_map(
            function ($eventDay) use ($config) {
              $eventDay["renderedEvents"] = implode(
                "\n",
                array_map(
                  function ($event) use ($config) {
                    return fill_template_string_dict($config["contentPreviewEvent"], $event);
                  },
                  $eventDay["events"]
                )
              );
              return fill_template_string_dict($config["contentPreviewDay"], $eventDay);
            },
            $eventList
          )
        );
      };

      $allEvents = array_map($eventToInformationArray, $events);
      $allEventsByDay = $groupByDate($allEvents);

      $upcomingEventsByDay = $getDateRange(
        $allEventsByDay,
        (new DateTime())->setTime(0,0,0),
        (new DateTime())->add(new DateInterval($config["mainPageInterval"]))
      );

      array_map(
        function ($event) use ($config, $write_to_file, $parse) {
          $rendered =  fill_template_string_dict($config["contentEventPage"], $event);
          $write_to_file($event["filename"].'.html', $parse($rendered), $event["summary"]);
        },
	$allEvents
      );

      $write_to_file('index.html', $parse($renderEvents($upcomingEventsByDay)), $config['title'], true);
      $write_to_file('all.html', $parse($renderEvents($allEventsByDay)), $config['title']);
    }
  ]
]

?>
