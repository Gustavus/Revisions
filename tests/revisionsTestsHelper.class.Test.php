<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

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
    'column' => 'name',
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
            `key` VARCHAR,
            `value` VARCHAR,
            `createdOn` DATETIME)
            ';
    return $sql;
  }

  /**
   * @param string $currContent
   * @param string $newContent
   * @return void
   */
  protected function saveRevisionToDB($currContent, $newContent, $object)
  {
    $revision = new \Gustavus\Revisions\Revision(array(
      'currentContent' => $currContent,
    ));

    $revisionInfo = $this->call($revision, 'renderRevisionForDB', array($newContent));
    $oldContentArray = $this->call($object, 'getRevisions', array(null, 1));
    // modify
    $this->call($object, 'saveRevision', array($revisionInfo, $newContent, $oldContentArray));
  }
}