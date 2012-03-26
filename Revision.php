<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
use \Format;

/**
 * A single Revision object that contains many RevisionData objects
 *
 * @package Revisions
 */
class Revision extends RevisionsBase
{
  /**
   * @var int id
   */
  protected $id;

  /**
   * revisionData's revision number of how many times it has changed to get to this point
   *
   * @var int number
   */
  protected $number;

  /**
   * @var DateTime when revision was made
   */
  protected $date;

  /**
   * @var string message
   */
  protected $message;

  /**
   * @var string createdBy
   */
  protected $createdBy;

  /**
   * @var array of revisionData objects keyed by column
   */
  protected $revisionData;

  /**
   * @var array of columns modified in this specific revision
   */
  protected $modifiedColumns;

  /**
   * flag set to true if the hash doesn't compute correctly
   *
   * @var boolean
   */
  protected $error = false;

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
    unset($this->id, $this->number, $this->date, $this->message, $this->createdBy, $this->revisionData, $this->modifiedColumns, $this->error);
  }

  /**
   * @return integer
   */
  public function getRevisionId()
  {
    return (int) $this->id;
  }

  /**
   * @return integer
   */
  public function getRevisionNumber()
  {
    return (int) $this->number;
  }

  /**
   * @return string
   */
  public function getRevisionDate()
  {
    return $this->date;
  }

  /**
   * Outputs a sentence of how long ago this revision was made.
   * ie. 2 years ago, 3 months ago, 5 days ago, 1 day ago, 3 hours ago, 4 minutes ago, and 23 seconds ago.
   *
   * @return string
   */
  public function getRevisionRelativeDate()
  {
    require_once('format/format.class.php');
    $now       = new \DateTime('now');
    $date      = new \DateTime($this->date);
    $interval  = $date->diff($now);
    $relative  = array();
    $days      = (int) $interval->format('%d');
    if ((int) $interval->format('%a') > 1) {
      $years    = (int) $interval->format('%y');
      $months   = (int) $interval->format('%m');
      if ($years !== 0) {
        $relative[] = Format::quantity($years, 'year ', 'years ');
      } else if ($months !== 0) {
        $relative[] = Format::quantity($months, 'month ', 'months ');
      } else if ($days !== 0) {
        $weeks = (int) floor($days / 7);
        $days  = $days % 7;
        if ($weeks !== 0) {
          $relative[] = Format::quantity($weeks, 'week ', 'weeks ');
        } else if ($days !== 0) {
          $relative[] = Format::quantity($days, 'day ', 'days ');
        }
      }
    } else {
      $hours    = (int) $interval->format('%h');
      $minutes  = (int) $interval->format('%i');
      if ($days !== 0) {
        $relative[] = Format::quantity($days, 'day ', 'days ');
      } else if ($hours !== 0) {
        $relative[] = Format::quantity($hours, 'hour ', 'hours ');
      } else if ($minutes !== 0) {
        $relative[] = Format::quantity($minutes, 'minute ', 'minutes ');
      } else if (empty($relative)) {
        $seconds = (int) $interval->format('%s');
        $relative[] = Format::quantity($seconds, 'second ', 'seconds ');
      }
    }
    return Format::arrayToSentence($relative) . ' ago';
  }

  /**
   * @return string
   */
  public function getCreatedBy()
  {
    return $this->createdBy;
  }

  /**
   * @return string
   */
  public function getRevisionMessage()
  {
    return $this->message;
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
   * @return array
   */
  public function getModifiedColumns()
  {
    return $this->modifiedColumns;
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
   * @param string $column
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
      $return[$column] = $revisionData->getContent();
    }
    return $return;
  }
}