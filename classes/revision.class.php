<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * @package Revisions
 */
class Revision
{
  /**
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
    unset($this->currentContent, $this->revisionInfo, $this->revisionId, $this->revisionDate, $this->revisonMessage, $this->createdBy);
  }

  /**
   * @return string
   */
  public function getRevisionNumber()
  {
    return $this->revisionNumber;
  }

  /**
   * @return string
   */
  public function getRevisionDate()
  {
    return $this->revisionDate;
  }

  /**
   * @return array
   */
  public function getRevisionData()
  {
    return $this->revisionData;
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
  public function getRevisionDataNumber($column)
  {
    if ($this->revisionContainsColumnRevisionData($column)) {
      $revisionData = $this->revisionData[$column];
      return $revisionData->getRevisionNumber();
    } else {
      return null;
    }
  }

  /**
   * @param array $array
   * @return void
   */
  private function populateObjectWithArray(Array $array)
  {
    foreach ($array as $key => $value) {
      if (property_exists($this, $key)) {
        // if ($key === 'revisionData') {
        //   $this->revisionData = new SplObjectStorage();
        //   $this->revisionData
        // }
        $this->$key = $value;
      }
    }
  }
}