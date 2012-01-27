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
class RevisionsRendererTest extends RevisionsHelper
{
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
      $this->dbalConnection = \Gustavus\DB\DBAL::getDBAL($tableName, self::$dbh);
    }

    $this->revisions = $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array($this->revisionsManagerInfo), $this->dbalConnection);
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions);
  }

  /**
   * @test
   */
  public function renderRevisions()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    var_dump($this->revisionsRenderer->renderRevisions(10));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  // /**
  //  * @test
  //  */
  // public function buildRevisionViewParams()
  // {
  //   $conn = $this->getConnection();
  //   $this->setUpMock('person-revision');
  //   $this->dbalConnection->query($this->getCreateQuery());
  //   $this->dbalConnection->query($this->getCreateDataQuery());

  //   $this->revisionsRenderer->makeAndSaveRevision(array('name' => 'Billy Visto'), 'Message', 'Billy Visto');
  //   $this->revisionsRenderer->makeAndSaveRevision(array('name' => 'Visto'), 'Message2', 'Billy Visto');

  //   $expected = array(
  //     'revisionNumber' => 3,
  //     'createdBy' => 'Billy Visto',
  //     'message' => 'Message2',
  //     'error' => false,
  //     'columns' => 'name',
  //   );

  //   $revision = $this->revisionsRenderer->getRevisionByNumber(3);

  //   $result = $this->call($this->revisionsRenderer, 'buildRevisionViewParams', array($revision));
  //   $this->assertSame($expected, $result);

  //   $this->dropCreatedTables(array('person-revision', 'revisionData'));
  // }

  // /**
  //  * @test
  //  */
  // public function parseRevisionColumnsModified()
  // {
  //   $conn = $this->getConnection();
  //   $this->setUpMock('person-revision');
  //   $this->dbalConnection->query($this->getCreateQuery());
  //   $this->dbalConnection->query($this->getCreateDataQuery());

  //   $this->revisionsRenderer->makeAndSaveRevision(array('name' => 'Billy Visto', 'age' => 23), 'Message', 'Billy Visto');

  //   $expected = 'age, name';

  //   $result = $this->call($this->revisionsRenderer, 'parseRevisionColumnsModified', array(2));
  //   $this->assertSame($expected, $result);

  //   $this->dropCreatedTables(array('person-revision', 'revisionData'));
  // }
}