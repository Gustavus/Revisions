<?php
/**
 * @package Revisions
 * @author  Billy Visto
 */
namespace Gustavus\Revisions;
use Gustavus\Resources\Resource,
  Gustavus\Extensibility\Actions,
  Gustavus\Extensibility\Filters;

/**
 * API to interact with the revisions project
 *
 * @package Revisions
 * @author  Billy Visto
 */
class API
{
  /**
   * Extensibility hook to use for restoring revisions
   */
  const RESTORE_HOOK           = '\Gustavus\Revisions\API\Restore';
  /**
   * Action to use for restoring revisions
   */
  const RESTORE_ACTION         = 'restore';
  /**
   * Action to use for undoing a revision restoration
   */
  const UNDO_ACTION            = 'undo';
  /**
   * Extensibility filter to use for building the revisions. (Allows you to remove html or anything else from the rendered revision)
   */
  const RENDER_REVISION_FILTER = '\Gustavus\Revisions\API\BuildRevision';
  /**
   * JS version
   */
  const REVISIONS_JS_VERSION   = 3;
  /**
   * CSS version
   */
  const REVISIONS_CSS_VERSION  = 2;

  /**
   * @var Revisions
   */
  private $revisions;

  /**
   * @var RevisionsRenderer
   */
  private $revisionsRenderer;

  /**
   * @var Boolean
   */
  private $allowRestore = true;

  /**
   * Whether to show insertions and deletions when rendering a diff
   *
   * @var Boolean
   */
  private $showInsertionsAndDeletions = true;

  /**
   * array of column labels used for mapping column names to formatted labels
   *
   * @var array
   */
  private $labels = array();

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
    'restore',
  );

  /**
   * Class constructor
   *
   * @param array $params application's revision info
   *   Params:
   *   <ul>
   *     <li>dbName: Required. name of the db connection</li>
   *     <li>revisionsTable: Required. table name that revisions will live in.</li>
   *     <li>revisionDataTable: Required. table name that revision data will live in.</li>
   *     <li>table: Required. Identifier to know what table or item we are keeping track of revisions for.</li>
   *     <li>rowId: Required. Identifier to know what row in our table we are keeping a revision for.</li>
   *     <li>dbal: Doctrine connection to use.</li>
   *     <li>labels: Labels to override the key for displaying a revision.</li>
   *     <li>allowRestore: Whether we want to allow restoring to a previous revision or not.</li>
   *     <li>splitStrategy: Strategy to use for splitting the content to generate diffs.</li>
   *   </ul>
   *
   * @throws  \RuntimeException If there isn't enough information supplied for the application
   * @return  void
   */
  public function __construct(array $params = array())
  {
    if (isset($params['dbName'],
        $params['revisionsTable'],
        $params['revisionDataTable'],
        $params['table'],
        $params['rowId']
      )
    ) {
      $this->revisions = new Revisions($params);
      if (isset($params['labels'])) {
        $this->labels = $params['labels'];
      }
      if (isset($params['allowRestore'])) {
        $this->allowRestore = $params['allowRestore'];
      }
      if (isset($params['showInsertionsAndDeletions'])) {
        $this->showInsertionsAndDeletions = $params['showInsertionsAndDeletions'];
      }
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
    unset($this->revisions, $this->revisionsRenderer, $this->possibleRevisionsQueryParams, $this->labels);
  }

  /**
   * Gets a specific revision
   *
   * @param  integer $revisionNumber Revision number to grab
   * @return Revision
   */
  public function getRevision($revisionNumber)
  {
    return $this->revisions->getRevisionByNumber($revisionNumber);
  }

  /**
   * Renders out the revisions or revision requested
   *
   * @param  boolean $return Whether to return results or allow content to be echoed.
   * @return string
   */
  public function render($return = false)
  {
    $post = $_POST;
    $queryStringArray = $_GET;

    if (!empty($post)) {
      // submitted the form with Post method.
      return $this->handlePostAction($_POST);
    }
    if (isset($queryStringArray['barebones'])) {
      // making an ajax request, so we don't want any extra information returned
      ob_end_clean();
    }
    // form was either submitted, or it is just a regular page load.
    // get revisionsRenderer ready to go
    $this->constructRevisionsRenderer($queryStringArray);
    if (!$return && isset($queryStringArray['barebones'])) {
      // an ajax call was made for new information
      // we want the new information echoed to the ajax call and then we want to exit so nothing else gets thrown in.
      echo $this->doWorkRequstedInUrl($queryStringArray);
      exit();
    } else {
      // regular page load
      return $this->doWorkRequstedInUrl($queryStringArray);
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
    if (!$this->allowRestore) {
      return;
    }
    $revisionContent = $this->revisions->getRevisionContentArray((int) $urlParams['restore']);
    $oldMessage = $this->revisions->getRevisionByNumber((int) $urlParams['restore'])->getRevisionMessage();
    Actions::apply(self::RESTORE_HOOK, $revisionContent, $oldMessage, self::RESTORE_ACTION);
  }

  /**
   * Handles undo action
   *
   * @return void
   */
  private function handleUndoAction()
  {
    if (!$this->allowRestore) {
      return;
    }
    $limit = $this->revisions->getLimit();
    $this->revisions->setLimit(2);
    $this->revisions->populateEmptyRevisions();
    $secondToLatestRevNum = $this->revisions->findOldestRevisionNumberPulled();
    $revisionContent = $this->revisions->getRevisionContentArray($secondToLatestRevNum);
    $oldMessage = $this->revisions->getRevisionByNumber($secondToLatestRevNum)->getRevisionMessage();
    Actions::apply(self::RESTORE_HOOK, $revisionContent, $oldMessage, self::UNDO_ACTION);
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
    if (!isset($urlParams['barebones'])) {
      // don't bother to set up the template if barebones is set
      $this->setUpTemplate();
    }
    if ($this->isRevisionData($urlParams) || $this->isSingleComparison($urlParams)) {
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
    Filters::add('head', array($this, 'renderRevisionsCSS'));
  }

  /**
   * @param string $content
   * @return string
   */
  public function renderRevisionsCSS($content = null)
  {
    return sprintf('%1$s<link rel="stylesheet" href="%2$s" type="text/css" media="screen, projection" />',
        $content,
        Resource::renderCSS(['path' => '/revisions/css/revisions.css', 'version' =>  self::REVISIONS_CSS_VERSION])
    );
  }

  /**
   * Adds JS to the template
   *
   * @return void
   */
  private function addJS()
  {
    Filters::add('scripts', array($this, 'renderRevisionsJS'));
  }

  /**
   * Renders out JS to send to the application
   *
   * @param string $content
   * @return string
   */
  final public function renderRevisionsJS($content = null)
  {
    $js = sprintf('<script>
        require.config({
          paths: {
            "revisions": "%s",
            "revisionsViewport": "%s",
            "revisionsMousewheel": "%s",
            "history": "/js/history/scripts/bundled/html4+html5/jquery.history"
          },
          shim: {
            "revisionsViewport": ["baseJS"],
            "revisionsMousewheel": ["baseJS"],
            "history": ["baseJS"],
            "revisions": ["baseJS", "ui/mouse", "ui/draggable", "ui/effect-slide", "revisionsViewport", "revisionsMousewheel", "history"]
          }
        });
        require(["revisions"]);
      </script>',
      Resource::renderResource(['urlutil', ['path' => '/revisions/js/revisions.js', 'version' => self::REVISIONS_JS_VERSION]]),
      Resource::renderResource(['path' => '/revisions/js/jquery-viewport/jquery.viewport.min.js', 'version' => 1]),
      Resource::renderResource(['path' => '/revisions/js/jquery-mousewheel/jquery.mousewheel.js', 'version' => 1])
    );

    return $content . $js;
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
   * Checks if params exist where the timeline might not need to be rendered out.
   *
   * @param  array $urlParams
   * @return boolean
   */
  private function timelineParamsExistAndNotRestore(array $urlParams = array())
  {
    return (isset($urlParams['barebones'], $urlParams['oldestRevisionInTimeline']) && !$this->isRestore($urlParams));
  }

  /**
   * Checks to see if the RevisionNumber of a visible revision is in the timeline.
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function revisionNumberIsInTimeline(array $urlParams = array())
  {
    return (isset($urlParams['revisionNumber'], $urlParams['oldestRevisionInTimeline']) &&
      ($urlParams['revisionNumber'] === false ||
        (
          (int) $urlParams['revisionNumber'] >= (int) $urlParams['oldestRevisionInTimeline']) ||
          (isset($urlParams['revisionNumbers']) && $this->arrayMin($urlParams['revisionNumbers']) >= (int) $urlParams['oldestRevisionInTimeline']
        )
      )
    );
  }

  /**
   * Checks if the oldestRevisionNumber is in the timeline or if we are supposed to pull more revisions in.
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function oldestRevisionNumberIsInTimeline(array $urlParams = array())
  {
    return (isset($urlParams['oldestRevisionNumber'], $urlParams['oldestRevisionInTimeline']) &&
      (
        (int) $urlParams['oldestRevisionInTimeline'] <= (int) $urlParams['oldestRevisionNumber']
      ) || (int) $urlParams['oldestRevisionNumber'] <= 1 && (int) $urlParams['oldestRevisionInTimeline'] <= 1
    );
  }

  /**
   * Checks if timeline contains the revisionNumbers, or if there are no revisionNumbers passed. This happens if we are going through the history stack and get back to the initial state.
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function timelineContainsRevisions(array $urlParams = array())
  {
    return (($this->revisionNumberIsInTimeline($urlParams) &&
      $this->oldestRevisionNumberIsInTimeline($urlParams)) ||
      (!isset($urlParams['oldestRevisionNumber']) && !isset($urlParams['revisionNumber'])));
  }

  /**
   * Checks the urlParams on whether to render out the timeline or not.
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function shouldRenderTimeline(array $urlParams = array())
  {
    if ($this->timelineParamsExistAndNotRestore($urlParams) &&
      $this->timelineContainsRevisions($urlParams)
      ) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * Checks to see if both revision numbers in the revisionsNumbers index are visible or not
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function revisionsAreVisible(array $urlParams = array())
  {
    if (isset($urlParams['revisionNumbers'], $urlParams['visibleRevisions'])) {
      $diff = array_diff($urlParams['revisionNumbers'], $urlParams['visibleRevisions']);
      return empty($diff);
    } else {
      return false;
    }
  }

  /**
   * Checks to see if the revision number is the only one in the visibleRevisions array
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function revisionIsOnlyVisible(array $urlParams = array())
  {
    if (isset($urlParams['revisionNumber'], $urlParams['visibleRevisions']) && $this->elementIsOnlyOneInArray($urlParams['revisionNumber'], $urlParams['visibleRevisions'])) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Checks if a revision has been specified or not. This will not be the case if we are pulling in the timeline.
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function noRevisionsSpecified(array $urlParams = array())
  {
    return (!isset($urlParams['revisionNumber']) && !isset($urlParams['revisionNumbers']));
  }

  /**
   * Checks if a revision has been specified or not. If a revision was visible, but no longer has a revision number, we want to redraw the revisionData
   *
   * @param  array  $urlParams
   * @return boolean
   */
  private function noRevisionSpecified(array $urlParams = array())
  {
    if (isset($urlParams['visibleRevisions'])) {
      // revision was visible
      // if it is empty, we don't want the revisionData rendered out
      return (empty($urlParams['visibleRevisions']) && $this->noRevisionsSpecified($urlParams));
    } else {
      return $this->noRevisionsSpecified($urlParams);
    }
  }

  /**
   * Checks to see if an element is the only one in an array
   *
   * @param  mixed $element
   * @param  array  $array
   * @return boolean
   */
  private function elementIsOnlyOneInArray($element, array $array = array())
  {
    if (in_array($element, $array) && count($array) === 1) {
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
    if (isset($urlParams['barebones']) &&
      (
        $this->revisionIsOnlyVisible($urlParams) ||
        $this->revisionsAreVisible($urlParams) ||
        $this->noRevisionSpecified($urlParams)
      ) && !$this->isRestore($urlParams)) {
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
   * Checks the urlParams to see if it only one checkbox was checked when comparing.
   *
   * @param  array   $urlParams
   * @return boolean
   */
  private function isSingleComparison(array $urlParams)
  {
    return (isset($urlParams['revisionNumber'], $urlParams['revisionNumbers']) && $urlParams['revisionNumber'] === 'false' && count($urlParams['revisionNumbers']) === 1);
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
      if ($this->isSingleComparison($urlParams)) {
        $revisionNumber = (int) $urlParams['revisionNumbers'][0];
      } else {
        $revisionNumber = (int) $urlParams['revisionNumber'];
      }
      return $this->renderRevisionData(
          $revisionNumber,
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
    return $this->renderRevisionComparisonText(
        (int) $urlParams['revisionNumbers'][0],
        (int) $urlParams['revisionNumbers'][1],
        (isset($urlParams['columns'])) ? $urlParams['columns'] : array(),
        $oldestRevNumToPull
    );
  }

  /**
   * constructs revisionsRenderer object
   *
   * @param array $urlParams
   * @return void
   */
  private function constructRevisionsRenderer(array $urlParams)
  {
    $this->revisionsRenderer = new RevisionsRenderer($this->revisions, $this->getRevisionsUrlParams($urlParams), $this->getApplicationUrlParams($urlParams), $this->labels, $this->allowRestore, $this->showInsertionsAndDeletions);
    $this->setUpItemsToRender($urlParams);
  }

  /**
   * make application url params
   *
   * @param  array $urlParams
   * @return array
   */
  private function getApplicationUrlParams(array $urlParams)
  {
    return array_diff_key($urlParams, array_flip($this->possibleRevisionsQueryParams));
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
      // make sure oldestRevNumToPull is an int if it isn't null
      $oldestRevNumToPull = (int) $oldestRevNumToPull;
    }
    return $this->revisionsRenderer->renderRevisions($limit, $oldestRevNumToPull);
  }

  /**
   * Renders out a table of revisionData for each column with the old content, and new content
   *
   * @param  integer $oldRevNum
   * @param  integer $newRevNum
   * @param  array $columns
   * @param  integer $oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  private function renderRevisionComparisonText($oldRevNum, $newRevNum, array $columns = array(), $oldestRevNum = null)
  {
    return $this->revisionsRenderer->renderRevisionComparisonText($oldRevNum, $newRevNum, $columns, $oldestRevNum);
  }

  /**
   * Renders out a table of revisionData for each column
   *
   * @param  integer $revNum
   * @param  array $columns
   * @param  integer $oldestRevNum oldestRevNum pulled into the revisions Object
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
   * @param  integer $revNum
   * @return string
   */
  private function renderRevisionRestore($revNum)
  {
    return $this->revisionsRenderer->renderRevisionRestore($revNum);
  }

  /**
   * Gets the number of revisions
   *
   * @return integer Number of revisions found
   */
  public function getRevisionCount()
  {
    if ($this->revisions->findLatestRevisionNumberPulled() === null) {
      $origLimit = $this->revisions->getLimit();
      $this->revisions->setLimit(1);
      $this->revisions->populateEmptyRevisions();
      $this->revisions->setLimit($origLimit);
    }
    $latestRevisionNumber = $this->revisions->findLatestRevisionNumberPulled();
    return ($latestRevisionNumber === null) ? 0 : $latestRevisionNumber;
  }
}