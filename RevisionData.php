<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * A single RevisionData object containing DiffInfo objects
 *
 * @package Revisions
 */
abstract class RevisionData extends RevisionsBase
{
  /**
   * revisionData's revision number of how many times this specific value has changed
   *
   * @var int revisionNumber
   */
  protected $revisionNumber;

  /**
   * revisionData's revision's revision number
   *
   * @var int revisionRevisionNumber
   */
  protected $revisionRevisionNumber;

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
   * added content size
   *
   * @var integer
   */
  protected $addedContentSize = 0;

  /**
   * removed content size
   *
   * @var integer
   */
  protected $removedContentSize = 0;

  /**
   * @return integer
   */
  public function getRevisionNumber()
  {
    return (int) $this->revisionNumber;
  }

  /**
   * @return integer
   */
  public function getRevisionRevisionNumber()
  {
    return (int) $this->revisionRevisionNumber;
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

  /**
   * get size of revision content
   *
   * @return integer
   */
  public function getRevisionContentSize()
  {
    return strlen($this->getRevisionContent());
  }

  /**
   * get size of current content
   *
   * @return integer
   */
  public function getCurrentContentSize()
  {
    return strlen($this->getCurrentContent());
  }

  /**
   * get size of removed content
   *
   * @return integer
   */
  public function getRemovedContentSize()
  {
    return $this->removedContentSize;
  }

  /**
   * get size of added content
   *
   * @return integer
   */
  public function getAddedContentSize()
  {
    return $this->addedContentSize;
  }
}