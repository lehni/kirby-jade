<?php

// Support for Jade Templates by Jürg Lehni
// Modified to support Kirby 2 by Leo Koppelkamm
// http://lehni.org/
// 
// For now, this code depends on: https://github.com/lehni/jade.php
// Which is forked from: https://github.com/sisoftrg/jade.php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

spl_autoload_register(function($class) {
  if(strstr($class, 'Jade'))
    include_once(str_replace('\\', DS, $class) . '.php');
});

function jade($template) {
  $templates = kirby::instance()->roots()->templates();
  
  // Closure to recursively get the modification time from jade templates and
  // their super templates (determeined by pre-parsing 'extends' statements).
  $getChangeTime = function($template, $time) use (&$getChangeTime, $templates) {
    $file = "$templates/$template.jade";
    $t = @filectime($file);
    if ($t === false)
      die("Can't open jade file '$file'");
    if ($t > $time)
      $time = $t;
    $fp = fopen($file, 'r');
    // Find all the lines of the template that contains an valid statements,
    // and see there are any 'extends' or 'include' statements to determine
    // dependencies.
    while (true) {
      $line = fgets($fp);
      if ($line === false)
        break;
      $line = trim($line);
      if (!$line || !strncmp($line, '//', 2))
        continue;
      if (!strncmp($line, 'extends ', 8) || !strncmp($line, 'include ', 8))
        $time = $getChangeTime(substr($line, 8), $time);
    }
    fclose($fp);
    return $time;
  };

  $time = $getChangeTime($template, 0);

  static $jade = null;
  if (!isset($jade) || !$jade)
    $jade = new Jade\Jade(true);

  $cache = kirby::instance()->roots()->cache() . DS . "$template.jade.php";
  $t = @filectime($cache);
  // Now get the modification time from the cached file, and regenerate if
  // the jade template or any of its dependencies have changed.
  if ($t === false || $t < $time)
    file_put_contents($cache, $jade->render("$templates/$template.jade"));
  return $cache;
}

// Register the jade engine as a handler for templates
$engines = c::get('tpl.engines');
$engines['jade'] = function($file) {
  return jade(basename($file, '.jade'));
};
c::set('tpl.engines', $engines);
