<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once 'revisions/classes/revisionsPuller.class.php';
require_once 'revisions/classes/revision.class.php';
require_once 'revisions/classes/revisionData.class.php';
require_once 'revisions/tests/revisionsTestsHelper.class.Test.php';
require_once '/cis/lib/db/DBAL.class.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsPullerTest extends RevisionsHelper
{
  /**
   * @var \Gustavus\Revisions\RevisionsPuller
   */
  private $revisionsPuller;

  /**
   * @var \Gustavus\Revisions\RevisionsPuller Mock
   */
  private $revisionsPullerMock;

  /**
   * @var Doctrine\DBAL connection
   */
  private $dbalConnection;

  /**
   * @var yml file for expected results
   */
  private $ymlFile = 'person.yml';

  /**
   * @var array of person table data for testing
   */
  private $personData = array(
    'name' => 'Billy Visto',
    'age' => 23,
    'city' => 'North Mankato',
    'aboutYou' => 'I like food',
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revisionsPuller = new Revisions\RevisionsPuller($this->revisionsPullerInfo);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisionsPuller, $this->revisionsPullerMock);
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

    $this->revisionsPullerMock = $this->getMockWithDB('\Gustavus\Revisions\RevisionsPuller', 'getDB', array($this->revisionsPullerInfo), $this->dbalConnection);
  }

  /**
   * function to set up db used for current versions
   *
   * @return void
   */
  public function insertToDB()
  {
    $name  = $this->personData['name'];
    $age   = $this->personData['age'];
    $city  = $this->personData['city'];
    $about = $this->personData['aboutYou'];
    $sql   = "
    INSERT INTO `person` (name, age, city, aboutYou)
    VALUES (?, ?, ?, ?)
    ";
    $this->dbalConnection->executeQuery($sql, array($name, $age, $city, $about));
  }

  /**
   * @test
   */
  public function getDBMock()
  {
    $conn = $this->getConnection();
    $this->setUpMock('revisions');
    $expected = $this->dbalConnection;
    $actual = $this->call($this->revisionsPullerMock, 'getDB');
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   * @expectedException RuntimeException
   */
  public function getDB()
  {
    $this->assertInstanceOf('RuntimeException', $this->call($this->revisionsPuller, 'getDB'));
  }

  /**
   * @test
   */
  public function getDBAlreadySet()
  {
    $conn = $this->getConnection();
    $this->setUpMock('revisions');
    $expected = $this->dbalConnection;
    $this->revisionsPuller = $this->set($this->revisionsPuller, 'dbal', $expected);
    $actual = $this->call($this->revisionsPuller, 'getDB');
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function getPerson()
  {
    $conn = $this->getConnection();
    $this->setUpMock('revisions');

    $this->ymlFile = 'person.yml';
    $expected = $this->getDataSet();
    //set up table
    $this->setUpDBFromDataset($expected);
    //modify
    $this->insertToDB();

    $actual = $conn->createDataSet(array('person'));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person'), $actual->getTable('person'));
    $this->dropCreatedTables();
  }

  /**
   * @test
   */
  public function getRevisions()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy Joel Visto';

    $this->dbalConnection->query($this->getCreateQuery());

    // modify
    $this->call($this->revisionsPullerMock, 'saveRevisionContent', array());
    $actual = $this->call($this->revisionsPullerMock, 'getRevisions');
    $expected = array(
      array(
        'id' => '1',
        'table' => 'person',
        'rowId' => '1',
        'revisionNumber' => '1',
        'message' => '',
        'createdBy' => '',
        'createdOn' => $actual[0]['createdOn']
      ),
    );
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionsStartingId()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy Joel Visto';

    $this->dbalConnection->query($this->getCreateQuery());

    // modify
    $this->call($this->revisionsPullerMock, 'saveRevisionContent', array());
    $actual = $this->call($this->revisionsPullerMock, 'getRevisions', array(2));
    $expected = array(
      array(
        'id' => '1',
        'table' => 'person',
        'rowId' => '1',
        'revisionNumber' => '1',
        'message' => '',
        'createdBy' => '',
        'createdOn' => $actual[0]['createdOn']
      ),
    );
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionsById()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy Joel Visto';

    $this->dbalConnection->query($this->getCreateQuery());

    // modify
    $this->call($this->revisionsPullerMock, 'saveRevisionContent', array());
    $actual = $this->call($this->revisionsPullerMock, 'getRevisions', array(null, null, 1));
    $expected = array(
      array(
        'id' => '1',
        'table' => 'person',
        'rowId' => '1',
        'revisionNumber' => '1',
        'message' => '',
        'createdBy' => '',
        'createdOn' => $actual[0]['createdOn']
      ),
    );
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionsAlreadyPulled()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy Joel Visto';

    $this->dbalConnection->query($this->getCreateQuery());

    // modify
    $this->call($this->revisionsPullerMock, 'saveRevisionContent', array());
    $actual = $this->call($this->revisionsPullerMock, 'getRevisions', array(null));
    $expected = array(
      array(
        'id' => '1',
        'table' => 'person',
        'rowId' => '1',
        'revisionNumber' => '1',
        'message' => '',
        'createdBy' => '',
        'createdOn' => $actual[0]['createdOn']
      ),
    );
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionData()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy Joel Visto';

    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    $this->call($this->revisionsPullerMock, 'saveRevisionData', array($newContent, 1, 'name'));
    $actual = $this->call($this->revisionsPullerMock, 'getRevisionData', array(1));
    $expected = array('name' => array(
        'id' => '1',
        'revisionNumber' => '1',
        'value' => 'Billy Joel Visto',
      ),
    );
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('revisionData', 'person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionData2()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy Joel Visto';

    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    $this->call($this->revisionsPullerMock, 'saveRevisionData', array($newContent, 1, 'name'));
    $actual = $this->call($this->revisionsPullerMock, 'getRevisionData', array(null, 'name'));
    $expected = array('name' => array(
      '1' => array(
        'id' => '1',
        'value' => 'Billy Joel Visto',
      ),
    ));
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('revisionData', 'person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionDataJoin()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');

    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    $insertId = $this->call($this->revisionsPullerMock, 'saveRevisionContent', array("", 'name'));
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('Billy Joel Visto', $insertId, 'name'));

    $insertId = $this->call($this->revisionsPullerMock, 'saveRevisionContent', array("", 'name'));
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('Billy Visto', $insertId, 'name'));

    $insertId = $this->call($this->revisionsPullerMock, 'saveRevisionContent', array("", 'age'));
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('23', $insertId, 'age'));

    $actual = $this->call($this->revisionsPullerMock, 'getRevisionData', array(null, 'name'));

    $expected = array('name' => array(
      '2' => array(
        "id" => "2",
        "value" =>  "Billy Visto",
      ),
      '1' => array(
        'id' => '1',
        'value' => 'Billy Joel Visto',
        ),
      ),
    );

    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('revisionData', 'person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionDataStartingRevNum()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');

    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    $insertId = $this->call($this->revisionsPullerMock, 'saveRevisionContent', array("", 'name'));
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('Billy Joel Visto', $insertId, 'name'));

    $insertId = $this->call($this->revisionsPullerMock, 'saveRevisionContent', array("", 'name'));
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('Billy Visto', $insertId, 'name'));

    $insertId = $this->call($this->revisionsPullerMock, 'saveRevisionContent', array("", 'age'));
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('23', $insertId, 'age'));

    $actual = $this->call($this->revisionsPullerMock, 'getRevisionData', array(null, 'name', true, null, 2));

    $expected = array('name' => array(
      '1' => array(
        'id' => '1',
        'value' => 'Billy Joel Visto',
        ),
      ),
    );

    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('revisionData', 'person-revision'));
  }


  /**
   * @test
   */
  public function getRevisionDataAlreadyPulled()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy Joel Visto';

    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    $this->call($this->revisionsPullerMock, 'saveRevisionData', array($newContent, 1, 'name'));
    $actual = $this->call($this->revisionsPullerMock, 'getRevisionData', array(null, 'name', true));
    $expected = array('name' => array(
      '1' => array(
        'id' => '1',
        'value' => 'Billy Joel Visto',
      ),
    ));
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('revisionData', 'person-revision'));
  }

  /**
   * @test
   */
  public function parseDataResult()
  {
    $fetchAllResult =  array(array(
      "id" => "1",
      "revisionNumber" => "1",
      "key" => "name",
      "value" => "Billy Joel Visto",
    ));
    $expected = array('name' => array(
        'id' => '1',
        'revisionNumber' => '1',
        'value' => 'Billy Joel Visto',
      ),
    );
    $actual = $this->call($this->revisionsPuller, 'parseDataResult', array($fetchAllResult));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function parseDataResult2()
  {
    $fetchAllResult =  array(array(
      "id" => "2",
      "revisionNumber" => "2",
      "key" => "name",
      "value" =>  "Billy Visto",
    ),
    array(
      "id" => "1",
      "revisionNumber" => "1",
      "key" => "name",
      "value" => "Billy Joel Visto",
    ));
    $expected = array('name' => array(
      '2' => array(
        "id" => "2",
        "value" =>  "Billy Visto",
      ),
      '1' => array(
        'id' => '1',
        'value' => 'Billy Joel Visto',
        ),
      ),
    );
    $actual = $this->call($this->revisionsPuller, 'parseDataResult', array($fetchAllResult, 'name'));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function saveRevisionData()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');

    $this->ymlFile = 'nameRevisionRevision.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    //$this->saveRevisionToDB($currContent, $newContent, $this->revisionsPullerMock);
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('[[1,null," Visto"]]', 1, 'name'));

    $actualDataSet = $conn->createDataSet(array('revisionData'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('revisionData' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('revisionData' => array('createdOn')));

    $this->assertTablesEqual($expected->getTable('revisionData'), $actual->getTable('revisionData'));
    $this->dropCreatedTables(array('revisionData', 'person-revision'));
  }

  /**
   * @test
   */
  public function saveRevisionData2()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $newContent = 'Billy Visto';

    $this->ymlFile = 'nameRevision1.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    //$this->saveRevisionToDB($currContent, $newContent, $this->revisionsPullerMock);
    //
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array($newContent, 1, 'name'));
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('[[1,null," Visto"]]', 1, 'name', 1));
    // add the new row to mimick a soon to be tested function
    $this->call($this->revisionsPullerMock, 'saveRevisionData', array('Billy', 2, 'name'));

    $actualDataSet = $conn->createDataSet(array('revisionData'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('revisionData' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('revisionData' => array('createdOn')));

    $this->assertTablesEqual($expected->getTable('revisionData'), $actual->getTable('revisionData'));
    $this->dropCreatedTables(array('revisionData', 'person-revision'));
  }

  /**
   * @test
   */
  public function saveRevisionContent()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');

    $this->ymlFile = 'nameRevisionRevision.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateQuery());

    //$this->saveRevisionToDB($currContent, $newContent, $this->revisionsPullerMock);
    $insertId = $this->call($this->revisionsPullerMock, 'saveRevisionContent', array("", 'name'));

    $actualDataSet = $conn->createDataSet(array('person-revision'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn')));

    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->assertSame(1, $insertId);
    $this->dropCreatedTables(array('person-revision'));
  }

  /**
   * @test
   */
  public function saveRevision()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy Visto';
    $newContent = 'Billy';

    $this->ymlFile = 'nameRevision1.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $revisionData = new \Gustavus\Revisions\RevisionData(array(
      'currentContent' => $currContent,
    ));
    $revisionInfo = $revisionData->renderRevisionForDB($newContent);
    $revisionInfoArray = array('name' => $revisionInfo);

    // modify
    $this->call($this->revisionsPullerMock, 'saveRevision', array($revisionInfoArray, array('name' => $newContent), array(), '', 'name'));

    //$this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisionsPullerMock);

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
  public function saveRevisionNewRevision()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = '';
    $newContent = 'Billy Visto';

    $this->ymlFile = 'nameRevision.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $revisionData = new \Gustavus\Revisions\RevisionData(array(
      'currentContent' => $currContent,
    ));
    $revisionInfo = $revisionData->renderRevisionForDB($newContent);
    $revisionInfoArray = array('name' => $revisionInfo);

    // modify
    $this->call($this->revisionsPullerMock, 'saveRevision', array($revisionInfoArray, array('name' => $newContent), array(), '', 'name'));

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
  public function saveRevision2()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy';
    $newContent = 'Billy Visto';

    $this->ymlFile = 'nameRevision2.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisionsPullerMock);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisionsPullerMock);

    $actualDataSet = $conn->createDataSet(array('revisionData', 'person-revision'));
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
  public function saveRevisionEmptyRevisionData()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy';
    $newContent = 'Billy Visto';

    $this->ymlFile = 'nameRevision2.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateDataQuery());
    $this->dbalConnection->query($this->getCreateQuery());

    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisionsPullerMock);
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->revisionsPullerMock, array());

    $actualDataSet = $conn->createDataSet(array('revisionData', 'person-revision'));
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
  public function saveRevisionAlreadySaved()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $currContent = 'Billy';
    $newContent = 'Billy Visto';

    $this->ymlFile = 'nameRevision2.yml';
    $expected = $this->getDataSet();

    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->saveRevisionToDB('Billy Visto', 'Billy', 'name', $this->revisionsPullerMock);
    $this->saveRevisionToDB($currContent, $newContent, 'name', $this->revisionsPullerMock);
    $this->saveRevisionToDB($newContent, $newContent, 'name', $this->revisionsPullerMock);

    $actualDataSet = $conn->createDataSet(array('person-revision', 'revisionData'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn'), 'revisionData' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }
}