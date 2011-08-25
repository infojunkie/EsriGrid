<?php

// Esri ARC/INFO ASCII GRID reader.
// http://en.wikipedia.org/wiki/Esri_grid

class EsriGridException extends Exception {
}

class EsriGridFile {
  var $file;
  var $ncols;
  var $nrows;
  var $xllcorner;
  var $yllcorner;
  var $cellsize;
  var $nodata_value = -9999;
  var $lines_seek = array();

  function __construct($filepath) {
    $this->file = fopen($filepath, 'r');
    if (!$this->file) {
      throw new EsriGridException;
    }
    $this->parseHeader();
  }

  function __destruct() {
    @fclose($this->file);
  }

  function parseHeader() {
    $fpos = 0;
    $time = microtime(TRUE);
    while (($line = fgets($this->file)) !== FALSE) {
      $matches = array();
      if (!preg_match('/(ncols|nrows|xllcorner|yllcorner|cellsize|nodata_value)\s+(\S+)/i', $line, $matches)) {
        $this->lines_seek[ $this->nrows ] = $fpos; // that's the offset of the *last* line because lines are arranged last-to-first
        break;
      }
      $fpos = ftell($this->file);
      $this->{strtolower($matches[1])} = (float) $matches[2];
    }
    $time = (microtime(TRUE) - $time) * 1000;
  }

  function getGridFromPoints($x_A, $y_A, $x_B, $y_B) {
    $x_0 = $this->xllcorner;
    $y_0 = $this->yllcorner;
    $dx = $dy = $this->cellsize;
    $line_A = floor(($y_A - $y_0) / $dy) + 1;
    $line_B = floor(($y_B - $y_0) / $dy) + 1;
    $offset_A = floor(($x_A - $x_0) / $dx);
    $offset_B = floor(($x_B - $x_0) / $dx);
    return $this->getGrid($line_A, $offset_A, $line_B, $offset_B);
  }

  function getGrid($line_A, $offset_A, $line_B, $offset_B) {
    $time = microtime(TRUE);
    $stats = array(
      'max' => 0,
      'min' => 100000000,
    );
    $grid = array();
    for ($l = $line_A; $l <= $line_B; $l++) {
      $grid_line = array();
      $line = $this->getLine($l);
      $line = explode(' ', $line);
      for ($o = $offset_A; $o <= $offset_B; $o++) {
        $elevation = $line[$o] == $this->nodata_value ? NULL : (float) $line[$o];
        $stats['max'] = max($stats['max'], $elevation);
        $stats['min'] = min($stats['min'], $elevation);
        $grid_line[] = $elevation;
      }
      $grid[] = $grid_line;
    }
    $time = (microtime(TRUE) - $time) * 1000;
    return array($grid, $stats);
  }

  function getLine($line) {
    if (!isset($this->lines_seek[$line])) {
      fseek($this->file, end($this->lines_seek));
      $current = key($this->lines_seek);
      while ($current != $line && fgets($this->file)) {
        $current--;
        $this->lines_seek[$current] = ftell($this->file);
      }
    }
    fseek($this->file, $this->lines_seek[$line]);
    return fgets($this->file);
  }

}

