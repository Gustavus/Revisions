<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use \Gustavus\Revisions;

require_once '/cis/lib/revisions/tests/revisionsTestsHelper.class.Test.php';
require_once '/cis/lib/revisions/classes/revisions.class.php';
require_once '/cis/lib/revisions/classes/revision.class.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsTest extends RevisionsHelper
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

    $this->revisions = $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array($this->revisionsPullerInfo), $this->dbalConnection);
  }

  /**
   * @test
   */
  public function renderDiff()
  {
    $expected = '<del>some</del><ins>new</ins> test content';
    $result = $this->revisions->renderDiff('some test content', 'new test content');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevision1()
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

    $this->revisions->makeRevision(array('name' => 'Billy Visto'));

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
  public function makeRevisionFirst()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->ymlFile = 'nameRevision.yml';
    $expected = $this->getDataSet();

    $this->revisions->makeRevision(array('name' => 'Billy Visto'), '', 'name');

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
  public function makeRevisionSecond()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->ymlFile = 'nameRevision2.yml';
    $expected = $this->getDataSet();

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->revisions->makeRevision(array('name' => 'Billy Visto'), '', 'name');

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
    $this->revisionsPullerInfo['limit'] = 1;
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
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->revisions->makeRevision(array('age' => 23));

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->call($this->revisions, 'populateObjectWithRevisions');

    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(2));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(1));

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
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeRevision(array('name' => 'Billy'));
    $this->revisions->makeRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->revisions->makeRevision(array('age' => '23'));
    $this->revisions->makeRevision(array('age' => 23));
    $this->revisions->makeRevision(array('age' => 29, 'name' => 'Billy Joel Visto'));

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertNull($this->revisions->getRevisionByNumber(2));
    $this->assertNull($this->revisions->getRevisionByNumber(1));
    $errorRevision = $this->revisions->getRevisionByNumber(4);

    $errorRevisionData = $errorRevision->getRevisionDataByColumn('name');
    $this->assertTrue($errorRevisionData->getError());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisionsNameAge()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeRevision(array('name' => 'Billy'));
    $this->revisions->makeRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeRevision(array('age' => '23'));
    $this->revisions->makeRevision(array('age' => 23));
    $this->revisions->makeRevision(array('age' => 29, 'name' => 'Billy Joel Visto'));

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(2));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(1));
    $errorRevision = $this->revisions->getRevisionByNumber(4);

    $errorRevisionData = $errorRevision->getRevisionDataByColumn('name');
    $this->assertFalse($errorRevisionData->getError());
    var_dump($this->revisions->getRevisionObjects());

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisionsColumn()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(2));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->revisions->getRevisionByNumber(1));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function populateObjectWithRevisionsColumn2()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsPullerInfo));

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
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsPullerInfo));

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(4, $this->call($this->revisions, 'findOldestRevisionNumberPulled'));
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(3, $this->call($this->revisions, 'findOldestRevisionNumberPulled'));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledFullRevisions()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(1, $this->call($this->revisions, 'findOldestRevisionNumberPulled'));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestColumnRevisionNumberPulled()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(1, $this->call($this->revisions, 'findOldestColumnRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledColumn()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 10;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
    $this->call($this->revisions, 'populateObjectWithRevisions');
    $this->assertSame(1, $this->call($this->revisions, 'findOldestRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledColumn2()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsPullerInfo));

    $this->saveRevisionToDB('', 'Billy Visto', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);

    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertSame(4, $this->call($this->revisions, 'findOldestRevisionNumberPulled', array('name')));
    $this->call($this->revisions, 'populateObjectWithRevisions', array('name'));
    $this->assertSame(3, $this->call($this->revisions, 'findOldestRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function findOldestRevisionNumberPulledColumnEmpty()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsPullerInfo));

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
    $this->revisionsPullerInfo['limit'] = 1;
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());
    //$this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsPullerInfo));

    $this->assertNull($this->call($this->revisions, 'findOldestColumnRevisionNumberPulled', array('name')));

    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  // /**
  //  * @test
  //  */
  // public function getOldestColumnRevisionDataPulledNull()
  // {
  //   $conn = $this->getConnection();
  //   $this->revisionsPullerInfo['limit'] = 10;
  //   $this->setUpMock('person-revision');
  //   $this->dbalConnection->query($this->getCreateQuery());
  //   $this->dbalConnection->query($this->getCreateDataQuery());

  //   $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
  //   $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
  //   $this->assertNull($this->call($this->revisions, 'getOldestColumnRevisionDataPulled', array('name')));

  //   $this->dropCreatedTables(array('person-revision', 'revisionData'));
  // }

  // /**
  //  * @test
  //  */
  // public function getOldestColumnRevisionDataPulled()
  // {
  //   $conn = $this->getConnection();
  //   $this->revisionsPullerInfo['limit'] = 10;
  //   $this->setUpMock('person-revision');
  //   $this->dbalConnection->query($this->getCreateQuery());
  //   $this->dbalConnection->query($this->getCreateDataQuery());

  //   $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisions);
  //   $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisions);
  //   $this->call($this->revisions, 'populateObjectWithRevisions');
  //   $result = $this->call($this->revisions, 'getOldestColumnRevisionDataPulled', array('name'));
  //   $this->assertInstanceOf('\Gustavus\Revisions\RevisionData', $result);

  //   $this->dropCreatedTables(array('person-revision', 'revisionData'));
  // }

  /**
   * @test
   */
  public function getOldestRevisionDataPulledNull()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 10;
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
    $this->revisionsPullerInfo['limit'] = 10;
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
  public function getOldestRevisionPulledNull()
  {
    $conn = $this->getConnection();
    $this->revisionsPullerInfo['limit'] = 10;
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
    $this->revisionsPullerInfo['limit'] = 10;
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

    $this->assertSame(5, count($this->revisions->getRevisionObjects()));
    $revisions = $this->revisions->getRevisionObjects();
    $this->assertSame($this->revisions->getRevisionByNumber(3), $revisions[3]);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }
}