# Tutorial

## setting up docker

Build the image:

`docker build -t mssg .`

Running the image is as simple as `sudo docker run -it -p 8000:80 -v /path/to/your/source:/var/www/html/source "/root/start-script.sh"`

## Design philosophy

All your content goes into the `source` folder. In there you have a `config.json` which defines everything. If you have a favicon, you can place it in the root of your source folder

## The minimum setup

create your `config.json`:

```json
{
  "routes": {
    "/": {}
  }
}
```

your `layout.html`:
```html
{{body}}
```

your `index.md`:
```markdown
# hello world
```

Then navigate to `/system/` and click the "build" button.

You'll notice a warning: `# Critical security error: No Authorization set! Allowing everything!`. You can ignore this for now, we'll get to that later.

A small explanation:
 - the `config.json` is the heart of your website. In there we define everything.
 - At the moment, we have set up a route pointing to `/` (called target dir), meaning the web root. Since we did not specify, where the markdown files are located, that will populate the route, it assumes `/source/$target_dir`, which results in `/source/`.
 - Inside the `layout.html` file can set up a general page design. We have a couple of fields to work with here, i.e. `{{date}}` or `{{body}}` (which will be replaced with the rendered markdown)
 - And finally, we created a `index.md`. Since this file lies in the `/source/` directory, it falls into our defined route and will be rendered into `$target_dir/$name.html`, which evaluates to `/index.html`.


## Styling your website

If you want to integrate a stylesheet into your app, you'll need to create a new folder: `/source/css/`. In there you can create `base.css`, which will be integrated into every route.

You can also specify css files on a per route basis. Just plop a `"css": "my_custom_css.css"` in your route's config and off you go. Then you can just create your `my_custom_css.css` file in your `css` folder and off you go.

If you want a specific layout for a specific route, set it with `"layout": "your-layout.html"`. You can also set a global layout, if you don't want to use the default `layout.html` (which is the default global layout).

## Adding other headers (e.g. external libraries)

If you want to add a header field, you can choose to add it globally to all routes, or just to specific routes. Either way, it goes like this: `"headers" : ["header1", "header2", ...]`. Global config fields go at the root of your config.

Your `config.json` might look something like this now:

```json
{
  "routes": {
    "/": {
      "css": "my_custom_css.css",
      "headers": [
        "<link href='https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.css' rel='stylesheet'>",
        "<link href='https://fonts.googleapis.com/css?family=Roboto:400,400i,500,500i' rel='stylesheet'>"
      ]
    }
  },
  "headers": [
    "<meta name='viewport' content='width=device-width, initial-scale=1'>"
  ]
}
```

Headers are ordered like this:
 - global headers
 - route-specific headers
 - `base.css` and `current_theme.php` (more on that later)
 - route css

 In this case, the resulting headers are ordered like this:

 ```html
 <meta name='viewport' content='width=device-width, initial-scale=1'>
 <link href='https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.css' rel='stylesheet'>
 <link href='https://fonts.googleapis.com/css?family=Roboto:400,400i,500,500i' rel='stylesheet'>
 <link rel="stylesheet" href="/css/base.css"/>
 <link rel="stylesheet" href="/css/current_theme.php"/>
 <link rel="stylesheet" href="/css/my_custom_css.css"/>
 ```

## Formatting page titles (the browser tab things)

You can specify a template from which titles will be derived globally, and for specific routes: `"title": "my website - %s"`, where `%s` will be replaced by the page's specific title. The specific title is either taken from metadata, or if that's not available, the contents of the first top level header are used.

## Metadata

You can specify any number of attributes in each markdown file like this:

```markdown
~title: test title
~date: 02.11.2019
~tags: Programming, Tutorial, Documentation

# hello world

Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
```

The metadata will be read out, and removed from the markdown before render. Metadata is also passed on to plugins, but that will be coveref later on.

## Adding auth keys to your application and automating the build process

Put `"authkeys": ["password"]` into your config file and try to press the build button. You'll notice an error: `# Error: You are not authorized!`. Now enter `password` into the textfield labeled `Auth key`, the build button should work again.

To start a build, all you need to do is send a request to `/system/generator.php?build` and pass a valid auth key per header. For example with `curl`: `curl -H "Authorization: Bearer <token>" "https://<url>/system/generator.php?build"`. You can also pass it via GET parameter `?build&auth=<token>` or POST parameter `auth`, or even a cookie with name `auth`.
