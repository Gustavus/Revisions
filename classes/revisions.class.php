<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'revisions/classes/revisionsPuller.class.php';
require_once 'revisions/classes/revision.class.php';

/**
 * @package Revisions
 */
class Revisions extends RevisionsPuller
{
  /**
   * @var array of revisionsInfo
   */
  private $revisionsInfo;

  /**
   * Class constructor
   *
   */
  public function __construct()
  {
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->revisionsInfo);
  }

  /**
   * function to render changes from oldText to newText
   *
   * @param string $oldText
   * @param string $newText
   * @return string
   */
  public function renderDiff($oldText, $newText)
  {
    $revision = new Revision(array('currentContent' => $oldText));
    $diff = $revision->makeDiff($newText);
    return $diff;
  }

  /**
   * function to make and store a revision
   * @param  string $newText       text that has replaced the old text
   * @param  string $revisionDB    revision database name
   * @param  string $revisionTable revision table name currenty working with
   * @param  string $table         project table name used for distinguishing between tables if db is used for multiple table's revisions
   * @param  integer $rowId        id of the row that the current content is in for complex databases
   * @param  string $key           column name that is being worked with
   * @return string of the diff
   */
  public function makeRevision($newText, $revisionDB, $revisionTable, $table, $rowId, $key)
  {
    $this->populateObjectWithArray(array(
      'dbName'         => $revisionDB,
      'revisionsTable' => $revisionTable,
      'column'         => $key,
      'table'          => $table,
      'rowId'          => $rowId,
    ));
    $oldContentArray = $this->getRevisions(null, 1);
    if (!empty($oldContentArray)) {
      $oldContent = $oldContentArray[0]['value'];
      //var_dump($oldContentArray);
      $revision = new Revision(array('currentContent' => $oldContent));
      $revisionInfo = $revision->renderRevisionForDB($newText);
      $diff = $revision->makeDiff($newText);
    } else {
      $revisionInfo = null;
      $diff = $this->renderDiff('', $newText);
    }
    $this->saveRevision($revisionInfo, $newText, $oldContentArray);
    return $diff;
  }

  /**
   * function to get and store revisions in the object
   *
   * @param
   */
  public function makeRevisions($newText, $revisionDB, $revisionTable, $table, $rowId, $key, $limit = 10)
  {
    // have to have the current version saved in db
    // when we make a new revision, we will turn the currentContent into a revision information to go from the new content back. It will use the same date
    // save new content to project db

  }
}