<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

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
  private $applicationUrlParams;

  /**
   * @var array
   */
  private $revisionsUrlParams;

  /**
   * @var boolean
   */
  private $shouldRenderTimeline = true;

  /**
   * @var boolean
   */
  private $shouldRenderRevisionData = true;

  /**
   * Class constructor
   * @param Revisions $revisions
   * @param array applicationUrlParams
   * @param array revisionsUrlParams
   */
  public function __construct(Revisions $revisions, array $applicationUrlParams = array(), array $revisionsUrlParams = array())
  {
    $this->revisions = $revisions;
    $this->applicationUrlParams = $applicationUrlParams;
    $this->revisionsUrlParams = $revisionsUrlParams;
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->revisions, $this->revisionsUrlParams, $this->applicationUrlParams);
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
    return $this->renderTwig('revisionData.twig', $this->revisions->getRevisionByNumber($revNum), array('visibleRevisions' => array($revNum), 'columns' => $columns), $oldestRevNum);
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
    if ($this->shouldRenderTimeline) {
      // don't bother pulling all the revisions for the timeline
      $params['revisions'] = $this->revisions->getRevisionObjects($oldestRevisionNumber);
    }
    $params = array_merge(
        $params,
        array(
          'revision'              => $revision,
          'oldestRevisionNumber'  => $oldestRevisionNumber,
          'limit'                 => $this->revisions->getLimit(),
          'maxColumnSizes'        => $this->revisions->getMaxColumnSizes(),
          'hiddenFields'          => $this->removeParams(array_merge(array('oldestRevisionNumber' => $oldestRevisionNumber + 1), $this->revisionsUrlParams), array('barebones', 'oldestRevisionInTimeline', 'visibleRevisions', 'revisionNumbers')),
          'shouldRenderTimeline'      => $this->shouldRenderTimeline,
          'shouldRenderRevisionData'  => $this->shouldRenderRevisionData,
        )
    );
    return \Gustavus\TwigFactory\TwigFactory::renderTwigFilesystemTemplate("/cis/lib/Gustavus/Revisions/views/$filename", $params);
  }
}