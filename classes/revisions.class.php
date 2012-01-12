<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'revisions/classes/revisionsPuller.class.php';
require_once 'revisions/classes/revision.class.php';
require_once 'revisions/classes/revisionData.class.php';

/**
 * @package Revisions
 */
class Revisions extends RevisionsPuller
{
  /**
   * @var SplFixedArray of revisions keyed by revision number
   */
  private $revisions = null;

  /**
   * @var array of current content info keyed by column
   */
  private $currentContent = array();

  /**
   * @var array keyed by column name of what the previous revision's content was
   */
  private $previousContent = array();

  /**
   * @var array of booleans keyed by column on whether object has tried to pull revisions or not
   */
  private $revisionDataHasBeenPulled = array();

  /**
   * @var boolean
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
      $params['revisionDataTable'],
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
    unset($this->revisions, $this->previousRevisionNumber, $this->currentContentInfo, $this->previousContent);
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
    $revisionData = new RevisionData(array('currentContent' => $oldText));
    $diff = $revisionData->makeDiff($newText);
    return $diff;
  }

  /**
   * function to make and store a revision
   * @param  array $newText       array of text that has replaced the old text keyed by column
   * @param  string $message      revision message
   * @param  string $createdBy    person creating revision
   * @return string of the diff
   */
  public function makeRevision(array $newText, $message = '', $createdBy = '')
  {
    $revisionInfoArray    = array();
    $oldRevisionDataArray = array();
    foreach ($newText as $key => $value) {
      $oldRevisionData = $this->getRevisionData(null, $key, true, 1);
      $oldRevisionDataArray = array_merge($oldRevisionDataArray, $oldRevisionData);
      if (isset($oldRevisionData[$key])) {
        $oldContentArray = array_shift($oldRevisionData[$key]);
        $revisionData    = new RevisionData(array('currentContent' => $oldContentArray['value']));
        $revisionInfo    = $revisionData->renderRevisionForDB($value);
        $revisionInfoArray[$key] = $revisionInfo;
      } else {
        // revision doesn't exist yet
        $revisionData    = new RevisionData(array('currentContent' => ''));
        $revisionInfo    = $revisionData->renderRevisionForDB($value);
        $revisionInfoArray[$key] = $revisionInfo;
      }
    }
    $this->saveRevision($revisionInfoArray, $newText, $oldRevisionDataArray, $message, $createdBy);
  }

  /**
   * function to get and store revisions in the object
   * Defaults to pull the latest 10 revisions to cache in the object
   *
   * @return void
   */
  private function populateObjectWithRevisions($column = null)
  {
    if ($column !== null) {
      $this->populateObjectWithColumnRevisions($column);
    } else {
      $currentContent = null;
      $revisions = $this->getRevisions($this->findOldestRevisionNumberPulled());
      foreach ($revisions as $revisionInfo) {
        if (!$this->revisionsHaveBeenPulled) {
          $splFixedArrayLength = $revisionInfo['revisionNumber'];
          $this->revisionsHaveBeenPulled = true;
          $this->revisions = new \SplFixedArray($splFixedArrayLength + 1);
        }
        $revisionData = $this->makeRevisionDataObjects($revisionInfo['id']);
        $params = array(
          'revisionNumber'  => $revisionInfo['revisionNumber'],
          'revisionDate'    => $revisionInfo['createdOn'],
          'revisionMessage' => $revisionInfo['message'],
          'createdBy'       => $revisionInfo['createdBy'],
          'revisionData'    => $revisionData,
        );
        $revision = new Revision($params);
        $this->revisions[$revisionInfo['revisionNumber']] = $revision;
      }
    }
  }

  private function populateObjectWithColumnRevisions($column)
  {
    $revisionDataHasBeenPulled = (isset($this->revisionDataHasBeenPulled[$column])) ? true : false;
    $revisionData = $this->makeRevisionDataObjects(null, $column, $revisionDataHasBeenPulled, null, $this->findOldestRevisionNumberPulled($column));
    foreach ($revisionData as $key => $value) {
      if (!$this->revisionsHaveBeenPulled) {
        $splFixedArrayLength = $value->getRevisionNumber();
        $this->revisionsHaveBeenPulled = true;
        $this->revisions = new \SplFixedArray($splFixedArrayLength + 1);
      }
      $revisionInfo   = $this->getRevisions(null, null, $value->getRevisionId());
      $revisionParams = array(
        'revisionNumber'  => $revisionInfo[0]['revisionNumber'],
        'revisionDate'    => $revisionInfo[0]['createdOn'],
        'revisionMessage' => $revisionInfo[0]['message'],
        'createdBy'       => $revisionInfo[0]['createdBy'],
        'revisionData'    => array($column => $value),
      );
      $revision = new Revision($revisionParams);
      $this->revisions[$revisionInfo[0]['revisionNumber']] = $revision;
    }
  }

  /**
   * function to make an array of revisionData objects keyed by column
   * @param  integer $revisionId
   * @param  string $column
   * @param  boolean $revisionsHaveBeenPulled
   * @param  integer $limit
   * @param  integer $prevRevisionNumber
   * @return array
   */
  private function makeRevisionDataObjects($revisionId, $column = null, $revisionsHaveBeenPulled = false, $limit = null, $prevRevisionNumber = null)
  {
    $revisionDataInfo = $this->getRevisionData($revisionId, $column, $revisionsHaveBeenPulled, $limit, $prevRevisionNumber);
    //var_dump($revisionDataInfo);
    $revisionDataArray = array();
    foreach ($revisionDataInfo as $key => $value) {
      if ($revisionId === null && $column !== null) {
        foreach ($value as $subKey => $subValue) {
          if (!isset($this->revisionDataHasBeenPulled[$key])) {
            // first revisionData will be current text
            $this->currentContent[$key] = $subValue['value'];
            $this->revisionDataHasBeenPulled[$key] = true;
            $this->previousContent[$key] = $subValue['value'];
          }
          $params = array(
            'revisionId'      => $subValue['revisionId'],
            'revisionNumber'  => $subKey,
            'value'           => $subValue['value'],
            'currentContent'  => $this->previousContent[$key],
            );
          $revisionData = new RevisionData($params);
          $this->previousContent[$key] = $revisionData->makeRevisionContent();
          $revisionDataArray[$subKey] = $revisionData;
        }
      } else {
        if (!isset($this->revisionDataHasBeenPulled[$key])) {
          // first revisionData will be current text
          $this->currentContent[$key] = $value['value'];
          $this->revisionDataHasBeenPulled[$key] = true;
          $this->previousContent[$key] = $value['value'];
        }
        $params = array(
          'revisionId'      => $revisionId,
          'revisionNumber'  => $value['revisionNumber'],
          'value'           => $value['value'],
          'currentContent'  => $this->previousContent[$key],
          );
        $revisionData = new RevisionData($params);
        $this->previousContent[$key] = $revisionData->makeRevisionContent();
        $revisionDataArray[$key] = $revisionData;
      }
    }
    return $revisionDataArray;
  }

  /**
   * function to get the oldest revision number pulled into the object
   * @param $column
   * @return integer
   */
  private function findOldestRevisionNumberPulled($column = null)
  {
    if ($this->revisions === null) {
      return null;
    }
    if ($column !== null) {
      return $this->findOldestColumnRevisionNumberPulled($column);
    }
    foreach ($this->revisions as $key => $value) {
      if ($value !== null) {
        return $key;
      }
    }
    return null;
  }

  /**
   * function to get the oldest column revision number pulled into the object
   * @param $column
   * @return integer
   */
  private function findOldestColumnRevisionNumberPulled($column = null)
  {
    if ($this->revisions === null) {
      return null;
    }
    foreach ($this->revisions as $key => $value) {
      if ($value !== null) {
        if ($value->revisionContainsColumnRevisionData($column)) {
          return $value->getRevisionDataNumber($column);
        }
      }
    }
    return null;
  }

  /**
   * pulls a specific revision out of the object to return
   * @param  integer $revisionNumber revision number you want
   * @return string
   */
  public function getRevisionByNumber($revisionNumber)
  {
    if (!$this->revisionDataHasBeenPulled) {
      // no revisions in the object
      $this->populateObjectWithRevisions();
    }
    if ($this->revisions === null || count($this->revisions) <= $revisionNumber) {
      return null;
    }
    while ($this->revisions[$revisionNumber] === null) {
      // keep pulling in revisions until the revision number is in the object
      $this->populateObjectWithRevisions();
    }
    return $this->revisions[$revisionNumber];
  }

  /**
   * pulls revisions out of the object to return an array keyed by revision
   * @return array of revisions
   */
  public function getRevisionObjects()
  {
    if (!$this->revisionDataHasBeenPulled) {
      // no revisions in the object
      $this->populateObjectWithRevisions();
    }
    return $this->revisions;
  }
}