<?php
require_once 'ss_template_engine.php';

// normal usage
$ss = new ss_template_engine();
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name|upper}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"<Mike>"));
echo $ss->cook("Hi,{{name|raw}}!<br>", array("name"=>"<b style='color:red'>Mike</b>"));

// eval method
echo $ss->cook("calc={{code|eval}}<br>", array("code"=>"30*5"));
echo $ss->cook("calc={{30*5|eval}}<br>", array());
echo $ss->cook("Hi,{{code|eval}}!<br>", array("code"=>"rand(0,1)?'Mike':'Saya'"));

// calc method like forth
echo $ss->cook("calc={{n|3|*}}<br>", array("n"=>2));
echo $ss->cook("is_even={{#ff0000|lineno|is_even}}<br>", array("lineno"=>10));
echo $ss->cook("is_odd={{#ff0000|lineno|is_odd}}<br>", array("lineno"=>10));
echo $ss->cook("ifelse1={{true|false|1|ifelse}}<br>", array());
echo $ss->cook("ifelse2={{true|false|0|ifelse}}<br>", array());
echo $ss->cook("5|5|eq={{true|false|5|5|==|ifelse}}<br>", array());
echo $ss->cook("5|3|gteq={{true|false|5|3|>=|ifelse}}<br>", array());
echo $ss->cook("10|2|/={{10|2|/}}<br>", array());

// cache
$ss = new ss_template_engine(['cache_dir'=>'./cache']);
$ss->show('test.tpl', array('name'=>'Mike'));

