<?php
require __DIR__ . '/Tailf.php';

$t = new Tailf('test/*.log', './runtime.dat');

$iter = $t->iterator();

foreach ($iter as $raw) {
  print($raw);
}