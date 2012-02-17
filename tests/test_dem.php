#!/usr/bin/php
<?php

include('../esri_grid.php');

$max = -99999;
$min = 100000;
$file = new EsriGridFile('test_dem1.txt');
$grid = $file->getGridFromPoints(0, 0, 140, 230, 'calc_stats');
echo "max: $max, min: $min\n";
var_dump($grid);

function calc_stats($x, $y, $elevation) {
  global $max, $min;
  echo "$x, $y: $elevation\n";
  if (!is_null($elevation)) {
    $max = max($max, $elevation);
    $min = min($min, $elevation);
  }
}

