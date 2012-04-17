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
   * Figures out the class name based off of the first value in the array
   *
   * @param  array  $array
   * @return string
   */
  private function getReturnClassName(array $array)
  {
    // first key will be the greatest time measurement that isn't empty
    $firstKey = key($array);
    // return a single class name
    if (!empty($firstKey)) {
      if ($firstKey === 'second') {
        if ($array['second'] > 10) {
          return 'minute';
        } else {
          return 'now';
        }
      } else if ($array[$firstKey] > 1) {
        return $firstKey . 's';
      } else {
        return $firstKey;
      }
    } else {
      return 'now';
    }
  }

  /**
   * Make non specific relative date. Either makes a string or an array of data
   *
   * @param  array  $array
   * @param  integer $totalDays
   * @return mixed either a string, or an array
   */
  private function makeNonSpecificRelativeDate(array $array, $totalDays = 0)
  {
    require_once('format/format.class.php');
    // first key will be the greatest time measurement that isn't empty
    $firstKey = key($array);
    $return = array();

    if (!empty($firstKey)) {
      switch ($firstKey) {
        case 'day':
          if ($array['day'] === 1) {
            return ($totalDays < 0) ? 'Tomorrow': 'Yesterday';
          }
            break;
        case 'second':
          if ($array['second'] > 10) {
            return 'A few seconds ago';
          } else {
            return 'Just Now';
          }
            break;
        case 'year':
          if ($array['year'] > 1) {
            $return['startText'] = 'Around ';
          }
            break;
      }
      if ($array[$firstKey] === 1 && !in_array($firstKey, array('hour', 'minute', 'second'))) {
        return ($totalDays < 0) ? 'Next ' . $firstKey : 'Last ' . $firstKey;
      }
      $return['relative'] = Format::quantity($array[$firstKey], $firstKey . ' ', $firstKey . 's ');
    }
    return $return;
  }

  /**
   * Outputs a sentence of how long ago this revision was made.
   * ie. 2 years ago, 3 months ago, 5 days ago, 1 day ago, 3 hours ago, 4 minutes ago, and 23 seconds ago.
   *
   * @param mixed $date either a DateTime object or timestamp
   * @param boolean $returnClassName whether to return a single class or not
   * @param boolean $beSpecific whether to output the greatest time measurement, or to be as specific as possible
   * @return string
   */
  private function makeRelativeDate($date, $returnClassName = false, $beSpecific = false)
  {
    require_once('format/format.class.php');
    if (is_int($date)) {
      // $date is a timestamp. We want it as a DateTime object
      $date = new \DateTime('@'.$date);
    }
    $now         = new \DateTime('now');
    $interval    = $date->diff($now);
    $relative    = array();
    $intervalArr = array('day' => $interval->format('%d'));
    $days        = (int) $interval->format('%d');
    $totalDays   = (int) $interval->format('%r%a');
    $startText   = '';

    if ($totalDays > 1 || $totalDays < -1) {
      $intervalArr = array_filter(array(
          'year'  => (int) $interval->format('%y'),
          'month' => (int) $interval->format('%m'),
          'week'  => (int) floor($days / 7),
          'day'   => $days % 7,
          )
      );
    } else {
      $intervalArr = array_filter(array(
          'day'    => $days,
          'hour'   => (int) $interval->format('%h'),
          'minute' => (int) $interval->format('%i'),
          'second' => (int) $interval->format('%s'),
          )
      );
    }

    if ($returnClassName) {
      return $this->getReturnClassName($intervalArr);
    } else if (!$beSpecific) {
      $nonSpecificDate = $this->makeNonSpecificRelativeDate($intervalArr, $totalDays);
      if (is_array($nonSpecificDate)) {
        if (!empty($nonSpecificDate['startText'])) {
          $startText = $nonSpecificDate['startText'];
        }
        if (!empty($nonSpecificDate['relative'])) {
          $relative[] = $nonSpecificDate['relative'];
        }
      } else {
        return $nonSpecificDate;
      }
    } else {
      // make specific date array
      foreach ($intervalArr as $key => $value) {
        $relative[] = Format::quantity($value, $key . ' ', $key . 's ');
      }
    }

    if (empty($relative)) {
      // modified less than a second ago, output just now
      return 'Just Now';
    }

    if ($interval->format('%r') === "") {
      // we are going into the future if it is a "-". format('%r') returns either "" or "-"
      return $startText . Format::arrayToSentence($relative) . ' ago';
    } else {
      return $startText . Format::arrayToSentence($relative) . ' from now';
    }
  }

  /**
   * Gets the relative date to now.
   *
   * @return string
   */
  public function getRevisionRelativeDate($beSpecific = false)
  {
    $date = new \DateTime($this->date);
    return $this->makeRelativeDate($date, false, $beSpecific);
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