<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'revisions/classes/revisionDataUtil.class.php';

/**
 * @package Revisions
 */
class RevisionData extends revisionDataUtil
{
  /**
   * revisionData's revision number of how many times this specific value has changed
   *
   * @var int revisionNumber
   */
  private $revisionNumber;

  /**
   * @var string current cell content
   */
  private $currentContent;

  /**
   * @var string revision cell content
   */
  private $revisionContent;

  /**
   * @var integer of revision's revisionId
   */
  private $revisionId;

  /**
   * array of keyless arrays of startIndex, endIndex, revisionContent in that order
   *
   * @var array of revision information
   */
  private $revisionInfo = array();

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
    unset($this->revisionNumber, $this->currentContent, $this->revisionContent, $this->revisionId, $this->revisionInfo, $this->error);
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
  public function getCurrentContent()
  {
    return $this->currentContent;
  }

  /**
   * @return string
   */
  public function getRevisionContent()
  {
    if (!isset($this->revisionContent)) {
      $this->revisionContent = $this->renderRevision();
    }
    return $this->revisionContent;
  }

  /**
   * @return string
   */
  public function getRevisionInfo()
  {
    return $this->revisionInfo;
  }

  /**
   * @return string
   */
  public function getRevisionId()
  {
    return (int) $this->revisionId;
  }

  /**
   * @return boolean
   */
  public function getError()
  {
    return $this->error;
  }

  /**
   * @param string $content
   * @return void
   */
  public function setRevisionContent($content)
  {
    $this->revisionContent = $content;
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
   * @param array $revisionInfo
   * @return void
   */
  public function setRevisionInfo($revisionInfo)
  {
    $this->revisionInfo = $revisionInfo;
  }

  /**
   * @param array $currentContent
   * @return void
   */
  public function setCurrentContent($currentContent)
  {
    $this->currentContent = $currentContent;
  }
}