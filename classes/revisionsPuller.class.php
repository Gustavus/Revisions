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
   * @var string database name
   */
  protected $dbName;

  /**
   * @var string revision DBName
   */
  protected $revisionDBName;

  /**
   * @var string database table name
   */
  protected $table;

  /**
   * @var string database table column name
   */
  protected $column;

  /**
   * Class constructor
   *
   * @param string $dbName
   * @param string $revisionDBName
   */
  public function __construct($dbName, $revisionDBName, $table, $column)
  {
    $this->dbName = $dbName;
    $this->revisionDBName = $revisionDBName;
    $this->table = $table;
    $this->column = $column;
  }

  /**
   * Class destructor
   *
   * @return void
   */
  public function __destruct()
  {
    unset($this->table, $this->column, $this->dbName, $this->revisionDBName);
  }

  protected function getDB()
  {
    return \Gustavus\DB\DBAL::getDBAL('revisions');
  }

  public function insertToDB()
  {
    $db = $this->getDB();
    $sql = "
    INSERT INTO `person` (name, age, city, aboutYou)
    VALUES ('Billy', 2, 'Mankato', 'food')
    ";
    //var_dump($db);
    $db->query($sql);
  }

  /**
   * @param array $params
   * @return MySQLi
   */
  protected function getDBRow(array $params)
  {

  }

}