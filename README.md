# Markdown static site generator

Please refer to [the tutorial](tutorial.md) for more details on usage.


## Example projects

If you want to start off with a working example project, You can use our provided examples:

All you need to do is upload the contents of these into your `/source` folder and press "build" in your panel.

Example project setups can be found inside the `/example` folder:
 - `/example/hello-world` The absolute minimum required to get your project going
 - `/example/basic` A goof starting off point. It has things configured like
   - The `bloglike` plugin. This enables you to have a blog, or something similar
   - A custom plugin for enabling LaTeX notation in markdown.
   - A layout with some CSS (internal + external) to make it look presentable
   - A favicon and some public folders / images



## `/config/config.json` format

 - `routes` define all your routes, keys are absolute paths (on the target blog), settings are:
   - `use: "/path"` the folder containing the markdown files (relative to the `/source` directory). Default is the target path (this objects key). All markdown files in this directory will be rendered to HTML and made public (except `README.md`).
   - `title: "title with %s placeholder"` template for the page titles, `%s` is replaced with the documents title
   - `css: "/path/to/css" | [paths]` additional css files to be included (either a string, or array of strings). Paths are relative to the css directory `/css`
   - `layout: "layout.html"` Use a special template for this route. Relative to the `/source` directory
   - `recursive: true|false` apply route to all subfolders as well (see "basic" example)
   - `plugin: {name: "name", <additional config>}`, you can also supply an array or plugin configs.
   - `headers: "<header tag>" | [header tags]` Additional header elements
 - `layout: "layout.html"`
 - `title: "template string %s"` specify a global title template  (gets overwritten by route-specific configurations)
 - `themes: ["theme1", "theme2", ...]` list of theme names
 - `headers: "<header tag>" | [header tags]` Tags to append to your html `<head>`
 - `authtokens: ["key1", "key2", ...]` list of authentication keys. When set, building requires authentication with one of these keys.
 - `external_plugins: true|false` Use custom plugins defined in the `/source/plugins` folder
 - `formatting: {date: <php date formattin>, time: <format>, datetime: <format>}` define formatting for displaying and parsing dates. Default is
   ```
    "formatting": {
        "date": "d.m.Y",
        "time": "H:i",
        "datetime": "d.m.Y - H:i"
    }
    ```
 - `fetch`: An array of pairs of urls and locations in the filesystem of files that have to be downloaded from the Web before the build starts. An example element of the array looks like `{ "source": "https:/example.org/file.zip", "target": "public/downloads/file.zip" }`

## Dropbox Sync

To activate a Sync from a Dropbox folder, create the JSON-File `source/.dropbox-login.json` with a content in the following format:
```
{
  "clientId": "xxxxxxxxxxxxxxx",
  "clientSecret": "xxxxxxxxxxxxxxx",
  "accessToken": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "rootFolder": "/Daten - Fachschaft Mathe/Website/koma84/"
}
```

* Before every build, the complete content of the Dropbox-folder specified will be downloaded.
* Files that were synced from Dropbox will be deleted as soon as they are deleted in the Dropbox.
* If `source/.dropbox-login.json` does not exist, this step will be skipped.


## Folder structure

An example folder structure might be:

```
source/
  |-- css/                   -> your css files
      |-- base.css           -> your base css file
      |-- *.css              -> all other .css files will be linked into /css
  |-- blog/                  -> files for a defined route
      |-- article1.md        -> a blog article
      |-- article2.md        -> next blog article
      |-- ...                -> ...
  |-- public/                -> public files, will be linked to /public
      |-- fonts/             -> fonts, images, etc can be put here
      |-- images/            -> these are then available in /public/images/
      |-- scripts/           -> of course you can also embed scripts
      |-- ...
  |-- plugins/               -> your custom plugins
      |-- plugin1.php
      |-- plugin2.php
      |-- ...
  |-- index.md               -> Website index
  |-- config.json            -> your config json
  |-- layout.html            -> your layout file
  |-- different-layout.html  -> another layout file
  |-- favicon.ico            -> your favicon, this will be linked to /favicon.ico
  |-- .htaccess              -> protect your config (containing deploy keys)
```

## Using Docker

build the image: `sudo docker build -t mssg .`

then run

```bash
sudo docker run -it -p 8000:80 \
-v /path/to/your/source:/var/www/html/source \
-v /path/to/your/system:/var/www/html/system mssg "/root/start-script.sh"
```

to
 - bind port 80  from the container to port 8000 on your machine
 - link your `source` folder to the `source` folder in the container
 - do the same thing for the `system` folder
 - run the startup script (which will drop a bash shell)

You can omit the system line if you don't plan on changin anything with the system.

## Plugins


Available hooks: (`$config` refers to plugin config, the route config can always be found in `$config['..']`)
 - `before_route($config, $target_path)` called before route is started, here you can modify the route config
 - `before_parse($config, $raw_content, $file, $metadata)` called right before the markdown gets rendered - you can change `$file` to change the target file name.
 - `after_parse($config, $content, $file, $metadata)` called right after the markdown gets rendered - you can change `$file` to change the target file name.
 - `after_route($config, $pages, $write_to_file, $parse)` to create other files like a generated `index.html` file - called after every file was rendered
   - `$pages` contains metadata, title (rendered through template), url, filename and contents (as html).
   - `$write_html_file($file, $content, $title)` is a funcion that writes the content to the specified html file and applies the current layout
   - `$parse($markdown)` renders markdown to html
   - `$write_file($file, $content)` is a function that writes the content to the specified file

### Add a plugin:

If you want to add a plugin, create a new php file in `/source/plugins`. Use the `define_plugin($name, $methods)` function like this to add a new plugin named `"your-plugin"`:

```php
<?php

define_plugin("your-plugin", [
  "before_parse" => function ($config, &$raw_content, $file, $metadata) {
    $raw_content .= "\n **custom plugin footer**";
  }
]);
?>
```

And in your `config.json` add `"plugin": {"name": "your-plugin"}` for a specific route. This will append `<strong>custom plugin footer</strong>` to every page on that route.
