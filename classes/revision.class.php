<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * A single Revision object that contains many RevisionData objects
 *
 * @package Revisions
 */
class Revision
{
  /**
   * @var int revisionId
   */
  private $revisionId;

  /**
   * data's revision number of how many times it has changed to get to this point
   *
   * @var int revisionNumber
   */
  private $revisionNumber;

  /**
   * @var DateTime when revision was made
   */
  private $revisionDate;

  /**
   * @var string revisionMessage
   */
  private $revisionMessage;

  /**
   * @var string createdBy
   */
  private $createdBy;

  /**
   * @var array of RevisionData objects keyed by column
   */
  private $revisionData;

  /**
   * flag set to true if the hash doesn't compute correctly
   *
   * @var boolean
   */
  private $error = false;

  /**
   * Class constructor
   *
   * @param array $params
   */
  public function __construct(array $params = array())
  {
    $this->populateObjectWithArray($params);
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->revisionId, $this->revisionNumber, $this->revisionDate, $this->revisonMessage, $this->createdBy, $this->revisionData, $this->error);
  }

  /**
   * @return integer
   */
  public function getRevisionId()
  {
    return (int) $this->revisionId;
  }

  /**
   * @return integer
   */
  public function getRevisionNumber()
  {
    return (int) $this->revisionNumber;
  }

  /**
   * @return string
   */
  public function getRevisionDate()
  {
    return $this->revisionDate;
  }

  /**
   * @param string $column
   * @return array
   */
  public function getRevisionData($column = null)
  {
    if ($column === null) {
      return $this->revisionData;
    } else {
      return $this->getRevisionDataByColumn($column);
    }
  }

  /**
   * @return boolean
   */
  public function getError()
  {
    return $this->error;
  }

  /**
   * @param boolean $isError
   * @return void
   */
  public function setError($isError)
  {
    $this->error = $isError;
  }

  /**
   * @return array
   */
  private function getRevisionDataByColumn($column)
  {
    if ($this->revisionContainsColumnRevisionData($column)) {
      return $this->revisionData[$column];
    } else {
      return null;
    }
  }

  /**
   * @param string $column
   * @return boolean
   */
  public function revisionContainsColumnRevisionData($column)
  {
    return isset($this->revisionData[$column]);
  }

  /**
   * @param string $column
   * @return boolean
   */
  public function getRevisionDataRevisionNumber($column)
  {
    if ($this->revisionContainsColumnRevisionData($column)) {
      return $this->revisionData[$column]->getRevisionNumber();
    } else {
      return null;
    }
  }

  /**
   * @return array
   */
  public function getRevisionDataContentArray()
  {
    $return = array();
    foreach ($this->revisionData as $column => $revisionData) {
      $return[$column] = $revisionData->getRevisionContent();
    }
    return $return;
  }

  /**
   * @param array $array
   * @return void
   */
  private function populateObjectWithArray(Array $array)
  {
    foreach ($array as $key => $value) {
      if (property_exists($this, $key)) {
        $this->$key = $value;
      }
    }
  }
}