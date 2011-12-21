<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * @package Revisions
 */
class Revisions {
  /**
   * @var array of revisions
   */
  private $revisions;

  /**
   * Class constructor
   *
   */
  public function __construct()
  {
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
  }

  /**
   * function to render changes from oldText to newText
   *
   * @param string $oldText
   * @param string $newText
   * @return string
   */
  protected function renderDiff($oldText, $newText)
  {
    $revision = new Revisions\Revision(array($oldText));
    $diff = $revision->makeDiff($newText);
    return $diff;
  }

}