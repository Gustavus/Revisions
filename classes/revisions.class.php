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
   * @var array of revisions keyed by revision id
   */
  private $revisions = array();

  /**
   * @var array of current content info
   */
  private $currentContentInfo;

  /**
   * @var string of what the previous revisions content was
   */
  private $previousContent;

  /**
   * @var int of previous revisions id
   */
  private $previousRevisionId = null;

  /**
   * @var boolean on whether object has tried to pull revisions or not
   */
  private $revisionsHaveBeenPulled = false;

  /**
   * Class constructor
   * @param array $params
   */
  public function __construct(array $params = array())
  {
    if (isset($params['dbName'],
      $params['revisionsTable'],
      $params['column'],
      $params['table'],
      $params['rowId'])) {
        $this->populateObjectWithArray($params);
    }
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->revisions, $this->previousRevisionId, $this->currentContentInfo, $this->previousContent);
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
  public function makeRevision($newText)
  {
    $oldContentArray = $this->getRevisions(null, 1);
    if (!empty($oldContentArray)) {
      $oldContent = $oldContentArray[0]['value'];
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
   * Defaults to pull the latest 10 revisions to cache in the object
   *
   * function to make and store a revision
   * @param  string $revisionDB    revision database name
   * @param  string $revisionTable revision table name currenty working with
   * @param  string $table         project table name used for distinguishing between tables if db is used for multiple table's revisions
   * @param  integer $rowId        id of the row that the current content is in for complex databases
   * @param  string $key           column name that is being worked with
   * @param  integer $limit        how many revisions to go back
   * @return void
   */
  private function populateObjectWithRevisions($limit = 10)
  {
    $currentContent = null;
    $revisions = $this->getRevisions($this->previousRevisionId, $limit);
    if ($this->previousRevisionId === null) {
      $this->currentContentInfo = array_shift($revisions);
      $currentContent = $this->currentContentInfo['value'];
    }
    foreach ($revisions as $revision) {
      $params = array(
        'revisionId'     => $revision['id'],
        'revisionDate'   => $revision['createdOn'],
        'currentContent' => $currentContent,
        'revisionInfo'   => json_decode($revision['value'], true),
      );
      $rev = new Revision($params);
      $revDiff = $rev->makeRevisionContent(true);
      $currentContent = $rev->makeRevisionContent(false);
      $this->revisions[$revision['id']] = array(
        'revision'        => $rev,
        'revisionContent' => $currentContent,
        'revisionDiff'    => $revDiff,
      );
    }
    $this->previousRevisionId = isset($revision['id']) ? $revision['id'] : null;
    $this->previousContent    = $currentContent;
  }

  /**
   * pulls a specific revision out of the object to return
   * @param  integer $id revision id you want
   * @param  boolean $diff whether to return plain text or a diff
   * @return string
   */
  public function getRevision($id, $diff = false)
  {
    if (!$this->revisionsHaveBeenPulled) {
      $this->populateObjectWithRevisions();
      $this->revisionsHaveBeenPulled = true;
    } else if ($this->previousRevisionId !== null && $this->previousRevisionId > $id) {
      $this->populateObjectWithRevisions();
    }
    if (!isset($this->revisions[$id])) {
      return null;
    }
    if ($diff) {
      $revContent = $this->revisions[$id]['revisionDiff'];
    } else {
      $revContent = $this->revisions[$id]['revisionContent'];
    }
    $rev = $this->revisions[$id]['revision'];
    return array(
      'date' => $rev->getRevisionDate(),
      'revisionContent' => $revContent,
    );
  }
}