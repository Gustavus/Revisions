<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

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
  public function __construct($revisions = null)
  {
    if ($revisions !== null) {
        $this->revisions = $revisions;
    }
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

  public function renderRevisions($limit = 1)
  {
    $this->revisions->setLimit($limit);
    $return = '';
    foreach ($this->revisions->getRevisionObjects() as $revision) {
      if ($revision !== null) {
        //$params = $this->buildRevisionViewParams($revision);
        $return .= '';
        //var_dump($revisionNumber, $revision);
      }
    }
  }

  public function renderRevisionData($revision)
  {
    foreach ($revision->getRevisionData() as $column => $revisionData) {

    }
  }

  // /**
  //  * puts together associative array for passing to a view formatter
  //  *
  //  * @param  Revision $revision
  //  * @return array
  //  */
  // private function buildRevisionViewParams($revision)
  // {
  //   $params = array(
  //     'revisionNumber'  => $revision->getRevisionNumber(),
  //     'createdBy'       => $revision->getCreatedBy(),
  //     'message'         => $revision->getRevisionMessage(),
  //     'error'           => $revision->getError(),
  //     'columns'         => $revision->getModifiedColumns(),
  //   );
  //   return $params;
  // }

  // /**
  //  * gets columns modified in a specific revision and formats it as a comma separated string
  //  *
  //  * @param  integer $revisionId
  //  * @return string
  //  */
  // private function parseRevisionColumnsModified($revisionId)
  // {
  //   $columns = $this->getRevisionColumnsById($revisionId);
  //   $return = array();
  //   foreach ($columns as $column) {
  //     $return[] = $column['key'];
  //   }
  //   return implode(', ', $return);
  // }
}