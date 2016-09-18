<?php
class ss_template_engine {
  public $template_dir = '.';
  public $cache_dir = null;
  public $plugins = [
    'upper' => 'strtoupper',
    'lower' => 'strtolower',
    'escape' => 'ss_template_engine_plugin_escape',
    'eval'  => 'ss_template_engine_plugin_eval',
    'if' => 'ss_template_engine_plugin_if',
    'test' => 'ss_template_engine_plugin_test',
  ];
  public function __construct($params = array()) {
    // check params
    if (isset($params['template_dir'])) $this->template_dir = $params['template_dir'];
    if (isset($params['plugins'])) $this->plugins = $params['plugins'];
  }
  public function show($name, $params) {
    $body = $this->load($name);
    $body = $this->cook($body, $params);
    echo $body;
  }
  public function load($name) {
    $path = $this->template_dir.'/'.$name;
    if (!file_exists($path)) {
      throw Exception('[SS_template_engine] not found');
    }
    $body = @file_get_contents($path);
    return $body;
  }
  public function cook($body, $params) {
    // global for plugins
    global $ss_template_engine_params;
    $ss_template_engine_params = $params;
    $plugins = $this->plugins;
    // replace
    $body = preg_replace_callback(
      '#\{\{(.*?)\}\}#',
      function ($m) use ($plugins) {
        return ss_template_engine::replace_vars($m, $plugins);
      },
      $body);
    return $body;
  }
  private static function replace_vars($m, $plugins) {
    global $ss_template_engine_params;
    $params = $ss_template_engine_params;
    // split parameters
    $line = trim($m[1]);
    if ($line == '') return '';
    $cmd_list = explode('|', $line);
    $raw_flag = false;
    // get value
    $var_name = array_shift($cmd_list);
    $value = $var_name;
    if (isset($params[$var_name])) {
      $value = $params[$var_name];
    }
    // filter
    foreach ($cmd_list as $cmd) {
      if ($cmd == 'raw') { $raw_flag = true; continue; }
      $args = explode(' ', trim($cmd));
      $cmd_name = array_shift($args);
      if (isset($plugins[$cmd_name])) {
        if (!$args) { $args = $value; } else { array_unshift($args, $value); }
        $value = call_user_func($plugins[$cmd_name], $args);
        $raw_flag = true;     
      } 
    }
    // escape
    if ($raw_flag == false) {
      $value = htmlentities($value, ENT_QUOTES);
    }
    return $value;
  }
}

function ss_template_engine_plugin_test($value) {
  return '<b>'.$value.'</b>';
}
function ss_template_engine_plugin_escape($value) {
  return htmlentities($value, ENT_QUOTES);
}
function ss_template_engine_plugin_eval($value) {
  $src = 'return ('.$value.');';
  return eval($src);
}
function ss_template_engine_plugin_if($value) {
  global $ss_template_engine_params;
  $params = $ss_template_engine_params;
  extract($params);
}

/*
$ss = new ss_template_engine();
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name|upper}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"<Mike>"));
echo $ss->cook("Hi,{{name|raw}}!<br>", array("name"=>"<b style='color:red'>Mike</b>"));
echo $ss->cook("calc={{code|eval}}<br>", array("code"=>"30*5"));
echo $ss->cook("calc={{30*5|eval}}<br>", array());
echo $ss->cook("Hi,{{code|eval}}!<br>", array("code"=>"rand(0,1)?'Mike':'Saya'"));
*/





