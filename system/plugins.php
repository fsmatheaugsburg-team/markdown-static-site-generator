<?php

/**
 *  calls method $name for all plugins in the $plugin array
 *  $plugins  is an array of plugin methods and config pairs. It's structured like this {methods: $PLUGIN[name], config: <plugin-config>}
 *  $name     is the name of the method that should be called
 *  $args     array of arguments for the plugin method
 */
function call_plugin_function(&$plugins, $name, $args) {
  if (sizeof($plugins) == 0) return;
  foreach ($plugins as &$plugin) {
    if (isset($plugin['methods'][$name])) {
      custom_log(" * applying plugin " . $plugin['config']['name'] . ".$name");
      try {
        $plugin['methods'][$name]($plugin['config'], ...$args);
      } catch (Exception | Error $e) {
        custom_log("Error applying plugin " . $plugin['config']['name'] . ".$name: " . $e->getMessage());
      }
    }
  }
}

// define a plugin with $name and $methods
function define_plugin($name, $methods) {
  global $PLUGINS;

  if (isset($PLUGINS[$name])) {
    custom_log(" - **Error: there already is a plugin loaded with name $name!**");
    return;
  }

  custom_log(' - loaded plugin ' . $name);

  $PLUGINS[$name] = $methods;
}

/**
 *  Load all php files inside (absolute) $folder (non-recursive)
 */
function load_plugins($folder) {
  $plugins = scandir($folder);
  foreach ($plugins as $plugin) {
    $plugin_abs = $folder . '/' . $plugin;
    if (is_file($plugin_abs) && preg_match("/\.php$/", $plugin_abs) !== false) {
      require_once($plugin_abs);
    }
  }
}

$PLUGINS = [];
?>
