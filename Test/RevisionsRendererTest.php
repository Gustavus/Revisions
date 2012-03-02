<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use \Gustavus\Revisions;

require_once '/cis/lib/Gustavus/Revisions/Test/RevisionsTestsHelperTest.php';
require_once '/cis/lib/Gustavus/Revisions/Revisions.php';
require_once '/cis/lib/Gustavus/Revisions/RevisionsRenderer.php';
require_once '/cis/lib/Gustavus/Revisions/Revision.php';

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
   * @var string
   */
  private $appUrl = 'https://gustavus.edu/billy';

  /**
   * @var array
   */
  private $appUrlParams = array();

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revisions = new Revisions\Revisions($this->revisionsManagerInfo);
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions, $this->appUrl, $this->appUrlParams);
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
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions, $this->appUrl, $this->appUrlParams);
  }

  /**
   * @test
   */
  public function makeUrl()
  {
    $this->assertSame("$this->appUrl?revisionsAction=revisions", $this->call($this->revisionsRenderer, 'makeUrl', array(array('revisionsAction' => 'revisions'))));
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
}