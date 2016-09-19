<?php
include_once 'ss_template.php';

$ss = ss_template::get_engine();
$ss->setup(array(
  'dir_tpl'   => './tpl',
  'dir_cache' => './cache',
));

$ss->show('test', array(
  'name' => 'Tom',
  'age'  => 18,
  'code' => '<script>alert("Neko")</script>',
));

$ss->show('test2', array());

