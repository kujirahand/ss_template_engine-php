# ss_template_engine-php

super small template engine for php

```php:test.php
// for template file
$ss = new ss_template_engine();
$ss->show('template.tpl', array('name'=>'Mike'));

// string
$ss = new ss_template_engine();
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name|upper}}!<br>", array("name"=>"Mike"));
echo $ss->cook("Hi,{{name}}!<br>", array("name"=>"<Mike>"));
echo $ss->cook("Hi,{{name|raw}}!<br>", array("name"=>"<b style='color:red'>Mike</b>"));
echo $ss->cook("calc={{code|eval}}<br>", array("code"=>"30*5"));
echo $ss->cook("calc={{30*5|eval}}<br>", array());
echo $ss->cook("Hi,{{code|eval}}!<br>", array("code"=>"rand(0,1)?'Mike':'Saya'"));
```


