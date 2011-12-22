<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

use Gustavus\Revisions;

require_once '/cis/lib/test/testDBPDO.class.php';
require_once 'revisions/classes/revisionsPuller.class.php';
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

  private $revisionsPullerMock;

  private $dbalConnection;

  private $ymlFile = 'person.yml';

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revisionsPuller = new Revisions\RevisionsPuller('person', 'person-revision', 'person', 'name');
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

    $this->revisionsPullerMock = $this->getMockWithDB('\Gustavus\Revisions\RevisionsPuller', 'getDB', array('person', 'person-revision', 'person', 'name'), $this->dbalConnection);
  }

  /**
   * @return PHPUnit_Extensions_Database_DataSet_IDataSet
   */
  protected function getDataSet()
  {
    return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(dirname(__FILE__).'/db/'.$this->ymlFile);
  }

  /**
   * @test
   */
  public function getDB()
  {
    $conn = $this->getConnection();
    $this->setUpMock();
    $expected = $this->dbalConnection;
    $actual = $this->callMethod($this->revisionsPullerMock, 'getDB');
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
    $this->revisionsPullerMock->insertToDB();

    $actual = $conn->createDataSet(array('person'));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person'), $actual->getTable('person'));
    $this->dropCreatedTables();
  }

  /**
   * @test
   */
  public function getDBRows()
  {
    $conn = $this->getConnection();
    $this->setUpMock();

    $this->xmlFile = 'person.yml';
    $expected = $this->getDataSet();

    //set up table
    $this->setUpDBFromDataset($expected, array('person'));

    //modify
    $this->revisionsPullerMock->insertToDB();

    $actual = $conn->createDataSet(array('person'));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person'), $actual->getTable('person'));
    $this->dropCreatedTables();
  }
}