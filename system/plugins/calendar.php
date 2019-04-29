<?php
require_once(__DIR__.'/../vendor/autoload.php');
use ICal\ICal;

define_plugin("calendar", [
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
]);

 ?>
