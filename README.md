# Markdown static site generator

This is only the page generator code, the webpage is defined in another repository.

Please refer to [the tutorial](tutorial.md) for more details on usage.

## `config.json` format

 - `routes` define all your routes, keys are absolute paths (on the target blog), settings are:
   - `use: "/path"` the folder containing the markdown files (relative to the `/source` directory). Default is the target path (this objects key). All markdown files in this directory will be rendered to HTML and made public (except `README.md`).
   - `title: "title with %s placeholder"` template for the page titles, `%s` is replaced with the documents title
   - `css: "/path/to/css" | [paths]` additional css files to be included (either a string, or array of strings). Paths are relative to the css directory `/css`
   - `layout: "layout.html"` Use a special template for this route. Relative to the `/source` directory
   - `protection: {<type>: <config>}` specify a protection mechanism. e.g. `password: "123456"` or `ip: {"220.248.0.0/14": "block", "65.19.146": "block"}` (block chinese ip addresses) (not implemented yet)
   - `plugin: {name: "name", <additional config>}`, you can also supply an array or plugin configs.
 - `layout: "layout.html"`
 - `title: "template string %s"` specify a global title template  (gets overwritten by route-specific configurations)
 - `themes: ["theme1", "theme2", ...]` list of theme names
 - `authkeys: ["key1", "key2", ...]` list of authentication keys. When set, building requires authentication with one of these keys.
 - `formatting: {date: <php date formattin>, time: <format>, datetime: <format>}` define formatting for displaying and parsing dates. Default is
   ```
    "formatting": {
        "date": "d.m.Y",
        "time": "H:i",
        "datetime": "d.m.Y - H:i"
    }
    ```

## Example projects

Just upload the contents of these into your `/source` folder and press "build" in your panel.

Example project setups can be found inside the `/example` folder:
 - `/example/hello-world` The absolute minimum required to get your project going
 - `/example/basic` A goof starting off point. It has things configured like
   - The `bloglike` plugin. This enables you to have a blog, or something similar
   - A layout with some CSS (internal + external) to make it look presentable
   - A favicon and some public folders / images


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
  |-- index.md               -> Website index
  |-- config.json            -> your config json
  |-- layout.html            -> your layout file
  |-- different-layout.html  -> another layout file
  |-- favicon.ico            -> your favicon, this will be linked to /favicon.ico

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

## TODO

 - different layouts for different routes / page
 - no passwords
 - global / local blacklist for files (i.e. don't render `README.md` for specific folders);
 - enable user supplied plugins (i.e. load everything in `/source/plugins` as php code)


## Plugins

Available hooks: (`$config` refers to plugin config, the route config can always be found in `$config['..']`)
 - `before_route($config, $target_path)` called before route is started, here you can modify the route config
 - `before_parse($config, $raw_content, $file, $metadata)` called right before the markdown gets rendered - you can change `$file` to change the target file name.
 - `after_parse($config, $content, $file, $metadata)` called right after the markdown gets rendered - you can change `$file` to change the target file name.
 - `index($config, $pages, $write_to_file, $parse)` to create a generated `index.html` file - called after every file was rendered
   - `$pages` contains metadata, title (rendered through template), url, filename and contents (as html).
   - `$write_to_file($content)` is a funcion that writes the content to the `index.html`
   - `$parse($markdown)` renders markdown to html

### Add a plugin:

If you want to add a plugin, use the `define_plugin($name, $methods)` function like this (make sure this file is included in the build process):

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
