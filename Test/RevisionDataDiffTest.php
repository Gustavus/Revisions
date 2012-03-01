<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once '/cis/lib/Gustavus/Test/Test.php';
require_once 'Gustavus/Revisions/RevisionDataDiff.php';
require_once 'Gustavus/Revisions/DiffInfo.php';

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
    'currentContent' => 'some test content',
    'revisionNumber' => 1,
    'revisionRevisionNumber' => 1,
    'revisionId'  => 1,
  );

  /**
   * @var array to fill DiffInfo objectWith
   */
  private $diffInfoProperties = array(
    'startIndex' => 1,
    'endIndex'  => null,
    'revisionInfo' =>' testing',
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $diffInfo = new Revisions\DiffInfo($this->diffInfoProperties);
    $this->revisionDataDiffProperties['revisionInfo'] = array($diffInfo);
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
      $this->assertSame($diffInfo->getRevisionInfo(), $actual[$key]->getRevisionInfo());
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
  public function getCurrentContent()
  {
    $this->assertSame($this->revisionDataDiffProperties['currentContent'], $this->revisionDataDiff->getCurrentContent());
  }

  /**
   * @test
   */
  public function getRevisionInfo()
  {
    $this->assertSame($this->revisionDataDiffProperties['revisionInfo'], $this->revisionDataDiff->getRevisionInfo());
  }

  /**
   * @test
   */
  public function getRevisionNumber()
  {
    $this->assertSame($this->revisionDataDiffProperties['revisionNumber'], $this->revisionDataDiff->getRevisionNumber());
  }

  /**
   * @test
   */
  public function getRevisionId()
  {
    $this->assertSame($this->revisionDataDiffProperties['revisionId'], $this->revisionDataDiff->getRevisionId());
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
    $this->revisionDataDiff->setRevisionContent('Billy');
    $this->assertSame('Billy', $this->revisionDataDiff->getRevisionContent());
  }

  /**
   * @test
   */
  public function getRevisionContentNotSet()
  {
    $this->assertSame('some testing test content', $this->revisionDataDiff->getRevisionContent());
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
  public function getCurrentContentSize()
  {
    $expected = strlen($this->revisionDataDiffProperties['currentContent']);
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $this->revisionDataDiff->getCurrentContentSize());
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
    $expected = strlen($this->diffInfoProperties['revisionInfo']);
    $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $this->revisionDataDiff->getAddedContentSize());
  }

  /**
   * @test
   */
  public function renderRevisionString()
  {
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', 'Revision Info');
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
      'currentContent' => 'some testing content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => 2,
      'revisionInfo' => 'test',
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
      'currentContent' => 'some testing content',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
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
      'currentContent' => 'Visto',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'revisionInfo' => 'Billy ',
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
      'currentContent' => 'Visto',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'revisionInfo' => 'Billy Joel ',
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
      'currentContent' => 'Billy Visto',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => null,
      'revisionInfo' => 'Joel ',
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
      'currentContent' => 'test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'revisionInfo' => 'some ',
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
      'currentContent' => 'some more testing content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => 4,
      'revisionInfo' => 'test',
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
      'currentContent' => 'some test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 2,
      'endIndex' => 2,
      'revisionInfo' => 'random other testing',
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
      'currentContent' => 'some test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 0,
      'endIndex' => null,
      'revisionInfo' => 'hello ',
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
      'currentContent' => 'some test content',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 1,
      'endIndex' => null,
      'revisionInfo' => ' hello',
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
      'currentContent' => 'some',
    );
    $this->diffInfoProperties = array(
      'startIndex' => 1,
      'endIndex' => null,
      'revisionInfo' => ' test content',
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
      'currentContent' => true,
    );
    $this->diffInfoProperties = array(
      'startIndex' => null,
      'endIndex' => null,
      'revisionInfo' => false,
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
      'currentContent' => 100101,
    );
    $this->diffInfoProperties = array(
      'startIndex' => null,
      'endIndex' => null,
      'revisionInfo' => 100010,
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
      'currentContent' => 100101,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', array());
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
      'currentContent' => 100101,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', array());
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
      'currentContent' => false,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', array());
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
      'currentContent' => 100101,
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
      'currentContent' => true,
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
      'currentContent' => 100101,
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
      'currentContent' => true,
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
      'currentContent' => true,
    );
    $this->setUp();
    $expected = '<ins>true</ins>';

    $result = $this->call($this->revisionDataDiff, 'renderNonStringRevision', array(null, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionContent()
  {
    $expected = 'some testing test content';
    $result = $this->call($this->revisionDataDiff, 'makeRevisionContent');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionContentWithDiff()
  {
    $expected = 'some<del> testing</del> test content';

    $result = $this->call($this->revisionDataDiff, 'makeRevisionContent', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionDataInfo()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some testing test content';
    $this->setUp();
    $this->revisionDataDiff->setRevisionInfo(array());
    $this->revisionDataDiff->makeRevisionDataInfo('some test content');

    $result = $this->revisionDataDiff->getRevisionInfo();
    $this->assertTrue(is_array($result));
    $this->assertInstanceOf('\Gustavus\Revisions\DiffInfo', $result[0]);
    $this->assertSame('some testing test content', $this->revisionDataDiff->getRevisionContent());
    $this->assertSame('some test content', $this->revisionDataDiff->getCurrentContent());
  }

  /**
   * @test
   */
  public function makeDiff()
  {
    $this->revisionDataDiffProperties = array(
      'currentContent' => 'some test content',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', array());
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
      'currentContent' => '',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
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
      'currentContent' => true,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
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
      'currentContent' => 100010,
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
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
      'currentContent' => 'some test content',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
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
      'currentContent' => 'some test content revision',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
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
      'currentContent' => 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.',
    );
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
    $expected = 'Hello, <del>my name is</del><ins>I am</ins> Billy. I am writing <del>this </del>to test <del>some</del><ins>a new</ins> diff functions I wrote.';

    $result = $this->call($this->revisionDataDiff, 'makeDiff', array('Hello, I am Billy. I am writing to test a new diff functions I wrote.', true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some testing test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'revisionInfo' => ' testing'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo2()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'revisionInfo' => ' test content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }
  /**
   * @test
   */
  public function makeRevisionInfoAdditionReplacement()
  {
   $this->revisionDataDiffProperties = array(
      'currentContent' => 'some test content revision',
    );
    $this->setUp();
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => 2, 'revisionInfo' => ''));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 6, 'endIndex' => 6, 'revisionInfo' => 'content revision'));
    $expected = array($diff, $diffTwo);

    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some new test change'));
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWord()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test new content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 4, 'endIndex' => 5, 'revisionInfo' => ''));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWords()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test new other content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 4, 'endIndex' => 7, 'revisionInfo' => ''));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWord()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => null, 'revisionInfo' => 'test '));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'revisionInfo' => ' test content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => null, 'revisionInfo' => 'some test '));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd2()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some more'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 2, 'revisionInfo' => 'test content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning2()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => null, 'revisionInfo' => 'some test '));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoChangedLetter()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some testr content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some tests content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 2, 'revisionInfo' => 'testr'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }


  /**
   * @test
   */
  public function makeRevisionInfoContentReplaced()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some random testing content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 2, 'revisionInfo' => 'random testing'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentReplacedAndRemoved()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some  tests content here and here';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => 2, 'revisionInfo' => '  tests'));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 5, 'endIndex' => null, 'revisionInfo' => ' here and here'));
    $expected = array($diff, $diffTwo);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAdded()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content here and here'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 5, 'endIndex' => 10, 'revisionInfo' => ''));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedAndRemoved()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some new test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content here'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 1, 'endIndex' => null, 'revisionInfo' => ' new'));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 5, 'endIndex' => 6, 'revisionInfo' => ''));
    $expected = array($diff, $diffTwo);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedRemovedReplaced()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('Hello, I am Billy. I am writing to test a new diff functions I wrote here.'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 4, 'revisionInfo' => 'my name is'));
    $diffTwo = new Revisions\DiffInfo(array('startIndex' => 14, 'endIndex' => null, 'revisionInfo' => 'this '));
    $diffThree = new Revisions\DiffInfo(array('startIndex' => 18, 'endIndex' => 20, 'revisionInfo' => 'some'));
    $diffFour = new Revisions\DiffInfo(array('startIndex' => 29, 'endIndex' => 30, 'revisionInfo' => ''));
    $expected = array($diff, $diffTwo, $diffThree, $diffFour);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoNewContent()
  {
    $this->revisionDataDiffProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $expected = array(new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => 4, 'revisionInfo' => '')));
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringRevisionInfo()
  {
    $this->revisionDataDiffProperties['currentContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeNonStringRevisionInfo', array(false));
    $diff = new Revisions\DiffInfo(array('startIndex' => null, 'endIndex' => null, 'revisionInfo' => true));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringRevisionInfoSame()
  {
    $this->revisionDataDiffProperties['currentContent'] = true;
    $this->setUp();
    $this->set($this->revisionDataDiff, 'revisionInfo', null);
    $result = $this->call($this->revisionDataDiff, 'makeNonStringRevisionInfo', array(true));
    $expected = array();
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoBoolean()
  {
    $this->revisionDataDiffProperties['currentContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array(false));
    $diff = new Revisions\DiffInfo(array('startIndex' => null, 'endIndex' => null, 'revisionInfo' => true));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoInt()
  {
    $this->revisionDataDiffProperties['currentContent'] = 101001;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array(100011));
    $diff = new Revisions\DiffInfo(array('startIndex' => null, 'endIndex' => null, 'revisionInfo' => 101001));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRenderRevisionNewContent()
  {
    $this->revisionDataDiffProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $expected = array(new Revisions\DiffInfo(array('startIndex' => 0, 'endIndex' => 4, 'revisionInfo' => '')));
    $this->compareRevisionInfo($expected, $result);
    $this->revisionDataDiff->setCurrentContent('some test content');
    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame('', $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDB()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some random content';
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
    $this->revisionDataDiffProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'renderRevisionForDB', array(23));
    $expected = json_encode(array());
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
    $this->revisionDataDiffProperties['currentContent'] = 'some random content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some tests contentss'));
    $diff = new Revisions\DiffInfo(array('startIndex' => 2, 'endIndex' => 4, 'revisionInfo' => 'random content'));
    $expected = array($diff);
    $this->compareRevisionInfo($expected, $result);
    $this->call($this->revisionDataDiff, 'populateObjectWithArray', array(array('revisionInfo' => $expected)));
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
    $currentContent = 'I like to eat a lot of food while triple jumping.';
    $splitR = $this->call($this->revisionDataDiff, 'splitWords', array($revisionContent));
    $expectedR = array('I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'food');
    $this->assertSame($expectedR, $splitR);
    $splitC = $this->call($this->revisionDataDiff, 'splitWords', array($currentContent));
    $expectedC = array('I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'a', ' ', 'lot', ' ', 'of', ' ', 'food', ' ', 'while', ' ', 'triple', ' ', 'jumping', '.');
    $this->assertSame($expectedC, $splitC);
  }

  /**
   * @test
   */
  public function splitWordsPeriodBeginning()
  {
    $revisionContent = 'I like to eat food';
    $currentContent = '.I like to eat a lot of food while triple jumping.';
    $splitR = $this->call($this->revisionDataDiff, 'splitWords', array($revisionContent));
    $expectedR = array('I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'food');
    $this->assertSame($expectedR, $splitR);
    $splitC = $this->call($this->revisionDataDiff, 'splitWords', array($currentContent));
    $expectedC = array('.', 'I', ' ', 'like', ' ', 'to', ' ', 'eat', ' ', 'a', ' ', 'lot', ' ', 'of', ' ', 'food', ' ', 'while', ' ', 'triple', ' ', 'jumping', '.');
    $this->assertSame($expectedC, $splitC);
  }

  /**
   * @test
   */
  public function diff()
  {
    $revisionContent = 'I like to eat food';
    $currentContent = 'I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'diff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($currentContent))));

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
    $currentContent = 'I like to eat a lot of food while triple jumping';
    $diff = $this->call($this->revisionDataDiff, 'diff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($currentContent))));

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
    $currentContent = 'I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($currentContent))));

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
    $currentContent = '.I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($currentContent))));

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
    $currentContent = '.I like to eat a lot of food while triple jumping.';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($currentContent))));

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
    $currentContent = 'I like to eat a lot of food while triple jumping';
    $diff = $this->call($this->revisionDataDiff, 'myArrayDiff', array($this->call($this->revisionDataDiff, 'splitWords', array($revisionContent)), $this->call($this->revisionDataDiff, 'splitWords', array($currentContent))));

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
  public function makeRevisionDataLargeChangeEndingWithPeriod()
  {
    $revisionData = new Revisions\RevisionDataDiff(array('currentContent' => 'I like to eat food'));
    $currentContent = 'I like to eat a lot of food while triple jumping.';
    $actual = $revisionData->renderRevisionForDB($currentContent);
    $expected = json_encode(array(array(8, 13, ""), array(15, 21, "")));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function makeRevisionDataLargeChangeEndingWithExclamation()
  {
    $revisionData = new Revisions\RevisionDataDiff(array('currentContent' => 'I like to eat food'));
    $currentContent = 'I like to eat a lot of food while triple jumping!';
    $actual = $revisionData->renderRevisionForDB($currentContent);
    $expected = json_encode(array(array(8, 13, ""), array(15, 21, "")));
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function makeRevisionDataLargeChange()
  {
    $revisionData = new Revisions\RevisionDataDiff(array('currentContent' => 'I like to eat food'));
    $currentContent = 'I like to eat a lot of food while triple jumping';
    $actual = $revisionData->renderRevisionForDB($currentContent);
    $expected = json_encode(array(array(8, 13, ""), array(15, 20, "")));
    $this->assertSame($expected, $actual);
  }
}