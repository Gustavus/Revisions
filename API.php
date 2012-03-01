<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * API to interact with the revisions project
 *
 * @package Revisions
 */
class API
{
  const RESTORE_HOOK = '\Gustavus\Revisions\API\Restore';

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
    'revisionNumbersToCompare',
    'column',
    'limit',
    'oldestRevisionNumber',
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
   * @param  array  $urlParams associative array
   * @param  string  $urlBase base location of the page requesting info
   * @return string
   */
  public function render(array $urlParams, $urlBase = '')
  {
    if (!empty($_GET)) {
      $urlParams = array_merge($urlParams, $_GET);
    }
    if (!empty($_POST) && isset($_POST['revisionsAction'])) {
      $this->handlePostAction($_POST, $urlParams);
    }
    $this->constructRevisionsRenderer($urlParams, $urlBase);
    return $this->doWorkRequstedInUrl($urlParams);
  }

  /**
   * Handles post actions like restore's and undo's calling a callback
   *
   * @param  array  $post
   * @param  array $urlParams
   * @return void
   */
  private function handlePostAction(array $post, array $urlParams)
  {
    if ($_POST['revisionsAction'] === 'restore' && isset($urlParams['revisionNumber'])) {
      $this->handleRestoreAction($urlParams);
    } else if ($_POST['revisionsAction'] === 'undo') {
      $this->handleUndoAction();
    }
  }

  /**
   * Handles restore action
   *
   * @param  array $urlParams
   * @return void
   */
  private function handleRestoreAction(array $urlParams)
  {
    $revisionContent = $this->revisions->getRevisionContentArray((int) $urlParams['revisionNumber']);
    $oldMessage = $this->revisions->getRevisionByNumber((int) $urlParams['revisionNumber'])->getRevisionMessage();
    \Gustavus\Extensibility\Actions::apply(self::RESTORE_HOOK, $revisionContent, $oldMessage);
  }

  /**
   * Handles undo action
   *
   * @return void
   */
  private function handleUndoAction()
  {
    $limit = $this->revisions->getLimit();
    $this->revisions->setLimit(2);
    $this->revisions->populateEmptyRevisions();
    $secondToLatestRevNum = $this->revisions->findOldestRevisionNumberPulled();
    $revisionContent = $this->revisions->getRevisionContentArray($secondToLatestRevNum);
    $oldMessage = $this->revisions->getRevisionByNumber($secondToLatestRevNum)->getRevisionMessage();
    \Gustavus\Extensibility\Actions::apply(self::RESTORE_HOOK, $revisionContent, $oldMessage);
    // reset limit to what it was originally at
    $this->revisions->setLimit($limit);
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
    if (!isset($urlParams['revisionsAction'])) {
      $revisionsAction = 'revisions';
    } else {
      $revisionsAction = $urlParams['revisionsAction'];
    }
    switch ($revisionsAction) {
      case 'text' :
        return $this->renderRevisionComparisonFromUrlParams($urlParams);
      case 'revision' :
        return $this->renderRevisionFromUrlParams($urlParams);
      case 'thankYou' :
        return $this->renderThankYouMessage();
      case 'revisions' :
        return $this->renderRevisionsFromUrlParams($urlParams);
      default :
        return $this->renderRevisionsFromUrlParams($urlParams);
    }
  }

  /**
   * Gets and returns the oldestRevisionNumber -1 from the urlParams or null if it is empty
   *
   * @param  array  $urlParams
   * @return mixed
   */
  private function getOldestRevisionNumberToPullFromURL(array $urlParams)
  {
    // -1 so we have one more revision than we need so we can get what changed
    return (isset($urlParams['oldestRevisionNumber'])) ? $urlParams['oldestRevisionNumber'] : null;
  }

  /**
   * Render Revisions list
   *
   * @param  array  $urlParams
   * @return string
   */
  private function renderRevisionsFromUrlParams(array $urlParams)
  {
    $oldestRevNumToPull = $this->getOldestRevisionNumberToPullFromURL($urlParams);
    if (isset($urlParams['limit'])) {
      return $this->renderRevisions($oldestRevNumToPull, $urlParams['limit']);
    } else {
      return $this->renderRevisions($oldestRevNumToPull);
    }
  }

  /**
   * Checks the urlParams to see if it is a restore action
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isRestore(array $urlParams)
  {
    return (isset($urlParams['restore']) && $urlParams['restore'] === 'true');
  }

  /**
   * Checks the urlParams to see if it is a comparison action
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isComparison(array $urlParams)
  {
    return (isset($urlParams['revisionNumbersToCompare'][0], $urlParams['revisionNumbersToCompare'][1]));
  }

  /**
   * Render Revision based on the params specified
   *
   * @param  array  $urlParams
   * @return string
   */
  private function renderRevisionFromUrlParams(array $urlParams)
  {
    $oldestRevNumToPull = $this->getOldestRevisionNumberToPullFromURL($urlParams);
    if (isset($urlParams['revisionNumber'])) {
      if ($this->isRestore($urlParams)) {
        return $this->renderRevisionRestore((int) $urlParams['revisionNumber']);
      } else {
        if (isset($urlParams['column'])) {
          return $this->renderRevisionData((int) $urlParams['revisionNumber'], $urlParams['column'], $oldestRevNumToPull);
        } else {
          return $this->renderRevisionData((int) $urlParams['revisionNumber'], null, $oldestRevNumToPull);
        }
      }
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
    $oldestRevNumToPull = $this->getOldestRevisionNumberToPullFromURL($urlParams);
    if ($this->isComparison($urlParams)) {
      $function = 'renderRevisionComparison' . ucfirst($urlParams['revisionsAction']);
      if (isset($urlParams['column'])) {
        return $this->{$function}((int) $urlParams['revisionNumbersToCompare'][0], (int) $urlParams['revisionNumbersToCompare'][1], $urlParams['column'], $oldestRevNumToPull);
      } else {
        return $this->{$function}((int) $urlParams['revisionNumbersToCompare'][0], (int) $urlParams['revisionNumbersToCompare'][1], null, $oldestRevNumToPull);
      }
    } else {
      return $this->renderRevisionsFromUrlParams($urlParams);
    }
  }

  /**
   * constructs revisionsRenderer object
   *
   * @param array urlParams
   * @param string urlBase baseUrl for generating links
   * @return void
   */
  private function constructRevisionsRenderer(array $urlParams, $urlBase)
  {
    $this->revisionsRenderer = new RevisionsRenderer($this->revisions, $urlBase, $this->getApplicationUrlParams($urlParams), $this->getRevisionsUrlParams($urlParams));
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
   * make revisions url params
   *
   * @param  array $urlParams
   * @return array
   */
  private function getRevisionsUrlParams(array $urlParams)
  {
    return array_intersect_key($urlParams, array_flip($this->possibleRevisionsQueryParams));
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
   * @param  integer $oldestRevNumToPull
   * @param  integer $limit
   * @return string
   */
  private function renderRevisions($oldestRevNumToPull = null, $limit = null)
  {
    if ($limit === null) {
      $limit = $this->revisions->getLimit();
    }
    if ($oldestRevNumToPull !== null) {
      $oldestRevNumToPull = (int) $oldestRevNumToPull;
    }
    return $this->revisionsRenderer->renderRevisions($limit, $oldestRevNumToPull);
  }

  /**
   * Renders out a table of revisionData for each column with the old content, and new content
   *
   * @param  integer oldRevNum
   * @param  integer $newRevNum
   * @param  string $column
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  private function renderRevisionComparisonText($oldRevNum, $newRevNum, $column = null, $oldestRevNum = null)
  {
    return $this->revisionsRenderer->renderRevisionComparisonText($oldRevNum, $newRevNum, $column, $oldestRevNum);
  }

  /**
   * Renders out a table of revisionData for each column
   *
   * @param  integer revNum
   * @param  string $column
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  private function renderRevisionData($revNum, $column = null, $oldestRevNum = null)
  {
    return $this->revisionsRenderer->renderRevisionData($revNum, $column, $oldestRevNum);
  }

  /**
   * Renders out thank you message
   *
   * @return string
   */
  private function renderThankYouMessage()
  {
    return $this->revisionsRenderer->renderRevisionThankYou();
  }

  /**
   * Renders out a table of revisionData for each column with a confirm restore button
   *
   * @param  integer revNum
   * @return string
   */
  private function renderRevisionRestore($revNum)
  {
    return $this->revisionsRenderer->renderRevisionRestore($revNum);
  }
}