<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'revisions/classes/revisionsPuller.class.php';
require_once 'revisions/classes/revision.class.php';
require_once 'revisions/classes/revisionDataDiff.class.php';

/**
 * @package Revisions
 */
class Revisions extends RevisionsPuller
{
  /**
   * @var SplFixedArray of revisions keyed by revision number
   */
  private $revisions;

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
    unset($this->revisions, $this->revisionDataHasBeenPulled, $this->revisionsHaveBeenPulled);
  }

  /**
   * Makes new RevisionDataDiff for oldText and newText
   *
   * @param string $oldText
   * @param string $newText
   * @return Revision
   */
  public function makeRevisionData($oldText, $newText)
  {
    $revisionData = new RevisionDataDiff(array('currentContent' => $oldText));
    $revisionData->makeRevisionContent($newText);
    return $revisionData;
  }

  /**
   * Makes new Revision with revisionData
   *
   * @param array $revisionData
   * @return Revision
   */
  public function makeRevision(array $revisionData)
  {
    $revision = new Revision(array('revisionData' => $revisionData));
    return $revision;
  }

  /**
   * Takes two revisions and returns a new revision containing only the diff of the two
   *
   * @param  integer  $revisionANum revision number to compare against
   * @param  integer  $revisionBNum revision number to compare
   * @param  string  $column       column to compare if only looking for a specific column
   * @return Revision
   */
  public function compareTwoRevisions($revisionANum, $revisionBNum, $column = null)
  {
    $revisionDataArray = array();
    $revA = $this->getRevisionByNumber($revisionANum);
    $revB = $this->getRevisionByNumber($revisionBNum);
    foreach ($revA->getRevisionData($column) as $key => $revisionData) {
      $revBDataContent = $revB->getRevisionData($key)->makeRevisionContent();
      $revADataContent = $revisionData->makeRevisionContent();
      $revisionDataArray[$key] = $this->makeRevisionData($revBDataContent, $revADataContent);
    }
    $revision = $this->makeRevision($revisionDataArray);
    return $revision;
  }

  /**
   * Makes and stores a revision
   *
   * @param  array $newText       array of text that has replaced the old text keyed by column
   * @param  string $message      revision message
   * @param  string $createdBy    person creating revision
   * @return void
   */
  public function makeAndSaveRevision(array $newText, $message = '', $createdBy = '')
  {
    $revisionInfoArray    = array();
    $oldRevisionDataArray = array();
    $oldText              = array();
    foreach ($newText as $key => $value) {
      $oldRevisionData = $this->getRevisionData(null, $key, true, 1);
      $oldRevisionDataArray = array_merge($oldRevisionDataArray, $oldRevisionData);
      if (isset($oldRevisionData[$key])) {
        // revision exists in DB, so the first item will be the full current content
        $oldContentArray         = array_shift($oldRevisionData[$key]);
        $revisionData            = new RevisionDataDiff(array('currentContent' => $oldContentArray['value']));
        $oldText[$key]           = $oldContentArray['value'];
      } else {
        // revision doesn't exist yet
        $revisionData            = new RevisionDataDiff(array('currentContent' => ''));
        $oldText[$key]           = '';
      }
      $revisionInfo            = $revisionData->renderRevisionForDB($value);
      $revisionInfoArray[$key] = $revisionInfo;
    }
    $missingColumns = $this->findMissingColumns($newText);
    foreach ($missingColumns as $column) {
      $missingRevisionDataInfo = $this->getRevisionData(null, $column, true, 1, null, true);
      $newText[$column] = $missingRevisionDataInfo[$column]['value'];
    }
    $this->saveRevision($revisionInfoArray, $newText, $oldText, $oldRevisionDataArray, $message, $createdBy);
  }

  /**
   * Gets and stores revisions in the object
   * Defaults to pull the latest 10 revisions to cache in the object
   *
   * @param string $column
   * @return void
   */
  private function populateObjectWithRevisions($column = null)
  {
    $currentContent = null;
    $revisions = $this->getRevisions($this->findOldestRevisionNumberPulled(), null, null, $column);

    foreach ($revisions as $revisionInfo) {
      $revisionData = $this->makeRevisionDataObjects((int) $revisionInfo['id']);
      if (!$this->revisionsHaveBeenPulled) {
        $splFixedArrayLength = $revisionInfo['revisionNumber'] + 1;
        $this->revisionsHaveBeenPulled = true;
        $this->revisions = new \SplFixedArray($splFixedArrayLength);
        $previousError = false;
      } else {
        $previousRevision = $this->getOldestRevisionPulled();
        $previousError = $previousRevision->getError();
      }
      if (!$previousError) {
        $params = array(
          'revisionId'      => $revisionInfo['id'],
          'revisionNumber'  => $revisionInfo['revisionNumber'],
          'revisionDate'    => $revisionInfo['createdOn'],
          'revisionMessage' => $revisionInfo['message'],
          'createdBy'       => $revisionInfo['createdBy'],
          'revisionData'    => $revisionData,
        );
        $revision = new Revision($params);
        if ($this->generateHashFromArray($revision->getRevisionDataContentArray()) !== $revisionInfo['contentHash']) {
          $revision->setError(true);
        }
        $this->revisions[$revisionInfo['revisionNumber']] = $revision;
      }
    }
  }

  /**
   * Makes an array of revisionData objects keyed by column
   *
   * @param  integer $revisionId
   * @return array
   */
  private function makeRevisionDataObjects($revisionId)
  {
    assert('is_int($revisionId)');
    $revisionDataInfo = $this->getRevisionData($revisionId);
    $revisionDataArray = array();

    $missingColumns = $this->findMissingColumns($revisionDataInfo);
    $revisionDataInfo = array_merge($revisionDataInfo, $this->getMissingRevisionDataInfo($missingColumns, $revisionId));

    foreach ($revisionDataInfo as $key => $value) {
      if (!isset($this->revisionDataHasBeenPulled[$key])) {
        // first revisionData will be current text
        $previousError = false;
        if (!in_array($key, $missingColumns)) {
          // we dont want to say that revision has been pulled if we are just populating the latest revision with the missing fields
          $this->revisionDataHasBeenPulled[$key] = true;
        }
        $previousContent = $value['value'];
      } else {
        $previousRevision = $this->getOldestRevisionDataPulled($key, $revisionId);
        $previousContent = $previousRevision->makeRevisionContent();
        $previousError = $previousRevision->getError();
      }
      $revisionInfo = $value['value'];
      if (!$previousError) {
        if (isset($previousRevision) && $previousRevision->getRevisionId() === $revisionId) {
          $revisionData = $previousRevision;
        } else {
          $params = array(
            'revisionId'      => $value['revisionId'],
            'revisionNumber'  => $value['revisionNumber'],
            'revisionInfo'    => $revisionInfo,
            'currentContent'  => $previousContent,
          );
          $revisionData = new RevisionDataDiff($params);

          if (md5($revisionData->getRevisionContent()) !== $value['contentHash']) {
            $revisionData->setError(true);
          }
        }
        $revisionDataArray[$key] = $revisionData;
      }
    }

    if ($this->revisionsHaveBeenPulled) {
      $missingColumns = $this->findMissingColumns($revisionDataInfo);
      $revisionDataArray = array_merge($revisionDataArray, $this->getMissingRevisionDataFromObject($missingColumns));
    }
    return $revisionDataArray;
  }

  /**
   * Finds the missing columns' revision data
   *
   * @param  array $missingColumns columns this revision doesnt have that others do
   * @param  integer $revisionId     current revision's id
   * @return array
   */
  private function getMissingRevisionDataInfo(array $missingColumns, $revisionId)
  {
    assert('is_int($revisionId)');
    $revisionDataInfo = array();
    foreach ($missingColumns as $missingColumn) {
      if (isset($this->revisionDataHasBeenPulled[$missingColumn])) {
        // revision has been pulled, so we might need to pull a later revision to get the current revision content
        $oldestColumnRevisionData = $this->getOldestRevisionDataPulled($missingColumn);

        if ($oldestColumnRevisionData->getRevisionId() > $revisionId) {
          // pulled revision data is newer than the revision we are working with, so pull later revision
          $missingRevisionDataInfo = $this->getRevisionData(null, $missingColumn, true, 1, $oldestColumnRevisionData->getRevisionNumber(), true);
        } else {
          // pulled revision data is older than the revision we are working with
          $missingRevisionDataInfo = array();
        }
      } else {
        $missingRevisionDataInfo = $this->getRevisionData(null, $missingColumn, true, 1, null, true);
      }
      $revisionDataInfo = array_merge($revisionDataInfo, $missingRevisionDataInfo);
    }
    return $revisionDataInfo;
  }

  /**
   * Gets missing revisionData looking for the oldest revisionData pulled and uses that object.
   *
   * @param  array  $missingColumns
   * @return array
   */
  private function getMissingRevisionDataFromObject(array $missingColumns = array())
  {
    $missingRevisionData = array();
    foreach ($missingColumns as $column) {
      $oldestRevisionData = $this->getOldestRevisionDataPulled($column);
      if ($oldestRevisionData->getRevisionNumber() !== 1 || ($oldestRevisionData->getRevisionNumber() === 1 && $oldestRevisionData->getRevisionId() < $this->findOldestRevisionNumberPulled())) {
        $missingRevisionData[$column] = $oldestRevisionData;
      }
    }
    return $missingRevisionData;
  }

  /**
   * Finds columns in the database that aren't in the revisionInfo
   *
   * @param  array $revisionInfo revision info keyed by column
   * @return array
   */
  private function findMissingColumns($revisionInfo)
  {
    $allColumns = $this->getRevisionDataColumns();
    $missingColumns = array();
    foreach ($allColumns as $column) {
      if (!isset($revisionInfo[$column['key']])) {
        $missingColumns[] = $column['key'];
      }
    }
    return $missingColumns;
  }

  /**
   * Gets the oldest revision number pulled into the object
   *
   * @param string $column
   * @return integer
   */
  private function findOldestRevisionNumberPulled($column = null)
  {
    if (!isset($this->revisions)) {
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
   * Gets the oldest column revision number pulled into the object
   *
   * @param string $column
   * @return integer
   */
  private function findOldestColumnRevisionNumberPulled($column = null)
  {
    if (!isset($this->revisions)) {
      return null;
    }
    foreach ($this->revisions as $key => $value) {
      if ($value !== null) {
        if ($value->revisionContainsColumnRevisionData($column)) {
          return $value->getRevisionDataRevisionNumber($column);
        }
      }
    }
    return null;
  }

  /**
   * Gets the oldest revisionData pulled into the object
   *
   * @param string $column
   * @param integer $revisionId
   * @return integer
   */
  private function getOldestRevisionDataPulled($column = null, $revisionId = 0)
  {
    assert('is_int($revisionId)');
    if (!isset($this->revisions)) {
      return null;
    }
    foreach ($this->revisions as $key => $value) {
      if ($value !== null) {
        if ($value->revisionContainsColumnRevisionData($column) && $value->getRevisionId() > $revisionId) {
          return $value->getRevisionData($column);
        }
      }
    }
    return null;
  }

  /**
   * Gets the oldest revision pulled into the object
   *
   * @param string $column
   * @return integer
   */
  private function getOldestRevisionPulled($column = null)
  {
    if (!isset($this->revisions)) {
      return null;
    }
    foreach ($this->revisions as $key => $value) {
      if ($value !== null) {
        return $value;
      }
    }
    return null;
  }

  /**
   * Pulls a specific revision out of the object to return
   *
   * @param  integer $revisionNumber revision number you want
   * @param  string $column
   * @return string
   */
  public function getRevisionByNumber($revisionNumber, $column = null)
  {
    assert('is_int($revisionNumber)');
    if (!$this->revisionDataHasBeenPulled) {
      // no revisions in the object
      $this->populateObjectWithRevisions($column);
    }
    if (!isset($this->revisions) || !array_key_exists($revisionNumber, $this->revisions)) {
      return null;
    }
    if ($this->revisions[$revisionNumber] === null) {
      $oldestRevNumPulled = $this->findOldestRevisionNumberPulled();
      for ($i = $oldestRevNumPulled; $i >= $revisionNumber; --$i) {
        // keep pulling in revisions until the revision number is in the object
        $oldestRevisionPulled = $this->getOldestRevisionPulled($column);
        if ($oldestRevisionPulled->getError()) {
          break;
        }
        $this->populateObjectWithRevisions($column);
      }
    }
    return $this->revisions[$revisionNumber];
  }

  /**
   * Pulls revisions out of the object to return an array keyed by revision
   *
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