<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use \Gustavus\Revisions;

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsRendererTest extends RevisionsTestsHelper
{
  /**
   * @var string
   */
  private $error = "An unexpected error occured.";

  /**
   * @var RevisionsRenderer
   */
  private $revisionsRenderer;

  /**
   * @var Revisions
   */
  private $revisions;

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revisions = new Revisions\Revisions($this->revisionsManagerInfo);
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisionsRenderer, $this->revisions);
  }

  /**
   * @param string $tableName
   */
  private function setUpMock($tableName)
  {
    if (!isset($this->dbalConnection)) {
      $this->dbalConnection = \Gustavus\Doctrine\DBAL::getDBAL($tableName, self::$dbh);
    }

    $this->revisions = $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array($this->revisionsManagerInfo), $this->dbalConnection);
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions);
  }

  /**
   * @test
   */
  public function removeParams()
  {
    $expected = array('revisionsAction' => 'revision');
    $actual = $this->call($this->revisionsRenderer, 'removeParams', array(array('revisionsAction' => 'revision', 'oldestRevisionNumber' => '2'), array('oldestRevisionNumber')));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function setShouldRenderTimeline()
  {
    $this->assertTrue($this->get($this->revisionsRenderer, 'shouldRenderTimeline'));
    $this->revisionsRenderer->setShouldRenderTimeline(false);
    $this->assertFalse($this->get($this->revisionsRenderer, 'shouldRenderTimeline'));
  }

  /**
   * @test
   */
  public function setShouldRenderRevisionData()
  {
    $this->assertTrue($this->get($this->revisionsRenderer, 'shouldRenderRevisionData'));
    $this->revisionsRenderer->setShouldRenderRevisionData(false);
    $this->assertFalse($this->get($this->revisionsRenderer, 'shouldRenderRevisionData'));
  }
}