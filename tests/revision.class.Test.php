<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once '/cis/lib/test/test.class.php';
require_once 'revisions/classes/revision.class.php';
require_once 'revisions/classes/revisionData.class.php';

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
    'currentContent' => 'some test content',
    'revisionNumber' => 1,
    'revisionDate' => '2012-01-05 23:34:15',
  );

  /**
   * @var array to fill object with
   */
  private $revisionDataProperties = array(
    'currentContent' => 'some test content',
    'revisionNumber' => 1,
    'revisionInfo' => array(array(
      2,
      null,
      'testing',
    )),
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revisionData = new Revisions\RevisionData($this->revisionDataProperties);
    $this->revisionProperties['revisionData'] = $this->revisionData;
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
  public function populateObjectWithArray()
  {
    $expected = $this->revision;
    $this->revisionProperties['newProp'] = 'test';
    $this->call($this->revision, 'populateObjectWithArray', array($this->revisionProperties));
    $this->assertSame($expected, $this->revision);
  }
}