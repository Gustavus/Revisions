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
    return $this->renderTwig('revisions.twig', $this->revisions->getRevisionObjects());
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
    return $this->renderTwig('revisionDataText.twig', $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $column));
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
    return $this->renderTwig('revisionDataDiff.twig', $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $column));
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
    return $this->renderTwig('revisionDataTextDiff.twig', $this->revisions->compareTwoRevisions($oldRevNum, $newRevNum, $column));
  }

  /**
   * Renders out a table of revisionData for each column
   *
   * @param  integer revNum
   * @param  string $column
   * @return string
   */
  public function renderRevisionData($revNum, $column = null)
  {
    return $this->renderTwig('revisionData.twig', $this->revisions->getRevisionByNumber($revNum, $column));
  }

  /**
   * Renders out the template
   *
   * @param  string $filename  location of twig template
   * @param  mixed $revisions array of revisions, or a single revision object
   * @return string
   */
  private function renderTwig($filename, $revisions)
  {
    return \Gustavus\TwigFactory\TwigFactory::renderTwigFilesystemTemplate("/cis/lib/Gustavus/Revisions/views/$filename", array('revisions' => $revisions));
  }
}