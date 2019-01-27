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
 - `layout: "layout.html"`
 - `title: "template string %s"` specify a global title template  (gets overwritten by route-specific configurations)
 - `themes: ["theme1", "theme2", ...]` list of theme names
 - `authkeys: ["key1", "key2", ...]` list of authentication keys. When set, building requires authentication with one of these keys.


# Docker

## local testing:

build the image

`sudo docker build -t mssg .`

then run

```
sudo docker run -it -p 8000:80 \
-v /path/to/your/source:/var/www/html/source \
-v /path/to/your/system:/var/www/html/system mssg "/root/start-script.sh"
```

to
 - bind port 80  from the container to port 8000 on your machine
 - link your `source` folder to the `source` folder in the container
 - do the same thing for the `system` folder
 - run the startup script (which will drop a bash shell)


## TODO

 - different layouts for different routes / page
 - no passwords
 - global / local blacklist for files (i.e. don't render `README.md` for specific folders);


## Plugins

Hooks (config refers to plugin config):
 - `before_parse($config, $raw_content, $metadata)` gets applied right before the markdown gets rendered
 - `index($config, $pages, $write_to_file, $parse)` to create a generated `index.html` file
  - `$pages` contains metadata, title (rendered through template), url, filename and contents (as html).
  - `$write_to_file($content)` is a funcion that writes the content to the `index.html`
  - `$parse($markdown)` renders markdown to html
