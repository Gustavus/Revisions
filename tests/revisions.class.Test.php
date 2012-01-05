<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

use \Gustavus\Revisions;

require_once '/cis/lib/revisions/tests/revisionsTestsHelper.class.Test.php';
require_once '/cis/lib/revisions/classes/revisions.class.php';

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

    $this->revisions = $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array(), $this->dbalConnection);
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
  public function makeRevision()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->call($this->revisions, 'populateObjectWithArray', array($this->revisionsPullerInfo));

    $this->saveRevisionToDB('Billy Visto', 'Billy', $this->revisions);
    $this->saveRevisionToDB('Billy', 'Billy Visto', $this->revisions);

    $this->ymlFile = 'nameRevision2.yml';
    $expected = $this->getDataSet();

    var_dump($this->revisions->makeRevision('Billy Visto', 'person-revision', 'person-revision', 'person', 1, 'name'));

    $actualDataSet = $conn->createDataSet(array('person-revision'));
    $actual = $this->getFilteredDataSet($actualDataSet, array('person-revision' => array('createdOn')));
    $expected = $this->getFilteredDataSet($expected, array('person-revision' => array('createdOn')));

    $this->assertDataSetsEqual($expected, $actual);
    $this->assertTablesEqual($expected->getTable('person-revision'), $actual->getTable('person-revision'));
    $this->dropCreatedTables(array('person-revision'));
  }
}