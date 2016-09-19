<?php
class ss_template_engine {
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
    // class static method
    '*' => array('ss_template_engine', 'plugin_mul'),
    '/' => array('ss_template_engine', 'plugin_div'),
    '%' => array('ss_template_engine', 'plugin_mod'),
    '+' => array('ss_template_engine', 'plugin_add'),
    '-' => array('ss_template_engine', 'plugin_sub'),
    '==' => array('ss_template_engine', 'plugin_eq'),
    '!=' => array('ss_template_engine', 'plugin_ne'),
    '>'  => array('ss_template_engine', 'plugin_gt'),
    '>=' => array('ss_template_engine', 'plugin_gteq'),
    '<'  => array('ss_template_engine', 'plugin_lt'),
    '<=' => array('ss_template_engine', 'plugin_lteq'),
    'eq' => array('ss_template_engine', 'plugin_eq'),
    'ne' => array('ss_template_engine', 'plugin_ne'),
    'gt'  => array('ss_template_engine', 'plugin_gt'),
    'gteq' => array('ss_template_engine', 'plugin_gteq'),
    'lt'  => array('ss_template_engine', 'plugin_lt'),
    'lteq' => array('ss_template_engine', 'plugin_lteq'),
    'is_even' => array('ss_template_engine', 'plugin_is_even'),
    'is_odd'  => array('ss_template_engine', 'plugin_is_odd'),
    'if'     => array('ss_template_engine', 'plugin_if'),
    'ifelse' => array('ss_template_engine', 'plugin_ifelse'),
  ];
  public function __construct($params = array()) {
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
  static function plugin_mul($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return $sv * $value;
  }
  static function plugin_div($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return $sv / $value;
  }
  static function plugin_mod($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return $sv % $value;
  }
  static function plugin_add($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return $sv + $value;
  }
  static function plugin_sub($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return $sv - $value;
  }
  static function plugin_eq($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($value == $sv);
  }
  static function plugin_ne($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($value != $sv);
  }
  static function plugin_gt($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($sv > $value);
  }
  static function plugin_lt($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($sv < $value);
  }
  static function plugin_gteq($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($sv >= $value);
  }
  static function plugin_lteq($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($sv <= $value);
  }
  static function plugin_is_even($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($value % 2 == 0) ? $sv : '';
  }
  static function plugin_is_odd($value) {
    global $ss_template_engine_stack;
    $sv = array_pop($ss_template_engine_stack);
    return ($value % 2 == 1) ? $sv : '';
  }
  static function plugin_if($value) {
    global $ss_template_engine_stack;
    $then = array_pop($ss_template_engine_stack);
    return ($value) ? $then : '';
  }
  static function plugin_ifelse($value) {
    global $ss_template_engine_stack;
    $else = array_pop($ss_template_engine_stack);
    $then = array_pop($ss_template_engine_stack);
    return ($value) ? $then : $else;
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
/*
$ss = new ss_template_engine();
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name|upper}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"<Mike>"));
echo $ss->cook("Hi,{{name|raw}}!<br>", array("name"=>"<b style='color:red'>Mike</b>"));
echo $ss->cook("calc={{code|eval}}<br>", array("code"=>"30*5"));
echo $ss->cook("calc={{30*5|eval}}<br>", array());
echo $ss->cook("Hi,{{code|eval}}!<br>", array("code"=>"rand(0,1)?'Mike':'Saya'"));
echo $ss->cook("calc={{n|3|*}}<br>", array("n"=>2));
echo $ss->cook("is_even={{#ff0000|lineno|is_even}}<br>", array("lineno"=>10));
echo $ss->cook("is_odd={{#ff0000|lineno|is_odd}}<br>", array("lineno"=>10));
echo $ss->cook("ifelse1={{true|false|1|ifelse}}<br>", array());
echo $ss->cook("ifelse2={{true|false|0|ifelse}}<br>", array());
echo $ss->cook("5|5|eq={{true|false|5|5|==|ifelse}}<br>", array());
echo $ss->cook("5|3|gteq={{true|false|5|3|>=|ifelse}}<br>", array());
echo $ss->cook("10|2|/={{10|2|/}}<br>", array());
*/




