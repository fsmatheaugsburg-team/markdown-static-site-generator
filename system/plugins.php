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

      $write_to_file($parse($markdown), $config['title']);
    }
  ]
]

?>
