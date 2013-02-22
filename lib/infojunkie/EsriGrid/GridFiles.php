<?php
namespace infojunkie\EsriGrid;

class GridFiles {
  var $tiles = array();

  function __construct($filepaths) {
    foreach ($filepaths as $filepath) {
      $this->tiles[] = new GridFile($filepath);
    }
  }

  function getGridFromPoints($x_A, $y_A, $x_B, $y_B, $callback = null) {
    $time = microtime(true);
    $grid = array();
    $x = $x_A;
    $y = $y_A;
    while (true) {
      list($tile, $line, $offset) = $this->locatePoint($x, $y);

      // TODO: Handle point not found by filling grid with nulls until the next available tile.
      if (empty($tile)) {
        throw new Exception(Exception::POINT_NOT_FOUND);
      }

      $box = $tile->getBoundingBox(); // this returns the end point outside the tile
      $x_N = min($x_B, $box[1][0]);
      $y_N = min($y_B, $box[1][1]);
      list($line_N, $offset_N) = $tile->locatePoint(min($x_N, $box[1][0] - $tile->cellsize), min($y_N, $box[1][1] - $tile->cellsize));
      $g = $tile->getGrid($line, $offset, $line_N, $offset_N, $callback);

      // Add g to grid.
      if ($x == $x_A) {
        // add g as new lines
        $grid = array_merge($grid, $g);
      }
      else {
        // append g to current lines
        $delta = count($grid) - count($g);
        foreach ($g as $g_i => $g_line) {
          $grid[$delta + $g_i] = array_merge($grid[$delta + $g_i], $g_line);
        }
      }
     
      // Next file.
      if ($x_N >= $x_B) {
        if ($y_N >= $y_B) break; // we're done
        $x = $x_A;
        $y = $y_N;
      }
      else {
        $x = $x_N;
      }
    }
    $time = (microtime(true) - $time) * 1000;
    return $grid;
  }

  function locatePoint($x, $y) {
    foreach ($this->tiles as $tile) {
      if ($tile->containsPoint($x, $y)) {
        list($line, $offset) = $tile->locatePoint($x, $y);
        return array($tile, $line, $offset);
      }
    }
    return false;
  }

}

