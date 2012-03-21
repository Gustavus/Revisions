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
   * @var int number
   */
  protected $number;

  /**
   * revisionData's revision's revision number
   *
   * @var int revisionNumber
   */
  protected $revisionNumber;

  /**
   * Current content if this is the latest revision, or the revision content of the previous revision
   *
   * @var string current cell content
   */
  protected $nextContent;

  /**
   * Content of this revision before it was changed. Result of following the revision info from the nextContent back
   *
   * @var string revision cell content
   */
  protected $content;

  /**
   * @var integer of revision's id
   */
  protected $id;

  /**
   * array of DiffInfo objects
   *
   * @var array
   */
  protected $diffInfo = array();

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
    return (int) $this->number;
  }

  /**
   * @return integer
   */
  public function getRevisionRevisionNumber()
  {
    return (int) $this->revisionNumber;
  }

  /**
   * @return string
   */
  public function getNextContent()
  {
    return $this->nextContent;
  }

  /**
   * @param boolean $showChanges
   * @return string
   */
  public function getContent($showChanges = false)
  {
    if ($showChanges) {
      return $this->getContentDiff();
    }
    if (!isset($this->content)) {
      $this->content = $this->renderRevision();
    }
    return $this->content;
  }

  /**
   * @return string
   */
  public function getContentDiff()
  {
    return $this->renderRevision(true);
  }

  /**
   * @return string
   */
  public function getDiffInfo()
  {
    return $this->diffInfo;
  }

  /**
   * @return string
   */
  public function getRevisionId()
  {
    return (int) $this->id;
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
  public function setContent($content)
  {
    $this->content = $content;
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
   * @param array $diffInfo
   * @return void
   */
  public function setDiffInfo($diffInfo)
  {
    $this->diffInfo = $diffInfo;
  }

  /**
   * @param array $nextContent
   * @return void
   */
  public function setNextContent($nextContent)
  {
    $this->nextContent = $nextContent;
  }

  /**
   * get size of revision content
   *
   * @return integer
   */
  public function getContentSize()
  {
    return strlen($this->getContent());
  }

  /**
   * get size of current content
   *
   * @return integer
   */
  public function getNextContentSize()
  {
    return strlen($this->getNextContent());
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