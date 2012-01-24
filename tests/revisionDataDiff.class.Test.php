<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once '/cis/lib/test/test.class.php';
require_once 'revisions/classes/revisionDataDiff.class.php';

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
    'revisionId'  => 1,
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
    $this->revisionDataDiff = new Revisions\RevisionDataDiff($this->revisionDataDiffProperties);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisionDataDiff, $this->revisionDataDiffProperties);
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
  public function renderRevisionString()
  {
    $this->revisionDataDiffProperties['revisionInfo'] = 'Revision Info';
    $this->setUp();
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
      'revisionInfo' => array(array(
        2,
        2,
        'test',
      )),
    );
    $this->setUp();
    $expected = 'some test content';

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
      'revisionInfo' => array(array(
        0,
        null,
        'some',
      )),
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
      'revisionInfo' => array(array(
        2,
        4,
        'test',
      )),
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
      'revisionInfo' => array(array(
        2,
        2,
        'random other testing',
      )),
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
      'revisionInfo' => array(array(
        0,
        null,
        'hello',
      )),
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
      'revisionInfo' => array(array(
        2,
        null,
        'hello',
      )),
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
      'revisionInfo' => array(array(
        1,
        null,
        ' test content',
      )),
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
      'revisionInfo' => array(array(
        null,
        null,
        false,
      )),
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
      'revisionInfo' => array(array(
        null,
        null,
        100010,
      )),
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
      'revisionInfo' => array(),
    );
    $this->setUp();
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
      'revisionInfo' => array(),
    );
    $this->setUp();
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
      'revisionInfo' => array(),
    );
    $this->setUp();
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
    $expected = 'some <del>testing</del>test content';

    $result = $this->call($this->revisionDataDiff, 'makeRevisionContent', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiff()
  {
    $this->revisionDataDiffProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(),
    );
    $this->setUp();
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
    $expected = 'some <ins>new </ins>test <del>content revision</del><ins>change</ins>';

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
    $expected = array(array(1, null, ' testing'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo2()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some'));
    $expected = array(array(1, null, ' test content'));
    $this->assertSame($expected, $result);
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
    $expected = array(array(2, 3, ''), array(6, 6, 'content revision'));

    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some new test change'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWord()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test new content'));
    $expected = array(array(4, 5, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWords()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test new other content'));
    $expected = array(array(4, 7, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWord()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some content'));
    $expected = array(array(2, null, 'test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some'));
    $expected = array(array(1, null, ' test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('content'));
    $expected = array(array(0, null, 'some test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd2()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some more'));
    $expected = array(array(2, 2, 'test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning2()
  {
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('content'));
    $expected = array(array(0, null, 'some test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoChangedLetter()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some testr content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some tests content'));
    $expected = array(array(2, 2, 'testr'));
    $this->assertSame($expected, $result);
  }


  /**
   * @test
   */
  public function makeRevisionInfoContentReplaced()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some random testing content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $expected = array(array(2, 2, 'random testing'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentReplacedAndRemoved()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some  tests content here and here';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $expected = array(array(1, 2, '  tests'), array(5, null, ' here and here'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAdded()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content here and here'));
    $expected = array(array(5, 10, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedAndRemoved()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'some new test content';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content here'));
    $expected = array(array(1, null, ' new'), array(5, 6, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedRemovedReplaced()
  {
    $this->revisionDataDiffProperties['currentContent'] = 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('Hello, I am Billy. I am writing to test a new diff functions I wrote here.'));
    $expected = array(array(2, 4, 'my name is'), array(14, null, 'this '), array(18, 20, 'some'), array(29, 30, ''));

    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoNewContent()
  {
    $this->revisionDataDiffProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $expected = array();
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringRevisionInfo()
  {
    $this->revisionDataDiffProperties['currentContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeNonStringRevisionInfo', array(false));
    $expected = array(array(null, null, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringRevisionInfoSame()
  {
    $this->revisionDataDiffProperties['currentContent'] = true;
    $this->setUp();
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
    $expected = array(array(null, null, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoInt()
  {
    $this->revisionDataDiffProperties['currentContent'] = 101001;
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array(100011));
    $expected = array(array(null, null, 101001));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRenderRevisionNewContent()
  {
    $this->revisionDataDiffProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionDataDiff, 'makeRevisionInfo', array('some test content'));
    $expected = array();
    $this->assertSame($expected, $result);
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
    $expected = array(array(2, 4, 'random content'));
    $this->assertSame($expected, $result);
    $this->call($this->revisionDataDiff, 'populateObjectWithArray', array(array('revisionInfo' => $expected)));
    $expected = 'some random content';
    $result = $this->call($this->revisionDataDiff, 'renderRevision');
    $this->assertSame($expected, $result);
  }
}