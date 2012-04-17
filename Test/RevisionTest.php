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
  public function getReturnClassName()
  {
    $revision = new \Gustavus\Test\TestObject($this->revision);

    $this->assertSame('now', $revision->getReturnClassName(array('second' => 4)));
    $this->assertSame('now', $revision->getReturnClassName(array()));
    $this->assertSame('minute', $revision->getReturnClassName(array('second' => 11)));
    $this->assertSame('minute', $revision->getReturnClassName(array('minute' => 1)));
    $this->assertSame('minutes', $revision->getReturnClassName(array('minute' => 10)));
    $this->assertSame('hour', $revision->getReturnClassName(array('hour' => 1)));
    $this->assertSame('hours', $revision->getReturnClassName(array('hour' => 2)));
    $this->assertSame('day', $revision->getReturnClassName(array('day' => 1)));
    $this->assertSame('days', $revision->getReturnClassName(array('day' => 2)));
    $this->assertSame('week', $revision->getReturnClassName(array('week' => 1)));
    $this->assertSame('weeks', $revision->getReturnClassName(array('week' => 2)));
    $this->assertSame('month', $revision->getReturnClassName(array('month' => 1)));
    $this->assertSame('months', $revision->getReturnClassName(array('month' => 2)));
    $this->assertSame('year', $revision->getReturnClassName(array('year' => 1)));
    $this->assertSame('years', $revision->getReturnClassName(array('year' => 2)));
  }

  /**
   * @test
   */
  public function makeNonSpecificRelativeDate()
  {
    $revision = new \Gustavus\Test\TestObject($this->revision);

    $this->assertSame('Just Now', $revision->makeNonSpecificRelativeDate(array('second' => 4)));
    $this->assertSame(array(), $revision->makeNonSpecificRelativeDate(array()));
    $this->assertSame('A few seconds ago', $revision->makeNonSpecificRelativeDate(array('second' => 11)));
    $this->assertSame(array('relative' => '1 minute '), $revision->makeNonSpecificRelativeDate(array('minute' => 1)));
    $this->assertSame(array('relative' => '10 minutes '), $revision->makeNonSpecificRelativeDate(array('minute' => 10)));
    $this->assertSame(array('relative' => '1 hour '), $revision->makeNonSpecificRelativeDate(array('hour' => 1)));
    $this->assertSame(array('relative' => '2 hours '), $revision->makeNonSpecificRelativeDate(array('hour' => 2)));
    $this->assertSame('Yesterday', $revision->makeNonSpecificRelativeDate(array('day' => 1), 1));
    $this->assertSame('Tomorrow', $revision->makeNonSpecificRelativeDate(array('day' => 1), -1));
    $this->assertSame(array('relative' => '2 days '), $revision->makeNonSpecificRelativeDate(array('day' => 2)));
    $this->assertSame('Last week', $revision->makeNonSpecificRelativeDate(array('week' => 1)));
    $this->assertSame(array('relative' => '2 weeks '), $revision->makeNonSpecificRelativeDate(array('week' => 2)));
    $this->assertSame('Last month', $revision->makeNonSpecificRelativeDate(array('month' => 1)));
    $this->assertSame(array('relative' => '2 months '), $revision->makeNonSpecificRelativeDate(array('month' => 2)));
    $this->assertSame('Last year', $revision->makeNonSpecificRelativeDate(array('year' => 1)));
    $this->assertSame(array('startText' => 'Around ', 'relative' => '2 years '), $revision->makeNonSpecificRelativeDate(array('year' => 2)));
  }

  /**
   * @test
   */
  public function makeRelativeDate()
  {
    $revision = new \Gustavus\Test\TestObject($this->revision);

    $this->assertSame('Last month', $revision->makeRelativeDate(new \DateTime('-1 months -3 weeks')));
    $this->assertSame('1 month, 3 weeks, and 2 days ago', $revision->makeRelativeDate(new \DateTime('-1 months -3 weeks'), false, true));
    $this->assertSame('1 year, 1 month, 3 weeks, and 2 days from now', $revision->makeRelativeDate(new \DateTime('+1 years +1 months +3 weeks +3 days'), false, true));
    $this->assertSame('1 minute ago', $revision->makeRelativeDate(new \DateTime('-1 minutes')));

    $this->assertSame('now', $revision->makeRelativeDate(new \DateTime('-2 seconds'), true, false));
    $this->assertSame('minute', $revision->makeRelativeDate(new \DateTime('-59 seconds'), true, false));
    $this->assertSame('minute', $revision->makeRelativeDate(new \DateTime('-60 seconds'), true, false));
    $this->assertSame('minutes', $revision->makeRelativeDate(new \DateTime('-2 minutes'), true, false));

    $this->assertSame('Just Now', $revision->makeRelativeDate(new \DateTime()));
    $this->assertSame('Just Now', $revision->makeRelativeDate(new \DateTime('-5 seconds')));
    $this->assertSame('A few seconds ago', $revision->makeRelativeDate(new \DateTime('-11 seconds')));
    $this->assertSame('1 minute ago', $revision->makeRelativeDate(new \DateTime('-61 seconds')));
    $this->assertSame('2 minutes ago', $revision->makeRelativeDate(new \DateTime('-120 seconds')));
    $this->assertSame('1 hour ago', $revision->makeRelativeDate(new \DateTime('-3600 seconds')));
    $this->assertSame('2 hours ago', $revision->makeRelativeDate(new \DateTime('-7200 seconds')));
    $this->assertSame('Yesterday', $revision->makeRelativeDate(new \DateTime('-86400 seconds')));
    $this->assertSame('2 days ago', $revision->makeRelativeDate(new \DateTime('-172800 seconds')));
    $this->assertSame('Last week', $revision->makeRelativeDate(new \DateTime('-604800 seconds')));
    $this->assertSame('2 weeks ago', $revision->makeRelativeDate(new \DateTime('-1209600 seconds')));
    $this->assertSame('Last month', $revision->makeRelativeDate(new \DateTime('-1 months')));
    $this->assertSame('2 months ago', $revision->makeRelativeDate(new \DateTime('-2 months')));
    $this->assertSame('Last year', $revision->makeRelativeDate(new \DateTime('-12 months')));
    $this->assertSame('Around 2 years ago', $revision->makeRelativeDate(new \DateTime('-2 years')));

    $this->assertSame('now', $revision->makeRelativeDate(new \DateTime(), true, false));
    $this->assertSame('now', $revision->makeRelativeDate(new \DateTime('-5 seconds'), true, false));
    $this->assertSame('minute', $revision->makeRelativeDate(new \DateTime('-11 seconds'), true, false));
    $this->assertSame('minute', $revision->makeRelativeDate(new \DateTime('-61 seconds'), true, false));
    $this->assertSame('minutes', $revision->makeRelativeDate(new \DateTime('-120 seconds'), true, false));
    $this->assertSame('hour', $revision->makeRelativeDate(new \DateTime('-3600 seconds'), true, false));
    $this->assertSame('hours', $revision->makeRelativeDate(new \DateTime('-7200 seconds'), true, false));
    $this->assertSame('day', $revision->makeRelativeDate(new \DateTime('-86400 seconds'), true, false));
    $this->assertSame('days', $revision->makeRelativeDate(new \DateTime('-172800 seconds'), true, false));
    $this->assertSame('week', $revision->makeRelativeDate(new \DateTime('-604800 seconds'), true, false));
    $this->assertSame('weeks', $revision->makeRelativeDate(new \DateTime('-1209600 seconds'), true, false));
    $this->assertSame('month', $revision->makeRelativeDate(new \DateTime('-1 month'), true, false));
    $this->assertSame('months', $revision->makeRelativeDate(new \DateTime('-61 days'), true, false));
    $this->assertSame('year', $revision->makeRelativeDate(new \DateTime('-366 days'), true, false));
    $this->assertSame('years', $revision->makeRelativeDate(new \DateTime('-2 years'), true, false));

    $this->assertSame('1 minute ago', $revision->makeRelativeDate(time()-60));
    $this->assertSame('Around 2 years ago', $revision->makeRelativeDate(time()-(62899200 + 86400 * 3)));
    $this->assertSame('Next year', $revision->makeRelativeDate(time()+62899200));
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
    $date = new \DateTime('-1 months -3 weeks');
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