<?php
/**
 * @package Revisions
 * @subpackage Tests
 * @author  Billy Visto
 */

namespace Gustavus\Revisions\Test;
use Gustavus\Revisions;

/**
 * Base class for tests
 *
 * @package Revisions
 * @subpackage Tests
 * @author  Billy Visto
 */
class RevisionsTestsHelper extends \Gustavus\Test\TestDBPDO
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
  protected $revisionsManagerInfo = array(
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
            `contentHash` VARCHAR,
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
            `contentHash` VARCHAR,
            `revisionId` INTEGER,
            `revisionNumber` INTEGER,
            `key` VARCHAR,
            `value` VARCHAR,
            `splitStrategy` VARCHAR)
            ';
    return $sql;
  }

  /**
   * @param string $currContent
   * @param string $newContent
   * @return void
   */
  protected function saveRevisionToDB($currContent, $newContent, $column, $object, $revisionDataArray = array(), $message = null, $createdBy = 'name', array $newColumns = array())
  {
    $revisionData = new \Gustavus\Revisions\RevisionDataDiff(array(
      'nextContent' => $currContent,
    ));

    $revisionInfo = $revisionData->renderRevisionForDB($newContent);
    $revisionInfoArray = array($column => $revisionInfo);

    $revision = $this->call($object, 'getRevisions', [null, 1]);
    if (isset($revision[0]['revisionNumber'])) {
      $latestRevisionNumber = (int) $revision[0]['revisionNumber'];
    } else {
      $latestRevisionNumber = null;
    }
    $this->set($object, 'latestRevisionNumber', $latestRevisionNumber);
    return $this->call($object, 'saveRevision', array($revisionInfoArray, array($column => $newContent), array($column => $currContent), $revisionDataArray, $message, $createdBy, $newColumns));
  }
}