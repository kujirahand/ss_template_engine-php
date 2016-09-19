<?php

class ss_template {
  private static $inst = null;
  public $dir_tpl = './tpl';
  public $dir_cache = './cache';
  public $globals = array();
  public $params = array();
  public $use_cache = false;
  public $page = "setup";
  public $plugins = array(
    'esc'     => 'ss_template_plugin_escape',
    'escape'  => 'ss_template_plugin_escape',
    'bold'    => 'ss_template_plugin_bold',
    'upper'   => 'strtoupper',
    'lower'   => 'strtolower',
  );
  
  public static function get_engine() {
    if (ss_template::$inst == null) {
      $p = new ss_template();
      ss_template::$inst = $p;
    }
    return ss_template::$inst;
  }
  public function setup($params) {
    $this->dir_tpl = $params['dir_tpl'];
    $this->dir_cache = $params['dir_cache'];
    $this->globals = isset($params['globals']) ? $params['globals'] : array();
    if (!is_writable($this->dir_cache)) {
      throw new TemplateException('cache_dir is not writable');
    }
  }
  public function show($name, $params = null) {
    if ($params != null) {
      $this->params = $params + $this->globals;
    }
    $this->page = $name;
    $f_tpl   = $this->dir_tpl  .'/'.$name.'.ss.html';
    $f_cache = $this->dir_cache.'/'.$name.'.cache.php';
    // check cache
    if (file_exists($f_cache) && $this->use_cache) {
      $m_tpl   = filemtime($f_tpl);
      $m_cache = filemtime($f_cache);
      if ($m_tpl > $m_cache) {
        $this->_compile($f_tpl, $f_cache);
      }
      return $this->_run($f_cache);
    }
    $this->_compile($f_tpl, $f_cache);
    $this->_run($f_cache);
  }
  public function _run($f_cache) {
    extract($this->params);
    try {
      include($f_cache);
    } catch (Exception $e) {
      throw new TemplateException($e->message, $page);
    }
  }
  public function _compile($f_tpl, $f_cache) {
    $params  = $this->params;
    $plugins = $this->plugins;
    $page = $this->page;
    // load
    $tpl = file_get_contents($f_tpl);
    // replace
    $tpl = preg_replace_callback(
      '#\{\{(.*?)\}\}#',
      function ($m) use (&$params, $plugins, $page) {
        $line = $m[1];
        $cmd_list = explode("|", $line);
        $raw_flag = false;
        $echo_flag = true;
        $res = "";
        foreach ($cmd_list as $cmd) {
          $cmd = trim($cmd);
          // var_name
          $args = explode(".", $cmd);
          $name = array_shift($args);
          if (isset($params[$name])) {
            $ins_name = ss_template__var_name($name, $args);
            $res = ($res == "") ? $ins_name : "{$res}.$ins_name";
            continue;
          }
          // plugins
          if (isset($plugins[$name])) {
            $raw_flag = true;
            $func = $plugins[$name];
            if (count($args) == 0) {
              $res = "$func($res)";
            } else {
              $arg_str = ss_template__quote_array($args);
              if ($res == "") { $res = 0; }
              $res = "$func($res, $arg_str)";
            }
            continue;
          }
          // syntax
          $args = explode(" ", $cmd);
          $name = array_shift($args);
          if ($name == "raw") { $raw_flag = true; continue; }
          // | - include 
          if ($name == "include" || $name == "require") {
            $raw_flag = true; $echo_flag = false;
            if (count($args) == 0) throw new TemplateException("include no params", $page);
            $file_name = $args[0];
            $res = "ss_template_syntax_include('$file_name')";
            break;
          }
          // | - foreach
          if ($name == "foreach" || $name == "each") {
            $raw_flag = true; $echo_flag = false;
            $items = array_shift($args);
            if ($items == null) throw new TemplateException("foreach no array", $page);
            $it = array_shift($args);
            if ($it == "as") $it = array_shift($args);
            if ($it == null) $it = "it";
            $items = ss_template__replace_var_name($items);
            $params[$it] = array();
            $params['index'] = 0;
            $res = "if($items){ \$index=-1; foreach({$items} as \${$it}) { \$index++;";
            continue;
          }
          if ($name == "else_foreach" || $name == "else_each") {
            $raw_flag = true; $echo_flag = false;
            $res = "}} else {";
            continue;
          }
          if ($name == "end_foreach" || $name == "end_each") {
            $raw_flag = true; $echo_flag = false;
            $res = "}";
            continue;
          }
          // | - if
          if ($name == "if") {
            $raw_flag = true; $echo_flag = false;
            $ar = implode(" ", $args);
            $ar = ss_template__replace_var_name($ar);
            $res = "if($ar){";
            continue;
          }
          if ($name == "else") {
            $raw_flag = true; $echo_flag = false;
            $res = "} else {";
            continue;
          }
          if ($name == "end_if" || $name == "endif") {
            $raw_flag = true; $echo_flag = false;
            $res = "}";
            continue;
          }
          // other
          $res .= ss_template__replace_var_name($cmd);
        }
        if ($raw_flag == false) {
          $res = "ss_template_plugin_escape($res)";
        }
        if ($echo_flag) $res = "echo $res;";
        return "<?php $res ?>";
      }, $tpl);
    file_put_contents($f_cache, $tpl);
  }
}

class TemplateException extends Exception {
  public $page;
  public function __construct($message, $page = '?', $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
    $this->page = $page;
  }
  public function __toString() {
    return __CLASS__ . ":[ '$this->page' ] {$this->message}\n";
  }
}

function ss_template__var_name($name, $args) {
  if ($name == null) {
    $name = array_shift($args);
  }
  $s = "\${$name}";
  if ($args == null || count($args) == 0) return $s;
  foreach ($args as $a) {
    $s .= "['$a']";
  }
  return $s;
}
function ss_template__quote_array($args) {
  foreach ($args as &$a) {
    $a = "'$a'";
  }
  return 'array('.implode(',',$args).')';
}
function ss_template__replace_var_name($ar) {
  $ar = preg_replace_callback('#([a-zA-Z][a-zA-Z0-9\.]*)#',
    function($m){
      $args = explode('.', $m[1]);
      return ss_template__var_name(null, $args);
    }, $ar);
  return $ar;
}

function ss_template_syntax_include($name) {
  $eng = ss_template::get_engine();
  $eng->show($name);
}

// plugins
function ss_template_plugin_escape($v) {
  return htmlentities($v);
}

function ss_template_plugin_bold($v) {
    return "<b>$v</b>";
}






