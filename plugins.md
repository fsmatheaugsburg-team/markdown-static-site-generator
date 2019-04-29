# MSSG Plugins

A list of out-of-the-box included plugins. You can remove them by deleting them from the `system/plugins` folder.

## Table of contents:

 - Bloglike
 - Password
 - Calendar
 - Download-page


## Bloglike

Turn a collection of markdown files into your own blog. Sorting by date, RSS-Feed and tagging included!

Available settings:

* `title: String` Text displayed inside a `<h1>` element at the top of the page (optional)
* `previewlen: Integer` Number of characters displayed in the preview (default 200)
* `layout: String` A templated text from wich the list entries for the blog are generated. Gets passed through markdown, so html support is wonky until we find a better parser.
* `tagged: Boolean` Wether or not articles are tagged. Enables Sorting by tags. Articles are required to have `tags` metadata. You also have to supply your own layout with an `<article tags="{{tags}}">` tag as the root. (requires JavaScript).


## Password

Simple password protection for folders. Uses `.htaccess` and `.htusers` files. Make sure they are enabled in your (apache) webserver config.

Supply `username` and `password` in the plugin config.


## Calendar:

The `calendar`-Plugin can be configured with the following JSON-object.
```
"plugin": {
  /* The name of the Calendar Plugin */
  "name": "calendar",
  /* The Title displayed at the title page */
  "title": "Programm",
  /* Forces the usage of the timezone instead of UTC (optional) */
  "forceTimeZone": true,
  /* The time starting from today displayed on the event page (optional) */
  "mainPageInterval": "P6M",
  /* The template for a date in the event lists (optional) */
  "contentPreviewDay": "## {{date}}\n{{renderedEvents}}\n",
  /* The template for an event in the event lists (optional) */
  "contentPreviewEvent": " * **{{startTime}}** [{{summary}}]({{filename}}.html)  \n",
  /* The template of the event page (optional) */
  "contentEventPage": "# {{summary}}\n\n*Start:* {{startDate}} {{startTime}}  \n*Ende:* {{endDate}} {{endTime}}  \n*Ort:* {{location}}\n\n{{description}}\n",
  /* The timezone used for the time of the events (optional) */
  "defaultTimeZone": "DE"
}
```

* The calendar plugin generated an `index.html` with the upcoming events, an `all.html` with all events in chronological order, and an own page for every event in the calendar.
* If there exists an `index.md` in the used folder, its contents are displayed before the calendar listing.

## Download-page

Make files inside a directory avalable for download.
