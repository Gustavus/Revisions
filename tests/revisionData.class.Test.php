<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once '/cis/lib/test/test.class.php';
require_once 'revisions/classes/revisionData.class.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionDataTest extends \Gustavus\Test\Test
{
  /**
   * @var \Gustavus\Revisions\Revision
   */
  private $revisionData;

  /**
   * @var array to fill object with
   */
  private $revisionDataProperties = array(
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
    $this->revisionData = new Revisions\RevisionData($this->revisionDataProperties);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisionData, $this->revisionDataProperties);
  }

  /**
   * @test
   */
  public function getCurrentContent()
  {
    $this->assertSame($this->revisionDataProperties['currentContent'], $this->revisionData->getCurrentContent());
  }

  /**
   * @test
   */
  public function getRevisionInfo()
  {
    $this->assertSame($this->revisionDataProperties['revisionInfo'], $this->revisionData->getRevisionInfo());
  }

  /**
   * @test
   */
  public function getRevisionNumber()
  {
    $this->assertSame($this->revisionDataProperties['revisionNumber'], $this->revisionData->getRevisionNumber());
  }

  /**
   * @test
   */
  public function getRevisionId()
  {
    $this->assertSame($this->revisionDataProperties['revisionId'], $this->revisionData->getRevisionId());
  }

  /**
   * @test
   */
  public function getError()
  {
    $this->assertFalse($this->revisionData->getError());
  }

  /**
   * @test
   */
  public function setAndGetError()
  {
    $this->revisionData->setError(true);
    $this->assertTrue($this->revisionData->getError());
  }

  /**
   * @test
   */
  public function setAndGetRevisionContent()
  {
    $this->revisionData->setRevisionContent('Billy');
    $this->assertSame('Billy', $this->revisionData->getRevisionContent());
  }

  /**
   * @test
   */
  public function getRevisionContentNotSet()
  {
    $this->assertSame('some testing test content', $this->revisionData->getRevisionContent());
  }

  /**
   * @test
   */
  public function populateObjectWithArray()
  {
    $expected = $this->revisionData;
    $this->revisionDataProperties['newProp'] = 'test';
    $this->call($this->revisionData, 'populateObjectWithArray', array($this->revisionDataProperties));
    $this->assertSame($expected, $this->revisionData);
  }

  /**
   * @test
   */
  public function renderRevision()
  {
    $expected = 'some testing test content';
    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionString()
  {
    $this->revisionDataProperties['revisionInfo'] = 'Revision Info';
    $this->setUp();
    $expected = 'Revision Info';
    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionDeletion()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some testing content',
      'revisionInfo' => array(array(
        2,
        2,
        'test',
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionDeletionFromBeginning()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'test content',
      'revisionInfo' => array(array(
        0,
        null,
        'some',
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionMultipleDeletion()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some more testing content',
      'revisionInfo' => array(array(
        2,
        4,
        'test',
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddition()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(array(
        2,
        2,
        'random other testing',
      )),
    );
    $this->setUp();
    $expected = 'some random other testing content';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddToBegin()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(array(
        0,
        null,
        'hello',
      )),
    );
    $this->setUp();
    $expected = 'hello some test content';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddToMiddle()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(array(
        2,
        null,
        'hello',
      )),
    );
    $this->setUp();
    $expected = 'some hello test content';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionRemoved()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some',
      'revisionInfo' => array(array(
        1,
        null,
        ' test content',
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionBoolean()
  {
    $this->revisionDataProperties = array(
      'currentContent' => true,
      'revisionInfo' => array(array(
        null,
        null,
        false,
      )),
    );
    $this->setUp();
    $expected = false;

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionInt()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 100101,
      'revisionInfo' => array(array(
        null,
        null,
        100010,
      )),
    );
    $this->setUp();
    $expected = 100010;

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionIntFirst()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 100101,
      'revisionInfo' => array(),
    );
    $this->setUp();
    $expected = '';

    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionIntFirstChanges()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 100101,
      'revisionInfo' => array(),
    );
    $this->setUp();
    $expected = '<ins>100101</ins>';

    $result = $this->call($this->revisionData, 'renderRevision', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionBooleanFirstChanges()
  {
    $this->revisionDataProperties = array(
      'currentContent' => false,
      'revisionInfo' => array(),
    );
    $this->setUp();
    $expected = '<ins>false</ins>';

    $result = $this->call($this->revisionData, 'renderRevision', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionInt()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 100101,
    );
    $this->setUp();
    $expected = 100010;

    $result = $this->call($this->revisionData, 'renderNonStringRevision', array(100010));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionBoolean()
  {
    $this->revisionDataProperties = array(
      'currentContent' => true,
    );
    $this->setUp();
    $expected = false;

    $result = $this->call($this->revisionData, 'renderNonStringRevision', array(false));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionIntChanges()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 100101,
    );
    $this->setUp();
    $expected = '<del>100010</del><ins>100101</ins>';

    $result = $this->call($this->revisionData, 'renderNonStringRevision', array(100010, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionBooleanChanges()
  {
    $this->revisionDataProperties = array(
      'currentContent' => true,
    );
    $this->setUp();
    $expected = '<del>false</del><ins>true</ins>';

    $result = $this->call($this->revisionData, 'renderNonStringRevision', array(false, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderNonStringRevisionBooleanChangesEmptyRevision()
  {
    $this->revisionDataProperties = array(
      'currentContent' => true,
    );
    $this->setUp();
    $expected = '<ins>true</ins>';

    $result = $this->call($this->revisionData, 'renderNonStringRevision', array(null, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionContent()
  {
    $expected = 'some testing test content';
    $result = $this->call($this->revisionData, 'makeRevisionContent');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionContentWithDiff()
  {
    $expected = 'some <del>testing</del>test content';

    $result = $this->call($this->revisionData, 'makeRevisionContent', array(true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiff()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(),
    );
    $this->setUp();
    $expected = '<del>some</del><ins>new</ins> test content';

    $result = $this->call($this->revisionData, 'makeDiff', array('new test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffNew()
  {
    $this->revisionDataProperties = array(
      'currentContent' => '',
    );
    $this->setUp();
    $expected = '<ins>new test content</ins>';

    $result = $this->call($this->revisionData, 'makeDiff', array('new test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffBoolean()
  {
    $this->revisionDataProperties = array(
      'currentContent' => true,
    );
    $this->setUp();
    $expected = '<del>true</del><ins>false</ins>';

    $result = $this->call($this->revisionData, 'makeDiff', array(false));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffInteger()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 100010,
    );
    $this->setUp();
    $expected = '<del>100010</del><ins>101010</ins>';

    $result = $this->call($this->revisionData, 'makeDiff', array(101010));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffRemoval()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some test content',
    );
    $this->setUp();
    $expected = '<del>some </del>test content';

    $result = $this->call($this->revisionData, 'makeDiff', array('test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffAdditionReplacement()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'some test content revision',
    );
    $this->setUp();
    $expected = 'some <ins>new </ins>test <del>content revision</del><ins>change</ins>';

    $result = $this->call($this->revisionData, 'makeDiff', array('some new test change'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffAdditionReplacementRemoval()
  {
    $this->revisionDataProperties = array(
      'currentContent' => 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.',
    );
    $this->setUp();
    $expected = 'Hello, <del>my name is</del><ins>I am</ins> Billy. I am writing <del>this </del>to test <del>some</del><ins>a new</ins> diff functions I wrote.';

    $result = $this->call($this->revisionData, 'makeDiff', array('Hello, I am Billy. I am writing to test a new diff functions I wrote.'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo()
  {
    $this->revisionDataProperties['currentContent'] = 'some testing test content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test content'));
    $expected = array(array(1, null, ' testing'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo2()
  {
    $this->revisionDataProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some'));
    $expected = array(array(1, null, ' test content'));
    $this->assertSame($expected, $result);
  }
  /**
   * @test
   */
  public function makeRevisionInfoAdditionReplacement()
  {
   $this->revisionDataProperties = array(
      'currentContent' => 'some test content revision',
    );
    $this->setUp();
    $expected = array(array(2, 3, ''), array(6, 6, 'content revision'));

    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some new test change'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWord()
  {
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test new content'));
    $expected = array(array(4, 5, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWords()
  {
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test new other content'));
    $expected = array(array(4, 7, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWord()
  {
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some content'));
    $expected = array(array(2, null, 'test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd()
  {
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some'));
    $expected = array(array(1, null, ' test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning()
  {
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('content'));
    $expected = array(array(0, null, 'some test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd2()
  {
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some more'));
    $expected = array(array(2, 2, 'test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning2()
  {
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('content'));
    $expected = array(array(0, null, 'some test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoChangedLetter()
  {
    $this->revisionDataProperties['currentContent'] = 'some testr content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some tests content'));
    $expected = array(array(2, 2, 'testr'));
    $this->assertSame($expected, $result);
  }


  /**
   * @test
   */
  public function makeRevisionInfoContentReplaced()
  {
    $this->revisionDataProperties['currentContent'] = 'some random testing content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test content'));
    $expected = array(array(2, 2, 'random testing'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentReplacedAndRemoved()
  {
    $this->revisionDataProperties['currentContent'] = 'some  tests content here and here';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test content'));
    $expected = array(array(1, 2, '  tests'), array(5, null, ' here and here'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAdded()
  {
    $this->revisionDataProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test content here and here'));
    $expected = array(array(5, 10, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedAndRemoved()
  {
    $this->revisionDataProperties['currentContent'] = 'some new test content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test content here'));
    $expected = array(array(1, null, ' new'), array(5, 6, ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedRemovedReplaced()
  {
    $this->revisionDataProperties['currentContent'] = 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('Hello, I am Billy. I am writing to test a new diff functions I wrote here.'));
    $expected = array(array(2, 4, 'my name is'), array(14, null, 'this '), array(18, 20, 'some'), array(29, 30, ''));

    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoNewContent()
  {
    $this->revisionDataProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test content'));
    $expected = array();
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringRevisionInfo()
  {
    $this->revisionDataProperties['currentContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeNonStringRevisionInfo', array(false));
    $expected = array(array(null, null, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeNonStringRevisionInfoSame()
  {
    $this->revisionDataProperties['currentContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeNonStringRevisionInfo', array(true));
    $expected = array();
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoBoolean()
  {
    $this->revisionDataProperties['currentContent'] = true;
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array(false));
    $expected = array(array(null, null, true));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoInt()
  {
    $this->revisionDataProperties['currentContent'] = 101001;
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array(100011));
    $expected = array(array(null, null, 101001));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRenderRevisionNewContent()
  {
    $this->revisionDataProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some test content'));
    $expected = array();
    $this->assertSame($expected, $result);
    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame('', $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDB()
  {
    $this->revisionDataProperties['currentContent'] = 'some random content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'renderRevisionForDB', array('some tests contentss'));
    $expected = json_encode(array(array(2, 4, 'random content')));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDBFirst()
  {
    $this->revisionDataProperties['currentContent'] = '';
    $this->setUp();
    $result = $this->call($this->revisionData, 'renderRevisionForDB', array(23));
    $expected = json_encode(array());
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDBSame()
  {
    $result = $this->call($this->revisionData, 'renderRevisionForDB', array('some test content'));
    $this->assertNull($result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRenderRevision()
  {
    $this->revisionDataProperties['currentContent'] = 'some random content';
    $this->setUp();
    $result = $this->call($this->revisionData, 'makeRevisionInfo', array('some tests contentss'));
    $expected = array(array(2, 4, 'random content'));
    $this->assertSame($expected, $result);
    $this->call($this->revisionData, 'populateObjectWithArray', array(array('revisionInfo' => $expected)));
    $expected = 'some random content';
    $result = $this->call($this->revisionData, 'renderRevision');
    $this->assertSame($expected, $result);
  }
}