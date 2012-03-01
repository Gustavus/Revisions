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
   * @var string
   */
  private $applicationBaseUrl;

  /**
   * @var array
   */
  private $applicationUrlParams;

  /**
   * @var array
   */
  private $revisionsUrlParams;

  /**
   * Class constructor
   * @param Revisions $revisions
   * @param string applicationBaseUrl
   * @param array applicationUrlParams
   * @param array revisionsUrlParams
   */
  public function __construct(Revisions $revisions, $applicationBaseUrl = '', array $applicationUrlParams = array(), array $revisionsUrlParams = array())
  {
    $this->revisions = $revisions;
    $this->applicationBaseUrl = $applicationBaseUrl;
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
    unset($this->revisions, $this->applicationBaseUrl, $this->applicationUrlParams);
  }

  /**
   * makes the url based off of the application's base url and the application's query string
   *
   * @param  array  $urlParams
   * @return string
   */
  private function makeUrl(array $urlParams)
  {
    $urlParams = array_merge($this->applicationUrlParams, $urlParams);
    $queryString = http_build_query($urlParams);
    $url = (empty($queryString)) ? $this->applicationBaseUrl : sprintf('%1$s?%2$s', $this->applicationBaseUrl, $queryString);
    return $url;
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
    if ($limit === null) {
      $limit = $this->revisions->getLimit();
    }
    if ($oldestRevNum === null) {
      // +1 so we pull in one more revision for calculating the added/removed bytes
      $this->revisions->setLimit($limit + 1);
      $this->revisions->populateEmptyRevisions();
      // reset limit
      $this->revisions->setLimit($limit);
    } else {
      // -1 so we pull in one more revision for calculating the added/removed bytes
      $this->revisions->populateEmptyRevisions($oldestRevNum - 1);
    }
    $oldestRevNumPulled = $this->revisions->findOldestRevisionNumberPulled();
    if ($oldestRevNumPulled !== null && $oldestRevNumPulled > 0 && !$this->revisions->revisionsHaveErrors()) {
      $moreRevisionButton = $oldestRevNumPulled;
    } else {
      $moreRevisionButton = 0;
    }
    return $this->renderTwig('revisions.twig', null, array('oldestRevisionNumber' => $moreRevisionButton), $oldestRevNumPulled);
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
  public function renderRevisionComparisonText($oldRevNum, $newRevNum, $column = null, $oldestRevNum = null)
  {
    return $this->renderTwig('revisionDataText.twig', $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $column), array('visibleRevisions' => array($oldRevNum, $newRevNum)), $oldestRevNum);
  }

  /**
   * Renders out a table of revisionData for each column
   *
   * @param  integer revNum
   * @param  string $column
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  public function renderRevisionData($revNum, $column = null, $oldestRevNum = null)
  {
    return $this->renderTwig('revisionData.twig', $this->revisions->getRevisionByNumber($revNum, $column), array('visibleRevisions' => array($revNum)), $oldestRevNum);
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
   * @param  integer oldestRevNum oldestRevNum pulled into the revisions Object
   * @return string
   */
  public function renderRevisionThankYou($oldestRevNum)
  {
    $limit = $this->revisions->getLimit();
    $this->revisions->setLimit(1);
    $this->revisions->populateEmptyRevisions();
    $revNum = $this->revisions->findLatestRevisionNumberPulled();
    // set limit to what the application initially set it to be
    $this->revisions->setLimit($limit);
    return $this->renderTwig('revisionThankYou.twig', $this->revisions->getRevisionByNumber($revNum), array('visibleRevisions' => array($revNum)), $oldestRevNum);
  }

  /**
   * Remove params that are in paramsToFilter
   *
   * @param  array  $params         [description]
   * @param  array  $paramsToFilter [description]
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
    $oldestRevisionNumber = ($oldestRevNum === null) ? $this->revisions->findOldestRevisionNumberPulled() - 1 : $oldestRevNum;
    $params = array_merge(
        array(
          'revision'             => $revision,
          'revisions'            => $this->revisions->getRevisionObjects($oldestRevisionNumber),
          'oldestRevisionNumber' => $oldestRevisionNumber,
          'revisionUrl'          => $this->makeUrl(array(
            'revisionsAction' => 'revision',
            'revisionNumber'  => '',
            )
          ),
          'limit'           => $this->revisions->getLimit(),
          'maxColumnSizes'  => $this->revisions->getMaxColumnSizes(),
          'fullUrl'         => $this->makeUrl($this->removeParams($this->revisionsUrlParams, array('oldestRevisionNumber'))),
        ),
        $params
    );
    return \Gustavus\TwigFactory\TwigFactory::renderTwigFilesystemTemplate("/cis/lib/Gustavus/Revisions/views/$filename", $params);
  }
}