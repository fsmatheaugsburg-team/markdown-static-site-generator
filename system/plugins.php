<?php

$PLUGINS = [
  'bloglike' => [
    'index' => function ($config, $pages, $write_to_file, $parse) {
      global $CONFIG;

      if (!isset($config['previewlen'])) $config['previewlen'] = 200;
      if (!isset($config['title'])) $config['title'] = 'Blog entries';

      $markdown = "# " . $config['title'] . "\n";

      $article_layout = isset($config['layout']) ? $config['layout'] : "## {{title}}\n*Writtenn on the {{date}}*\n\n{{preview}} [more]({{url}})";

      $articles = [];

      $index = 0;
      foreach ($pages as $post) {
        $metadata = $post['metadata'];
        if (!isset($metadata['date'])) {
          throw new Error("[plugin:bloglike] all blog entries must have a date in their metadata!");
        }
        $timestamp = timestamp_from_date($metadata['date'], $CONFIG['formatting']['date']);

        $dict = array_merge(default_dict([
          'index' => $index,
          'preview' => substr(simplify_text(strip_tags($post['content'])), 0, $config['previewlen']) . '...',
          'url' => $post['url']
        ], 0), $metadata);

        $articles[] = [
          'content' => "\n" . fill_template_string_dict($article_layout, $dict) . "\n",
          'date' => $timestamp
        ];

        $index++;
      }

      // TODO figure out sorting
      //usort($articles, "date");

      foreach($articles as $page) {
        $markdown .= $page['content'];
      }

      $rendered = $parse($markdown);

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
                document.getElementById('tag-highliht-output').innerHTML = '&nbsp;';
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
            out.innerHTML = '<em id=\"tag-highliht-output\">&nbsp;</em>';
            let firsth1 = document.getElementById('tagging-script').parentNode.querySelector('h1');
            firsth1.parentNode.insertBefore(out, firsth1.nextSibling);
          }
          if (location.hash.length > 1) {
            let tag = location.hash.substr(1);
            taggedArticles.highlight(tag);
          }
        </script>";
      }

      $write_to_file($rendered, $config['title']);
    }
  ]
]

?>
