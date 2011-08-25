#!/usr/bin/php
<?php

include('./esri_grid.php');

$file = new EsriGridFile('test_dem.txt');
$grid = $file->getGridFromPoints(0, 0, 140, 230);
var_dump($grid);

