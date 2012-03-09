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
  const RESTORE_HOOK          = '\Gustavus\Revisions\API\Restore';
  const REVISIONS_JS_VERSION  = 1;
  const REVISIONS_CSS_VERSION = 1;

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
    'revisionNumbers',
    'columns',
    'limit',
    'oldestRevisionNumber',
    'barebones',
    'visibleRevisions',
    'oldestRevisionInTimeline',
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
    unset($this->revisions, $this->revisionsRenderer, $this->possibleRevisionsQueryParams);
  }

  /**
   * Renders out the revisions or revision requested
   *
   * @return string
   */
  public function render()
  {
    if (isset($_GET['barebones'])) {
      ob_end_clean();
    }
    if (!empty($_POST)) {
      return $this->handlePostAction($_POST);
    }
    $this->constructRevisionsRenderer($_GET);
    if (isset($_GET['barebones'])) {
      echo $this->doWorkRequstedInUrl($_GET);
      exit();
    } else {
      return $this->doWorkRequstedInUrl($_GET);
    }
  }

  /**
   * Handles post actions like restore's and undo's calling a callback
   *
   * @param  array  $post
   * @return void
   */
  private function handlePostAction(array $post)
  {
    unset($_POST);
    if ($this->isRestore($post)) {
      $this->handleRestoreAction($post);
    } else if ($this->isUndo($post)) {
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
    $revisionContent = $this->revisions->getRevisionContentArray((int) $urlParams['restore']);
    $oldMessage = $this->revisions->getRevisionByNumber((int) $urlParams['restore'])->getRevisionMessage();
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
    unset($_GET);
    if (!isset($urlParams['barebones'])) {
      // don't bother to set up the template if barebones is set
      $this->setUpTemplate();
    }
    if ($this->isRevisionData($urlParams)) {
      return $this->renderRevisionFromUrlParams($urlParams);
    } else if ($this->isComparison($urlParams)) {
      return $this->renderRevisionComparisonFromUrlParams($urlParams);
    } else if ($this->isThankYou($urlParams)) {
      return $this->renderThankYouMessage();
    } else {
      return $this->renderRevisionsFromUrlParams($urlParams);
    }
  }

  /**
   * Set up Javascript and CSS in the template
   *
   * @return void
   */
  private function setUpTemplate()
  {
    $this->addJS();
    $this->addCSS();
  }

  /**
   * Adds CSS to the template
   *
   * @return void
   */
  private function addCSS()
  {
    \Gustavus\Extensibility\Filters::add('head', array($this, 'renderRevisionsCSS'));
  }

  /**
   * @param string $content
   * @return string
   */
  final public function renderRevisionsCSS($content = null)
  {
    return sprintf('%1$s<link rel="stylesheet" href="/min/f=/revisions/css/revisions.css&%2$s" type="text/css" media="screen, projection" />',
        $content,
        self::REVISIONS_CSS_VERSION
    );
  }

  /**
   * Adds JS to the template
   *
   * @return void
   */
  private function addJS()
  {
    \Gustavus\Extensibility\Filters::add('scripts', array($this, 'renderRevisionsJS'));
  }

  /**
   * Renders out JS to send to the application
   *
   * @param string $content
   * @return string
   */
  final public function renderRevisionsJS($content = null)
  {
    $revisionsScripts = array(
      '/js/history/scripts/bundled/html4+html5/jquery.history.js',
      sprintf('/min/f=/revisions/js/revisions.js&%1$s',
          self::REVISIONS_JS_VERSION
      ),
    );
    $js = $this->modernizeJS($revisionsScripts);
    return $content . $js;
  }

  /**
   * Throws js into modernizer.load
   *
   * @param array $scripts
   * @return string
   */
  private function modernizeJS(Array $scripts)
  {
    return sprintf('
      <script type="text/javascript">
        Modernizr.load([
          "%1$s"
        ]);
      </script>',
        implode('","', $scripts)
    );
    //return '';
  }

  /**
   * Takes an array of ints and returns the lowest value
   *
   * @param  array  $array
   * @return integer
   */
  private function arrayMin(array $array = array())
  {
    ksort($array);
    return (int) $array[0];
  }

  /**
   * Sets properties in revisionsRenderer so it knows what items to render and what items to skip if it is an ajax request
   *
   * @param array $urlParams
   * @return void
   */
  private function setUpItemsToRender(array $urlParams = array())
  {
    $this->revisionsRenderer->setShouldRenderTimeline($this->shouldRenderTimeline($urlParams));
    $this->revisionsRenderer->setShouldRenderRevisionData($this->shouldRenderRevisionData($urlParams));
  }

  /**
   * Checks the urlParams on whether to render out the timeline or not.
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function shouldRenderTimeline(array $urlParams = array())
  {
    if (isset($urlParams['barebones'], $urlParams['oldestRevisionInTimeline']) && ((isset($urlParams['revisionNumber']) && (int) $urlParams['revisionNumber'] > (int) $urlParams['oldestRevisionInTimeline']) || (isset($urlParams['revisionNumbers']) && $this->arrayMin($urlParams['revisionNumbers']) > (int) $urlParams['oldestRevisionInTimeline'])) && (isset($urlParams['oldestRevisionNumber']) && (int) $urlParams['oldestRevisionInTimeline'] <= (int) $urlParams['oldestRevisionNumber']) && !$this->isRestore($urlParams)) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * Checks to see if both revision numbers in arrayA are visible or not
   *
   * @param  array  $arrayA
   * @param  array  $arrayB
   * @return boolean
   */
  private function revisionsAreVisible(array $arrayA = array(), array $arrayB = array())
  {
    $diff = array_diff($arrayA, $arrayB);
    return empty($diff);
  }

  /**
   * Checks to see if the revision number is the only one in the visibleRevisions array
   *
   * @param  integer $revisionNumber
   * @param  array  $visibleRevisions
   * @return boolean
   */
  private function revisionIsOnlyVisible($revisionNumber, array $visibleRevisions = array())
  {
    if (in_array($revisionNumber, $visibleRevisions) && count($visibleRevisions) === 1) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Checks the urlParams on whether to render out revisionData or not.
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function shouldRenderRevisionData(array $urlParams = array())
  {
    if (isset($urlParams['barebones'], $urlParams['visibleRevisions']) && ((isset($urlParams['revisionNumber']) && $this->revisionIsOnlyVisible($urlParams['revisionNumber'], $urlParams['visibleRevisions'])) || (isset($urlParams['revisionNumbers']) && $this->revisionsAreVisible($urlParams['revisionNumbers'], $urlParams['visibleRevisions']))) && !$this->isRestore($urlParams)) {
      return false;
    } else {
      return true;
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
    return $this->renderRevisions(
        $this->getOldestRevisionNumberToPullFromURL($urlParams),
        (isset($urlParams['limit'])) ? $urlParams['limit'] : null
    );
  }

  /**
   * Checks the urlParams to see if it is a revision data action
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isRevisionData(array $urlParams)
  {
    return (isset($urlParams['revisionNumber']) && is_numeric($urlParams['revisionNumber']) || $this->isRestore($urlParams));
  }

  /**
   * Checks the urlParams to see if it is a thank you action
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isThankYou(array $urlParams)
  {
    return (isset($urlParams['revisionsAction']) && $urlParams['revisionsAction'] === 'thankYou');
  }

  /**
   * Checks the urlParams to see if it is a restore action
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isRestore(array $urlParams)
  {
    return (isset($urlParams['restore']) && is_numeric($urlParams['restore']));
  }

  /**
   * Checks the urlParams to see if it is an undo action
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isUndo(array $urlParams)
  {
    return (isset($urlParams['revisionsAction']) && $urlParams['revisionsAction'] === 'undo');
  }

  /**
   * Checks the urlParams to see if it is a comparison action
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isComparison(array $urlParams)
  {
    return (isset($urlParams['revisionNumbers'][0], $urlParams['revisionNumbers'][1]));
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
    if ($this->isRestore($urlParams)) {
      return $this->renderRevisionRestore((int) $urlParams['restore']);
    } else {
      return $this->renderRevisionData(
          (int) $urlParams['revisionNumber'],
          (isset($urlParams['columns'])) ? $urlParams['columns'] : array(),
          $oldestRevNumToPull
      );
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
      return $this->renderRevisionComparisonText(
          (int) $urlParams['revisionNumbers'][0],
          (int) $urlParams['revisionNumbers'][1],
          (isset($urlParams['columns'])) ? $urlParams['columns'] : array(),
          $oldestRevNumToPull
      );
    } else {
      return $this->renderRevisionsFromUrlParams($urlParams);
    }
  }

  /**
   * constructs revisionsRenderer object
   *
   * @param array urlParams
   * @return void
   */
  private function constructRevisionsRenderer(array $urlParams)
  {
    $this->revisionsRenderer = new RevisionsRenderer($this->revisions, $this->getRevisionsUrlParams($urlParams));
    $this->setUpItemsToRender($urlParams);
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
   * @param  array $columns
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  private function renderRevisionComparisonText($oldRevNum, $newRevNum, array $columns = array(), $oldestRevNum = null)
  {
    return $this->revisionsRenderer->renderRevisionComparisonText($oldRevNum, $newRevNum, $columns, $oldestRevNum);
  }

  /**
   * Renders out a table of revisionData for each column
   *
   * @param  integer revNum
   * @param  array $columns
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  private function renderRevisionData($revNum, array $columns = array(), $oldestRevNum = null)
  {
    return $this->revisionsRenderer->renderRevisionData($revNum, $columns, $oldestRevNum);
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