<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * Creates Revision objects and sets things up for saving revisions
 *
 * @package Revisions
 */
class Revisions extends RevisionsManager
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
   * array of max column sizes pulled into the object in bytes
   *
   * @var array
   */
  private $maxColumnSizes = array();

  /**
   * Class constructor
   *
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
    unset($this->revisions, $this->revisionDataHasBeenPulled, $this->revisionsHaveBeenPulled, $this->maxColumnSizes);
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
    $revisionData->makeRevisionDataInfo($newText);
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
   * returns maxColumnSizes array
   *
   * @return array
   */
  public function getMaxColumnSizes()
  {
    return $this->maxColumnSizes;
  }

  /**
   * Takes two revision numbers and returns a new revision containing only the diff of the two
   *
   * @param  integer  $oldRevisionNum revision number to compare against
   * @param  integer  $newRevisionNum revision number to compare
   * @param  array  $columns       columns to compare if only looking for specific columns
   * @return Revision
   */
  public function compareTwoRevisions($oldRevisionNum, $newRevisionNum, array $columns = array())
  {
    assert('is_int($oldRevisionNum)');
    assert('is_int($newRevisionNum)');
    $revisionDataArray = array();
    if ($newRevisionNum < $oldRevisionNum) {
      // to make sure we show how an older revision changed to get to the newer content
      $num = $newRevisionNum;
      $newRevisionNum = $oldRevisionNum;
      $oldRevisionNum = $num;
    }
    $revA = $this->getRevisionForComparison($oldRevisionNum, false);
    $revB = $this->getRevisionForComparison($newRevisionNum, true);
    if (empty($columns)) {
      $revBData = $revB->getRevisionData();
    } else {
      // this way we don't have to do something different for non arrays
      $revBData = array();
      foreach ($columns as $column) {
        $revBData[$column] = $revB->getRevisionData($column);
      }
    }
    foreach ($revBData as $key => $revisionDataB) {
      // revA might not have all the columns that B has
      $revisionDataA = $revA->getRevisionData($key);
      if ($revisionDataB->getError() || ($revisionDataA !== null && $revisionDataA->getError())) {
        $revisionDataArray[$key] = $this->makeRevisionData('', '');
        $revisionDataArray[$key]->setError(true);
      } else {
        $revADataContent = ($revisionDataA === null) ? '' : $revisionDataA->makeRevisionContent();
        $revBDataContent = $revisionDataB->makeRevisionContent();
        $revisionDataArray[$key] = $this->makeRevisionData($revADataContent, $revBDataContent);
      }
    }
    if ($revB->getError() || $revA->getError()) {
      // if either revisions being compared encountered an error, we want the new revision to also have an error
      $revision = $this->makeRevision($revisionDataArray);
      $revision->setError(true);
      return $revision;
    } else {
      // this way we don't always set a variable to return
      return $this->makeRevision($revisionDataArray);
    }
  }

  /**
   * Gets a revision by revision number, or it guesses what revision to return if that revision number is empty in the object.
   *
   * @param  integer  $revisionNum
   * @param  boolean $isNewerRevision whether the revision is newer or older than the one it is compared against
   * @return Revision
   */
  private function getRevisionForComparison($revisionNum, $isNewerRevision)
  {
    assert('is_int($revisionNum)');
    $revision = $this->getRevisionByNumber($revisionNum);
    if ($revision === null) {
      // if revision number doesn't exist, or there was an error, use the oldest revision pulled
      $revision = ($isNewerRevision) ? $this->getRevisionByNumber($this->findLatestRevisionNumberPulled()) : $this->getRevisionByNumber($this->findOldestRevisionNumberPulled());
    }
    return $revision;
  }

  /**
   * Makes and stores a revision
   * NewText array can be only fields that were edited. It will find missing fields and add them in when saving to get the correct hash
   *
   * @param  array $newText       array of text that has replaced the old text keyed by column
   * @param  string $message      revision message
   * @param  string $createdBy    person creating revision
   * @return boolean
   */
  public function makeAndSaveRevision(array $newText, $message = null, $createdBy = null)
  {
    $revisionInfoArray    = array();
    $oldRevisionDataArray = array();
    $oldText              = array();
    $brandNewColumns      = array();
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
        $brandNewColumns[]       = $key;
      }
      $revisionInfo            = $revisionData->renderRevisionForDB($value);
      $revisionInfoArray[$key] = $revisionInfo;
    }
    $columnInfo = $this->getColumnInformation($newText);
    foreach ($columnInfo['missingColumns'] as $column) {
      $missingRevisionDataInfo = $this->getRevisionData(null, $column, true, 1, null, true);
      $newText[$column] = $missingRevisionDataInfo[$column]['value'];
    }
    return $this->saveRevision($revisionInfoArray, $newText, $oldText, $oldRevisionDataArray, $message, $createdBy, $brandNewColumns);
  }

  /**
   * Gets and stores revisions in the object
   * Pulls in 1 revision at a time unless the limit is set in construction
   *
   * @return void
   */
  private function populateObjectWithRevisions()
  {
    $currentContent = null;
    $revisions = $this->getRevisions($this->findOldestRevisionNumberPulled(), null, null);

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
          'revisionData'    => $revisionData['revisionData'],
          'modifiedColumns' => $revisionData['modifiedColumns'],
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

    $columnInfo       = $this->getColumnInformation($revisionDataInfo);
    // set modified columns here so it doesn't get overwritten below if getColumnInformation gets called again
    $modifiedColumns  = $columnInfo['modifiedColumns'];
    $revisionDataInfo = array_merge($revisionDataInfo, $this->getMissingRevisionDataInfo($columnInfo['missingColumns'], $revisionId));

    foreach ($revisionDataInfo as $key => $value) {
      if (!isset($this->revisionDataHasBeenPulled[$key])) {
        // first revisionData will be current text
        $previousError = false;
        if (!in_array($key, $columnInfo['missingColumns'])) {
          // we dont want to say that revision has been pulled if we are just populating the latest revision with the missing fields
          $this->revisionDataHasBeenPulled[$key] = true;
        }
        $previousContent = $value['value'];
        $previousRevisionData = null;
      } else {
        $previousRevisionData = $this->getOldestRevisionDataPulled($key, $revisionId);
        $previousContent = $previousRevisionData->getRevisionContent();
        $previousError = $previousRevisionData->getError();
      }
      if (!$previousError) {
        if (isset($previousRevisionData) && $previousRevisionData->getRevisionId() === $revisionId) {
          $revisionData = $previousRevisionData;
        } else {
          $params = array(
            'revisionId'             => $value['revisionId'],
            'revisionNumber'         => $value['revisionNumber'],
            'revisionRevisionNumber' => $value['revisionRevisionNumber'],
            'revisionInfo'           => $this->makeDiffInfoObjects($value['value']),
            'currentContent'         => $previousContent,
          );
          $revisionData = new RevisionDataDiff($params);

          if (md5($revisionData->getRevisionContent()) !== $value['contentHash']) {
            $revisionData->setError(true);
          }
        }
        // set max column sizes
        if (!isset($this->maxColumnSizes[$key]) || $this->maxColumnSizes[$key] < $revisionData->getRevisionContentSize()) {
          $this->maxColumnSizes[$key] = $revisionData->getRevisionContentSize();
        }
        $revisionDataArray[$key] = $revisionData;
      }
    }

    if ($this->revisionsHaveBeenPulled) {
      $columnInfo = $this->getColumnInformation($revisionDataInfo);
      $revisionDataArray = array_merge($revisionDataArray, $this->getMissingRevisionDataFromObject($columnInfo['missingColumns']));
    }
    ksort($revisionDataArray);
    return array('revisionData' => $revisionDataArray, 'modifiedColumns' => $modifiedColumns);
  }

  /**
   * Makes DiffInfo Objects with the revisionInfo
   *
   * @param  mixed $revisionInfo revisionInfo pulled from getRevisionData
   * @return mixed
   */
  private function makeDiffInfoObjects($revisionInfo)
  {
    if (!is_array($revisionInfo)) {
      return $revisionInfo;
    }
    $return = array();
    foreach ($revisionInfo as $revInfo) {
      $return[] = new DiffInfo(array('startIndex' => $revInfo[0], 'endIndex' => $revInfo[1], 'revisionInfo' => $revInfo[2]));
    }
    return $return;
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
      if ($oldestRevisionData->getRevisionNumber() !== 0 || ($oldestRevisionData->getRevisionNumber() === 0 && $oldestRevisionData->getRevisionId() < $this->getOldestRevisionPulled()->getRevisionId())) {
        $missingRevisionData[$column] = $oldestRevisionData;
      }
    }
    return $missingRevisionData;
  }

  /**
   * Finds columns in the database that aren't in the revisionInfo as well as columns modified for a certain revision
   *
   * @param  array $revisionInfo revision info keyed by column
   * @return array
   */
  private function getColumnInformation($revisionInfo)
  {
    $allColumns = $this->getRevisionDataColumns();
    $missingColumns = array();
    $modifiedColumns = array();
    foreach ($allColumns as $column) {
      if (!isset($revisionInfo[$column['key']])) {
        $missingColumns[] = $column['key'];
      } else {
        $modifiedColumns[] = $column['key'];
      }
    }
    return array('missingColumns' => $missingColumns, 'modifiedColumns' => $modifiedColumns);;
  }

  /**
   * Gets the oldest revision number pulled into the object
   *
   * @param string $column
   * @return integer
   */
  public function findOldestRevisionNumberPulled($column = null)
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
   * Gets the latest revision number pulled into the object
   *
   * @return integer
   */
  public function findLatestRevisionNumberPulled()
  {
    if (!isset($this->revisions)) {
      return null;
    }
    for ($i = count($this->revisions) - 1; $i >= 0; --$i) {
      if ($this->revisions[$i] !== null) {
        return $i;
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
   * @return integer
   */
  private function getOldestRevisionPulled()
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
   * @return string
   */
  public function getRevisionByNumber($revisionNumber, $column = null)
  {
    assert('is_int($revisionNumber)');
    $this->populateEmptyRevisions($revisionNumber);
    if (!isset($this->revisions) || !array_key_exists($revisionNumber, $this->revisions)) {
      return null;
    }
    if ($this->revisions[$revisionNumber] === null) {
      $this->pullRevisionsUntilRevisionNumber($revisionNumber);
    }
    return $this->revisions[$revisionNumber];
  }

  /**
   * Pulls in revisions until the specified revision number is in the object
   *
   * @param  integer $revisionNumber
   * @return void
   */
  private function pullRevisionsUntilRevisionNumber($revisionNumber)
  {
    // $i = $i - $this->getLimit() avoids pulling in the limit everytime and allows us to jump ahead to only pull in the necessary amount of revisions
    for ($i = $this->findOldestRevisionNumberPulled(); $i > $revisionNumber; $i = $i - $this->getLimit()) {
      // keep pulling in revisions until the revision number is in the object
      $oldestRevisionPulled = $this->getOldestRevisionPulled();
      if ($oldestRevisionPulled->getError()) {
        break;
      }
      $this->populateObjectWithRevisions();
    }
  }

  /**
   * check if any revisions with errors have been pulled
   *
   * @return boolean
   */
  public function revisionsHaveErrors()
  {
    foreach ($this->revisions as $revision) {
      if ($revision !== null && $revision->getError()) {
        return true;
      }
    }
    return false;
  }

  /**
   * gets the revision content and returns an associative array for the application to use for saving
   * @param  integer $revisionNumber
   * @return array
   */
  public function getRevisionContentArray($revisionNumber)
  {
    $return = array();
    foreach ($this->getRevisionByNumber($revisionNumber)->getRevisionData() as $column => $revisionData) {
      $return[$column] = $revisionData->getRevisionContent();
    }
    return $return;
  }

  /**
   * Pulls revisions out of the object to return an array keyed by revision
   *
   * @param integer $oldestRevisionNumberToPull
   * @return array of revisions
   */
  public function getRevisionObjects($oldestRevisionNumberToPull = null)
  {
    $this->populateEmptyRevisions();
    if ($oldestRevisionNumberToPull !== null) {
      $oldestRevNumToPull = $oldestRevisionNumberToPull;
      $oldestRevPulled = $this->findOldestRevisionNumberPulled();
      if ($oldestRevNumToPull < 0) {
        $oldestRevNumToPull = 0;
      }
      if ($oldestRevPulled !== $oldestRevNumToPull) {
        // don't try to do anything if the oldest rev to pull is the oldest rev pulled
        $limit = $this->getLimit();
        // set limit to be the difference of the oldest revision pulled and the oldest revision to pull so it only makes one call to the DB
        $this->setLimit($oldestRevPulled - $oldestRevNumToPull);
        $this->pullRevisionsUntilRevisionNumber($oldestRevNumToPull);
        // set limit to be what it used to be
        $this->setLimit($limit);
      }
    }
    return $this->revisions;
  }

  /**
   * Populates revisions into the object if that hasn't happened already
   *
   * @param integer $revNum
   * @return void
   */
  public function populateEmptyRevisions($revNum = null)
  {
    if (!$this->revisionsHaveBeenPulled) {
      // no revisions in the object
      if ($revNum !== null) {
        $oldLimit = $this->getLimit();
        // set limit to be 1 so we can figure out how many revisions there are so we pull only the number of revisions we need
        $this->setLimit(1);
        $this->populateObjectWithRevisions();
        $limit = $this->findOldestRevisionNumberPulled() - $revNum;
        // set limit to only pull the number of revisions we need
        $this->setLimit($limit);
        $this->pullRevisionsUntilRevisionNumber($revNum);
        // set limit to what it originally was
        $this->setLimit($oldLimit);
      } else {
        $this->populateObjectWithRevisions();
      }
    }
  }
}