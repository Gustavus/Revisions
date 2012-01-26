<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once '/cis/lib/test/test.class.php';
require_once 'revisions/classes/revision.class.php';
require_once 'revisions/classes/revisionDataDiff.class.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionTest extends \Gustavus\Test\Test
{
  /**
   * @var \Gustavus\Revisions\Revision
   */
  private $revision;

  /**
   * @var \Gustavus\Revisions\Revision
   */
  private $revisionData;

  /**
   * @var array to fill object with
   */
  private $revisionProperties = array(
    'revisionId' => 1,
    'currentContent' => 'some test content',
    'revisionNumber' => 1,
    'revisionDate' => '2012-01-05 23:34:15',
  );

  /**
   * @var array to fill object with
   */
  private $revisionDataProperties = array(
    'currentContent' => 'billy',
    'revisionNumber' => 1,
  );

  /**
   * @var array to fill diffInfo object with
   */
  private $diffInfoProperties = array(
    'startIndex' => 1,
    'endIndex' => null,
    'revisionInfo' => ' visto',
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $diffInfo = new Revisions\DiffInfo($this->diffInfoProperties);
    $this->revisionDataProperties['revisionInfo'] = array($diffInfo);
    $this->revisionData = new Revisions\RevisionDataDiff($this->revisionDataProperties);
    $this->revisionProperties['revisionData'] = array('name' => $this->revisionData);
    $this->revision = new Revisions\Revision($this->revisionProperties);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revision, $this->revisionProperties, $this->revisionData, $this->revisionDataProperties);
  }

  /**
   * @test
   */
  public function getRevisionNumber()
  {
    $this->assertSame($this->revisionProperties['revisionNumber'], $this->revision->getRevisionNumber());
  }

  /**
   * @test
   */
  public function getRevisionDate()
  {
    $this->assertSame($this->revisionProperties['revisionDate'], $this->revision->getRevisionDate());
  }

  /**
   * @test
   */
  public function getRevisionData()
  {
    $this->assertSame($this->revisionProperties['revisionData'], $this->revision->getRevisionData());
  }

  /**
   * @test
   */
  public function getRevisionId()
  {
    $this->assertSame($this->revisionProperties['revisionId'], $this->revision->getRevisionId());
  }

  /**
   * @test
   */
  public function getError()
  {
    $this->assertFalse($this->revision->getError());
  }

  /**
   * @test
   */
  public function setAndGetError()
  {
    $this->revision->setError(true);
    $this->assertTrue($this->revision->getError());
  }

  /**
   * @test
   */
  public function revisionContainsColumnRevisionData()
  {
    $this->assertTrue($this->revision->revisionContainsColumnRevisionData('name'));
  }

  /**
   * @test
   */
  public function revisionContainsColumnRevisionDataFalse()
  {
    $this->revisionProperties['revisionData'] = array();
    $this->call($this->revision, 'populateObjectWithArray', array($this->revisionProperties));
    $this->assertFalse($this->revision->revisionContainsColumnRevisionData('name'));
  }

  /**
   * @test
   */
  public function getRevisionDataRevisionNumber()
  {
    $this->assertSame(1, $this->revision->getRevisionDataRevisionNumber('name'));
  }

  /**
   * @test
   */
  public function getRevisionDataRevisionNumberNull()
  {
    $this->revisionProperties['revisionData'] = array();
    $this->call($this->revision, 'populateObjectWithArray', array($this->revisionProperties));
    $this->assertNull($this->revision->getRevisionDataRevisionNumber('name'));
  }

  /**
   * @test
   */
  public function getRevisionDataByColumn()
  {
    $this->assertInstanceOf('\Gustavus\Revisions\RevisionData', $this->revision->getRevisionData('name'));
  }

  /**
   * @test
   */
  public function getRevisionDataByColumnNull()
  {
    $this->revisionProperties['revisionData'] = array();
    $this->call($this->revision, 'populateObjectWithArray', array($this->revisionProperties));
    $this->assertNull($this->revision->getRevisionData('name'));
  }

  /**
   * @test
   */
  public function getRevisionDataContentArray()
  {
    $expected = array('name' => 'billy visto');
    $result = $this->revision->getRevisionDataContentArray();
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function populateObjectWithArray()
  {
    $expected = $this->revision;
    $this->revisionProperties['newProp'] = 'test';
    $this->call($this->revision, 'populateObjectWithArray', array($this->revisionProperties));
    $this->assertSame($expected, $this->revision);
  }
}