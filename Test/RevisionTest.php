<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

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
    'id' => 1,
    'number' => 1,
    'date' => '2012-01-05 23:34:15',
    'createdBy' => 'Billy',
    'message' => 'Message',
    'modifiedColumns' => array('name'),
  );

  /**
   * @var array to fill object with
   */
  private $revisionDataProperties = array(
    'nextContent' => 'billy',
    'number' => 1,
  );

  /**
   * @var array to fill diffInfo object with
   */
  private $diffInfoProperties = array(
    'startIndex' => 1,
    'endIndex' => null,
    'info' => ' visto',
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $diffInfo = new Revisions\DiffInfo($this->diffInfoProperties);
    $this->revisionDataProperties['diffInfo'] = array($diffInfo);
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
    $this->assertSame($this->revisionProperties['number'], $this->revision->getRevisionNumber());
  }

  /**
   * @test
   */
  public function getRevisionDate()
  {
    $this->assertSame($this->revisionProperties['date'], $this->revision->getRevisionDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDate()
  {
    $date = new \DateTime('-1 months -3 weeks');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('Last month', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateOneYearAgo()
  {
    $date = new \DateTime('-1 years');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('Last year', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateSpecific()
  {
    $date = new \DateTime('-1 months -3 weeks -2 days');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('1 month, 3 weeks, and 2 days ago', $this->revision->getRevisionRelativeDate(true));
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateAFewSecondsAgo()
  {
    $date = new \DateTime('-5 hours -3 minutes');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('5 hours ago', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateAFewSecondsAgoSpecific()
  {
    $date = new \DateTime('-5 hours -3 minutes');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('5 hours and 3 minutes ago', $this->revision->getRevisionRelativeDate(true));
  }


  /**
   * @test
   */
  public function getRevisionRelativeDateAFewSecondsInTheFuture()
  {
    $date = new \DateTime('+5 hours +3 minutes');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('5 hours from now', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateLessThanADay()
  {
    $date = new \DateTime('-5 seconds');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('Just Now', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateYears()
  {
    $date = new \DateTime('-2 years -5 days');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('Around 2 years ago', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateDays()
  {
    $date = new \DateTime('-2 days');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('2 days ago', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateDayOne()
  {
    $date = new \DateTime('-1 days');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('Yesterday', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateDayTomorrow()
  {
    $date = new \DateTime('+1 days');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('Tomorrow', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateMinutes()
  {
    $date = new \DateTime('-20 minutes');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('20 minutes ago', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateMinutesSeconds()
  {
    $date = new \DateTime('-20 minutes -30 seconds');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('20 minutes ago', $this->revision->getRevisionRelativeDate());
  }

  /**
   * @test
   */
  public function getRevisionRelativeDateWeeks()
  {
    $date = new \DateTime('-20 days');
    $this->revisionProperties['date'] = $date->format('Y-m-d H:i:s');
    $this->setUp();
    $this->assertSame('2 weeks ago', $this->revision->getRevisionRelativeDate());
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
    $this->assertSame($this->revisionProperties['id'], $this->revision->getRevisionId());
  }

  /**
   * @test
   */
  public function getCreatedBy()
  {
    $this->assertSame($this->revisionProperties['createdBy'], $this->revision->getCreatedBy());
  }

  /**
   * @test
   */
  public function getRevisionMessage()
  {
    $this->assertSame($this->revisionProperties['message'], $this->revision->getRevisionMessage());
  }

  /**
   * @test
   */
  public function getModifiedColumns()
  {
    $this->assertSame($this->revisionProperties['modifiedColumns'], $this->revision->getModifiedColumns());
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