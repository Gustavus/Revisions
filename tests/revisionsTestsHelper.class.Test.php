<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

require_once '/cis/lib/test/testDBPDO.class.php';
require_once 'revisions/classes/revision.class.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsHelper extends \Gustavus\Test\TestDBPDO
{
  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
  }

  /**
   * @var array of revisionsPuller info
   */
  protected $revisionsPullerInfo = array(
    'dbName' => 'person-revision',
    'revisionsTable' => 'person-revision',
    'revisionDataTable' => 'revisionData',
    'table'  => 'person',
    'rowId'  => 1,
  );

  /**
   * @return string
   */
  protected function getCreateQuery()
  {
    $sql = 'CREATE TABLE IF NOT EXISTS `person-revision`
            (`id` INTEGER PRIMARY KEY,
            `table` VARCHAR,
            `rowId` INTEGER,
            `revisionNumber` INTEGER,
            `message` VARCHAR,
            `createdBy` VARCHAR,
            `createdOn` DATETIME)
            ';
    return $sql;
  }

  /**
   * @return string
   */
  protected function getCreateDataQuery()
  {
    $sql = 'CREATE TABLE IF NOT EXISTS `revisionData`
            (`id` INTEGER PRIMARY KEY,
            `revisionId` INTEGER,
            `revisionNumber` INTEGER,
            `key` VARCHAR,
            `value` VARCHAR)
            ';
    return $sql;
  }

  /**
   * @param string $currContent
   * @param string $newContent
   * @return void
   */
  protected function saveRevisionToDB($currContent, $newContent, $column, $object, $revisionDataArray = array(), $message = '', $createdBy = 'name')
  {
    $revisionData = new \Gustavus\Revisions\RevisionData(array(
      'currentContent' => $currContent,
    ));

    $revisionInfo = $revisionData->renderRevisionForDB($newContent);
    $revisionInfoArray = array($column => $revisionInfo);
    $this->call($object, 'saveRevision', array($revisionInfoArray, array($column => $newContent), $revisionDataArray, $message, $createdBy));
  }
}