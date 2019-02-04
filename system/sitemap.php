<?php

function generate_sitemap($rendered_pages) {
  global $CONFIG;

  $hostname = $_SERVER['HTTP_HOST'];
  $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
  $webroot_offset = get_webroot_offset();

  $output = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
  foreach ($rendered_pages as $page) {
    $output .= "\t<url>\n\t\t<loc>${protocol}${hostname}${webroot_offset}$page[url]</loc>\n\t\t<lastmod>" . date('Y-m-d') . "</lastmod>\n\t</url>\n";
  }
  $output .= "</urlset>\n";

  return $output;
}
?>
