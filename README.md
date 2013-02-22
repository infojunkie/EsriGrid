# Esri ARC/INFO ASCII GRID reader

Designed to handle large files. Tested with SRTM 90m Digital Elevation Data: http://srtm.csi.cgiar.org/.

## Usage

### Single tile handling

```php
<?php

use infojunkie\EsriGrid;

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

?>

### Multiple tile handling

```php
<?php

use infojunkie\EsriGrid;

$files = new EsriGridFiles(array('test_dem1.txt', 'test_dem2.txt', 'test_dem3.txt', 'test_dem4.txt', 'test_dem5.txt', 'test_dem6.txt'));
$grid = $files->getGridFromPoints(175, 225, 325, 675, 'calc_stats');
echo "max: $max, min: $min\n";
var_dump($grid);

?>

