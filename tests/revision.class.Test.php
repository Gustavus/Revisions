<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

use Gustavus\Revisions;

require_once '/cis/lib/test/test.class.php';
require_once 'revisions/classes/revision.class.php';

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
   * @var array to fill object with
   */
  private $revisionProperties = array(
    'currentContent' => 'some test content',
    'revisionInfo' => array(array(
      'revisionContent' => 'testing',
      'startIndex' => 2,
      'endIndex' => null,
    )),
  );

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revision = new Revisions\Revision($this->revisionProperties);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revision, $this->revisionProperties);
  }

  /**
   * @test
   */
  public function getCurrentContent()
  {
    $this->assertSame($this->revisionProperties['currentContent'], $this->revision->getCurrentContent());
  }

  /**
   * @test
   */
  public function getRevisionInfo()
  {
    $this->assertSame($this->revisionProperties['revisionInfo'], $this->revision->getRevisionInfo());
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

  /**
   * @test
   */
  public function renderRevision3()
  {
    $expected = 'some testing test content';
    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionDeletion()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some testing content',
      'revisionInfo' => array(array(
        'revisionContent' => 'test',
        'startIndex' => 2,
        'endIndex' => 2,
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionDeletionFromBeginning()
  {
    $this->revisionProperties = array(
      'currentContent' => 'test content',
      'revisionInfo' => array(array(
        'revisionContent' => 'some',
        'startIndex' => 0,
        'endIndex' => null,
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionMultipleDeletion()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some more testing content',
      'revisionInfo' => array(array(
        'revisionContent' => 'test',
        'startIndex' => 2,
        'endIndex' => 4,
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddition()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(array(
        'revisionContent' => 'random other testing',
        'startIndex' => 2,
        'endIndex' => 2,
      )),
    );
    $this->setUp();
    $expected = 'some random other testing content';

    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddToBegin()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(array(
        'revisionContent' => 'hello',
        'startIndex' => 0,
        'endIndex' => null,
      )),
    );
    $this->setUp();
    $expected = 'hello some test content';

    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionAddToMiddle()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some test content',
      'revisionInfo' => array(array(
        'revisionContent' => 'hello',
        'startIndex' => 2,
        'endIndex' => null,
      )),
    );
    $this->setUp();
    $expected = 'some hello test content';

    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionRemoved()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some',
      'revisionInfo' => array(array(
        'revisionContent' => ' test content',
        'startIndex' => 1,
        'endIndex' => null,
      )),
    );
    $this->setUp();
    $expected = 'some test content';

    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiff()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some test content',
    );
    $this->setUp();
    $expected = '<del>some</del><ins>new</ins> test content';

    $result = $this->call($this->revision, 'makeDiff', array('new test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffRemoval()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some test content',
    );
    $this->setUp();
    $expected = '<del>some </del>test content';

    $result = $this->call($this->revision, 'makeDiff', array('test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffAdditionReplacement()
  {
    $this->revisionProperties = array(
      'currentContent' => 'some test content revision',
    );
    $this->setUp();
    $expected = 'some <ins>new </ins>test <del>content revision</del><ins>change</ins>';

    $result = $this->call($this->revision, 'makeDiff', array('some new test change'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeDiffAdditionReplacementRemoval()
  {
    $this->revisionProperties = array(
      'currentContent' => 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.',
    );
    $this->setUp();
    $expected = 'Hello, <del>my name is</del><ins>I am</ins> Billy. I am writing <del>this </del>to test <del>some</del><ins>a new</ins> diff functions I wrote.';

    $result = $this->call($this->revision, 'makeDiff', array('Hello, I am Billy. I am writing to test a new diff functions I wrote.'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo()
  {
    $this->revisionProperties['currentContent'] = 'some testing test content';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some test content'));
    $expected = array(array('startIndex' => 1, 'endIndex' => null, 'revisionContent' => ' testing'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfo2()
  {
    $this->revisionProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some'));
    $expected = array(array('startIndex' => 1, 'endIndex' => null, 'revisionContent' => ' test content'));
    $this->assertSame($expected, $result);
  }
  /**
   * @test
   */
  public function makeRevisionInfoAdditionReplacement()
  {
   $this->revisionProperties = array(
      'currentContent' => 'some test content revision',
    );
    $this->setUp();
    $expected = array(array('startIndex' => 2, 'endIndex' => 3, 'revisionContent' => ''), array('startIndex' => 6, 'endIndex' => 6, 'revisionContent' => 'content revision'));

    $result = $this->call($this->revision, 'makeRevisionInfo', array('some new test change'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWord()
  {
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some test new content'));
    $expected = array(array('startIndex' => 4, 'endIndex' => 5, 'revisionContent' => ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoAddedWords()
  {
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some test new other content'));
    $expected = array(array('startIndex' => 4, 'endIndex' => 7, 'revisionContent' => ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWord()
  {
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some content'));
    $expected = array(array('startIndex' => 2, 'endIndex' => null, 'revisionContent' => 'test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd()
  {
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some'));
    $expected = array(array('startIndex' => 1, 'endIndex' => null, 'revisionContent' => ' test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning()
  {
    $result = $this->call($this->revision, 'makeRevisionInfo', array('content'));
    $expected = array(array('startIndex' => 0, 'endIndex' => null, 'revisionContent' => 'some test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromEnd2()
  {
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some more'));
    $expected = array(array('startIndex' => 2, 'endIndex' => 2, 'revisionContent' => 'test content'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRemovedWordsFromBeginning2()
  {
    $result = $this->call($this->revision, 'makeRevisionInfo', array('content'));
    $expected = array(array('startIndex' => 0, 'endIndex' => null, 'revisionContent' => 'some test '));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoChangedLetter()
  {
    $this->revisionProperties['currentContent'] = 'some testr content';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some tests content'));
    $expected = array(array('startIndex' => 2, 'endIndex' => 2, 'revisionContent' => 'testr'));
    $this->assertSame($expected, $result);
  }


  /**
   * @test
   */
  public function makeRevisionInfoContentReplaced()
  {
    $this->revisionProperties['currentContent'] = 'some random testing content';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some test content'));
    $expected = array(array('startIndex' => 2, 'endIndex' => 2, 'revisionContent' => 'random testing'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentReplacedAndRemoved()
  {
    $this->revisionProperties['currentContent'] = 'some  tests content here and here';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some test content'));
    $expected = array(array('startIndex' => 1, 'endIndex' => 2, 'revisionContent' => '  tests'), array('startIndex' => 5, 'endIndex' => null, 'revisionContent' => ' here and here'));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAdded()
  {
    $this->revisionProperties['currentContent'] = 'some test content';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some test content here and here'));
    $expected = array(array('startIndex' => 5, 'endIndex' => 10, 'revisionContent' => ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedAndRemoved()
  {
    $this->revisionProperties['currentContent'] = 'some new test content';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some test content here'));
    $expected = array(array('startIndex' => 1, 'endIndex' => null, 'revisionContent' => ' new'), array('startIndex' => 5, 'endIndex' => 6, 'revisionContent' => ''));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoContentAddedRemovedReplaced()
  {
    $this->revisionProperties['currentContent'] = 'Hello, my name is Billy. I am writing this to test some diff functions I wrote.';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('Hello, I am Billy. I am writing to test a new diff functions I wrote here.'));
    $expected = array(array('startIndex' => 2, 'endIndex' => 4, 'revisionContent' => 'my name is'), array('startIndex' => 14, 'endIndex' => null, 'revisionContent' => 'this '), array('startIndex' => 18, 'endIndex' => 20, 'revisionContent' => 'some'), array('startIndex' => 29, 'endIndex' => 30, 'revisionContent' => ''));

    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDB()
  {
    $this->revisionProperties['currentContent'] = 'some random content';
    $this->setUp();
    $result = $this->call($this->revision, 'renderRevisionForDB', array('some tests contentss'));
    $expected = json_encode(array(array('startIndex' => 2, 'endIndex' => 4, 'revisionContent' => 'random content')));
    $this->assertSame($expected, $result);
  }

  /**
   * @test
   */
  public function renderRevisionForDBSame()
  {
    $result = $this->call($this->revision, 'renderRevisionForDB', array('some test content'));
    $this->assertNull($result);
  }

  /**
   * @test
   */
  public function makeRevisionInfoRenderRevision()
  {
    $this->revisionProperties['currentContent'] = 'some random content';
    $this->setUp();
    $result = $this->call($this->revision, 'makeRevisionInfo', array('some tests contentss'));
    $expected = array(array('startIndex' => 2, 'endIndex' => 4, 'revisionContent' => 'random content'));
    $this->assertSame($expected, $result);
    $this->call($this->revision, 'populateObjectWithArray', array(array('revisionInfo' => $expected)));
    $expected = 'some random content';
    $result = $this->call($this->revision, 'renderRevision');
    $this->assertSame($expected, $result);
  }
}