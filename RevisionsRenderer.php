<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'Gustavus/TwigFactory/TwigFactory.php';

/**
 * Renders out revisions to the application
 *
 * @package Revisions
 */
class RevisionsRenderer
{
  /**
   * @var Revisions
   */
  private $revisions;

  /**
   * Class constructor
   * @param Revisions $revisions
   */
  public function __construct(Revisions $revisions)
  {
    $this->revisions = $revisions;
    if (\Config::isBeta()) {
      \Gustavus\TwigFactory\TwigFactory::getTwigFilesystem('/cis/lib/Gustavus/Revisions/views')->clearCacheFiles();
    }
  }

  /**
   * @return mixed
   */
  public function __get($name)
  {
    switch ($name) {
      case 'revisions':
        return $this->revisions->getRevisionObjects();

      default:
        if ($this->__isset($name)) {
          return $this->{$name};
        }
    }
  }

  /**
   * @param string $name
   * @return boolean
   */
  public function __isset($name)
  {
    return isset($this->{$name});
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->revisions);
  }

  /**
   * Renders out all the revisions with information about them
   *
   * @param  integer $limit
   * @return string
   */
  public function renderRevisions($limit = 5)
  {
    $this->revisions->setLimit($limit);
    return \Gustavus\TwigFactory\TwigFactory::renderTwigFilesystemTemplate('/cis/lib/Gustavus/Revisions/views/revisions.twig', array('revisions' => $this->revisions->getRevisionObjects()));
  }

  /**
   * Renders out a table of revisionData for each column with the old content, and new content
   *
   * @param  integer oldRevNum
   * @param  integer $newRevNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionComparisonText($oldRevNum, $newRevNum, $column = null)
  {
    return \Gustavus\TwigFactory\TwigFactory::renderTwigFilesystemTemplate('/cis/lib/Gustavus/Revisions/views/revisionDataText.twig', array('revisions' => $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $column)));
  }

  /**
   * Renders out a table of revisionData for each column with the diff of what changed from the old content to get the new content
   *
   * @param  integer oldRevNum
   * @param  integer $newRevNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionComparisonDiff($oldRevNum, $newRevNum, $column = null)
  {
    return \Gustavus\TwigFactory\TwigFactory::renderTwigFilesystemTemplate('/cis/lib/Gustavus/Revisions/views/revisionDataDiff.twig', array('revisions' => $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $column)));
  }

  /**
   * Renders out a table of revisionData for each column with the diff of what changed from the old content to get the new content
   *
   * @param  integer oldRevNum
   * @param  integer $newRevNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionComparisonTextDiff($oldRevNum, $newRevNum, $column = null)
  {
    return \Gustavus\TwigFactory\TwigFactory::renderTwigFilesystemTemplate('/cis/lib/Gustavus/Revisions/views/revisionDataTextDiff.twig', array('revisions' => $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $column)));
  }
}