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
   * array of possible query string parameters for revisions
   *
   * @var array
   */
  private $possibleRevisionsQueryParams = array(
    'revisionsAction',
    'revisionNumber',
    'newRevisionNumber',
    'oldRevisionNumber',
    'column',
    'limit',
  );

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
    unset($this->revisions, $this->revisionsRenderer);
  }

  /**
   * Renders out the revisions or revision requested
   *
   * @param  array  $urlParams $_GET syntax
   * @param  string  $urlBase base location of the page requesting info
   * @return string
   */
  public function render(array $urlParams, $urlBase = '')
  {
    $this->constructRevisionsRenderer($urlParams, $urlBase);
    return $this->doWorkRequstedInUrl($urlParams);
  }

  /**
   * Figure out what application wants to do with what is in the url
   * available actions: revisions, revision, text, diff, textDiff
   *
   * @param  array $urlParams associative array of url
   * @return string
   */
  private function doWorkRequstedInUrl(array $urlParams)
  {
    switch ($urlParams['revisionsAction']) {
      case 'revisions' :
        return $this->renderRevisionsFromUrlParams($urlParams);
      case 'revision' :
        return $this->renderRevisionFromUrlParams($urlParams);
      default :
        return $this->renderRevisionComparisonFromUrlParams($urlParams);
    }
  }

  /**
   * Render Revisions list
   *
   * @param  array  $urlParams
   * @return string
   */
  private function renderRevisionsFromUrlParams(array $urlParams)
  {
    return (isset($urlParams['limit'])) ? $this->renderRevisions($urlParams['limit']) : $this->renderRevisions();
  }

  /**
   * Render Revision based on the params specified
   *
   * @param  array  $urlParams
   * @return string
   */
  private function renderRevisionFromUrlParams(array $urlParams)
  {
    if (isset($urlParams['revisionNumber'])) {
      return (isset($urlParams['column'])) ? $this->renderRevisionData($urlParams['revisionNumber'], $urlParams['column']) : $this->renderRevisionData($urlParams['revisionNumber']);
    } else {
      return $this->renderRevisionsFromUrlParams($urlParams);
    }
  }

  /**
   * Render Revision comparison based on params
   *
   * @param  array  $urlParams
   * @return string
   */
  private function renderRevisionComparisonFromUrlParams(array $urlParams)
  {
    if (isset($urlParams['oldRevisionNumber'], $urlParams['newRevisionNumber'])) {
      $function = 'renderRevisionComparison' . ucfirst($urlParams['revisionsAction']);
      return (isset($urlParams['column'])) ? $this->{$function}($urlParams['oldRevisionNumber'], $urlParams['newRevisionNumber'], $urlParams['column']) : $this->{$function}($urlParams['oldRevisionNumber'], $urlParams['newRevisionNumber']);
    } else {
      return $this->renderRevisionsFromUrlParams($urlParams);
    }
  }

  /**
   * constructs revisionsRenderer object
   *
   * @return void
   */
  private function constructRevisionsRenderer(array $urlParams, $urlBase)
  {
    $this->revisionsRenderer = new RevisionsRenderer($this->revisions, $urlBase, $this->getApplicationUrlParams($urlParams));
  }

  /**
   * make application url params
   *
   * @param  array $urlParams
   * @return array
   */
  private function getApplicationUrlParams(array $urlParams)
  {
    $applicationUrlParams = array();
    foreach ($urlParams as $key => $value) {
      if (!in_array($key, $this->possibleRevisionsQueryParams)) {
        $applicationUrlParams[$key] = $value;
      }
    }
    return $applicationUrlParams;
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
    return $this->revisionsRenderer->renderRevisionData($revNum, $column);
  }
}