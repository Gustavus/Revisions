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
  protected function getDB()
  {
    return \Gustavus\DB\DBAL::getDBAL($this->dbName);
  }

  /**
   * @param integer $prevRevisionId
   * @param integer $limit
   * @return array of revisions
   */
  protected function getRevisions($prevRevisionId = null, $limit = 10)
  {
    $db = $this->getDB();
    $args = array(
      ':table' => $this->table,
      ':rowId' => $this->rowId,
      ':key' => $this->column,
    );
    $dbName = "`$this->dbName`";
    $qb = $db->createQueryBuilder();
    $qb->addSelect('`id`, `table`, `rowId`, `key`, `value`, `createdOn`')
      ->from($dbName, $dbName)
      ->where('`table` = :table')
      ->andWhere('`rowId` = :rowId')
      ->andWhere('`key` = :key')
      ->orderBy('`id`', 'DESC')
      ->setMaxResults($limit);

    if ($prevRevisionId !== null) {
      $qb->andWhere('`id` < :id');
      $args[':id'] = $prevRevisionId;
    }
    return $db->fetchAll($qb->getSQL(), $args);
  }

  /**
   * function to save the new content in the revisions db so we know the date it was modified
   * @param string $newContent
   * @return void
   */
  private function saveNewContent($newContent)
  {
    $db = $this->getDB();
    $args = array(
      ':table' => $this->table,
      ':rowId' => $this->rowId,
      ':key' => $this->column,
      ':revisionInfo' => $newContent,
    );
    $sql = sprintf('
      INSERT INTO `%1$s` (`table`, `rowId`, `key`, `value`, `createdOn`)
      VALUES (:table, :rowId, :key, :revisionInfo, %2$s)',
        $this->dbName,
        $db->getDatabasePlatform()->getNowExpression()
    );
    $db->executeQuery($sql, $args);
  }

  /**
   * @param json $revisionInfo
   * @param integer $oldContentId
   * @return void
   */
  private function saveRevisionContent($revisionInfo, $oldContentId = null)
  {
    $db = $this->getDB();
    $args = array(
      ':table' => $this->table,
      ':rowId' => $this->rowId,
      ':key' => $this->column,
      ':revisionInfo' => $revisionInfo,
    );
    if ($oldContentId === null) {
      $sql = sprintf('
        INSERT INTO `%1$s` (`table`, `rowId`, `key`, `value`, `createdOn`)
        VALUES (:table, :rowId, :key, :revisionInfo, %2$s)',
          $this->dbName,
          $db->getDatabasePlatform()->getNowExpression()
      );
    } else {
      $sql = sprintf('
        UPDATE `%1$s` SET
          `table`     = :table,
          `rowId`     = :rowId,
          `key`       = :key,
          `value`     = :revisionInfo
        WHERE `id` = :id',
          $this->dbName
      );
      $args[':id'] = $oldContentId;
    }
    $db->executeQuery($sql, $args);
  }

  /**
   * @param json $revisionInfo
   * @param string $newContent
   * @return void
   */
  protected function saveRevision($revisionInfo, $newContent)
  {
    $currContentArray = $this->getRevisions(null, 1);
    $currContentId = empty($currContentArray) ? null : $currContentArray[0]['id'];
    $this->saveRevisionContent($revisionInfo, $currContentId);
    $this->saveNewContent($newContent);
  }
}