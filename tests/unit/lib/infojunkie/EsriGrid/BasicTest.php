<?php
namespace infojunkie\EsriGrid;

class BasicTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
  }

  public function testFileNotFound() {
    $this->setExpectedException('infojunkie\EsriGrid\Exception');
    $file = new GridFile('notfound');
  }

  public function testSingleTile() {
    $this->max = -99999;
    $this->min = 100000;
    $file = new GridFile(__DIR__ . '/../../../../data/test_dem1.txt');
    $grid = $file->getGridFromPoints(0, 0, 140, 230, array($this, 'calcStats'));
    $this->assertEquals(array(
      array(  13,    5,    1),
      array(  88,   75,   27),
      array(  32,   42,   50),
      array(   3,    8,   35),
      array(null,   20,  100),
    ), $grid);
    $this->assertEquals(1, $this->min);
    $this->assertEquals(100, $this->max);
  }

  public function testMultipleTiles() {
    $this->max = -99999;
    $this->min = 100000;
    $files = new GridFiles(array(
      __DIR__ . '/../../../../data/test_dem1.txt', 
      __DIR__ . '/../../../../data/test_dem2.txt', 
      __DIR__ . '/../../../../data/test_dem3.txt', 
      __DIR__ . '/../../../../data/test_dem4.txt', 
      __DIR__ . '/../../../../data/test_dem5.txt', 
      __DIR__ . '/../../../../data/test_dem6.txt',
    ));
    $grid = $files->getGridFromPoints(175, 225, 325, 675, array($this, 'calcStats'));
    $this->assertEquals(array(
      array(  36, null,   20, 100),
      array(   2, null, null,   5),
      array(null,   13,    5,   1),
      array(   9,   88,   75,  27),
      array(   6,   32,   42,  50),
      array(  10,    3,    8,  35),
      array(  36, null,   20, 100),
      array(   2, null, null,   5),
      array(null,   13,    5,   1),
      array(   9,   88,   75,  27),
    ), $grid);
    $this->assertEquals(1, $this->min);
    $this->assertEquals(100, $this->max);
  }

  function calcStats($x, $y, $elevation) {
    if (!is_null($elevation)) {
      $this->max = max($this->max, $elevation);
      $this->min = min($this->min, $elevation);
    }
  }
}

