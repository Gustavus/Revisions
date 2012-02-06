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

    $now = Date("F jS \\a\\t g:ia");
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));
    $expected = "<table class=\"fancy\">
  <thead>
    <tr>
      <th>Revision Number</th>
      <th>Created On</th>
      <th>Created By</th>
      <th>Message</th>
      <th>Modified Columns</th>
    </tr>
  </thead>
  <tbody>
  <tr>
    <td>4</td>
    <td>$now</td>
    <td></td>
    <td></td>
    <td>age, name</td>
  </tr>
    <tr>
    <td>3</td>
    <td>$now</td>
    <td></td>
    <td></td>
    <td>age</td>
  </tr>
    <tr>
    <td>2</td>
    <td>$now</td>
    <td></td>
    <td></td>
    <td>name</td>
  </tr>
    <tr>
    <td>1</td>
    <td>$now</td>
    <td></td>
    <td></td>
    <td>name</td>
  </tr>
  </tbody>
</table>";


    $actual = $this->revisionsRenderer->renderRevisions(10);
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonText()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $now = Date("F jS \\a\\t g:ia");
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "<table class=\"fancy\">
  <thead>
    <tr>
      <th>Field</th>
      <th>Old Text</th>
      <th>New Text</th>
    </tr>
  </thead>
  <tbody>
   <tr>
    <td>age</td>
    <td></td>
    <td>23</td>
  </tr>
     <tr>
    <td>name</td>
    <td></td>
    <td>Visto</td>
  </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionComparisonText(2, 5);
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonDiff()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $now = Date("F jS \\a\\t g:ia");
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "<table class=\"fancy\">
  <thead>
    <tr>
      <th>Field</th>
      <th>Diff</th>
    </tr>
  </thead>
  <tbody>
   <tr>
    <td>age</td>
    <td><ins>23</ins></td>
  </tr>
     <tr>
    <td>name</td>
    <td><ins>Visto</ins></td>
  </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionComparisonDiff(2, 5);
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }
}