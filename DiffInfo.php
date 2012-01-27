<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'Gustavus/Revisions/RevisionsBase.php';

/**
 * Contains information on how to roll back each specific part of a revision
 *
 * @package Revisions
 */
class DiffInfo extends RevisionsBase
{
  /**
   * @var mixed index change starts
   */
  protected $startIndex;

  /**
   * @var mixed index change ends
   */
  protected $endIndex;

  /**
   * @var mixed content revision contained
   */
  protected $revisionInfo;

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
    unset($this->startInder, $this->endIndex, $this->revisionInfo);
  }

  /**
   * @return mixed
   */
  public function getStartIndex()
  {
    if ($this->startIndex === null) {
      return $this->startIndex;
    } else {
      return (int) $this->startIndex;
    }
  }

  /**
   * @return mixed
   */
  public function getEndIndex()
  {
    if ($this->endIndex === null) {
      return $this->endIndex;
    } else {
      return (int) $this->endIndex;
    }
  }

  /**
   * @return mixed
   */
  public function getRevisionInfo()
  {
    return $this->revisionInfo;
  }
}