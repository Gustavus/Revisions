<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

use Gustavus\Revisions;

require_once 'revisions/classes/revisionsPuller.class.php';
require_once 'revisions/classes/revision.class.php';
require_once 'revisions/tests/revisionsTestsHelper.class.Test.php';
require_once 'db/DBAL.class.php';

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
  public function getDB()
  {
    $conn = $this->getConnection();
    $this->setUpMock('revisions');
    $expected = $this->dbalConnection;
    $actual = $this->call($this->revisionsPullerMock, 'getDB');
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

    $revision = new \Gustavus\Revisions\Revision(array(
      'currentContent' => $currContent,
    ));
    $revisionInfo = $this->call($revision, 'renderRevisionForDB', array($newContent));
    // modify
    $this->call($this->revisionsPullerMock, 'saveRevision', array($revisionInfo, $newContent));
    $actual = $this->call($this->revisionsPullerMock, 'getRevisions');
    $expected = array(
      array(
        'id' => '2',
        'table' => 'person',
        'rowId' => '1',
        'key' => 'name',
        'value' => 'Billy Joel Visto',
        'createdOn' => $actual[1]['createdOn']
      ),
      array(
        'id' => '1',
        'table' => 'person',
        'rowId' => '1',
        'key' => 'name',
        'value' => $revisionInfo,
        'createdOn' => $actual[0]['createdOn'],
      ),
    );
    $this->assertSame($expected, $actual);
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

    $this->ymlFile = 'nameRevision.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->dbalConnection->query($this->getCreateQuery());

    $this->saveRevisionToDB($currContent, $newContent, $this->revisionsPullerMock);

    $actualDataSet = $conn->createDataSet(array('person-revision'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision'));
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

    $this->dbalConnection->query($this->getCreateQuery());
    $this->saveRevisionToDB('Billy Visto', 'Billy', $this->revisionsPullerMock);
    $this->saveRevisionToDB($currContent, $newContent, $this->revisionsPullerMock);

    $actualDataSet = $conn->createDataSet(array('person-revision'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision'));
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
    $this->saveRevisionToDB('Billy Visto', 'Billy', $this->revisionsPullerMock);
    $this->saveRevisionToDB($currContent, $newContent, $this->revisionsPullerMock);
    $this->saveRevisionToDB($newContent, $newContent, $this->revisionsPullerMock);

    $actualDataSet = $conn->createDataSet(array('person-revision'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision'));
  }

  /**
   * @test
   */
  public function getRevisionsStartingId()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->saveRevisionToDB('Billy Visto', 'Billy', $this->revisionsPullerMock);
    $this->saveRevisionToDB('Billy', 'Billy Visto', $this->revisionsPullerMock);

    $actual = $this->call($this->revisionsPullerMock, 'getRevisions', array(2));

    $revision = new \Gustavus\Revisions\Revision(array(
      'currentContent' => 'Billy Visto',
    ));
    $revisionInfo = $this->call($revision, 'renderRevisionForDB', array('Billy'));
    $expected = array(
      array(
        'id' => '1',
        'table' => 'person',
        'rowId' => '1',
        'key' => 'name',
        'value' => $revisionInfo,
        'createdOn' => $actual[0]['createdOn'],
      ),
    );
    $this->assertSame($expected, $actual);
    $this->dropCreatedTables(array('person-revision'));
  }
}