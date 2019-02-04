<?php

function generate_sitemap($rendered_pages) {
  global $CONFIG;

  $output = '<?xml version="1.0" encoding="UTF-8" ?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';
  foreach ($rendered_pages as $page) {
    $output .= '<url>\n<loc>' . $page['url'] . '</loc>\n<lastmod>' . date('Y-m-d') . '</lastmod>';
  }
  $output .= '</urlset>\n';

  return $output;
}
?>
