<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

require_once 'db/DBAL.class.php';
/**
 * @package Revisions
 */
class RevisionsPuller
{
  /**
   * @var string database name where revisions are stored
   */
  private $dbName;

  /**
   * @var string database table name
   */
  private $table;

  /**
   * @var string database table column name
   */
  private $column;

  /**
   * @var int of the rowId in the table
   */
  private $rowId;

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
    unset($this->table, $this->column, $this->dbName, $this->rowId);
  }

  /**
   * @param array $array
   * @return void
   */
  private function populateObjectWithArray(array $array)
  {
    foreach ($array as $key => $value) {
      if (property_exists($this, $key)) {
        $this->$key = $value;
      }
    }
  }

  /**
   * @return /Doctrine/DBAL connection
   */
  private function getDB()
  {
    return \Gustavus\DB\DBAL::getDBAL('revisions');
  }

  /**
   * @param  array $revisionInfo
   * @return void
   */
  protected function saveRevision($revisionInfo)
  {
    $db = $this->getDB();
    $sql = sprintf("
      INSERT INTO `%1$s` (`table`, `rowId`, `key`, `value`)
      VALUES (:table, :rowId, :key, :revisionInfo)",
        $this->dbName
    );
    $db->executeQuery($sql, array(':table' => $this->table, ':rowId' => $this->rowId, ':key' => $this->column, ':revisionInfo' => $revisionInfo));
  }

  /**
   * @param array $params
   * @return MySQLi
   */
  protected function getDBRow(array $params)
  {

  }
}