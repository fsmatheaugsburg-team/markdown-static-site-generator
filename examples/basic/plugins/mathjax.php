<?php
define_plugin("mathjax", [
  "before_route" => function (&$config) {
    if (!isset($config[".."]["headers"])) {
      $config[".."]["headers"] = [];
    }
    $config[".."]["headers"][] = "<script type='text/javascript' async src='https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML'></script>";
  }
]);
?>
