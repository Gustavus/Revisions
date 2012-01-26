<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'revisions/classes/revisionsBase.class.php';
require_once 'revisions/classes/diffInfo.class.php';

/**
 * A single RevisionData object
 *
 * @package Revisions
 */
abstract class RevisionData extends RevisionsBase
{
  /**
   * index of the start index in the revisionInfo
   */
  const START_INDEX   = 0;

  /**
   * index of the end index in the revisionInfo
   */
  const END_INDEX     = 1;

  /**
   * index of the revision info in the revisionInfo
   */
  const REVISION_INFO = 2;
  /**
   * revisionData's revision number of how many times this specific value has changed
   *
   * @var int revisionNumber
   */
  protected $revisionNumber;

  /**
   * Current content if this is the latest revision, or the revision content of the previous revision
   *
   * @var string current cell content
   */
  protected $currentContent;

  /**
   * Content of this revision before it was changed. Result of following the revision info from the currentContent back
   *
   * @var string revision cell content
   */
  protected $revisionContent;

  /**
   * @var integer of revision's revisionId
   */
  protected $revisionId;

  /**
   * array of DiffInfo objects
   *
   * @var array
   */
  protected $revisionInfo = array();

  /**
   * flag set to true if the hash doesn't compute correctly
   *
   * @var boolean
   */
  protected $error = false;

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