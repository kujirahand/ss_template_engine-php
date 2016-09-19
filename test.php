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

$ss->show('if', array("id" => 100));


$ss->show('foreach', array(
  "items" => array(
    array("name" => "Taro"), array("name" => "Jiro"),
    array("name" => "Saburo"), array("name"=>"Siro"),
  ),
));

$ss->show('foreach', array(
  "items" => array(
  ),
));

