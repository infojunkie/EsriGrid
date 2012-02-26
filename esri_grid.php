<?php

// Esri ARC/INFO ASCII GRID reader.
// http://en.wikipedia.org/wiki/Esri_grid

class EsriGridException extends Exception {
  const FILE_NOT_FOUND  = 'File not found';
  const POINT_NOT_FOUND = 'Point not found';

  function __construct($error) {
    parent::__construct($error);
  }
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
      throw new EsriGridException(EsriGridException::FILE_NOT_FOUND);
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

class EsriGridFiles {
  var $tiles = array();

  function __construct($filepaths) {
    foreach ($filepaths as $filepath) {
      $this->tiles[] = new EsriGridFile($filepath);
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
        throw new EsriGridException(EsriGridException::POINT_NOT_FOUND);
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

