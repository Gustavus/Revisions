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

    $now = date("F jS \\a\\t g:ia");
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
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionsError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $now = date("F jS \\a\\t g:ia");
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
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
    <td>5</td>
    <td>$now</td>
    <td></td>
    <td></td>
    <td>age, name</td>
  </tr>
  <tr>
    <td>4</td>
    <td>$now</td>
    <td></td>
    <td></td>
    <td>age</td>
  </tr>
  <tr>
    <td>3</td>
    <td>$now</td>
    <td>name</td>
    <td></td>
    <td>name</td>
  </tr>
  <tr class=\"error\">
    <td>2</td>
    <td>{$this->error}</td>
    <td>{$this->error}</td>
    <td>{$this->error}</td>
    <td>{$this->error}</td>
  </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisions(10);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
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
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonTextError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
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
    <td></td>
  </tr>
     <tr class=\"error\">
    <td>name</td>
    <td>{$this->error}</td>
    <td>{$this->error}</td>
  </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionComparisonText(2, 4);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
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
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonDiffError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
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
     <tr class=\"error\">
    <td>name</td>
    <td>{$this->error}</td>
  </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionComparisonDiff(2, 5);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonTextDiff()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "<table class=\"fancy\">
  <thead>
    <tr>
      <th>Field</th>
      <th>Old Text</th>
      <th>Diff</th>
      <th>New Text</th>
    </tr>
  </thead>
  <tbody>
   <tr>
    <td>age</td>
    <td></td>
    <td><ins>23</ins></td>
    <td>23</td>
  </tr>
     <tr>
    <td>name</td>
    <td></td>
    <td><ins>Visto</ins></td>
    <td>Visto</td>
  </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionComparisonTextDiff(2, 5);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonTextDiffError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "<table class=\"fancy\">
  <thead>
    <tr>
      <th>Field</th>
      <th>Old Text</th>
      <th>Diff</th>
      <th>New Text</th>
    </tr>
  </thead>
  <tbody>
   <tr>
    <td>age</td>
    <td></td>
    <td><ins>23</ins></td>
    <td>23</td>
  </tr>
     <tr class=\"error\">
    <td>name</td>
    <td>{$this->error}</td>
    <td>{$this->error}</td>
    <td>{$this->error}</td>
  </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionComparisonTextDiff(2, 5);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionDataDiff()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "<table class=\"fancy\">
  <thead>
    <tr>
      <th>Field</th>
      <th>Revision Content</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>name</td>
      <td>Billy Visto</td>
    </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionData(2);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionDataError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "<table class=\"fancy\">
  <thead>
    <tr>
      <th>Field</th>
      <th>Revision Content</th>
    </tr>
  </thead>
  <tbody>
    <tr class=\"error\">
      <td>name</td>
      <td>{$this->error}</td>
    </tr>
  </tbody>
</table>";
    $actual = $this->revisionsRenderer->renderRevisionData(2);
    $this->assertXmlStringEqualsXmlString($expected, $actual);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }
}