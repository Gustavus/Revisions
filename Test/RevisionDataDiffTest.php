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
class RevisionDataDiffTest extends \Gustavus\Test\Test
{
  /**
   * @var \Gustavus\Revisions\Revision
   */
  private $revisionDataDiff;

  /**
   * @var array to fill object with
   */
  private $revisionDataDiffProperties = array(
    'nextContent' => 'some test content',
    'nextContentRevisionNumber' => null,
    'number' => 1,
    'revisionNumber' => 1,
    'id'  => 1,
  );

  /**
   * @var array to fill DiffInfo objectWith
   */
  private $diffInfoProperties = array(
    'startIndex' => 1,
    'endIndex'  => null,
    'info' =>' testing',
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $diffInfo = new Revisions\DiffInfo($this->diffInfoProperties);
    $this->revisionDataDiffProperties['diffInfo'] = array($diffInfo);
    $this->revisionDataDiff = new Revisions\RevisionDataDiff($this->revisionDataDiffProperties);
  }

  /**
   * compares revision info by looking at the objects individually since assertSame doesnt work on different instances of objects
   * @param  array $expected
   * @param  array $actual
   * @return void
   */
  private function compareRevisionInfo(array $expected, array $actual)
  {
    foreach ($expected as $key => $diffInfo) {
      $this->assertSame($diffInfo->getStartIndex(), $actual[$key]->getStartIndex());
      $this->assertSame($diffInfo->getEndIndex(), $actual[$key]->getEndIndex());
      $this->assertSame($diffInfo->getInfo(), $actual[$key]->getInfo());
    }
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisionDataDiff, $this->revisionDataDiffProperties, $this->diffInfoProperties);
  }

  /**
   * @test
   */
  public function getNextContent()
  {
    $this->assertSame($this->revisionDataDiffProperties['nextContent'], $this->revisionDataDiff->getNextContent());
  }

  /**
   * @test
   */
  public function getDiffInfo()
  {
    $this->assertSame($this->revisionDataDiffProperties['diffInfo'], $this->revisionDataDiff->getDiffInfo());
  }

  /**
   * @test
   */
  public function getRevisionNumber()
  {
    $this->assertSame($this->revisionDataDiffProperties['number'], $this->revisionDataDiff->getRevisionNumber());
  }

  /**
   * @test
   */
  public function getRevisionId()
  {
    $this->assertSame($this->revisionDataDiffProperties['id'], $this->revisionDataDiff->getRevisionId());
  }

  /**
   * @test
   */
  public function getError()
  {
    $this->assertFalse($this->revisionDataDiff->getError());
  }

  /**
   * @test
   */
  public function setAndGetError()
  {
    $this->revisionDataDiff->setError(true);
    $this->assertTrue($this->revisionDataDiff->getError());
  }

  /**
   * @test
   */
  public function setAndGetRevisionContent()
  {
    $this->revisionDataDiff->setContent('Billy');
    $this->assertSame('Billy', $this->revisionDataDiff->getContent());
  }

  /**
   * @test
   */
  public function getContentNotSet()
  {
    $this->assertSame('some testing test content', $this->revisionDataDiff->getContent());
  }

  /**
   * @test
   */
  public function populateObjectWithArray()
  {
    $expected = $this->revisionDataDiff;
    $this->revisionDataDiffProperties['newProp'] = 'test';
    $this->call($this->revisionDataDiff, 'populateObjectWithArray', array($this->revisionDataDiffProperties));
    $this->assertSame($expected, $this->revisionDataDiff);
  }

  /**
   * @test
   */
  public function renderRevision()
  {
    $expected = 'some testing test content';
    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionNewContent()
  {
    $this->revisionDataDiff->setDiffInfo(array());
    $expected = '<ins>some test content</ins>';
    $result = $this->call($this->revisionDataDiff, 'renderRevision', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function getContentSize()
  {
    $expected = strlen($this->revisionDataDiff->getContent());
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $this->revisionDataDiff->getContentSize());
  }

  /**
   * @test
   */
  public function getContentNumericSize()
  {
    $currContent = 23;
    $content = 22;
    $this->revisionDataDiffProperties = array(
      'nextContent' => $currContent,
      'content'     => $content,
    );
    $this->diffInfoProperties = array(
      'startIndex' => null,
      'endIndex' => null,
      'info' => 22,
    );
    $this->setUp();
    $expected = $content;
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $this->revisionDataDiff->getContentSize());
  }

  /**
   * @test
   */
  public function getNextContentSize()
  {
    $expected = strlen($this->revisionDataDiffProperties['nextContent']);
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $this->revisionDataDiff->getNextContentSize());
  }

  /**
   * @test
   */
  public function getNextContentNumericSize()
  {
    $currContent = 23;
    $content = 22;
    $this->revisionDataDiffProperties = array(
      'nextContent' => $currContent,
      'content'     => $content,
    );
    $this->diffInfoProperties = array(
      'startIndex' => null,
      'endIndex' => null,
      'info' => 23,
    );
    $this->setUp();
    $expected = $currContent;
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $this->revisionDataDiff->getNextContentSize());
  }

  /**
   * @test
   */
  public function getRemovedContentSize()
  {
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame(0, $this->revisionDataDiff->getRemovedContentSize());
  }

  /**
   * @test
   */
  public function getAddedContentSize()
  {
    $expected = strlen($this->diffInfoProperties['info']);
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $this->revisionDataDiff->getAddedContentSize());
  }

  /**
   * @test
   */
  public function renderRevisionString()
  {
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', 'Revision Info');
    $expected = 'Revision Info';
    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionDeletion()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some testing content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => 2,
      'info' => 'test',
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionSameAsWas()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some testing content',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $expected = 'some testing content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionTwoWords()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'Visto',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'info' => 'Billy ',
    );
    $this->setUp();
    $expected = 'Billy Visto';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionThreeWords()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'Visto',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'info' => 'Billy Joel ',
    );
    $this->setUp();
    $expected = 'Billy Joel Visto';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionThreeWordsMiddle()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'Billy Visto',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => null,
      'info' => 'Joel ',
    );
    $this->setUp();

    $expected = 'Billy Joel Visto';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionDeletionFromBeginning()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'info' => 'some ',
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionMultipleDeletion()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some more testing content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => 4,
      'info' => 'test',
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddition()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => 2,
      'info' => 'random other testing',
    );
    $this->setUp();
    $expected = 'some random other testing content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddToBegin()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'info' => 'hello ',
    );
    $this->setUp();
    $expected = 'hello some test content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddToMiddle()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 1,
      'endIndex' => null,
      'info' => ' hello',
    );
    $this->setUp();
    $expected = 'some hello test content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionRemoved()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 1,
      'endIndex' => null,
      'info' => ' test content',
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionBoolean()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => true,
    );
    $this->diffInfoProperties = array(
      'startIndex' => null,
      'endIndex' => null,
      'info' => false,
    );
    $this->setUp();
    $expected = false;

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionInt()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 100101,
    );
    $this->diffInfoProperties = array(
      'startIndex' => null,
      'endIndex' => null,
      'info' => 100010,
    );
    $this->setUp();
    $expected = 100010;

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionIntFirst()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 100101,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $expected = '';

    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionIntFirstChanges()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 100101,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $expected = '<ins>100101</ins>';

    $result = $this->call($this->revisionDataDiff, 'renderRevision', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionBooleanFirstChanges()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => false,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $expected = '<ins>false</ins>';

    $result = $this->call($this->revisionDataDiff, 'renderRevision', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionInt()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 100101,
    );
    $this->setUp();
    $expected = 100010;

    $result = $this->call($this->revisionDataDiff, 'renderNonStringRevision', array(100010));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionBoolean()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => true,
    );
    $this->setUp();
    $expected = false;

    $result = $this->call($this->revisionDataDiff, 'renderNonStringRevision', array(false));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionIntChanges()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 100101,
    );
    $this->setUp();
    $expected = '<del>100010</del><ins>100101</ins>';

    $result = $this->call($this->revisionDataDiff, 'renderNonStringRevision', array(100010, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionBooleanChanges()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => true,
    );
    $this->setUp();
    $expected = '<del>false</del><ins>true</ins>';

    $result = $this->call($this->revisionDataDiff, 'renderNonStringRevision', array(false, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionBooleanChangesEmptyRevision()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => true,
    );
    $this->setUp();
    $expected = '<ins>true</ins>';

    $result = $this->call($this->revisionDataDiff, 'renderNonStringRevision', array(null, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function getContent()
  {
    $expected = 'some testing test content';
    $result = $this->call($this->revisionDataDiff, 'getContent');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function getContentWithDiff()
  {
    $expected = 'some<del> testing</del> test content';

    $result = $this->call($this->revisionDataDiff, 'getContent', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionDataInfo()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some testing test content';
    $this->setUp();
    $this->revisionDataDiff->setDiffInfo(array());
    $this->revisionDataDiff->makeRevisionDataInfo('some test content');

    $result = $this->revisionDataDiff->getDiffInfo();
    $this->assertTrue(is_array($result));
    $this->assertInstanceOf('\Gustavus\Revisions\DiffInfo', $result[0]);
    $this->assertSame('some testing test content', $this->revisionDataDiff->getContent());
    $this->assertSame('some test content', $this->revisionDataDiff->getNextContent());
  }

  /**
   * @test
   */
  public function makeDiff()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some test content',
    );
    $this->setUp();
    $this->revisionDataDiff->setDiffInfo(array());
    $expected = '<del>some</del><ins>new</ins> test content';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array('new test content', true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffNew()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => '',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $expected = '<ins>new test content</ins>';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array('new test content', true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffBoolean()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => true,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $expected = '<del>true</del><ins>false</ins>';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array(false, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffInteger()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 100010,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $expected = '<del>100010</del><ins>101010</ins>';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array(101010, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffRemoval()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some test content',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $expected = '<del>some </del>test content';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array('test content', true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffAdditionReplacement()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'some test content revision',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $expected = 'some<ins> new</ins> test <del>content revision</del><ins>change</ins>';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array('some new test change', true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffAdditionReplacementRemoval()
  {
    $this->revisionDataDiffProperties = array(
      'nextContent' => 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $expected = 'Hello, <del>my name is</del><ins>I am</ins> Billy. I am writing <del>this </del>to test <del>some</del><ins>a new</ins> diff functions I wrote.';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array('Hello, I am Billy. I am writing to test a new diff functions I wrote.', true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some testing test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'info' => ' testing'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo2()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'info' => ' test content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }
  /**
   * @test
   */
  public function makeRevisionInfoAdditionReplacement()
  {
   $this->revisionDataDiffProperties = array(
      'nextContent' => 'some test content revision',
    );
    $this->setUp();
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => 2, 'info' => ''));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 6, 'endIndex' => 6, 'info' => 'content revision'));
    $expected = array($diff, $diffTwo);

    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some new test change'));
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWord()
  {
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test new content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 4, 'endIndex' => 5, 'info' => ''));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWords()
  {
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test new other content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 4, 'endIndex' => 7, 'info' => ''));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWord()
  {
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => null, 'info' => 'test '));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd()
  {
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'info' => ' test content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning()
  {
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => null, 'info' => 'some test '));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd2()
  {
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some more'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 2, 'info' => 'test content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning2()
  {
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => null, 'info' => 'some test '));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoChangedLetter()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some testr content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some tests content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 2, 'info' => 'testr'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }


  /**
   * @test
   */
  public function makeRevisionInfoContentReplaced()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some random testing content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 2, 'info' => 'random testing'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentReplacedAndRemoved()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some  tests content here and here';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => 2, 'info' => '  tests'));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 5, 'endIndex' => null, 'info' => ' here and here'));
    $expected = array($diff, $diffTwo);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAdded()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test content here and here'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 5, 'endIndex' => 10, 'info' => ''));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedAndRemoved()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some new test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test content here'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'info' => ' new'));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 5, 'endIndex' => 6, 'info' => ''));
    $expected = array($diff, $diffTwo);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedRemovedReplaced()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('Hello, I am Billy. I am writing to test a new diff functions I wrote here.'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 3, 'endIndex' => 5, 'info' => 'my name is'));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 16, 'endIndex' => null, 'info' => 'this '));
    $diffThree = new Revisions\DiffInfo(array('startIndex' => 20, 'endIndex' => 22, 'info' => 'some'));
    $diffFour = new Revisions\DiffInfo(array('startIndex' => 31, 'endIndex' => 32, 'info' => ''));
    $expected = array($diff, $diffTwo, $diffThree, $diffFour);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoNewContent()
  {
    $this->revisionDataDiffProperties['nextContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test content'));
    $expected = array(new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => 4, 'info' => '')));
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringDiffInfo()
  {
    $this->revisionDataDiffProperties['nextContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeNonStringDiffInfo', array(false));
    $diff = new Revisions\DiffInfo(array('startIndex' => null, 'endIndex' => null, 'info' => true));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringDiffInfoSame()
  {
    $this->revisionDataDiffProperties['nextContent'] = true;
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', null);
    $result = $this->call($this->revisionDataDiff, 'makeNonStringDiffInfo', array(true));
    $expected = array();
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoBoolean()
  {
    $this->revisionDataDiffProperties['nextContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array(false));
    $diff = new Revisions\DiffInfo(array('startIndex' => null, 'endIndex' => null, 'info' => true));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoInt()
  {
    $this->revisionDataDiffProperties['nextContent'] = 101001;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array(100011));
    $diff = new Revisions\DiffInfo(array('startIndex' => null, 'endIndex' => null, 'info' => 101001));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRenderRevisionNewContent()
  {
    $this->revisionDataDiffProperties['nextContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some test content'));
    $expected = array(new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => 4, 'info' => '')));
    $this->compareRevisionInfo($expected, $result);
    $this->revisionDataDiff->setNextContent('some test content');
    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame('', $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDB()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some random content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'renderRevisionForDB', array('some tests contentss'));
    $expected = json_encode(array(array(2, 4, 'random content')));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDBFirst()
  {
    $this->revisionDataDiffProperties['nextContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'renderRevisionForDB', array(23));
    $expected = json_encode(array(array(null,null,"")));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDBFirstAndSecondSame()
  {
    $this->revisionDataDiffProperties['nextContent'] = 23;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'renderRevisionForDB', array(23));
    $expected = null;
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDBSame()
  {
    $result = $this->call($this->revisionDataDiff, 'renderRevisionForDB', array('some test content'));
    $this->assertNull($result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRenderRevision()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'some random content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('some tests contentss'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 4, 'info' => 'random content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
    $this->call($this->revisionDataDiff, 'populateObjectWithArray', array(array('info' => $expected)));
    $expected = 'some random content';
    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function splitWords()
  {
    $array = array('some', ' ', 'text');
    $result = $this->call($this->revisionDataDiff, 'splitWords', array('some text'));
    $this->assertSame($array, $result);
  }

  /**
   * @test
   */
  public function splitWordsPunctuation()
  {
    $array = array('Hi', '.', ' ', 'Hello', '.');
    $result = $this->call($this->revisionDataDiff, 'splitWords', array('Hi. Hello.'));
    $this->assertSame($array, $result);
  }

  /**
   * @test
   */
  public function getRevisionRevisionNumber()
  {
    $this->assertSame(1, $this->revisionDataDiff->getRevisionRevisionNumber());
  }

  /**
   * @test
   */
  public function splitWords2()
  {
    $revisionContent = 'I like to eat food';
    $nextContent = 'I like to eat a lot of food while triple jumping.';
    $splitR = $this->call($this->revisionDataDiff, 'splitWords', array($revisionContent));
    $expectedR = array('I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'food');
    $this->assertSame($expectedR, $splitR);
    $splitC = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $expectedC = array('I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'a', ' ', 'lot', ' ', 'of', ' ', 'food', ' ', 'while', ' ', 'triple', ' ', 'jumping', '.');
    $this->assertSame($expectedC, $splitC);
  }

  /**
   * @test
   */
  public function splitWordsPeriodBeginning()
  {
    $revisionContent = 'I like to eat food';
    $nextContent = '.I like to eat a lot of food while triple jumping.';
    $splitR = $this->call($this->revisionDataDiff, 'splitWords', array($revisionContent));
    $expectedR = array('I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'food');
    $this->assertSame($expectedR, $splitR);
    $splitC = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $expectedC = array('.', 'I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'a', ' ', 'lot', ' ', 'of', ' ', 'food', ' ', 'while', ' ', 'triple', ' ', 'jumping', '.');
    $this->assertSame($expectedC, $splitC);
  }

  /**
   * @test
   */
  public function diff()
  {
    $revisionContent = 'I like to eat food';
    $nextContent = 'I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'diff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($nextContent))));

    $expected = array(
      array(
        "d" => array(),
        "i" => array(),
      ),
      "I",
      " ",
      "like",
      " ",
      "to",
      " ",
      "eat",
      " ",
      array(
        "d" => array(),
        "i" => array(
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
        ),
      ),
      "food",
      array(
        "d" => array(),
        "i" => array(
          " ",
          "while",
          " ",
          "triple",
          " ",
          "jumping",
          ".",
        ),
      ),
    );
    $this->assertSame($expected, $diff);
  }

  /**
   * @test
   */
  public function diffNoPeriod()
  {
    $revisionContent = 'I like to eat food';
    $nextContent = 'I like to eat a lot of food while triple jumping';
    $diff = $this->call($this->revisionDataDiff, 'diff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($nextContent))));

    $expected = array(
      array(
        "d" => array(),
        "i" => array(),
      ),
      "I",
      " ",
      "like",
      " ",
      "to",
      " ",
      "eat",
      " ",
      array(
        "d" => array(),
        "i" => array(
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
        ),
      ),
      "food",
      array(
        "d" => array(),
        "i" => array(
          " ",
          "while",
          " ",
          "triple",
          " ",
          "jumping",
        ),
      ),
    );
    $this->assertSame($expected, $diff);
  }

  /**
   * @test
   */
  public function myArrayDiff()
  {
    $revisionContent = 'I like to eat food';
    $nextContent = 'I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($nextContent))));

    $expected = array(
      "8" => array(
        "d" => array(),
        "i" => array(
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
        ),
      ),
      "15" => array(
        "d" => array(),
        "i" => array(
          " ",
          "while",
          " ",
          "triple",
          " ",
          "jumping",
          ".",
        ),
      ),
    );
    $this->assertSame($expected, $diff);
  }

  /**
   * @test
   */
  public function myArrayDiffPeriodBeginning()
  {
    $revisionContent = 'I like to eat food';
    $nextContent = '.I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($nextContent))));

    $expected = array(
      "0" => array(
        "d" => array(),
        "i" => array(
          ".",
        ),
      ),
      "9" => array(
        "d" => array(),
        "i" => array(
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
        ),
      ),
      "16" => array(
        "d" => array(),
        "i" => array(
          " ",
          "while",
          " ",
          "triple",
          " ",
          "jumping",
          ".",
        ),
      ),
    );
    $this->assertSame($expected, $diff);
  }

  /**
   * @test
   */
  public function myArrayDiffPunctuationBeginning()
  {
    $revisionContent = '?I like to eat food';
    $nextContent = '.I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($nextContent))));

    $expected = array(
      "0" => array(
        "d" => array(
          "?",
        ),
        "i" => array(
          ".",
        ),
      ),
      "9" => array(
        "d" => array(),
        "i" => array(
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
        ),
      ),
      "16" => array(
        "d" => array(),
        "i" => array(
          " ",
          "while",
          " ",
          "triple",
          " ",
          "jumping",
          ".",
        ),
      ),
    );
    $this->assertSame($expected, $diff);
  }

  /**
   * @test
   */
  public function myArrayDiff2()
  {
    $revisionContent = 'I like to eat food';
    $nextContent = 'I like to eat a lot of food while triple jumping';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($nextContent))));

    $expected = array(
      "8" => array(
        "d" => array(),
        "i" => array(
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
        ),
      ),
      "15" => array(
        "d" => array(),
        "i" => array(
          " ",
          "while",
          " ",
          "triple",
          " ",
          "jumping",
        ),
      ),
    );
    $this->assertSame($expected, $diff);
  }

  /**
   * @test
   */
  public function myArrayDiffRemovedPeriod()
  {
    $revisionContent = 'I like to eat a lot of food.';
    $nextContent = 'I like to eat a lot of food';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($nextContent))));

    $expected = array(
      "15" => array(
        "d" => array('.'),
        "i" => array(),
      )
    );
    $this->assertSame($expected, $diff);
  }

  /**
   * @test
   */
  public function makeRevisionDataLargeChangeEndingWithPeriod()
  {
    $revisionData = new Revisions\RevisionDataDiff(array('nextContent' => 'I like to eat food'));
    $nextContent = 'I like to eat a lot of food while triple jumping.';
    $actual = $revisionData->renderRevisionForDB($nextContent);
    $expected = json_encode(array(array(8, 13, ""), array(15, 21, "")));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function makeRevisionDataLargeChangeEndingWithExclamation()
  {
    $revisionData = new Revisions\RevisionDataDiff(array('nextContent' => 'I like to eat food'));
    $nextContent = 'I like to eat a lot of food while triple jumping!';
    $actual = $revisionData->renderRevisionForDB($nextContent);
    $expected = json_encode(array(array(8, 13, ""), array(15, 21, "")));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function makeRevisionDataLargeChange()
  {
    $revisionData = new Revisions\RevisionDataDiff(array('nextContent' => 'I like to eat food'));
    $nextContent = 'I like to eat a lot of food while triple jumping';
    $actual = $revisionData->renderRevisionForDB($nextContent);
    $expected = json_encode(array(array(8, 13, ""), array(15, 20, "")));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function getAddedAndRemovedContentSizeFullChange()
  {
    $revisionContent = 'Brooklyn Park';
    $nextContent = 'Saint Peter';
    $this->revisionDataDiff->setDiffInfo(array());
    $this->revisionDataDiff->setNextContent($revisionContent);
    $this->assertSame($revisionContent, $this->revisionDataDiff->makeDiff($nextContent));
    $this->assertSame(13, $this->revisionDataDiff->getAddedContentSize());
    $this->assertSame(11, $this->revisionDataDiff->getRemovedContentSize());
  }

  /**
   * @test
   */
  public function getAddedAndRemovedContentSizeNumber()
  {
    $revisionContent = 1234;
    $nextContent = 25;
    $this->revisionDataDiff->setDiffInfo(array());
    $this->revisionDataDiff->setNextContent($revisionContent);
    $this->assertSame($revisionContent, $this->revisionDataDiff->makeDiff($nextContent));
    $this->assertSame(4, $this->revisionDataDiff->getAddedContentSize());
    $this->assertSame(2, $this->revisionDataDiff->getRemovedContentSize());
  }

  /**
   * @test
   */
  public function makeDiffInfo()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'Hi.';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('Hi. Hello.'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 4, 'info' => ''));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffInfo2()
  {
    $this->revisionDataDiffProperties['nextContent'] = 'Hi. Hello';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array('Hi.'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => null, 'info' => ' Hello'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function myArrayDiffTwoSentencesToOne()
  {
    $oldContent = 'I like to eat. Food is good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $nextContent = 'I like to eat a lot of food';
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $myArrDiff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($oldSplit, $nextSplit));
    //var_dump($oldSplit, $nextSplit, $myArrDiff);.
    $expected = array(
      '7' => array(
        'd' => array(
          ".",
          " ",
          "Food",
          " ",
          "is",
          " ",
          "good",
          "!",
        ),
        'i' => array(
          " ",
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
          "food",
        ),
      ),
    );
    $this->assertSame($expected, $myArrDiff);
  }

  /**
   * @test
   */
  public function myArrayDiffCity()
  {
    $oldContent = 'Brooklyn Park';
    $nextContent = 'Saint Peter';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $myArrDiff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($oldSplit, $nextSplit));
    $expected = array(
      '0' => array(
        'd' => array(
          "Brooklyn",
          " ",
          "Park",
        ),
        'i' => array(
          "Saint",
          " ",
          "Peter",
        ),
      ),
    );
    $this->assertSame($expected, $myArrDiff);
  }

  /**
   * @test
   */
  public function myArrayDiffRestored()
  {
    $oldContent = 'I like to eat a lot of junk food';
    $nextContent = 'I like to jump. I also like to eat. Food is good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $myArrDiff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($oldSplit, $nextSplit));
    $expected = array(
      '6' => array(
        'd' => array(
        ),
        'i' => array(
          "jump",
          ".",
          " ",
          "I",
          " ",
          "also",
          " ",
          "like",
          " ",
          "to",
          " "
        ),
      ),
      '18' => array(
        'd' => array(
          " ",
          "a",
          " ",
          "lot",
          " ",
          "of",
          " ",
          "junk",
          " ",
          "food"
        ),
        'i' => array(
          ".",
          " ",
          "Food",
          " ",
          "is",
          " ",
          "good",
          "!"
        ),
      ),
    );
    $this->assertSame($expected, $myArrDiff);
  }

  /**
   * @test
   */
  public function myArrayDiffWeirdSpaces()
  {
    $oldContent = 'I like to eat a  lot of junk food';
    $nextContent = 'I like  to jump. I also like  to eat. Food is good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $myArrDiff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($oldSplit, $nextSplit));
    $expected = array(
      '3' => array(
        'd' => array(
          " "
        ),
        'i' => array(
          "  ",
          "to",
          " ",
          "jump",
          ".",
          " ",
          "I",
          " ",
          "also",
          " ",
          "like",
          "  ",
        ),
      ),
      '18' => array(
        'd' => array(
          " ",
          "a",
          "  ",
          "lot",
          " ",
          "of",
          " ",
          "junk",
          " ",
          "food"
        ),
        'i' => array(
          ".",
          " ",
          "Food",
          " ",
          "is",
          " ",
          "good",
          "!"
        ),
      ),
    );
    $this->assertSame($expected, $myArrDiff);
  }

  /**
   * @test
   */
  public function myArrayDiffReallyWeirdSpaces()
  {
    $oldContent = 'I  like  to  eat  a  lot  of  junk  food';
    $nextContent = 'I  like  to  jump.  I  also  like   to  eat.  Food  is  good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $myArrDiff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($oldSplit, $nextSplit));
    $expected = array(
      '6' => array(
        'd' => array(
        ),
        'i' => array(
          "jump",
          ".",
          "  ",
          "I",
          "  ",
          "also",
          "  ",
          "like",
          "   ",
          "to",
          "  "
        ),
      ),
      '18' => array(
        'd' => array(
          "  ",
          "a",
          "  ",
          "lot",
          "  ",
          "of",
          "  ",
          "junk",
          "  ",
          "food"
        ),
        'i' => array(
          ".",
          "  ",
          "Food",
          "  ",
          "is",
          "  ",
          "good",
          "!"
        ),
      ),
    );
    $this->assertSame($expected, $myArrDiff);
  }

  /**
   * @test
   */
  public function renderRevisionWeirdSpaces()
  {
    $oldContent = 'I like to eat a  lot of junk food';
    $nextContent = 'I like  to jump. I also like  to eat. Food is good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $resultDB = $this->revisionDataDiff->renderRevisionForDB($nextContent);
    $expectedDB = '[[3,14," "],[18,25," a  lot of junk food"]]';
    $this->assertSame($expectedDB, $resultDB);

    $result = $this->revisionDataDiff->makeDiff($nextContent);
    $expected = $oldContent;
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDBRestored()
  {
    $oldContent = 'I like to eat a lot of junk food';
    $nextContent = 'I like to jump. I also like to eat. Food is good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $result = $this->revisionDataDiff->renderRevisionForDB($nextContent);
    $expected = '[[6,16,""],[18,25," a lot of junk food"]]';
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffAndContentRestored()
  {
    $oldContent = 'I like to eat a lot of junk food';
    $nextContent = 'I like to jump. I also like to eat. Food is good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $result = $this->revisionDataDiff->makeDiff($nextContent);
    $expected = 'I like to eat a lot of junk food';
    $this->assertSame($expected, $result);
    $resultDiff = $this->revisionDataDiff->makeDiff($nextContent, true);
    $expectedDiff = 'I like to <ins>jump. I also like to </ins>eat<del> a lot of junk food</del><ins>. Food is good!</ins>';
    $this->assertSame($expectedDiff, $resultDiff);
  }

  /**
   * @test
   */
  public function skippedValuesExist()
  {
    $oldContent = 'I like to eat a lot of junk food';
    $nextContent = 'I like to jump. I also like to eat. Food is good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $diff = $this->call($this->revisionDataDiff, 'diff', array($oldSplit, $nextSplit));
    $prevKey = array(9, 11);
    $key = 13;
    $result = $this->call($this->revisionDataDiff, 'skippedValuesExist', array($prevKey, $key, $diff));
    $this->assertTrue($result);
  }

  /**
   * @test
   */
  public function skippedValuesExistMultipleSpaces()
  {
    $oldContent = 'I  like  to  eat  a  lot  of  junk  food';
    $nextContent = 'I  like  to  jump.  I  also  like  to  eat.  Food  is  good!';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $diff = $this->call($this->revisionDataDiff, 'diff', array($oldSplit, $nextSplit));
    $prevKey = array(9, 11);
    $key = 13;
    $result = $this->call($this->revisionDataDiff, 'skippedValuesExist', array($prevKey, $key, $diff));
    $this->assertTrue($result);
  }

  /**
   * @test
   */
  public function makeDiffInfoRemovedPeriod()
  {
    $oldContent = 'I like to eat a lot of food.';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $nextContent = 'I like to eat a lot of food';
    $result = $this->call($this->revisionDataDiff, 'makeDiffInfo', array($nextContent));
    $oldSplit = $this->call($this->revisionDataDiff, 'splitWords', array($oldContent));
    $nextSplit = $this->call($this->revisionDataDiff, 'splitWords', array($nextContent));
    $myArrDiff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($oldSplit, $nextSplit));
    $diff = new Revisions\DiffInfo(array('startIndex' => 15, 'endIndex' => null, 'info' => '.'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function contentIsNumericFalse()
  {
    $this->assertFalse($this->revisionDataDiff->contentIsNumeric());
  }

  /**
   * @test
   */
  public function contentIsNumeric()
  {
    $oldContent = 22;
    $nextContent = 23;
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $result = $this->revisionDataDiff->makeDiff($nextContent);
    $this->assertTrue($this->revisionDataDiff->contentIsNumeric());
  }

  /**
   * @test
   */
  public function contentIsNumericFloat()
  {
    $oldContent = 22.2;
    $nextContent = 23.9;
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $result = $this->revisionDataDiff->makeDiff($nextContent);
    $this->assertTrue($this->revisionDataDiff->contentIsNumeric());
  }

  /**
   * @test
   */
  public function contentIsNumericBoolean()
  {
    $oldContent = false;
    $nextContent = true;
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $result = $this->revisionDataDiff->makeDiff($nextContent);
    $this->assertFalse($this->revisionDataDiff->contentIsNumeric());
  }

  /**
   * @test
   */
  public function contentIsNumericBooleanString()
  {
    $oldContent = 'false';
    $nextContent = 'true';
    $this->revisionDataDiffProperties['nextContent'] = $oldContent;
    $this->setUp();
    $this->set($this->revisionDataDiff, 'diffInfo', array());
    $result = $this->revisionDataDiff->makeDiff($nextContent);
    $this->assertFalse($this->revisionDataDiff->contentIsNumeric());
  }
}