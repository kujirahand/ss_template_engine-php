<?php

class ss_template {
  public $template_dir = '.';
  public $cache_dir = null;
  public $global_vars = array();
  public $plugins = [
    // php function
    'upper'  => 'strtoupper',
    'lower'  => 'strtolower',
    // global function
    'raw'    => 'ss_template_engine_plugin_raw',
    'esc'    => 'ss_template_engine_plugin_escape',
    'escape' => 'ss_template_engine_plugin_escape',
    'eval'   => 'ss_template_engine_plugin_eval',
    'bold'   => 'ss_template_engine_plugin_bold',
  ];
  public function __construct($params = array()) {
    global $ss_template_engine_instance;
    $ss_template_engine_instance = $this;
    // check params
    if (isset($params['template_dir'])) $this->template_dir = $params['template_dir'];
    if (isset($params['cache_dir'])) $this->cache_dir = $params['cache_dir'];
    if (isset($params['plugins'])) $this->plugins = $params['plugins'];
    if (isset($params['global_vars'])) $this->global_vars = $params['global_vars'];
  }
  public function show($name, $params) {
    // check template file
    $tpl_file = $this->template_dir.'/'.$name;
    if (!file_exists($tpl_file)) {
      throw new Exception('[ss_template_engine] template is not found');
    }
    // check cache file
    if ($this->cache_dir) {
      $cache_file = $this->cache_dir.'/_'.$name.'.cache.php';
      if (file_exists($cache_file)) {
        // need to update?
        $cache_time = filemtime($cache_file);
        $tpl_time = filemtime($tpl_file);
        if ($cache_time > $tpl_time) { // use cache file
          $this->run_cache($cache_file, $params);
          return;
        }
      }
      // run
      $body = file_get_contents($tpl_file);
      $this->cook($body, $params, $cache_file);
      $this->run_cache($cache_file, $params);
      return;
    }
    // direct
    $body = file_get_contents($tpl_file);
    echo $this->cook($body, $params, $cache_file);
  }
  public function run_cache($cache_file, $params) {
    extract($this->global_vars);
    extract($params);
    include($cache_file);
  }
  public function cook($body, $params, $cache_file = null) {
    global $ss_template_engine_params;
    global $ss_template_engine_plugins;
    $ss_template_engine_params = $this->global_vars + $params;
    $ss_template_engine_plugins = $this->plugins;
    // replace
    $body = preg_replace_callback(
      '#\{\{(.*?)\}\}#',
      function ($m) {
        return ss_template_engine::replace_vars($m);
      },
      $body);
    // save cache
    if ($cache_file != null) {
      file_put_contents($cache_file, $body);
    }
    return $body;
  }
  private static function exec_cmd_list($cmd_list) {
    global $ss_template_engine_params;
    global $ss_template_engine_stack;
    global $ss_template_engine_plugins;
    foreach ($cmd_list as $cmd) {
      $cmd = trim($cmd);
      // param value
      if (isset($ss_template_engine_params[$cmd])) {
        $ss_template_engine_stack[] = $ss_template_engine_params[$cmd];
        continue;
      }
      // plugins
      if (isset($ss_template_engine_plugins[$cmd])) {
        $pfunc = $ss_template_engine_plugins[$cmd];
        $arg = array_pop($ss_template_engine_stack);
        $value = call_user_func($pfunc, $arg);
        $ss_template_engine_stack[] = $value;
        $ss_template_engine_params['__raw'] = true;
        continue;
      }
      // else
      $ss_template_engine_stack[] = $cmd;
    }
  }
  private static function replace_vars($m) {
    global $ss_template_engine_params;
    global $ss_template_engine_stack;
    global $ss_template_engine_plugins;
    $ss_template_engine_stack = array();
    $ss_template_engine_params['__raw'] = false;
    // split parameters
    $line = trim($m[1]);
    if ($line == '') return '';
    $cmd_list = explode('|', $line);
    // check filter
    ss_template_engine::exec_cmd_list($cmd_list);
    $value = array_pop($ss_template_engine_stack);
    // escape
    if ($ss_template_engine_params['__raw'] == false) {
      $value = ss_template_engine_plugin_escape($value);
    }
    return $value;
  }
}

function ss_template_engine_plugin_bold($value) {
  return '<b>'.$value.'</b>';
}
function ss_template_engine_plugin_raw($value) {
  return $value;
}
function ss_template_engine_plugin_escape($value) {
  return htmlentities($value, ENT_QUOTES);
}
function ss_template_engine_plugin_eval($value) {
  $src = 'return('.$value.');';
  $value = eval($src);
  return $value;
}




