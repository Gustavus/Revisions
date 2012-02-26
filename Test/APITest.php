<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use \Gustavus\Revisions;

require_once '/cis/lib/Gustavus/Revisions/API.php';
require_once '/cis/lib/Gustavus/Revisions/Test/RevisionsTestsHelperTest.php';
require_once '/cis/lib/Gustavus/Revisions/Revision.php';
require_once '/cis/lib/Gustavus/Revisions/DiffInfo.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class APITest extends RevisionsTestsHelper
{
  /**
   * @var string
   */
  private $error = "An unexpected error occured.";

  /**
   * @var RevisionsAPI
   */
  private $revisionsAPI;

  /**
   * @var Doctrine\DBAL connection
   */
  private $dbalConnection;

  /**
   * @var string
   */
  private $appUrl = 'https://gustavus.edu/billy';

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revisionsAPI = new Revisions\API($this->revisionsManagerInfo);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisionsAPI);
  }

  /**
   * @param string $tableName
   */
  private function setUpMock($tableName)
  {
    if (!isset($this->dbalConnection)) {
      $this->dbalConnection = \Gustavus\DB\DBAL::getDBAL($tableName, self::$dbh);
    }

    $this->set($this->revisionsAPI, 'revisions', $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array($this->revisionsManagerInfo), $this->dbalConnection));
  }

  /**
   * @test
   */
  public function testConstruction()
  {
    $this->assertInstanceOf('Gustavus\Revisions\Revisions', $this->get($this->revisionsAPI, 'revisions'));
  }

  /**
   * @test
   * @expectedException RuntimeException
   */
  public function testConstructionException()
  {
    $this->revisionsAPI = new Revisions\API();
    $this->assertInstanceOf('Gustavus\Revisions\Revisions', $this->get($this->revisionsAPI, 'revisions'));
  }

  /**
   * @test
   */
  public function saveRevision()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->assertTrue($this->revisionsAPI->saveRevision(array('name' => 'Billy Visto')));
    $this->assertTrue($this->revisionsAPI->saveRevision(array('name' => 'Billy Visto')));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

//   /**
//    * @test
//    */
//   public function renderRevisions()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $now = date("F jS \\a\\t g:ia");
//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\">
//   <button id='compareRevisions'>Compare Selected Revisions</button>
// <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Revision Number</th>
//       <th></th>
//       <th></th>
//       <th>Created On</th>
//       <th>Created By</th>
//       <th>Message</th>
//       <th>Modified Columns</th>
//       <th></th>
//     </tr>
//   </thead>
//   <tbody>
//   <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4' class='revision' data-revisionNumber='4'>4</a></td>
//     <td><input id='oldNum-4' type='radio' class='compare' value='4' /></td>
//     <td><input id='newNum-4' type='radio' class='compareAgainst' value='4' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age, name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4&amp;rollback=true' id='rollback-4' class='rollback button' data-revisionNumber='4'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3' class='revision' data-revisionNumber='3'>3</a></td>
//     <td><input id='oldNum-3' type='radio' class='compare' value='3' /></td>
//     <td><input id='newNum-3' type='radio' class='compareAgainst' value='3' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3&amp;rollback=true' id='rollback-3' class='rollback button' data-revisionNumber='3'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=2' class='revision' data-revisionNumber='2'>2</a></td>
//     <td><input id='oldNum-2' type='radio' class='compare' value='2' /></td>
//     <td><input id='newNum-2' type='radio' class='compareAgainst' value='2' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=2&amp;rollback=true' id='rollback-2' class='rollback button' data-revisionNumber='2'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=1' class='revision' data-revisionNumber='1'>1</a></td>
//     <td><input id='oldNum-1' type='radio' class='compare' value='1' /></td>
//     <td><input id='newNum-1' type='radio' class='compareAgainst' value='1' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=1&amp;rollback=true' id='rollback-1' class='rollback button' data-revisionNumber='1'>Rollback</a></td>
//   </tr>
//   </tbody>
// </table>
// </form>";

//     $urlParams = array('revisionsAction' => 'revisions', 'limit' => 10);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisions(10);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionsError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $now = date("F jS \\a\\t g:ia");
//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\">
//   <button id='compareRevisions'>Compare Selected Revisions</button>
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Revision Number</th>
//       <th></th>
//       <th></th>
//       <th>Created On</th>
//       <th>Created By</th>
//       <th>Message</th>
//       <th>Modified Columns</th>
//       <th></th>
//     </tr>
//   </thead>
//   <tbody>
//   <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=5' class='revision' data-revisionNumber='5'>5</a></td>
//     <td><input id='oldNum-5' type='radio' class='compare' value='5' /></td>
//     <td><input id='newNum-5' type='radio' class='compareAgainst' value='5' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age, name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=5&amp;rollback=true' id='rollback-5' class='rollback button' data-revisionNumber='5'>Rollback</a></td>
//   </tr>
//   <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4' class='revision' data-revisionNumber='4'>4</a></td>
//     <td><input id='oldNum-4' type='radio' class='compare' value='4' /></td>
//     <td><input id='newNum-4' type='radio' class='compareAgainst' value='4' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4&amp;rollback=true' id='rollback-4' class='rollback button' data-revisionNumber='4'>Rollback</a></td>
//   </tr>
//   <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3' class='revision' data-revisionNumber='3'>3</a></td>
//     <td><input id='oldNum-3' type='radio' class='compare' value='3' /></td>
//     <td><input id='newNum-3' type='radio' class='compareAgainst' value='3' /></td>
//     <td>$now</td>
//     <td>name</td>
//     <td></td>
//     <td>name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3&amp;rollback=true' id='rollback-3' class='rollback button' data-revisionNumber='3'>Rollback</a></td>
//   </tr>
//   <tr class=\"error\">
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=2' class='revision' data-revisionNumber='2'>2</a></td>
//     <td></td>
//     <td></td>
//     <td>{$this->error}</td>
//     <td>{$this->error}</td>
//     <td>{$this->error}</td>
//     <td>{$this->error}</td>
//     <td>{$this->error}</td>
//   </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'revisions', 'limit' => 10);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisions(10);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonText()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Old Text</th>
//       <th>New Text</th>
//     </tr>
//   </thead>
//   <tbody>
//    <tr>
//     <td>age</td>
//     <td></td>
//     <td>23</td>
//   </tr>
//      <tr>
//     <td>name</td>
//     <td></td>
//     <td>Visto</td>
//   </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'text', 'oldRevisionNumber' => 2, 'newRevisionNumber' => 5);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionComparisonText(2, 5);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonTextError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Old Text</th>
//       <th>New Text</th>
//     </tr>
//   </thead>
//   <tbody>
//   <tr>
//     <td>age</td>
//     <td></td>
//     <td></td>
//   </tr>
//      <tr class=\"error\">
//     <td>name</td>
//     <td>{$this->error}</td>
//     <td>{$this->error}</td>
//   </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'text', 'oldRevisionNumber' => 2, 'newRevisionNumber' => 4);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionComparisonText(2, 4);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonDiff()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Diff</th>
//     </tr>
//   </thead>
//   <tbody>
//    <tr>
//     <td>age</td>
//     <td><ins>23</ins></td>
//   </tr>
//      <tr>
//     <td>name</td>
//     <td><ins>Visto</ins></td>
//   </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'diff', 'oldRevisionNumber' => 2, 'newRevisionNumber' => 5);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionComparisonDiff(2, 5);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonDiffError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Diff</th>
//     </tr>
//   </thead>
//   <tbody>
//    <tr>
//     <td>age</td>
//     <td><ins>23</ins></td>
//   </tr>
//      <tr class=\"error\">
//     <td>name</td>
//     <td>{$this->error}</td>
//   </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'diff', 'oldRevisionNumber' => 2, 'newRevisionNumber' => 5);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionComparisonDiff(2, 5);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonTextDiff()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Old Text</th>
//       <th>Diff</th>
//       <th>New Text</th>
//     </tr>
//   </thead>
//   <tbody>
//    <tr>
//     <td>age</td>
//     <td></td>
//     <td><ins>23</ins></td>
//     <td>23</td>
//   </tr>
//      <tr>
//     <td>name</td>
//     <td></td>
//     <td><ins>Visto</ins></td>
//     <td>Visto</td>
//   </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'textDiff', 'oldRevisionNumber' => 2, 'newRevisionNumber' => 5);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionComparisonTextDiff(2, 5);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonTextDiffError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Old Text</th>
//       <th>Diff</th>
//       <th>New Text</th>
//     </tr>
//   </thead>
//   <tbody>
//    <tr>
//     <td>age</td>
//     <td></td>
//     <td><ins>23</ins></td>
//     <td>23</td>
//   </tr>
//      <tr class=\"error\">
//     <td>name</td>
//     <td>{$this->error}</td>
//     <td>{$this->error}</td>
//     <td>{$this->error}</td>
//   </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'textDiff', 'oldRevisionNumber' => 2, 'newRevisionNumber' => 5);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionComparisonTextDiff(2, 5);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionDataDiff()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Revision Content</th>
//     </tr>
//   </thead>
//   <tbody>
//     <tr>
//       <td>name</td>
//       <td>Billy Visto</td>
//     </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'revision', 'revisionNumber' => 2);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionData(2);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionDataError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\">
//       <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Field</th>
//       <th>Revision Content</th>
//     </tr>
//   </thead>
//   <tbody>
//     <tr class=\"error\">
//       <td>name</td>
//       <td>{$this->error}</td>
//     </tr>
//   </tbody>
// </table>
//       </form>";
//     $urlParams = array('revisionsAction' => 'revision', 'revisionNumber' => 2);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisionData(2);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonDiffRevisions()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $now = date("F jS \\a\\t g:ia");
//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\">
//   <button id='compareRevisions'>Compare Selected Revisions</button>
// <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Revision Number</th>
//       <th></th>
//       <th></th>
//       <th>Created On</th>
//       <th>Created By</th>
//       <th>Message</th>
//       <th>Modified Columns</th>
//       <th></th>
//     </tr>
//   </thead>
//   <tbody>
//   <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4' class='revision' data-revisionNumber='4'>4</a></td>
//     <td><input id='oldNum-4' type='radio' class='compare' value='4' /></td>
//     <td><input id='newNum-4' type='radio' class='compareAgainst' value='4' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age, name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4&amp;rollback=true' id='rollback-4' class='rollback button' data-revisionNumber='4'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3' class='revision' data-revisionNumber='3'>3</a></td>
//     <td><input id='oldNum-3' type='radio' class='compare' value='3' /></td>
//     <td><input id='newNum-3' type='radio' class='compareAgainst' value='3' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3&amp;rollback=true' id='rollback-3' class='rollback button' data-revisionNumber='3'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=2' class='revision' data-revisionNumber='2'>2</a></td>
//     <td><input id='oldNum-2' type='radio' class='compare' value='2' /></td>
//     <td><input id='newNum-2' type='radio' class='compareAgainst' value='2' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=2&amp;rollback=true' id='rollback-2' class='rollback button' data-revisionNumber='2'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=1' class='revision' data-revisionNumber='1'>1</a></td>
//     <td><input id='oldNum-1' type='radio' class='compare' value='1' /></td>
//     <td><input id='newNum-1' type='radio' class='compareAgainst' value='1' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=1&amp;rollback=true' id='rollback-1' class='rollback button' data-revisionNumber='1'>Rollback</a></td>
//   </tr>
//   </tbody>
// </table>
// </form>";

//     $urlParams = array('revisionsAction' => 'diff', 'limit' => 10);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisions(10);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionRevisions()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $now = date("F jS \\a\\t g:ia");
//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\">
//   <button id='compareRevisions'>Compare Selected Revisions</button>
// <table class=\"fancy\">
//   <thead>
//     <tr>
//       <th>Revision Number</th>
//       <th></th>
//       <th></th>
//       <th>Created On</th>
//       <th>Created By</th>
//       <th>Message</th>
//       <th>Modified Columns</th>
//       <th></th>
//     </tr>
//   </thead>
//   <tbody>
//   <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4' class='revision' data-revisionNumber='4'>4</a></td>
//     <td><input id='oldNum-4' type='radio' class='compare' value='4' /></td>
//     <td><input id='newNum-4' type='radio' class='compareAgainst' value='4' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age, name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=4&amp;rollback=true' id='rollback-4' class='rollback button' data-revisionNumber='4'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3' class='revision' data-revisionNumber='3'>3</a></td>
//     <td><input id='oldNum-3' type='radio' class='compare' value='3' /></td>
//     <td><input id='newNum-3' type='radio' class='compareAgainst' value='3' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>age</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=3&amp;rollback=true' id='rollback-3' class='rollback button' data-revisionNumber='3'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=2' class='revision' data-revisionNumber='2'>2</a></td>
//     <td><input id='oldNum-2' type='radio' class='compare' value='2' /></td>
//     <td><input id='newNum-2' type='radio' class='compareAgainst' value='2' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=2&amp;rollback=true' id='rollback-2' class='rollback button' data-revisionNumber='2'>Rollback</a></td>
//   </tr>
//     <tr>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=1' class='revision' data-revisionNumber='1'>1</a></td>
//     <td><input id='oldNum-1' type='radio' class='compare' value='1' /></td>
//     <td><input id='newNum-1' type='radio' class='compareAgainst' value='1' /></td>
//     <td>$now</td>
//     <td></td>
//     <td></td>
//     <td>name</td>
//     <td><a href='{$this->appUrl}?revisionsAction=revision&amp;revisionNumber=1&amp;rollback=true' id='rollback-1' class='rollback button' data-revisionNumber='1'>Rollback</a></td>
//   </tr>
//   </tbody>
// </table>
// </form>";

//     $urlParams = array('revisionsAction' => 'revision', 'limit' => 10);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     //$actual = $this->revisionsAPI->renderRevisions(10);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

  /**
   * @test
   */
  public function getApplicationUrlParams($value='')
  {
    $actual = $this->call($this->revisionsAPI, 'getApplicationUrlParams', array(array('revisionsAction' => 'revision', 'limit' => 10, 'pr' => 'manage')));
    $expected = array('pr' => 'manage');
    $this->assertSame($expected, $actual);
  }
}