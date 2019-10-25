<?php
define_plugin('bloglike', [
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
    $article_layout = isset($config['layout']) ? $config['layout'] : "## [{{title}}]({{url}})\n*Written on the {{date}}*\n\n{{preview}} [more]({{url}})";

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
        'preview' => substr(simplify_text(strip_tags($post['content'])), 0, $config['previewlen']) . '...',
        'url' => get_webroot_offset() . $post['url']
      ], 0), $metadata);

      // put article in the list
      $articles[] = [
        'metadata' => $dict,
        'content' => "\n" . fill_template_string_dict($article_layout, $dict) . "\n",
        'date' => $timestamp,
        'post' => $post['content']
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
      $markdown .= "\n\n*[RSS feed](" . get_webroot_offset() . $config['..']['url'] . 'feed.rss)*';
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
        $rss_rendered .= '<guid>' . hash('sha256', $page['metadata']['url']) . '</guid>';
        if (isset($config['author'])) {
          $rss_rendered .= '<author>' . $config['author'] . '</author>';
        }
        $rss_rendered .= '<description><![CDATA[' . $page['post'] . ']]></description>';
        $rss_rendered .= '<pubDate>' . date(DATE_RSS, $page['date']) . '</pubDate>';
        $rss_rendered .= '</item>';
      }
      $rss_rendered .= '</channel></rss>';

      // write rss feed to file
      $write_raw('feed.rss', $rss_rendered);
    }
  }
]);

?>
