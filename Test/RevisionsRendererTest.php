<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use \Gustavus\Revisions;

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsRendererTest extends RevisionsTestsHelper
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
      $this->dbalConnection = \Gustavus\Doctrine\DBAL::getDBAL($tableName, self::$dbh);
    }

    $this->revisions = $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array($this->revisionsManagerInfo), $this->dbalConnection);
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions);
  }

  /**
   * @test
   */
  public function removeParams()
  {
    $expected = array('revisionsAction' => 'revision');
    $actual = $this->call($this->revisionsRenderer, 'removeParams', array(array('revisionsAction' => 'revision', 'oldestRevisionNumber' => '2'), array('oldestRevisionNumber')));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function setShouldRenderTimeline()
  {
    $this->assertTrue($this->get($this->revisionsRenderer, 'shouldRenderTimeline'));
    $this->revisionsRenderer->setShouldRenderTimeline(false);
    $this->assertFalse($this->get($this->revisionsRenderer, 'shouldRenderTimeline'));
  }

  /**
   * @test
   */
  public function setShouldRenderRevisionData()
  {
    $this->assertTrue($this->get($this->revisionsRenderer, 'shouldRenderRevisionData'));
    $this->revisionsRenderer->setShouldRenderRevisionData(false);
    $this->assertFalse($this->get($this->revisionsRenderer, 'shouldRenderRevisionData'));
  }

  /**
   * @test
   */
  public function makeLabels()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto', 'aboutYou' => ""));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('age' => 123));

    $this->revisions->populateEmptyRevisions(1);

    $labels = array(
      'aboutYou' => 'About You',
      'age'     => 'Age',
    );
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions, array(), array(), $labels);

    $expected = array(
      'age'     => 'Age',
      'name'    => 'name',
    );
    $result = $this->call($this->revisionsRenderer, 'makeLabels');
    $this->assertSame($expected, $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeLabelsDifferentOrder()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto', 'aboutYou' => ""));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('age' => 123));

    $this->revisions->populateEmptyRevisions(1);

    $labels = array(
      'name'    => 'Name',
      'age'     => 'Age',
    );
    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions, array(), array(), $labels);

    $expected = array(
      'name'    => 'Name',
      'age'     => 'Age',
    );
    $result = $this->call($this->revisionsRenderer, 'makeLabels');
    $this->assertSame($expected, $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeLabelsEmpty()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto', 'aboutYou' => ""));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('age' => 123));

    $this->revisions->populateEmptyRevisions(1);

    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions);

    $expected = array(
      'age' => 'age',
      'name' => 'name',
    );
    $result = $this->call($this->revisionsRenderer, 'makeLabels');
    $this->assertSame($expected, $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function makeLabelsEmptyObject()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisions->makeAndSaveRevision(array('age' => 23, 'name' => 'Billy Visto', 'aboutYou' => ""));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy'));
    $this->revisions->makeAndSaveRevision(array('name' => 'Billy Visto'));
    $this->revisions->makeAndSaveRevision(array('age' => 123));

    //$this->revisions->populateEmptyRevisions(1);

    $this->revisionsRenderer = new Revisions\RevisionsRenderer($this->revisions);

    $expected = array();
    $result = $this->call($this->revisionsRenderer, 'makeLabels');
    $this->assertSame($expected, $result);
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }
}