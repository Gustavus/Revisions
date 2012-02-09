<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

require_once 'Gustavus/Revisions/RevisionsRenderer.php';
require_once 'Gustavus/Revisions/Revisions.php';

/**
 * API to interact with the revisions project
 *
 * @package Revisions
 */
class API
{
  /**
   * @var Revisions
   */
  private $revisions;

  /**
   * @var RevisionsRenderer
   */
  private $revisionsRenderer;

  /**
   * Class constructor
   *
   * @param array $params application's revision info
   */
  public function __construct(array $params = array())
  {
    if (isset($params['dbName'],
      $params['revisionsTable'],
      $params['revisionDataTable'],
      $params['table'],
      $params['rowId'])) {
        $this->revisions = new Revisions($params);
    } else {
      throw new \RuntimeException('Insufficient application information');
    }
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->revisions);
  }

  private function constructRevisionsRenderer()
  {
    $this->revisionsRenderer = new RevisionsRenderer($this->revisions);
  }

  /**
   * Saves Revision to revision DB
   *
   * @param  array  $newContent new content keyed by column
   * @param  string $message    revision message
   * @param  string $createdBy  person who modified revision
   * @return boolean
   */
  public function saveRevision(array $newContent, $message = null, $createdBy = null)
  {
    return $this->revisions->makeAndSaveRevision($newContent, $message, $createdBy);
  }

  /**
   * Renders out all the revisions with information about them
   *
   * @param  integer $limit
   * @return string
   */
  public function renderRevisions($limit = 5)
  {
    $this->revisions->setLimit($limit);
    $this->constructRevisionsRenderer();
    return $this->revisionsRenderer->renderRevisions($limit);
  }

  /**
   * Renders out a table of revisionData for each column with the old content, and new content
   *
   * @param  integer oldRevNum
   * @param  integer $newRevNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionComparisonText($oldRevNum, $newRevNum, $column = null)
  {
    $this->constructRevisionsRenderer();
    return $this->revisionsRenderer->renderRevisionComparisonText($oldRevNum, $newRevNum, $column);
  }

  /**
   * Renders out a table of revisionData for each column with the diff of what changed from the old content to get the new content
   *
   * @param  integer oldRevNum
   * @param  integer $newRevNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionComparisonDiff($oldRevNum, $newRevNum, $column = null)
  {
    $this->constructRevisionsRenderer();
    return $this->revisionsRenderer->renderRevisionComparisonDiff($oldRevNum, $newRevNum, $column);
  }

  /**
   * Renders out a table of revisionData for each column with the diff of what changed from the old content to get the new content
   *
   * @param  integer oldRevNum
   * @param  integer $newRevNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionComparisonTextDiff($oldRevNum, $newRevNum, $column = null)
  {
    $this->constructRevisionsRenderer();
    return $this->revisionsRenderer->renderRevisionComparisonTextDiff($oldRevNum, $newRevNum, $column);
  }

  /**
   * Renders out a table of revisionData for each column
   *
   * @param  integer revNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionData($revNum, $column = null)
  {
    $this->constructRevisionsRenderer();
    return $this->revisionsRenderer->renderRevisionData($revNum, $column);
  }
}