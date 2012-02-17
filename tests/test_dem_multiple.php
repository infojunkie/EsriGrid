#!/usr/bin/php
<?php

include('./esri_grid.php');

$max = -99999;
$min = 100000;
$files = new EsriGridFiles(array('test_dem1.txt', 'test_dem2.txt', 'test_dem3.txt', 'test_dem4.txt', 'test_dem5.txt', 'test_dem6.txt'));
$grid = $files->getGridFromPoints(175, 225, 325, 675, 'calc_stats');
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

