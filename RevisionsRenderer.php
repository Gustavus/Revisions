<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
use Gustavus\TwigFactory\TwigFactory;

/**
 * Renders out revisions to the application
 *
 * @package Revisions
 */
class RevisionsRenderer
{
  /**
   * @var Revisions
   */
  private $revisions;

  /**
   * @var array
   */
  private $revisionsUrlParams;

  /**
   * @var array
   */
  private $applicationUrlParams;

  /**
   * @var boolean
   */
  private $shouldRenderTimeline = true;

  /**
   * @var boolean
   */
  private $shouldRenderRevisionData = true;

  /**
   * array of column labels used for mapping column names to formatted labels
   *
   * @var array
   */
  private $labels = array();

  /**
   * Class constructor
   * @param Revisions $revisions
   * @param array revisionsUrlParams
   * @param array applicationUrlParams
   */
  public function __construct(Revisions $revisions, array $revisionsUrlParams = array(), array $applicationUrlParams = array(), array $labels = array())
  {
    $this->revisions            = $revisions;
    $this->revisionsUrlParams   = $revisionsUrlParams;
    $this->applicationUrlParams = $applicationUrlParams;
    $this->labels               = $labels;
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->revisions, $this->revisionsUrlParams, $this->applicationUrlParams, $this->shouldRenderTimeline, $this->shouldRenderRevisionData, $this->labels);
  }

  /**
   * Set shouldRenderTimeline
   *
   * @param boolean $value
   * @return void
   */
  public function setShouldRenderTimeline($value)
  {
    $this->shouldRenderTimeline = $value;
  }

  /**
   * Set shouldRenderRevisionData
   *
   * @param boolean $value
   * @return void
   */
  public function setShouldRenderRevisionData($value)
  {
    $this->shouldRenderRevisionData = $value;
  }

  /**
   * Renders out all the revisions with information about them
   *
   * @param  integer $limit
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  public function renderRevisions($limit = null, $oldestRevNum = null)
  {
    if ($limit !== null) {
      $this->revisions->setLimit($limit);
    }
    if ($oldestRevNum === null) {
      $this->revisions->populateEmptyRevisions();
    } else {
      $this->revisions->populateEmptyRevisions($oldestRevNum);
    }
    $oldestRevNumPulled = $this->revisions->findOldestRevisionNumberPulled();
    return $this->renderTwig('revisions.twig', null, array(), $oldestRevNumPulled);
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
  public function renderRevisionComparisonText($oldRevNum, $newRevNum, array $columns = array(), $oldestRevNum = null)
  {
    // - 1 so we pull in one more revision for rendering content changes
    $this->revisions->populateEmptyRevisions(min($oldRevNum, $newRevNum) - 1);
    return $this->renderTwig('revisionDataText.twig', $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $columns), array('visibleRevisions' => array($oldRevNum, $newRevNum), 'columns' => $columns), $oldestRevNum);
  }

  /**
   * Renders out a table of revisionData for each column
   *
   * @param  integer revNum
   * @param  array $columns
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  public function renderRevisionData($revNum, array $columns = array(), $oldestRevNum = null)
  {
    // - 1 so we pull in one more revision for rendering content changes
    $this->revisions->populateEmptyRevisions($revNum - 1);
    if ($oldestRevNum > $revNum) {
      $oldestRevNum = $revNum;
    }
    // $revNum - 1 so we are looking at how it changed from the previous revision since the current revision is the current text in the previous revision
    return $this->renderTwig('revisionData.twig', $this->revisions->getRevisionByNumber($revNum - 1), array('visibleRevisions' => array($revNum), 'columns' => $columns), $oldestRevNum);
  }

  /**
   * Renders out a table of revisionData for each column with a button to confirm restore
   *
   * @param  integer revNum
   * @return string
   */
  public function renderRevisionRestore($revNum)
  {
    return $this->renderTwig('revisionDataRestore.twig', $this->revisions->getRevisionByNumber($revNum), array('visibleRevisions' => array($revNum)));
  }

  /**
   * Renders out a table of revisionData for each column with a button to undo action
   *
   * @return string
   */
  public function renderRevisionThankYou()
  {
    return $this->renderTwig('revisionThankYou.twig', null);
  }

  /**
   * Make labels based off of the applications labels that it prefers and falls back to using the column name if none specified.
   * This will also let the application change the order of columns.
   *
   * @return array
   */
  private function makeLabels()
  {
    if ($this->revisions->findLatestRevisionNumberPulled() !== null) {
      $columnNames = array_keys($this->revisions->getRevisionByNumber($this->revisions->findLatestRevisionNumberPulled())->getRevisionData());
      // set labels to be the labels specified by the application
      $labels = array_intersect_key($this->labels, array_flip($columnNames));
      // default labels to be the column name if not specified
      foreach (array_diff_key(array_flip($columnNames), $this->labels) as $key => $value) {
        $labels[$key] = $key;
      }
    } else {
      $labels = array();
    }
    return $labels;
  }

  /**
   * Remove params that are in paramsToFilter
   *
   * @param  array  $params
   * @param  array  $paramsToFilter
   * @return array
   */
  private function removeParams(array $params, array $paramsToFilter = array())
  {
    foreach ($paramsToFilter as $filter) {
      unset($params[$filter]);
    }
    return $params;
  }

  /**
   * Renders out the template
   *
   * @param  string $filename  location of twig template
   * @param  mixed $revisions array of revisions, or a single revision object
   * @param  array $params  array of additional params to pass to twig
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  private function renderTwig($filename, $revision, array $params = array(), $oldestRevNum = null)
  {
    $oldestRevisionNumber = ($oldestRevNum === null) ? $this->revisions->findOldestRevisionNumberPulled() : $oldestRevNum - 1;
    $params = array_merge(
        $params,
        array(
          'revisions'             => $this->revisions->getRevisionObjects($oldestRevisionNumber),
          'labels'                => $this->makeLabels(),
          'revision'              => $revision,
          'oldestRevisionNumber'  => $oldestRevisionNumber,
          'limit'                 => $this->revisions->getLimit(),
          'maxColumnSizes'        => $this->revisions->getMaxColumnSizes(),
          'hiddenFields'          => $this->removeParams(array_merge($this->applicationUrlParams, array('oldestRevisionNumber' => $oldestRevisionNumber + 1), $this->revisionsUrlParams), array('barebones', 'oldestRevisionInTimeline', 'visibleRevisions', 'revisionNumbers')),
          'shouldRenderTimeline'      => $this->shouldRenderTimeline,
          'shouldRenderRevisionData'  => $this->shouldRenderRevisionData,
        )
    );
    return TwigFactory::renderTwigFilesystemTemplate("/cis/lib/Gustavus/Revisions/views/$filename", $params);
  }
}