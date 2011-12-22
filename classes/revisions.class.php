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
   *
   * @param
   */
  public function makeRevision($oldText, $newText, $revisionDB, $table, $rowId, $key)
  {
    //need to update revisions.yml to have revisionInfo which will be json_encode(array of revisionInfo made by Revision->getRevisionInfo() after makeRevisionInfo)
    //make revisionInfo and save into revisionDB
  }


}