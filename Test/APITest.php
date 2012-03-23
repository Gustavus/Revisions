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
   * @var string
   */
  private $appUrl = 'https://gustavus.edu/billy';

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
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

    $this->set($this->revisionsAPI, 'revisions', $this->getMockWithDB('\Gustavus\Revisions\Revisions', 'getDB', array($this->revisionsManagerInfo), $this->dbalConnection));
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

//   /**
//    * @test
//    */
//   public function renderRevisions()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th/>
//         <th class=\"1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\">
//           <label for=\"revisionNum-1\">
//             <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 1\" class=\"compare\" value=\"1\"/>
//           </label>
//         </td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\"/>
// </form>";

//     $urlParams = array('limit' => 10);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionsTryingForRevisionAndComparison()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th/>
//         <th class=\"1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\">
//           <label for=\"revisionNum-1\">
//             <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 1\" class=\"compare\" value=\"1\"/>
//           </label>
//         </td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\"/>
// </form>";

//     $urlParams = array('limit' => 10, 'revisionsAction' => 'revision');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);

//     // try to do a comparison, but realize it doesnt have the info, falls back to revisions
//     $urlParams = array('limit' => 10, 'revisionsAction' => 'text');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }


//   /**
//    * @test
//    */
//   public function renderRevisionsNoLimitUntilRevision3()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th>
//           <a id=\"showMoreRevisions\" href=\"$this->appUrl?oldestRevisionNumber=2\" class=\"button small\" title=\"Show Revision  in table\">Show More Revisions</a>
//         </th>
//         <th class=\"1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?oldestRevisionNumber=1\" class=\"missingRevisions button\" title=\"Show Revision 1 in table\"/>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?oldestRevisionNumber=2\" class=\"missingRevisions button\" title=\"Show Revision 2 in table\"/>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?oldestRevisionNumber=1\" class=\"missingRevisions button\" title=\"Show Revision 1 in table\"/>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?oldestRevisionNumber=2\" class=\"missingRevisions button\" title=\"Show Revision 2 in table\"/>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\"></td>
//         <td class=\"2\"></td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\"/>
// </form>";

//     $urlParams = array('oldestRevisionNumber' => 3);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionsError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th/>
//         <th class=\"1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//         <th class=\"4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">4</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"error\">$this->error</td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" class=\"revision\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"error\">$this->error</td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"6 Bytes added\" style=\"height:54.545454545455%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" class=\"revision\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\"></td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//         <td class=\"4\">
//           <label for=\"revisionNum-4\">
//             <input id=\"revisionNum-4\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 4\" class=\"compare\" value=\"4\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\"/>
// </form>";
//     $urlParams = array('oldestRevisionNumber' => 1);
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }
//   /**
//    * @test
//    */
//   public function renderRevisionsRevisionsActionNotAccepted()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th/>
//         <th class=\"1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\">
//           <label for=\"revisionNum-1\">
//             <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 1\" class=\"compare\" value=\"1\"/>
//           </label>
//         </td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\"/>
// </form>";

//     $urlParams = array('limit' => 10, 'revisionsAction' => 'randomAction');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonText()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $now = date("F jS \\a\\t g:ia");
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <h4>Revision History</h4>
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th/>
//         <th class=\"1 old\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1 old\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1 old\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"12 Bytes removed\" style=\"height:240%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1 old\">
//           <label for=\"revisionNum-1\">
//             <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 1\" class=\"compare\" value=\"1\" checked=\"checked\"/>
//           </label>
//         </td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\">
//   <section class=\"clearfix revisionData comparison\">
//     <div class=\"clearfix headers\">
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//     </div>
//     <dl class=\"clearfix\">
//       <dt>age</dt>
//       <dd>
//         <ins>23</ins>
//       </dd>
//       <dd>
//         <ins>23</ins>
//       </dd>
//       <dt>name</dt>
//       <dd><del>Billy </del>Visto</dd>
//       <dd><del>Billy </del>Visto</dd>
//     </dl>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1&amp;restore=true\" id=\"restore-1\" class=\"restore button\" data-revisionNumber=\"1\" title=\"Restore Revision 1\">Restore #1</a>
//     </footer>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4&amp;restore=true\" id=\"restore-4\" class=\"restore button\" data-revisionNumber=\"4\" title=\"Restore Revision 4\">Restore #4</a>
//     </footer>
//   </section>
//   </div>
// </form>";
//     $urlParams = array('revisionNumbersToCompare' => array(1,4), 'revisionsAction' => 'text');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonTextColumns()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $now = date("F jS \\a\\t g:ia");
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <h4>Revision History</h4>
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th/>
//         <th class=\"1 old\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1 old\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1 old\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" class=\"revision\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"11 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"12 Bytes removed\" style=\"height:240%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1 old\">
//           <label for=\"revisionNum-1\">
//             <input id=\"revisionNum-1\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 1\" class=\"compare\" value=\"1\" checked=\"checked\"/>
//           </label>
//         </td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\">
//   <section class=\"clearfix revisionData comparison\">
//     <div class=\"clearfix headers\">
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//     </div>
//     <dl class=\"clearfix\">
//       <dt>name</dt>
//       <dd><del>Billy </del>Visto</dd>
//       <dd><del>Billy </del>Visto</dd>
//     </dl>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1&amp;restore=true\" id=\"restore-1\" class=\"restore button\" data-revisionNumber=\"1\" title=\"Restore Revision 1\">Restore #1</a>
//     </footer>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4&amp;restore=true\" id=\"restore-4\" class=\"restore button\" data-revisionNumber=\"4\" title=\"Restore Revision 4\">Restore #4</a>
//     </footer>
//   </section>
//   </div>
// </form>";
//     $urlParams = array('revisionNumbersToCompare' => array(1,4), 'revisionsAction' => 'text', 'columns' => array('name'));
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionComparisonTextError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $now = date("F jS \\a\\t g:ia");
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <h4>Revision History</h4>
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th>
//           <a id=\"showMoreRevisions\" href=\"$this->appUrl?revisionNumbersToCompare%5B0%5D=1&amp;revisionNumbersToCompare%5B1%5D=3&amp;revisionsAction=text&amp;oldestRevisionNumber=1\" class=\"button small\" title=\"Show Revision  in table\">Show More Revisions</a>
//         </th>
//         <th class=\"1 old\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//         <th class=\"4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">4</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"error\">$this->error</td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" class=\"revision\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"error\">$this->error</td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"6 Bytes added\" style=\"height:54.545454545455%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" class=\"revision\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\"></td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3 young\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\" checked=\"checked\"/>
//           </label>
//         </td>
//         <td class=\"4\">
//           <label for=\"revisionNum-4\">
//             <input id=\"revisionNum-4\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 4\" class=\"compare\" value=\"4\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\">
//   <section class=\"clearfix revisionData comparison\">
//     <div class=\"clearfix headers\">
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//     </div>
//     <dl class=\"clearfix\">
//       <dt>age</dt>
//       <dd/>
//       <dd/>
//       <dt class=\"error\">name</dt>
//       <dd>$this->error</dd>
//       <dd>$this->error</dd>
//     </dl>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1&amp;restore=true\" id=\"restore-1\" class=\"restore button\" data-revisionNumber=\"1\" title=\"Restore Revision 1\">Restore #1</a>
//     </footer>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3&amp;restore=true\" id=\"restore-3\" class=\"restore button\" data-revisionNumber=\"3\" title=\"Restore Revision 3\">Restore #3</a>
//     </footer>
//   </section>
//   </div>
// </form>";

//     $urlParams = array('revisionNumbersToCompare' => array(1,3), 'revisionsAction' => 'text');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionData()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $now = date("F jS \\a\\t g:ia");
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <h4>Revision History</h4>
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th>
//           <a id=\"showMoreRevisions\" href=\"$this->appUrl?revisionNumber=2&amp;revisionsAction=revision&amp;oldestRevisionNumber=1\" class=\"button small\" title=\"Show Revision  in table\">Show More Revisions</a>
//         </th>
//         <th class=\"1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionNumber=2&amp;revisionsAction=revision&amp;oldestRevisionNumber=1\" class=\"missingRevisions button\" title=\"Show Revision 1 in table\"/>
//         </td>
//         <td class=\"bytes 2 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionNumber=2&amp;revisionsAction=revision&amp;oldestRevisionNumber=1\" class=\"missingRevisions button\" title=\"Show Revision 1 in table\"/>
//         </td>
//         <td class=\"bytes 2 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\"></td>
//         <td class=\"2 young\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\" checked=\"checked\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\">
//   <section class=\"clearfix revisionData\">
//     <div class=\"clearfix headers\">
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//     </div>
//     <dl class=\"clearfix\">
//       <dt>age</dt>
//       <dd>
//         <ins>23</ins>
//       </dd>
//       <dt>name</dt>
//       <dd><del>Billy </del>Visto</dd>
//     </dl>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2&amp;restore=true\" id=\"restore-2\" class=\"restore button\" data-revisionNumber=\"2\" title=\"Restore Revision 2\">Restore #2</a>
//     </footer>
//   </section>
//   </div>
// </form>";

//     $urlParams = array('revisionNumber' => '2', 'revisionsAction' => 'revision');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionDataError()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->saveRevisionToDB('Billy', 'Billy Visto', 'name', $this->get($this->revisionsAPI, 'revisions'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $now = date("F jS \\a\\t g:ia");
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <h4>Revision History</h4>
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th>
//           <a id=\"showMoreRevisions\" href=\"$this->appUrl?revisionNumber=1&amp;revisionsAction=revision&amp;oldestRevisionNumber=1\" class=\"button small\" title=\"Show Revision  in table\">Show More Revisions</a>
//         </th>
//         <th class=\"1 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//         <th class=\"4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">4</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"error\">$this->error</td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" class=\"revision\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"error\">$this->error</td>
//         <td class=\"bytes 2\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"6 Bytes added\" style=\"height:54.545454545455%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 4\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=4\" class=\"revision\" data-revisionNumber=\"4\" title=\"Look at Revision 4\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\"></td>
//         <td class=\"2\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//         <td class=\"4\">
//           <label for=\"revisionNum-4\">
//             <input id=\"revisionNum-4\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 4\" class=\"compare\" value=\"4\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\">
//   <section class=\"clearfix revisionData\">
//     <div class=\"clearfix headers\">
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//     </div>
//     <dl class=\"clearfix\">
//       <dt class=\"error\">name</dt>
//       <dd>$this->error</dd>
//     </dl>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1&amp;restore=true\" id=\"restore-1\" class=\"restore button\" data-revisionNumber=\"1\" title=\"Restore Revision 1\">Restore #1</a>
//     </footer>
//   </section>
//   </div>
// </form>";
//     $urlParams = array('revisionNumber' => '1', 'revisionsAction' => 'revision');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionDataColumn()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $now = date("F jS \\a\\t g:ia");
//     $expected = "<form id=\"revisionsForm\" method=\"GET\">
//   <h4>Revision History</h4>
//   <div id=\"revisionTimeline\">
//   <table class=\"fancy\">
//     <thead>
//       <tr>
//         <th>
//           <a id=\"showMoreRevisions\" href=\"$this->appUrl?revisionNumber=2&amp;revisionsAction=revision&amp;columns%5B0%5D=name&amp;oldestRevisionNumber=1\" class=\"button small\" title=\"Show Revision  in table\">Show More Revisions</a>
//         </th>
//         <th class=\"1\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=1\" data-revisionNumber=\"1\" title=\"Look at Revision 1\">1</a>
//         </th>
//         <th class=\"2 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">2</a>
//         </th>
//         <th class=\"3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">3</a>
//         </th>
//       </tr>
//     </thead>
//     <tbody>
//       <tr>
//         <th>age</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionNumber=2&amp;revisionsAction=revision&amp;columns%5B0%5D=name&amp;oldestRevisionNumber=1\" class=\"missingRevisions button\" title=\"Show Revision 1 in table\"/>
//         </td>
//         <td class=\"bytes 2 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:0%;\"/>
//                 <span class=\"bytes added\" title=\" Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\" Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"0 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"2 Bytes added\" style=\"height:100%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//       <tr>
//         <th>name</th>
//         <td class=\"bytes 1\">
//           <a href=\"$this->appUrl?revisionNumber=2&amp;revisionsAction=revision&amp;columns%5B0%5D=name&amp;oldestRevisionNumber=1\" class=\"missingRevisions button\" title=\"Show Revision 1 in table\"/>
//         </td>
//         <td class=\"bytes 2 young\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2\" class=\"revision\" data-revisionNumber=\"2\" title=\"Look at Revision 2\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"11 Bytes unchanged\" style=\"height:100%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"0 Bytes removed\" style=\"height:0%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//         <td class=\"bytes 3\">
//           <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=3\" class=\"revision\" data-revisionNumber=\"3\" title=\"Look at Revision 3\">
//             <span class=\"bytes container\">
//               <span class=\"bytes positive\">
//                 <span class=\"bytes unchanged\" title=\"5 Bytes unchanged\" style=\"height:45.454545454545%;\"/>
//                 <span class=\"bytes added\" title=\"0 Bytes added\" style=\"height:0%;\"/>
//               </span>
//               <span class=\"bytes negative\">
//                 <span class=\"bytes removed\" title=\"6 Bytes removed\" style=\"height:120%;\"/>
//               </span>
//             </span>
//           </a>
//         </td>
//       </tr>
//     </tbody>
//     <tfoot>
//       <tr class=\"compare\">
//         <th>
//           <button id=\"compareButton\" class=\"positive\" name=\"revisionsAction\" value=\"text\">Compare</button>
//         </th>
//         <td class=\"1\"></td>
//         <td class=\"2 young\">
//           <label for=\"revisionNum-2\">
//             <input id=\"revisionNum-2\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 2\" class=\"compare\" value=\"2\" checked=\"checked\"/>
//           </label>
//         </td>
//         <td class=\"3\">
//           <label for=\"revisionNum-3\">
//             <input id=\"revisionNum-3\" type=\"checkbox\" name=\"revisionNumbersToCompare[]\" title=\"Revision 3\" class=\"compare\" value=\"3\"/>
//           </label>
//         </td>
//       </tr>
//     </tfoot>
//   </table>
//   </div>
//   <div id=\"formExtras\">
//   <section class=\"clearfix revisionData\">
//     <div class=\"clearfix headers\">
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//     </div>
//     <dl class=\"clearfix\">
//       <dt>name</dt>
//       <dd><del>Billy </del>Visto</dd>
//     </dl>
//     <footer>
//       <a href=\"$this->appUrl?revisionsAction=revision&amp;revisionNumber=2&amp;restore=true\" id=\"restore-2\" class=\"restore button\" data-revisionNumber=\"2\" title=\"Restore Revision 2\">Restore #2</a>
//     </footer>
//   </section>
//   </div>
// </form>";

//     $urlParams = array('revisionNumber' => '2', 'revisionsAction' => 'revision', 'columns' => array('name'));
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionRestore()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $now = date("F jS \\a\\t g:ia");
//     $expected = "<form id=\"revisionsForm\" method=\"POST\">
//   <div id=\"formExtras\">
//       <p class=\"message\">You are restoring to revision #2</p>
//   <section class=\"clearfix revisionData\">
//     <div class=\"clearfix headers\">
//       <header>
//         <hgroup>
//           <h1/>
//           <h2>$now</h2>
//           <h2>by </h2>
//         </hgroup>
//       </header>
//     </div>
//     <dl class=\"clearfix\">
//       <dt>age</dt>
//       <dd><ins>23</ins></dd>
//       <dt>name</dt>
//       <dd><del>Billy </del>Visto</dd>
//     </dl>
//   </section>
//   <button id=\"restoreButton\" class=\"positive\" type=\"submit\" name=\"revisionsAction\" value=\"restore\">Confirm Restore</button>
//   </div>
//   </form>";
//     $urlParams = array('revisionNumber' => '2');
//     $_GET = array('revisionsAction' => 'revision', 'restore' => 'true');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

//   /**
//    * @test
//    */
//   public function renderRevisionThankYou()
//   {
//     $conn = $this->getConnection();
//     $this->setUpMock('person-revision');
//     $this->dbalConnection->query($this->getCreateQuery());
//     $this->dbalConnection->query($this->getCreateDataQuery());

//     $this->revisionsAPI->saveRevision(array('name' => 'Billy Visto'));
//     $this->revisionsAPI->saveRevision(array('name' => 'Visto', 'age' => 23));

//     $expected = "<form id=\"revisionsForm\" method=\"POST\">
//   <div id=\"formExtras\">
//       <button type=\"submit\" name=\"revisionsAction\" value=\"undo\">Undo</button>
//   </div>
//       </form>";
//     $urlParams = array('oldestRevisionNumber' => '2', 'revisionsAction' => 'thankYou');
//     $actual = $this->revisionsAPI->render($urlParams, $this->appUrl);
//     $this->assertXmlStringEqualsXmlString($expected, $actual);
//     $this->dropCreatedTables(array('person-revision', 'revisionData'));
//   }

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
  public function revisionIsOnlyVisible()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'revisionIsOnlyVisible', array('2', array('2'))));
  }

  /**
   * @test
   */
  public function revisionIsOnlyVisibleFalse()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionIsOnlyVisible', array('2', array('3'))));
  }

  /**
   * @test
   */
  public function revisionIsOnlyVisibleFalseMultipleVisible()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionIsOnlyVisible', array('2', array('2', '3'))));
  }

  /**
   * @test
   */
  public function revisionsAreVisible()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'revisionsAreVisible', array(array('2'), array('2'))));
  }

  /**
   * @test
   */
  public function revisionsAreVisibleComplex()
  {
    $this->assertTrue($this->call($this->revisionsAPI, 'revisionsAreVisible', array(array('3', '2'), array('2', '3'))));
  }

  /**
   * @test
   */
  public function revisionsAreNotVisible()
  {
    $this->assertFalse($this->call($this->revisionsAPI, 'revisionsAreVisible', array(array('2'), array('3'))));
  }
}