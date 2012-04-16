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
class APITest extends RevisionsTestsHelper
{
  /**
   * @var string
   */
  private $error = "An unexpected error occured.";

  /**
   * @var RevisionsAPI
   */
  private $revisionsAPI;

  /**
   * @var Doctrine\DBAL connection
   */
  private $dbalConnection;

  /**
   * @var string DateTime string
   */
  private $date;

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->date = new \DateTime('-3 weeks');
    $this->revisionsAPI = new Revisions\API($this->revisionsManagerInfo);
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisionsAPI);
  }

  /**
   * @param string $tableName
   */
  private function setUpMock($tableName)
  {
    if (!isset($this->dbalConnection)) {
      $this->dbalConnection = \Gustavus\Doctrine\DBAL::getDBAL($tableName, self::$dbh);
    }

    $dbMock = $this->getMock('\Gustavus\Revisions\Revisions', array('getDB', 'getNowExpression'), array($this->revisionsManagerInfo));
    $dbMock->expects($this->any())
      ->method('getDB')
      ->will($this->returnValue($this->dbalConnection));
    $date = $this->date->format('Y-m-d H:i:s');
    $dbMock->expects($this->any())
      ->method('getNowExpression')
      ->will($this->returnValue("'{$date}'"));

    $this->set($this->revisionsAPI, 'revisions', $dbMock);

    // $this->set($this->revisionsAPI, 'revisions', $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array($this->revisionsManagerInfo), $this->dbalConnection));
  }

  private function getDateTitle()
  {
    return $this->date->format('c');
  }

  /**
   * @test
   */
  public function testConstruction()
  {
    $this->assertInstanceOf('Gustavus\Revisions\Revisions', $this->get($this->revisionsAPI, 'revisions'));
  }

  /**
   * @test
   * @expectedException RuntimeException
   */
  public function testConstructionException()
  {
    $this->revisionsAPI = new Revisions\API();
    $this->assertInstanceOf('Gustavus\Revisions\Revisions', $this->get($this->revisionsAPI, 'revisions'));
  }

  /**
   * @test
   */
  public function saveRevision()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->assertTrue($this->revisionsAPI->saveRevision(array('name' => 'Billy Visto')));
    $this->assertTrue($this->revisionsAPI->saveRevision(array('name' => 'Billy Visto')));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getOldestRevisionNumberToPullFromURL()
  {
    $this->assertSame('1', $this->call($this->revisionsAPI, 'getOldestRevisionNumberToPullFromURL', array(array('oldestRevisionNumber' => '1'))));
  }

  /**
   * @test
   */
  public function getOldestRevisionNumberToPullFromURLNull()
  {
    $this->assertNull($this->call($this->revisionsAPI, 'getOldestRevisionNumberToPullFromURL', array(array())));
  }

  /**
   * @test
   */
  public function renderRevisions()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"0\" />
        <input type=\"hidden\" name=\"limit\" value=\"10\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1\" title=\"Modified 3 weeks ago by\" data-revision-number=\"1\">1</th>
                <th class=\"2\" title=\"Modified 3 weeks ago by\" data-revision-number=\"2\">2</th>
                <th class=\"3\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"bytes 1\" data-revision-number=\"1\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                <td class=\"bytes 1\" data-revision-number=\"1\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class=\"compare\">
                <th> </th>
                <td class=\"1\">
                  <label for=\"revisionNum-1\">
                    <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 1\" class=\"compare\" value=\"1\"/>
                  </label>
                </td>
                <td class=\"2\">
                  <label for=\"revisionNum-2\">
                    <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
                  </label>
                </td>
                <td class=\"3\">
                  <label for=\"revisionNum-3\">
                    <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
                  </label>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div id=\"formExtras\"></div>
    </form>";

    $_GET = array('limit' => 10);
    $actual = $this->revisionsAPI->render();

    // $echo = str_replace('"', '\"', str_replace('&nbsp;', ' ', $actual));
    // echo "<pre>$echo</pre>";
    // exit;

    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionsNoLimitUntilRevision3()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"3\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1\" title=\"Look at revision 1\" data-revision-number=\"1\">1</th>
                <th class=\"2\" title=\"Look at revision 2\" data-revision-number=\"2\">2</th>
                <th class=\"3\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"missingRevisions bytes 1\" data-oldest-revision-number=\"1\" title=\"Show More Revisions\"></td>
                <td class=\"missingRevisions bytes 2\" data-oldest-revision-number=\"2\" title=\"Show More Revisions\"></td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                <td class=\"missingRevisions bytes 1\" data-oldest-revision-number=\"1\" title=\"Show More Revisions\"></td>
                <td class=\"missingRevisions bytes 2\" data-oldest-revision-number=\"2\" title=\"Show More Revisions\"></td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class=\"compare\">
                <th> </th>
                <td class=\"1\"></td>
                <td class=\"2\"></td>
                <td class=\"3\">
                  <label for=\"revisionNum-3\">
                    <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
                  </label>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div id=\"formExtras\"></div>
    </form>";

    $_GET = array('oldestRevisionNumber' => 3);
    $actual = $this->revisionsAPI->render();

    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionsError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"1\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1\" title=\"Modified 3 weeks ago by\" data-revision-number=\"1\">1</th>
                <th class=\"2\" title=\"Modified 3 weeks ago by name\" data-revision-number=\"2\">2</th>
                <th class=\"3\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
                <th class=\"4\" title=\"Modified 3 weeks ago by\" data-revision-number=\"4\">4</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"error\">An unexpected error occured.</td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 4\" data-revision-number=\"4\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                <td class=\"error\">An unexpected error occured.</td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                    <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:100%;\"></span>
                    <span class=\"bytes added\" title=\"6 Bytes added\" style=\"height:54.545454545455%;\"></span>
                  </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 4\" data-revision-number=\"4\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class=\"compare\"><th> </th>
                <td class=\"1\"></td>
                <td class=\"2\">
                  <label for=\"revisionNum-2\">
                    <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
                  </label>
                </td>
                <td class=\"3\">
                  <label for=\"revisionNum-3\">
                    <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
                  </label>
                </td>
                <td class=\"4\">
                  <label for=\"revisionNum-4\">
                    <input id=\"revisionNum-4\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 4\" class=\"compare\" value=\"4\"/>
                  </label>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div id=\"formExtras\"></div>
    </form>";

    $_GET = array('oldestRevisionNumber' => 1);
    $actual = $this->revisionsAPI->render();

    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonText()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"1\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1 old\" title=\"Modified 3 weeks ago by\" data-revision-number=\"1\">1</th>
                <th class=\"2\" title=\"Modified 3 weeks ago by\" data-revision-number=\"2\">2</th>
                <th class=\"3 young\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"bytes 1 old\" data-revision-number=\"1\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3 young\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                <td class=\"bytes 1 old\" data-revision-number=\"1\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3 young\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
              </tbody>
              <tfoot>
                <tr class=\"compare\">
                  <th> </th>
                  <td class=\"1 old\">
                    <label for=\"revisionNum-1\">
                      <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 1\" class=\"compare\" value=\"1\" checked=\"checked\"/>
                    </label>
                  </td>
                  <td class=\"2\">
                    <label for=\"revisionNum-2\">
                      <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
                    </label>
                  </td>
                  <td class=\"3 young\">
                    <label for=\"revisionNum-3\">
                      <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\" checked=\"checked\" />
                    </label>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div id=\"formExtras\"><section class=\"clearfix revisionData comparison\">
          <div class=\"clearfix headers\">
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
          </div>
          <dl class=\"clearfix\">
            <dt title=\"age\">age</dt>
              <dd><ins>23</ins></dd>
              <dd><ins>23</ins></dd>
            <dt title=\"name\">name</dt>
              <dd><del>Billy </del>Visto</dd>
              <dd><del>Billy </del>Visto</dd>
          </dl>
          <footer>
            <button name=\"restore\" value=\"1\" id=\"restore-1\" class=\"restore\" title=\"Restore Revision 1\">Restore #1</button>
          </footer>
          <footer>
            <button name=\"restore\" value=\"3\" id=\"restore-3\" class=\"restore disabled\" title=\"Restore Revision 3\">Restore #3</button>
          </footer>
        </section>
      </div>
    </form>";
    $_GET = array('revisionNumbers' => array(1,3));
    $actual = $this->revisionsAPI->render();

    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonTextColumns()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"1\" />
        <input type=\"hidden\" name=\"columns[0]\" value=\"name\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1 old\" title=\"Modified 3 weeks ago by\" data-revision-number=\"1\">1</th>
                <th class=\"2\" title=\"Modified 3 weeks ago by\" data-revision-number=\"2\">2</th>
                <th class=\"3 young\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"bytes 1 old\" data-revision-number=\"1\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3 young\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                <td class=\"bytes 1 old\" data-revision-number=\"1\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3 young\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
              </tbody>
              <tfoot>
                <tr class=\"compare\">
                  <th> </th>
                  <td class=\"1 old\">
                    <label for=\"revisionNum-1\">
                      <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 1\" class=\"compare\" value=\"1\" checked=\"checked\"/>
                    </label>
                  </td>
                  <td class=\"2\">
                    <label for=\"revisionNum-2\">
                      <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
                    </label>
                  </td>
                  <td class=\"3 young\">
                    <label for=\"revisionNum-3\">
                      <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\" checked=\"checked\"/>
                    </label>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div id=\"formExtras\"><section class=\"clearfix revisionData comparison\">
          <div class=\"clearfix headers\">
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
          </div>
          <dl class=\"clearfix\">
            <dt title=\"name\">name</dt>
              <dd><del>Billy </del>Visto</dd>
              <dd><del>Billy </del>Visto</dd>
          </dl>
          <footer>
            <button name=\"restore\" value=\"1\" id=\"restore-1\" class=\"restore\" title=\"Restore Revision 1\">Restore #1</button>
          </footer>
          <footer>
            <button name=\"restore\" value=\"3\" id=\"restore-3\" class=\"restore disabled\" title=\"Restore Revision 3\">Restore #3</button>
          </footer>
        </section>
      </div>
    </form>";

    $_GET = array('revisionNumbers' => array(1,3), 'columns' => array('name'));
    $actual = $this->revisionsAPI->render();

    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionComparisonTextError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"2\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1 old\" title=\"Look at revision 1\" data-revision-number=\"1\">1</th>
                <th class=\"2\" title=\"Modified 3 weeks ago by name\" data-revision-number=\"2\">2</th>
                <th class=\"3 young\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
                <th class=\"4\" title=\"Modified 3 weeks ago by\" data-revision-number=\"4\">4</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"error\">An unexpected error occured.</td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3 young\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 4\" data-revision-number=\"4\">
                  <span class=\"bytes container\">
                      <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                <td class=\"error\">An unexpected error occured.</td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"6 Bytes added\" style=\"height:54.545454545455%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3 young\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 4\" data-revision-number=\"4\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class=\"compare\">
                <th> </th>
                <td class=\"1\"></td>
                <td class=\"2\">
                  <label for=\"revisionNum-2\">
                    <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
                  </label>
                </td>
                <td class=\"3 young\">
                  <label for=\"revisionNum-3\">
                    <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\" checked=\"checked\"/>
                  </label>
                </td>
                <td class=\"4\">
                  <label for=\"revisionNum-4\">
                    <input id=\"revisionNum-4\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 4\" class=\"compare\" value=\"4\"/>
                  </label>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div id=\"formExtras\">
        <section class=\"clearfix revisionData comparison\">
          <div class=\"clearfix headers\">
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
          </div>
          <dl class=\"clearfix\">
            <dt title=\"age\">age</dt>
              <dd></dd>
              <dd></dd>
            <dt class=\"error\" title=\"name\">name</dt>
              <dd>An unexpected error occured.</dd>
              <dd>An unexpected error occured.</dd>
          </dl>
          <footer>
            <button name=\"restore\" value=\"1\" id=\"restore-1\" class=\"restore\" title=\"Restore Revision 1\">Restore #1</button></footer><footer><button name=\"restore\" value=\"3\" id=\"restore-3\" class=\"restore\" title=\"Restore Revision 3\">Restore #3</button>
          </footer>
        </section>
      </div>
    </form>";

    $_GET = array('revisionNumbers' => array(1,3));
    $actual = $this->revisionsAPI->render();
    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionData()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"3\" />
        <input type=\"hidden\" name=\"revisionNumber\" value=\"2\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1\" title=\"Look at revision 1\" data-revision-number=\"1\">1</th>
                <th class=\"2 young\" title=\"Modified 3 weeks ago by\" data-revision-number=\"2\">2</th>
                <th class=\"3\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"missingRevisions bytes 1\" data-oldest-revision-number=\"1\" title=\"Show More Revisions\"></td>
                <td class=\"bytes 2 young\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                <td class=\"missingRevisions bytes 1\" data-oldest-revision-number=\"1\" title=\"Show More Revisions\"></td>
                <td class=\"bytes 2 young\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                      <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class=\"compare\">
                <th> </th>
                <td class=\"1\"></td>
                <td class=\"2 young\">
                  <label for=\"revisionNum-2\">
                    <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\" checked=\"checked\"/>
                  </label>
                </td>
                <td class=\"3\">
                  <label for=\"revisionNum-3\">
                    <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
                  </label>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div id=\"formExtras\">
        <section class=\"clearfix revisionData\">
          <div class=\"clearfix headers\">
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
          </div>
          <dl class=\"clearfix\">
            <dt title=\"name\">name</dt>
              <dd>Billy Visto</dd>
          </dl>
          <footer>
            <button name=\"restore\" value=\"2\" id=\"restore-2\" class=\"restore\" title=\"Restore Revision 2\">Restore #2</button>
          </footer>
        </section>
      </div>
    </form>";

    $_GET = array('revisionNumber' => '2', 'oldestRevisionNumber' => '3');
    $actual = $this->revisionsAPI->render();
    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionDataError()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"2\" />
        <input type=\"hidden\" name=\"revisionNumber\" value=\"1\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1 young\" title=\"Look at revision 1\" data-revision-number=\"1\">1</th>
                <th class=\"2\" title=\"Modified 3 weeks ago by name\" data-revision-number=\"2\">2</th>
                <th class=\"3\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
                <th class=\"4\" title=\"Modified 3 weeks ago by\" data-revision-number=\"4\">4</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"error\">An unexpected error occured.</td>
                <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 4\" data-revision-number=\"4\">
                  <span class=\"bytes container\">
                      <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                  <td class=\"error\">An unexpected error occured.</td>
                  <td class=\"bytes 2\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"6 Bytes added\" style=\"height:54.545454545455%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                      <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 4\" data-revision-number=\"4\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class=\"compare\">
                <th> </th>
                <td class=\"1\"></td>
                <td class=\"2\">
                  <label for=\"revisionNum-2\">
                    <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
                  </label>
                </td>
                <td class=\"3\">
                  <label for=\"revisionNum-3\">
                    <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
                  </label>
                </td>
                <td class=\"4\">
                  <label for=\"revisionNum-4\">
                    <input id=\"revisionNum-4\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 4\" class=\"compare\" value=\"4\"/>
                  </label>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div id=\"formExtras\">
        <section class=\"clearfix revisionData\">
          <div class=\"clearfix headers\">
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
          </div>
          <dl class=\"clearfix\" />
          <footer>
            <button name=\"restore\" value=\"1\" id=\"restore-1\" class=\"restore\" title=\"Restore Revision 1\">Restore #1</button>
          </footer>
        </section>
      </div>
    </form>";

    $_GET = array('revisionNumber' => '1');
    $actual = $this->revisionsAPI->render();
    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionDataColumn()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"GET\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"2\" />
        <input type=\"hidden\" name=\"revisionNumber\" value=\"false\" />
        <input type=\"hidden\" name=\"columns[0]\" value=\"name\" />
      </div>
      <div id=\"revisionTimeline\">
        <h4>Revision History</h4>
        <div class=\"labels\">
          <div>age</div>
          <div>name</div>
          <div>
            <button id=\"compareButton\" class=\"positive\" name=\"revisionNumber\" value=\"false\">Compare</button>
          </div>
        </div>
        <div class=\"viewport\">
          <span class=\"scrollHotspot scrollLeft disabled\">◂</span>
          <span class=\"scrollHotspot scrollRight disabled\">▸</span>
          <table class=\"fancy\">
            <thead>
              <tr>
                <th> </th>
                <th class=\"1\" title=\"Look at revision 1\" data-revision-number=\"1\">1</th>
                <th class=\"2 young\" title=\"Modified 3 weeks ago by\" data-revision-number=\"2\">2</th>
                <th class=\"3\" title=\"Modified 3 weeks ago by\" data-revision-number=\"3\">3</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>age</th>
                <td class=\"missingRevisions bytes 1\" data-oldest-revision-number=\"1\" title=\"Show More Revisions\"></td>
                <td class=\"bytes 2 young\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\"></span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"\" style=\"height:100%;\"></span>
                      <span class=\"bytes added\" title=\"added 23\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
              </tr>
              <tr>
                <th>name</th>
                  <td class=\"missingRevisions bytes 1\" data-oldest-revision-number=\"1\" title=\"Show More Revisions\"></td>
                  <td class=\"bytes 2 young\" data-revision-number=\"2\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"></span>
                    </span>
                    <span class=\"bytes negative\"></span>
                  </span>
                </td>
                <td class=\"bytes 3\" data-revision-number=\"3\">
                  <span class=\"bytes container\">
                    <span class=\"bytes positive\">
                      <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"></span>
                    </span>
                    <span class=\"bytes negative\">
                      <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:54.545454545455%;\"></span>
                    </span>
                  </span>
                </td>
              </tr>
              </tbody>
              <tfoot>
                <tr class=\"compare\"><th> </th>
                  <td class=\"1\"></td>
                  <td class=\"2 young\">
                    <label for=\"revisionNum-2\">
                      <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 2\" class=\"compare\" value=\"2\" checked=\"checked\"/>
                    </label>
                  </td>
                  <td class=\"3\">
                    <label for=\"revisionNum-3\">
                      <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbers[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
                    </label>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div id=\"formExtras\">
          <section class=\"clearfix revisionData\">
            <div class=\"clearfix headers\">
              <header>
                <hgroup title=\"{$this->getDateTitle()}\">
                  <h1></h1>
                  <h2>3 weeks ago</h2>
                  <h2></h2>
                </hgroup>
              </header>
            </div>
          <dl class=\"clearfix\">
            <dt title=\"name\">name</dt>
              <dd>Billy Visto</dd>
          </dl>
          <footer>
            <button name=\"restore\" value=\"2\" id=\"restore-2\" class=\"restore\" title=\"Restore Revision 2\">Restore #2</button>
          </footer>
        </section>
      </div>
    </form>";

    $_GET = array('revisionNumbers' => array('2'), 'revisionNumber' => 'false', 'columns' => array('name'));
    $actual = $this->revisionsAPI->render();
    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionRestore()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"POST\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"3\" />
        <input type=\"hidden\" name=\"restore\" value=\"2\" />
      </div>
      <div id=\"revisionTimeline\"></div>
      <div id=\"formExtras\">
        <p class=\"message\">You are restoring to revision #2</p>
        <section class=\"clearfix revisionData\">
          <div class=\"clearfix headers\">
            <header>
              <hgroup title=\"{$this->getDateTitle()}\">
                <h1></h1>
                <h2>3 weeks ago</h2>
                <h2></h2>
              </hgroup>
            </header>
          </div>
          <dl class=\"clearfix\">
            <dt title=\"age\">age</dt>
              <dd><ins>23</ins></dd>
            <dt title=\"name\">name</dt>
              <dd><del>Billy </del>Visto</dd>
          </dl>
        </section>
        <button id=\"restoreButton\" class=\"positive\" type=\"submit\" name=\"restore\" value=\"2\">Confirm Restore</button>
      </div>
    </form>";

    $_GET = array('restore' => '2');
    $actual = $this->revisionsAPI->render();
    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function renderRevisionThankYou()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $expected = "
    <form id=\"revisionsForm\" method=\"POST\">
      <div id=\"hiddenFields\">
        <input id=\"oldestRevisionNumber\" type=\"hidden\" name=\"oldestRevisionNumber\" value=\"2\" />
        <input type=\"hidden\" name=\"revisionsAction\" value=\"thankYou\" />
      </div>
      <div id=\"formExtras\"><button type=\"submit\" name=\"revisionsAction\" value=\"undo\">Undo</button></div>
    </form>";

    $_GET = array('oldestRevisionNumber' => '2', 'revisionsAction' => 'thankYou');
    $actual = $this->revisionsAPI->render();
    $this->assertXmlStringEqualsXmlString($expected, str_replace('&nbsp;', ' ', $actual));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @test
   */
  public function getRevisionsUrlParams()
  {
    $actual = $this->call($this->revisionsAPI, 'getRevisionsUrlParams', array(array('revisionsAction' => 'revision', 'limit' => 10, 'pr' => 'manage')));
    $expected = array('revisionsAction' => 'revision', 'limit' => 10);
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function getApplicationUrlParams()
  {
    $actual = $this->call($this->revisionsAPI, 'getApplicationUrlParams', array(array('revisionsAction' => 'revision', 'limit' => 10, 'pr' => 'manage')));
    $expected = array('pr' => 'manage');
    $this->assertSame($expected, $actual);
  }

  /**
   * @test
   */
  public function isRestore()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'isRestore', array(array('restore' => '1'))));
  }

  /**
   * @test
   */
  public function isRestoreFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'isRestore', array(array('revisionsAction' => 'revision', 'limit' => 10))));
  }

  /**
   * @test
   */
  public function isRevisionData()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'isRevisionData', array(array('revisionNumber' => '1'))));
  }

  /**
   * @test
   */
  public function isRevisionDataFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'isRevisionData', array(array('revisionsAction' => 'revision', 'limit' => 10))));
  }

  /**
   * @test
   */
  public function isThankYou()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'isThankYou', array(array('revisionsAction' => 'thankYou'))));
  }

  /**
   * @test
   */
  public function isThankYouFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'isThankYou', array(array('revisionsAction' => 'revision', 'limit' => 10))));
  }

  /**
   * @test
   */
  public function isUndo()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'isUndo', array(array('revisionsAction' => 'undo'))));
  }

  /**
   * @test
   */
  public function isUndoFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'isUndo', array(array('revisionsAction' => 'revision', 'limit' => 10))));
  }

  /**
   * @test
   */
  public function isComparison()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'isComparison', array(array('revisionNumbers' => array(1, 2)))));
  }

  /**
   * @test
   */
  public function isComparisonFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'isComparison', array(array('revisionsAction' => 'revision', 'limit' => 10))));
  }

  /**
   * @test
   */
  public function isSingleComparison()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'isSingleComparison', array(array('revisionNumbers' => array(1), 'revisionNumber' => 'false'))));
  }

  /**
   * @test
   */
  public function isSingleComparisonFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'isSingleComparison', array(array('revisionNumbers' => array('1', '2'), 'limit' => 10))));
  }

  /**
   * @test
   */
  public function handleRestoreAction()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

    $_POST = array('restore' => '2');

    $this->assertNull($this->get($this->revisionsAPI, 'revisions')->getRevisionByNumber(4));
    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->get($this->revisionsAPI, 'revisions')->getRevisionByNumber(3));

    \Gustavus\Extensibility\Actions::add(Revisions\API::RESTORE_HOOK, array($this, 'restore'));
    $this->revisionsAPI->render();

    // simulate page loaded and object deconstruction
    $this->setUpMock('person-revision');

    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->get($this->revisionsAPI, 'revisions')->getRevisionByNumber(4));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * @depends handleRestoreAction
   * @test
   */
  public function handleUndoAction()
  {
    $conn = $this->getConnection();
    $this->setUpMock('person-revision');
    $this->dbalConnection->query($this->getCreateQuery());
    $this->dbalConnection->query($this->getCreateDataQuery());

    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
    $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
    $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));

    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->get($this->revisionsAPI, 'revisions')->getRevisionByNumber(4));

    $this->assertNull($this->get($this->revisionsAPI, 'revisions')->getRevisionByNumber(5));

    $_POST = array('revisionsAction' => 'undo');

    // simulate page loaded and object deconstruction
    $this->setUpMock('person-revision');

    $this->revisionsAPI->render();

    // simulate page loaded and object deconstruction
    $this->setUpMock('person-revision');

    $this->assertInstanceOf('\Gustavus\Revisions\Revision', $this->get($this->revisionsAPI, 'revisions')->getRevisionByNumber(5));
    $this->dropCreatedTables(array('person-revision', 'revisionData'));
  }

  /**
   * function to test restoring
   *
   * @param array $revisionContent
   * @param string $oldMessage
   * @return void
   */
  public function restore(array $revisionContent, $oldMessage = '')
  {
    if (!isset($this->revisionsAPI)) {
      $this->setUp();
      $this->setUpMock('person-revision');
    }
    $this->revisionsAPI->saveRevision($revisionContent, $oldMessage, 'bvisto');
  }

  /**
   * @test
   */
  public function arrayMin()
  {
    $this->assertSame(1, $this->call($this->revisionsAPI, 'arrayMin', array(array('1', '2'))));
  }

  /**
   * @test
   */
  public function shouldRenderTimelineFalse()
  {
    $urlParams = array('oldestRevisionNumber' => '4', 'revisionNumber' => '4', 'barebones' => 'true', 'oldestRevisionInTimeline' => '4', 'visibleRevisions' => array(9));
    $this->assertFalse($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function timelineParamsExistAndNotRestore()
  {
    $urlParams = array('oldestRevisionNumber' => '4', 'revisionNumber' => '4', 'barebones' => 'true', 'visibleRevisions' => array(9));
    $this->assertFalse($this->call($this->revisionsAPI, 'timelineParamsExistAndNotRestore', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderTimelineFalseComparison()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'patrick', 'oldestRevisionNumber' => '0', 'revisionNumber' => 'false', 'revisionNumbers' => array('4', '6'), 'barebones' => 'true', 'oldestRevisionInTimeline' => '1', 'visibleRevisions' => array('4', '7'));
    $this->assertFalse($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderTimeline()
  {
    $urlParams = array('oldestRevisionNumber' => '2', 'revisionNumber' => '10', 'barebones' => 'true', 'oldestRevisionInTimeline' => '2');
    $this->assertFalse($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderTimelineBackToEmptyData()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '1', 'visibleRevisions' => array('39'));
    $this->assertFalse($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderTimelineBackToEmptyDataNotFullTimeline()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '11', 'visibleRevisions' => array('39'));
    $this->assertFalse($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionNumberIsInTimelineNoRevisionNumber()
  {
    $urlParams = array('oldestRevisionNumber' => '2', 'revisionNumbers' => array('3', '4'), 'barebones' => 'true', 'oldestRevisionInTimeline' => '2');
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionNumberIsInTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderTimelineNoOldestRevisionNumberModified()
  {
    $urlParams = array('pr' => 'manage',
      'user' => 'bvisto',
      'oldestRevisionNumber' => '6',
      'revisionNumber' => '4',
      'barebones' => 'true',
      'oldestRevisionInTimeline' => '6',
      'visibleRevisions' => array('7'),
    );
    $this->assertTrue($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderTimelineTrue()
  {
    $urlParams = array('revisionNumber' => '8', 'barebones' => 'true', 'oldestRevisionInTimeline' => '10');
    $this->assertTrue($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderTimelineOldestRevisionNumberTrue()
  {
    $urlParams = array('revisionNumber' => '11', 'barebones' => 'true', 'oldestRevisionInTimeline' => '10', 'oldestRevisionNumber' => '8');
    $this->assertTrue($this->call($this->revisionsAPI, 'shouldRenderTimeline', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderRevisionDataBackToEmptyData()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '1', 'visibleRevisions' => array('39'));
    $this->assertTrue($this->call($this->revisionsAPI, 'shouldRenderRevisionData', array($urlParams)));
  }

  /**
   * @test
   */
  public function noRevisionSpecified()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '1', 'visibleRevisions' => array('39'));
    $this->assertFalse($this->call($this->revisionsAPI, 'noRevisionSpecified', array($urlParams)));
  }

  /**
   * @test
   */
  public function noRevisionSpecifiedNoVisibleRevisions()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '1');
    $this->assertTrue($this->call($this->revisionsAPI, 'noRevisionSpecified', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionIsOnlyVisibleBackToEmptyData()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '1', 'visibleRevisions' => array('39'));
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionIsOnlyVisible', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionsAreVisibleBackToEmptyData()
  {
    $urlParams = array('pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '1', 'visibleRevisions' => array('39'));
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionsAreVisible', array($urlParams)));
  }


  /**
   * @test
   */
  public function shouldRenderRevisionData()
  {
    $urlParams = array('oldestRevisionNumber' => '2', 'revisionNumber' => '10', 'visibleRevisions' => array('10'), 'barebones' => 'true');
    $this->assertFalse($this->call($this->revisionsAPI, 'shouldRenderRevisionData', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderRevisionDataTrue()
  {
    $urlParams = array('oldestRevisionNumber' => '2', 'revisionNumber' => '10', 'visibleRevisions' => array('11'), 'barebones' => 'true');
    $this->assertTrue($this->call($this->revisionsAPI, 'shouldRenderRevisionData', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderRevisionDataTrueMultipleVisible()
  {
    $urlParams = array('oldestRevisionNumber' => '2', 'revisionNumber' => '10', 'visibleRevisions' => array('11', '10'), 'barebones' => 'true');
    $this->assertTrue($this->call($this->revisionsAPI, 'shouldRenderRevisionData', array($urlParams)));
  }

  /**
   * @test
   */
  public function shouldRenderRevisionDataNoRevisionSpecified()
  {
    $urlParams = array('oldestRevisionNumber' => '4', 'pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '7');
    $this->assertFalse($this->call($this->revisionsAPI, 'shouldRenderRevisionData', array($urlParams)));
  }

  /**
   * @test
   */
  public function noRevisionsSpecified()
  {
    $urlParams = array('oldestRevisionNumber' => '4', 'pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'oldestRevisionInTimeline' => '7');
    $this->assertTrue($this->call($this->revisionsAPI, 'noRevisionsSpecified', array($urlParams)));
  }

  /**
   * @test
   */
  public function noRevisionSpecifiedFalse()
  {
    $urlParams = array('revisionNumber' => '4', 'pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', 'revisionNumbers' => array('7', '8'));
    $this->assertFalse($this->call($this->revisionsAPI, 'noRevisionsSpecified', array($urlParams)));
  }

  /**
   * @test
   */
  public function noRevisionSpecifiedRevisionNumber()
  {
    $urlParams = array('revisionNumber' => '4', 'pr' => 'manage', 'user' => 'bvisto', 'barebones' => 'true', );
    $this->assertFalse($this->call($this->revisionsAPI, 'noRevisionsSpecified', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionIsOnlyVisible()
  {
    $urlParams = array(
      'revisionNumber'    => '2',
      'visibleRevisions'  => array('2'),
    );
    $this->assertTrue($this->call($this->revisionsAPI, 'revisionIsOnlyVisible', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionIsOnlyVisibleFalse()
  {
    $urlParams = array(
      'revisionNumber'    => '2',
      'visibleRevisions'  => array('3'),
    );
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionIsOnlyVisible', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionIsOnlyVisibleFalseMultipleVisible()
  {
    $urlParams = array(
      'revisionNumber'    => '2',
      'visibleRevisions'  => array('2', '3'),
    );
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionIsOnlyVisible', array($urlParams)));
  }

  /**
   * @test
   */
  public function elementIsOnlyOneInArray()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'elementIsOnlyOneInArray', array('2', array('2'))));
  }

  /**
   * @test
   */
  public function elementIsOnlyOneInArrayFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'elementIsOnlyOneInArray', array('2', array('3'))));
  }

  /**
   * @test
   */
  public function elementIsOnlyOneInArrayFalseMultipleVisible()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'elementIsOnlyOneInArray', array('2', array('2', '3'))));
  }

  /**
   * @test
   */
  public function revisionsAreVisible()
  {
    $urlParams = array(
      'revisionNumbers' => array('2'),
      'visibleRevisions' => array('2'),
    );
    $this->assertTrue($this->call($this->revisionsAPI, 'revisionsAreVisible', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionsAreVisibleComplex()
  {
    $urlParams = array(
      'revisionNumbers' => array('3', '2'),
      'visibleRevisions' => array('2', '3'),
    );
    $this->assertTrue($this->call($this->revisionsAPI, 'revisionsAreVisible', array($urlParams)));
  }

  /**
   * @test
   */
  public function revisionsAreNotVisible()
  {
    $urlParams = array(
      'revisionNumbers' => array('2'),
      'visibleRevisions' => array('3'),
    );
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionsAreVisible', array($urlParams)));
  }
}