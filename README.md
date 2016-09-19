# ss_template_engine-php

ss_template_engine is super simple template engine.

## how to use

setup code

```php:test.php
// make instance
$ss = ss_template::get_engine();
// setup directories
$ss->setup(array(
  'dir_tpl'   => './tpl',
  'dir_cache' => './cache',
));
```

show template

```php:test.php
// show template
$ss->show('test', array(
  'name' => 'Tom',
  'age'  => 18,
  'code' => '<script>alert("Neko")</script>',
));
```

template code:

```php:tpl/test.ss.html
<h1>Hello, {{name}}!</h1>
<p>He is {{age}} years old.</p>
```

The name should be "(name).ss.html".


## include other template

main template:

```html:tpl/test2.ss.html
<div><h1>main</h1></div>
{{ include test2-sub }}
```

sub template:

```html:tpl/test2-sub.ss.html
<div><h3>sub</h3></div>
```

## template format

```html:tpl/test3.ss.html
{{ (variable) }} ... auto escape html tag
{{ (variable_name) | (filter_func) }} ... use plugins
```

## foreach || each

```html:tpl/foreach.ss.html
<ul>
{{ each items as it }}
  <li>{{index + 1 | bold }}.{{it.name}}</li>
{{ else_each }}
  <li>no data</li>
{{ end_each }}
</ul>
```







