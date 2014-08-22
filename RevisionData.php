<?php
/**
 * @package Revisions
 * @author  Billy Visto
 */
namespace Gustavus\Revisions;

use Gustavus\Extensibility\Filters;

/**
 * A single RevisionData object containing DiffInfo objects
 *
 * @package Revisions
 * @author  Billy Visto
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
   * Revision number that the current content belongs to
   *
   * @var integer
   */
  protected $nextContentRevisionNumber;

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
   * Strategy to use for splitting strings up to generate a diff
   *   Currently implemented strategies:
   *   <ul>
   *     <li>words: Splits at every word boundary.</li>
   *     <li>sentenceOrTag: Splits at every '.','?', '!', or any tag starting with '<' and ending with '>'.</li>
   *   </ul>
   *
   * @var string
   */
  protected $splitStrategy = 'words';

  /**
   * Array of valid split strategies
   *
   * @var array
   */
  public static $validSplitStrategies = [
    'words',
    'sentenceOrTag',
  ];

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
   * @param integer $currentRevisionNumber Number of the revision we are currently working with
   * @param boolean $runFilters Whether to run filters on the rendered content or not.
   * @return string
   */
  public function getContent($showChanges = false, $currentRevisionNumber = null, $runFilters = false)
  {
    if ($showChanges && ($currentRevisionNumber === null || $currentRevisionNumber >= $this->getNextContentRevisionNumber())) {
      // revisionData doesn't belong to a future revision, so we can render the diff

      $content = $this->getContentDiff();
      if ($runFilters && Filters::exists(API::RENDER_REVISION_FILTER)) {
        return Filters::apply(API::RENDER_REVISION_FILTER, $content);
      }
      return $content;
    }
    if (!isset($this->content)) {
      $this->content = $this->renderRevision();
    }

    if ($runFilters && Filters::exists(API::RENDER_REVISION_FILTER)) {
      return Filters::apply(API::RENDER_REVISION_FILTER, $this->content);
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
    if ($this->contentIsNumeric()) {
      return $this->getContent();
    } else {
      return strlen($this->getContent());
    }
  }

  /**
   * get size of current content
   *
   * @return integer
   */
  public function getNextContentSize()
  {
    if ($this->contentIsNumeric()) {
      return $this->getNextContent();
    } else {
      return strlen($this->getNextContent());
    }
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

  /**
   * get revision number of next content
   *
   * @return integer
   */
  public function getNextContentRevisionNumber()
  {
    return (int) $this->nextContentRevisionNumber;
  }

  /**
   * Checks to see if the content is numeric or not for rendering out changes
   *
   * @return boolean
   */
  public function contentIsNumeric()
  {
    return ((is_int($this->getContent()) || is_float($this->getContent())) && (is_int($this->getNextContent()) || is_float($this->getNextContent())));
  }
}