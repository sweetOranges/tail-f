# tail-f
## 描述
php版本的tail -f
把一个文件下的文件顺序读取，且最后一个文件支持增量更新
## 用法
```php
<?php
require __DIR__ . '/Tailf.php';

$t = new Tailf('test/*.log', './runtime.dat');

$iter = $t->iterator();

foreach ($iter as $raw) {
  print($raw);
}

```
