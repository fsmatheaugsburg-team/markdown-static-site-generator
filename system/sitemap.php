<?php

function generate_sitemap($rendered_pages) {
  global $CONFIG;

  $output = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
  foreach ($rendered_pages as $page) {
    $link = absolute_url($page['url']);
    $output .= "\t<url>\n\t\t<loc>$link</loc>\n\t\t<lastmod>" . date('Y-m-d') . "</lastmod>\n\t</url>\n";
  }
  $output .= "</urlset>\n";

  return $output;
}
?>
