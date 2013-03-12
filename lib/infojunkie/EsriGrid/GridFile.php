<?php
namespace infojunkie\EsriGrid;

class GridFile {
  var $file;
  var $ncols;
  var $nrows;
  var $xllcorner;
  var $yllcorner;
  var $cellsize;
  var $nodata_value = -9999;
  var $lines_seek = array();

  function __construct($filepath) {
    $this->file = @fopen($filepath, 'r');
    if (!$this->file) {
      throw new Exception(Exception::FILE_NOT_FOUND);
    }
    $this->parseHeader();
  }

  function __destruct() {
    @fclose($this->file);
  }

  function parseHeader() {
    $fpos = 0;
    $time = microtime(true);
    while (($line = fgets($this->file)) !== false) {
      $matches = array();
      if (!preg_match('/(ncols|nrows|xllcorner|yllcorner|cellsize|nodata_value)\s+(\S+)/i', $line, $matches)) {
        $this->lines_seek[ $this->nrows ] = $fpos; // that's the offset of the *last* line because lines are arranged last-to-first
        break;
      }
      $fpos = ftell($this->file);
      $this->{strtolower($matches[1])} = (float) $matches[2];
    }
    $time = (microtime(true) - $time) * 1000;
  }

  function getGridFromPoints($x_A, $y_A, $x_B, $y_B, $callback = null) {
    list($line_A, $offset_A) = $this->locatePoint($x_A, $y_A);
    list($line_B, $offset_B) = $this->locatePoint($x_B, $y_B);
    return $this->getGrid($line_A, $offset_A, $line_B, $offset_B, $callback);
  }

  function locatePoint($x, $y) {
    $line = floor(($y - $this->yllcorner) / $this->cellsize);
    $offset = floor(($x - $this->xllcorner) / $this->cellsize);
    return array($line, $offset);
  }

  function containsPoint($x, $y) {
    return $x >= $this->xllcorner &&
           $y >= $this->yllcorner &&
           $x  < $this->xllcorner + $this->cellsize * $this->ncols &&
           $y  < $this->yllcorner + $this->cellsize * $this->nrows;
  }

  function getBoundingBox() {
    return array(
      array($this->xllcorner, $this->yllcorner),
      array($this->xllcorner + $this->cellsize * $this->ncols, $this->yllcorner + $this->cellsize * $this->nrows),
    );
  }

  function getGrid($line_A, $offset_A, $line_B, $offset_B, $callback = null) {
    $time = microtime(true);
    $grid = array();
    for ($l = $line_A; $l <= $line_B; $l++) {
      $grid_line = array();
      $line = $this->getLine($l);
      $line = explode(' ', $line);
      for ($o = $offset_A; $o <= $offset_B; $o++) {
        $elevation = $line[$o] == $this->nodata_value ? null : (float) $line[$o];
        if (!is_null($callback)) {
          call_user_func($callback, $this->xllcorner + $this->cellsize * $o, $this->yllcorner + $this->cellsize * $l, $elevation);
        }
        $grid_line[] = $elevation;
      }
      $grid[] = $grid_line;
    }
    $time = (microtime(true) - $time) * 1000;
    return $grid;
  }

  function getLine($line) {
    $line++; // we start at 1 instead of 0
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

