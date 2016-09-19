<?php

class ss_template {
  private static $inst;
  public $dir_tpl = './tpl';
  public $dir_cache = './cache';
  public $globals = array();
  public $params = array();
  public $use_cache = false;
  public $plugins = array(
    'esc'     => 'ss_template_plugin_escape',
    'escape'  => 'ss_template_plugin_escape',
    'upper'   => 'strtoupper',
    'lower'   => 'strtolower',
    'include' => 'ss_template_plugin_include',
  );
  
  public static function get_engine() {
    $p = new ss_template();
    ss_template::$inst = $p;
    return $p;
  }
  public function setup($params) {
    $this->dir_tpl = $params['dir_tpl'];
    $this->dir_cache = $params['dir_cache'];
    $this->globals = isset($params['globals']) ? $params['globals'] : array();
    if (!is_writable($this->dir_cache)) {
      throw new Exception('[error] cache_dir is not writable');
    }
  }
  public function show($name, $params) {
    if ($params != null) {
      $this->params = $params + $this->globals;
    }
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
    include_once($f_cache);
  }
  public function _compile($f_tpl, $f_cache) {
    $params  = $this->params;
    $plugins = $this->plugins;
    // load
    $tpl = file_get_contents($f_tpl);
    // replace
    $tpl = preg_replace_callback(
      '#\{\{(.*?)\}\}#',
      function ($m) use ($params, $plugins) {
        $line = $m[1];
        $cmd_list = explode("|", $line);
        $raw_flag = false;
        $res = "";
        foreach ($cmd_list as $cmd) {
          $cmd = trim($cmd);
          if (isset($params[$cmd])) {
            $res = ($res == "") ? "\${$cmd}" : "{$res}.\${$cmd}";
            continue;
          }
          if ($cmd == "raw") {
            $raw_flag = true;
            continue;
          }
          if (isset($plugins[$cmd])) {
            $raw_flag = true;
            $func = $plugins[$cmd];
            $res = "$func($res)";
            continue;
          }
          // other
          $cmd = str_replace("'", "\'", $cmd);
          $res = ($res == "") ? "'$cmd'" : "{$res}.'{$cmd}'";
        }
        if ($raw_flag == false) {
          $res = "ss_template_plugin_escape($res)";
        }
        return "<?php echo $res ?>";
      }, $tpl);
    file_put_contents($f_cache, $tpl);
  }
}

function ss_template_plugin_escape($v) {
  return htmlentities($v);
}

function ss_template_plugin_include($name) {
echo "[$name]";
  $eng = ss_template::get_engine();
  $eng->show($name, null);
}








