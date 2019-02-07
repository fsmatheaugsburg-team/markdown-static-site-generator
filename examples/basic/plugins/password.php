<?php

define_plugin('password', [
  'after_route' => function($config, $pages, $write_to_html_file, $parse, $write_to_file) {
    $content = '';
    $password = crypt($config['password']);
    $content .= $config['username'] . ':' . $password . "\n";
    $htusers = $write_to_file('.htusers', $content);
    custom_log(".htusers: $htusers");

    $write_to_file('.htaccess', 'AuthType Basic
AuthName "Password"
AuthUserFile ' . $htusers . '
Require user ' . $config['username'] . '
');
  }
]);

?>
