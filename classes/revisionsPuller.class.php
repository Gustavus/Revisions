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
   * @var string database table name where revisions are stored
   */
  private $revisionsTable;

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
   * @var DBAL connection
   */
  private $dbal;

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
  protected function populateObjectWithArray(array $array)
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
    if (!isset($this->dbal)) {
      $this->dbal = \Gustavus\DB\DBAL::getDBAL($this->dbName);
    }
    return $this->dbal;
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
    $dbName = "`$this->revisionsTable`";
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
      ':table'     => $this->table,
      ':rowId'     => $this->rowId,
      ':key'       => $this->column,
      ':value'     => $newContent,
    );
    $sql = sprintf('
      INSERT INTO `%1$s` (
        `table`,
        `rowId`,
        `key`,
        `value`,
        `createdOn`
      ) VALUES (
        :table,
        :rowId,
        :key,
        :value,
        %2$s
      )',
        $this->revisionsTable,
        $db->getDatabasePlatform()->getNowExpression()
    );

    return $db->executeUpdate($sql, $args);
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
      ':key'   => $this->column,
      ':value' => $revisionInfo,
    );
    if ($oldContentId === null) {
      $sql = sprintf('
        INSERT INTO `%1$s` (
          `table`,
          `rowId`,
          `key`,
          `value`,
          `createdOn`
        ) VALUES (
          :table,
          :rowId,
          :key,
          :value,
          %2$s
        );
          ',
          $this->revisionsTable,
          $db->getDatabasePlatform()->getNowExpression()
      );
      return $db->executeUpdate($sql, $args);
    } else {
      $args[':oldContentId'] = $oldContentId;
      $sql = sprintf('
        UPDATE `%1$s` SET
          `table` = :table,
          `rowId` = :rowId,
          `key` = :key,
          `value` = :value
        WHERE `id` = :oldContentId',
          $this->revisionsTable
      );
      return $db->executeUpdate($sql, $args);
    }
  }

  /**
   * @param json $revisionInfo
   * @param string $newContent
   * @param array $currContentArray set if we are saving
   * @return void
   */
  protected function saveRevision($revisionInfo, $newContent, array $currContentArray = array())
  {
    $currContentId = empty($currContentArray) ? null : $currContentArray[0]['id'];
    if ($revisionInfo !== null) {
      //either revision is the first one, or it is the same as it used to be
      $this->saveRevisionContent($revisionInfo, $currContentId);
    }
    if ($currContentId !== null && $revisionInfo === null) {
      // the current content is what it used to be, so don't add it again
      return false;
    }
    $this->saveNewContent($newContent);
  }
}