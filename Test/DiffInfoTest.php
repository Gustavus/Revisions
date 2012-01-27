<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once '/cis/lib/Gustavus/Test/Test.php';
require_once 'Gustavus/Revisions/DiffInfo.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class DiffInfoTest extends \Gustavus\Test\Test
{
  /**
   * @var \Gustavus\Revisions\Revision
   */
  private $diffInfo;

  /**
   * @var array to fill DiffInfo objectWith
   */
  private $diffInfoProperties = array(
    'startIndex' => 1,
    'endIndex'  => null,
    'revisionInfo' =>' testing',
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->diffInfo = new Revisions\DiffInfo($this->diffInfoProperties);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->diffInfo, $this->diffInfoProperties);
  }

  /**
   * @test
   */
  public function getStartIndex()
  {
    $this->assertSame($this->diffInfoProperties['startIndex'], $this->diffInfo->getStartIndex());
  }

  /**
   * @test
   */
  public function getEndIndex()
  {
    $this->assertSame($this->diffInfoProperties['endIndex'], $this->diffInfo->getEndIndex());
  }

  /**
   * @test
   */
  public function getRevisionInfo()
  {
    $this->assertSame($this->diffInfoProperties['revisionInfo'], $this->diffInfo->getRevisionInfo());
  }
}