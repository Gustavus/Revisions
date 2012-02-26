<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use \Gustavus\Revisions;

require_once '/cis/lib/Gustavus/Revisions/Test/RevisionsTestsHelperTest.php';
require_once '/cis/lib/Gustavus/Revisions/Revisions.php';
require_once '/cis/lib/Gustavus/Revisions/Revision.php';
require_once '/cis/lib/Gustavus/Revisions/DiffInfo.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsTest extends RevisionsTestsHelper
{
  /**
   * @var yml file for expected results
   */
  private $ymlFile = 'person.yml';

  /**
   * @var \Gustavus\Revisions\Revisions
   */
  private $revisions;

  /**
   * @var Doctrine\DBAL connection
   */
  private $dbalConnection;

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revisions = new Revisions\Revisions();
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisions);
  }

  /**
   * @return PHPUnit_Extensions_Database_DataSet_IDataSet
   */
  protected function getDataSet()
  {
    return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(dirname(__FILE__).'/db/'.$this->ymlFile);
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
  }

  /**
   * @test
   */
  public function makeRevisionData()
  {
    $result = $this->revisions->makeRevisionData('some test content', 'new test content');
    $this->assertInstanceOf('\Gustavus\Revisions\RevisionData', $result);
  }

  /**
   * @test
   */
  public function makeRevision()
  {
    $rData = $this->revisions->makeRevisionData('some test content', 'new test content');
    $result = $this->revisions->makeRevision(array('info' => $rData));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $result);
  }

  /**
   * @test
   */
  public function compareTwoRevisions()
  {
    $this->revisionsManagerInfo['limit'] = 10;
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('age' => 22));
    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto'));

    $result = $this->revisions->compareTwoRevisions(3, 1);
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function compareTwoRevisionsColumn()
  {
    $this->revisionsManagerInfo['limit'] = 10;
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('age' => 22));
    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto'));

    $result = $this->revisions->compareTwoRevisions(3, 2, 'name');
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function compareTwoRevisionsColumnNumsDontExist()
  {
    $this->revisionsManagerInfo['limit'] = 10;
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('age' => 22));
    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto'));

    $result = $this->revisions->compareTwoRevisions(0, 20, 'name');
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function compareTwoRevisionsError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->revisions->makeAndSaveRevision(array('age' => '23'));
    $this->revisions->makeAndSaveRevision(array('age' => 23));
    $this->revisions->makeAndSaveRevision(array('age' => 29, 'name' => 'Billy Joel Visto'));

    $this->assertNull($this->revisions->getRevisionByNumber(1));
    $this->assertNull($this->revisions->getRevisionByNumber(0));
    $errorRevision = $this->revisions->getRevisionByNumber(3);

    $errorRevisionData = $errorRevision->getRevisionData('name');
    $this->assertTrue($errorRevisionData->getError());
    $this->assertNotNull($this->revisions->getRevisionByNumber(4));
    $newRevision = $this->revisions->compareTwoRevisions(3, 4);
    $this->assertTrue($newRevision->getError());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeAndSaveRevision1()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->ymlFile = 'nameRevision2.yml';
    $expected = $this->getDataSet();

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));

    $actualDataSet = $conn->createDataSet(array('person-revision', 'revisionData'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeAndSaveRevisionFirst()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->ymlFile = 'nameRevision.yml';
    $expected = $this->getDataSet();

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'), '', 'name');

    $actualDataSet = $conn->createDataSet(array('person-revision', 'revisionData'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->assertTablesEqual($expected->getTable('revisionData'), $actual->getTable('revisionData'));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeAndSaveRevisionSecond()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->ymlFile = 'nameRevision2.yml';
    $expected = $this->getDataSet();

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'), '', 'name');

    $actualDataSet = $conn->createDataSet(array('person-revision', 'revisionData'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeAndSaveRevisionNameAge()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->ymlFile = 'nameRevisionAdvanced.yml';
    $expected = $this->getDataSet();

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('age' => 22));
    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto'));

    $actualDataSet = $conn->createDataSet(array('person-revision', 'revisionData'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeDiffInfoObjects()
  {
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => 2, 'revisionInfo' => ''));
    $expected = array($diff);
    $actual = $this->call($this->revisions, 'makeDiffInfoObjects', array(array(array(1,2,""))));
    $this->assertSame($expected[0]->getStartIndex(), $actual[0]->getStartIndex());
    $this->assertSame($expected[0]->getEndIndex(), $actual[0]->getEndIndex());
    $this->assertSame($expected[0]->getRevisionInfo(), $actual[0]->getRevisionInfo());
  }

  /**
   * @test
   */
  public function getRevisionByNumber()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->assertNull($this->revisions->getRevisionByNumber(0));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getRevisionByNumberRevisionNumberDoesntExist()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->assertNull($this->revisions->getRevisionByNumber(5));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   * @expectedException PDOException
   */
  public function getRevisionByNumberWithNoTable()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->assertInstanceOf('PDOException', $this->revisions->getRevisionByNumber(1));
  }

  /**
   * @test
   */
  public function getRevisionByNumberMultiple()
  {
    $this->revisionsManagerInfo['limit'] = 1;
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(1));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisions1()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->call($this->revisions, 'populateObjectWithRevisions');

    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(2));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(0));

    // $actualDataSet = $conn->createDataSet(array('person-revision', 'revisionData'));
    // $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));
    // $this->ymlFile = 'nameRevision2.yml';
    // $expected = $this->getDataSet();
    // $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));

    // $this->assertDataSetsEqual($expected, $actual);

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisionsError()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->revisions->makeAndSaveRevision(array('age' => '23'));
    $this->revisions->makeAndSaveRevision(array('age' => 23));
    $this->revisions->makeAndSaveRevision(array('age' => 29, 'name' => 'Billy Joel Visto'));

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertNull($this->revisions->getRevisionByNumber(1));
    $this->assertNull($this->revisions->getRevisionByNumber(0));
    $errorRevision = $this->revisions->getRevisionByNumber(3);

    $errorRevisionData = $errorRevision->getRevisionData('name');
    $this->assertTrue($errorRevisionData->getError());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisionsNameAge()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('age' => '23'));
    $this->revisions->makeAndSaveRevision(array('age' => 23));
    $this->revisions->makeAndSaveRevision(array('age' => 29, 'name' => 'Billy Joel Visto'));

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(2));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(1));
    $errorRevision = $this->revisions->getRevisionByNumber(3);

    $errorRevisionData = $errorRevision->getRevisionData('name');
    $this->assertFalse($errorRevisionData->getError());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisionsColumn()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(2));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(0));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisionsColumn2()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsManagerInfo));

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(2));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(1));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulled()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsManagerInfo));

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(3, $this->call($this->revisions, 'findOldestRevisionNumberPulled'));
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(2, $this->call($this->revisions, 'findOldestRevisionNumberPulled'));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledEmpty()
  {
    $this->set($this->revisions, 'revisions', array());

    $this->assertNull($this->call($this->revisions, 'findOldestRevisionNumberPulled'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledFullRevisions()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(0, $this->call($this->revisions, 'findOldestRevisionNumberPulled'));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestColumnRevisionNumberPulled()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 2;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(2, $this->call($this->revisions, 'findOldestColumnRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestColumnRevisionNumberPulledFull()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(0, $this->call($this->revisions, 'findOldestColumnRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestColumnRevisionNumberPulledColumn()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));;
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertNull($this->call($this->revisions, 'findOldestColumnRevisionNumberPulled', array('age')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestColumnRevisionNumberPulledEmpty()
  {
    $this->set($this->revisions, 'revisions', array());

    $this->assertNull($this->call($this->revisions, 'findOldestColumnRevisionNumberPulled'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledColumn()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(0, $this->call($this->revisions, 'findOldestRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledColumn2()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsManagerInfo));

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertSame(3, $this->call($this->revisions, 'findOldestRevisionNumberPulled', array('name')));
    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertSame(2, $this->call($this->revisions, 'findOldestRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledColumnEmpty()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsManagerInfo));

    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertNull($this->call($this->revisions, 'findOldestRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledColumnNull()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsManagerInfo));

    $this->assertNull($this->call($this->revisions, 'findOldestColumnRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getOldestRevisionDataPulledNull()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->assertNull($this->call($this->revisions, 'getOldestRevisionDataPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getOldestRevisionDataPulled()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $result = $this->call($this->revisions, 'getOldestRevisionDataPulled', array('name'));
    $this->assertInstanceOf('\Gustavus\Revisions\RevisionData', $result);

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getOldestRevisionDataPulledEmpty()
  {
    $this->set($this->revisions, 'revisions', array());

    $this->assertNull($this->call($this->revisions, 'getOldestRevisionDataPulled'));
  }

  /**
   * @test
   */
  public function getOldestRevisionPulledNull()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->assertNull($this->call($this->revisions, 'getOldestRevisionPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getOldestRevisionPulled()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $result = $this->call($this->revisions, 'getOldestRevisionPulled', array('name'));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $result);

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getOldestRevisionPulledEmpty()
  {
    $this->set($this->revisions, 'revisions', array());

    $this->assertNull($this->call($this->revisions, 'getOldestRevisionPulled'));
  }

  /**
   * @test
   */
  public function getRevisionObjectsEmpty()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->assertEmpty($this->revisions->getRevisionObjects());
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getRevisionObjects()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->assertSame(4, count($this->revisions->getRevisionObjects()));
    $revisions = $this->revisions->getRevisionObjects();
    $this->assertSame($this->revisions->getRevisionByNumber(2), $revisions[2]);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getMissingRevisionDataFromObject()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $revisions = $this->revisions->getRevisionObjects();
    $missingRevisionData = $this->call($this->revisions, 'getMissingRevisionDataFromObject', array(array('name')));
    $this->assertSame($revisions[3]->getRevisionData(), $missingRevisionData);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeAndSaveRevisionDataAndPull()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->revisions->setLimit(10);
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));

    $this->assertFalse($this->revisions->getRevisionByNumber(1)->getError());
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findLatestRevisionNumberPulled()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->revisions->setLimit(10);
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 23));
    $this->revisions->getRevisionObjects();

    $result = $this->call($this->revisions, 'findLatestRevisionNumberPulled');
    $this->assertSame(3, $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findLatestRevisionNumberPulledEmpty()
  {
    $this->set($this->revisions, 'revisions', array(null, null));

    $this->assertNull($this->call($this->revisions, 'findLatestRevisionNumberPulled'));
  }

  /**
   * @test
   */
  public function findLatestRevisionNumberPulledNotSet()
  {
    $this->assertNull($this->call($this->revisions, 'findLatestRevisionNumberPulled'));
  }

  /**
   * @test
   */
  public function findLatestRevisionNumberPulledFullRevisions()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(3, $this->call($this->revisions, 'findLatestRevisionNumberPulled'));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getRevisionContentArray()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 22));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Joel Visto', 'age' => 23));
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $expected = array('age' => 22, 'name' => 'Visto');
    $actual = $this->revisions->getRevisionContentArray(3);
    $this->assertSame($expected, $actual);

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getRevisionObjectsStartingNum()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 22));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Joel Visto', 'age' => 23));
    $this->revisions->getRevisionObjects();
    $this->assertSame(4, $this->revisions->findOldestRevisionNumberPulled());
    $this->revisions->getRevisionObjects(4);
    $this->assertSame(4, $this->revisions->findOldestRevisionNumberPulled());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getRevisionObjectsStartingNumGoingPastZero()
  {
    $conn = $this->getConnection();
    $this->revisionsManagerInfo['limit'] = 3;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Visto', 'age' => 22));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Joel Visto', 'age' => 23));
    $this->revisions->getRevisionObjects();
    $this->assertSame(2, $this->revisions->findOldestRevisionNumberPulled());
    $this->revisions->getRevisionObjects(1);
    $this->assertSame(1, $this->revisions->findOldestRevisionNumberPulled());
    $this->revisions->getRevisionObjects(0);
    $this->assertSame(0, $this->revisions->findOldestRevisionNumberPulled());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function revisionsHaveErrors()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('age' => '23'));
    $this->revisions->makeAndSaveRevision(array('age' => 23));
    $this->revisions->makeAndSaveRevision(array('age' => 29, 'name' => 'Billy Joel Visto'));

    $this->assertNotNull($this->revisions->getRevisionByNumber(0));
    $this->assertFalse($this->revisions->revisionsHaveErrors());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function revisionsHaveErrorsError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->revisions->makeAndSaveRevision(array('age' => '23'));
    $this->revisions->makeAndSaveRevision(array('age' => 23));
    $this->revisions->makeAndSaveRevision(array('age' => 29, 'name' => 'Billy Joel Visto'));

    $this->assertNull($this->revisions->getRevisionByNumber(0));
    $this->assertTrue($this->revisions->revisionsHaveErrors());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }
}