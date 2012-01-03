<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

use Gustavus\Revisions;

require_once '/cis/lib/test/testDBPDO.class.php';
require_once 'revisions/classes/revisionsPuller.class.php';
require_once 'revisions/classes/revision.class.php';
require_once 'db/DBAL.class.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsPullerTest extends \Gustavus\Test\TestDBPDO
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
    'name' => 'Billy',
    'age' => 2,
    'city' => 'Mankato',
    'aboutYou' => 'food',
  );

  /**
   * @var array of revisionsPuller info
   */
  private $revisionsPullerInfo = array(
    'dbName' => 'person-revision',
    'column' => 'name',
    'table'  => 'person',
    'column' => 'name',
    'rowId'  => 1,
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
    unset($this->revisionsPuller);
  }

  private function setUpMock()
  {
    if (!isset($this->dbalConnection)) {
      $this->dbalConnection = \Gustavus\DB\DBAL::getDBAL('revisions', self::$dbh);
    }

    $this->revisionsPullerMock = $this->getMockWithDB('\Gustavus\Revisions\RevisionsPuller', 'getDB', array($this->revisionsPullerInfo), $this->dbalConnection);
  }

  /**
   * @return PHPUnit_Extensions_Database_DataSet_IDataSet
   */
  protected function getDataSet()
  {
    return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(dirname(__FILE__).'/db/'.$this->ymlFile);
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
    $this->setUpMock();
    $expected = $this->dbalConnection;
    $actual = $this->call($this->revisionsPullerMock, 'getDB');
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function getDBRow()
  {
    $conn = $this->getConnection();
    $this->setUpMock();

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
  public function saveRevision()
  {
    $conn = $this->getConnection();
    $this->setUpMock();

    $this->ymlFile = 'nameRevision.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->setUpDBFromDataset($expected, array('personRevision'));

    // $revision = \Gustavus\Revisions\Revision(array(
    //   'currentContent' => 'Billy',
    //   'revisionInfo' => array(array(
    //     'revisionContent' => 'Visto',
    //     'startIndex' => 1,
    //     'endIndex' => null,
    //   )),
    // ));

    // $revision = \Gustavus\Revisions\Revision(array(
    //   'currentContent' => 'Billy Visto',
    // ));

    // $revisionInfo = $this->call($revision, 'renderRevisionForDB', array('Billy'));

    // //modify
    // $this->call($this->revisionsPuller, 'saveRevision', array($revisionInfo));

    // $actual = $conn->createDataSet(array('person-revision'));

    // $this->assertDataSetsEqual($expected, $actual);
    // $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    // $this->dropCreatedTables();
  }
}