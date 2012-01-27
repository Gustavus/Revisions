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
  public function __construct($revisions = null)
  {
    if ($revisions !== null) {
        $this->revisions = $revisions;
    }
  }

  /**
   * @return mixed
   */
  public function __get($name)
  {
    switch ($name) {
      case 'revisions':
        return $this->getItems();

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

  public function renderRevisions($limit = 1)
  {
    $this->revisions->setLimit($limit);
    $return = '';
    $twigFactory = new \Gustavus\TwigFactory\TwigFactory;
    return $twigFactory->renderTwigFilesystemTemplate('/cis/lib/Gustavus/Revisions/views/revisions.twig', array('revisions' => $this->revisions->getRevisionObjects()));
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