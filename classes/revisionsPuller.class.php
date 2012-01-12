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
   * @var string database table name where revisionData is stored
   */
  private $revisionDataTable;

  /**
   * @var string database table name
   */
  private $table;

  /**
   * @var int of the rowId in the table
   */
  private $rowId;

  /**
   * @var DBAL connection
   */
  private $dbal;

  /**
   * @var integer limit of how many revisions to pull
   */
  private $limit = 10;

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
    unset($this->dbName, $this->revisionDataTable, $this->revisionsTable, $this->table, $this->column, $this->dbal, $this->rowId, $this->limit);
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
   * @param integer $prevRevisionNum
   * @param integer $limit
   * @param integer $revisionId
   * @return array of revisions
   */
  protected function getRevisions($prevRevisionNum = null, $limit = null, $revisionId = null)
  {
    if ($limit === null) {
      $limit = $this->limit;
    }
    $db = $this->getDB();
    $dbName = "`$this->revisionsTable`";
    $qb = $db->createQueryBuilder();
    $qb->addSelect('`id`, `table`, `rowId`, `revisionNumber`, `message`, `createdBy`, `createdOn`')
      ->from($dbName, $dbName);
    if ($revisionId === null) {
      $args = array(
        ':table' => $this->table,
        ':rowId' => $this->rowId,
      );
      $qb->where('`table` = :table')
        ->andWhere('`rowId` = :rowId')
        ->orderBy('`id`', 'DESC')
        ->setMaxResults($limit);

      if ($prevRevisionNum !== null) {
        $qb->andWhere('`revisionNumber` < :revNum');
        $args[':revNum'] = $prevRevisionNum;
      }
    } else {
      $qb->where('`id` = :revId');
      $args = array(':revId' => $revisionId);
    }
    return $db->fetchAll($qb->getSQL(), $args);
  }

  /**
   * @param integer $revisionId
   * @param string $column
   * @param boolean $revisionsHaveBeenPulled
   * @param integer $limit
   * @param integer $prevRevisionNum
   * @return array of revisionData keyed by column
   */
  protected function getRevisionData($revisionId, $column = null, $revisionsHaveBeenPulled = false, $limit = null, $prevRevisionNum = null)
  {
    $db = $this->getDB();
    $dbDataName = "`$this->revisionDataTable`";
    $qb = $db->createQueryBuilder();
    $qb->select('dataDB.`id`, dataDB.`revisionId`, dataDB.`revisionNumber`, dataDB.`key`, dataDB.`value`')
      ->from($dbDataName, 'dataDB');
    if ($revisionId === null && $column !== null) {
      if ($limit === null) {
        if ($revisionsHaveBeenPulled) {
          $limit = $this->limit;
        } else {
          // first revision will be the current content and not an actual revision
          $limit = $this->limit + 1;
        }
      }
      $dbName = "`$this->revisionsTable`";
      $qb = $qb->leftJoin('dataDB', $dbName, 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId');
      $qb->where('dataDB.`key` = :key')
        ->orderBy('dataDB.`id`', 'DESC')
        ->setMaxResults($limit);
      $args = array(
        ':key'        => $column,
        ':table'      => $this->table,
        ':rowId'      => $this->rowId,
      );
      if ($prevRevisionNum !== null) {
        $qb->andWhere('dataDB.`revisionNumber` < :revNum');
        $args[':revNum'] = $prevRevisionNum;
      }
    } else {
      $qb->orderBy('`key`', 'ASC')
        ->where('`revisionId` = :revisionId');
      $args = array(
        ':revisionId' => $revisionId,
      );
    }
    return $this->parseDataResult($db->fetchAll($qb->getSQL(), $args), $column);
  }

  /**
   * parses fetchAll result into an array we can work with a bit better
   * @param  array  $resultArray fetchAll result
   * @param  string $column      column name we are looking for
   * @return array keyed always keyed by column and revision number only if column is set
   */
  private function parseDataResult(array $resultArray, $column = null)
  {
    $return = array();
    foreach ($resultArray as $result) {
      if ($column === null) {
        $return[$result['key']] = array(
          'id'             => $result['id'],
          'revisionId'     => $result['revisionId'],
          'revisionNumber' => $result['revisionNumber'],
          'value'          => $result['value'],
        );
      } else {
         $return[$result['key']][$result['revisionNumber']] = array(
          'id'         => $result['id'],
          'revisionId' => $result['revisionId'],
          'value'      => $result['value'],
        );
      }
    }

    return $return;
  }

  /**
   * @param json $revisionInfo
   * @param integer $revisionId id of the revision in the revision db
   * @param string $revisionNumber current cells revision number
   * @param string $column column of the cell
   * @param integer $oldRevisionId
   * @return void
   */
  private function saveRevisionData($revisionInfo, $revisionId, $column, $oldRevisionId = null)
  {
    $db = $this->getDB();
    $args = array(
      ':value'          => $revisionInfo,
    );
    if ($oldRevisionId === null) {
      $args = array_merge($args, array(
        ':revisionId'     => $revisionId,
        ':key'            => $column,
      ));

      $dbDataName = "`$this->revisionDataTable`";
      $qb = $db->createQueryBuilder();
      $qb->select(':revisionId, COUNT(dataDB.`revisionNumber`) + 1, :key, :value')
        ->from($dbDataName, 'dataDB');
      $dbName = "`$this->revisionsTable`";
      $qb = $qb->leftJoin('dataDB', $dbName, 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId')
        ->where('`key` = :key');
      $select = $qb->getSQL();

      $sql = sprintf('
        INSERT INTO `%1$s` (
          `revisionId`,
          `revisionNumber`,
          `key`,
          `value`
        ) %2$s;
          ',
          $this->revisionDataTable,
          $select
      );
      return $db->executeUpdate($sql, $args);
    } else {
      $args[':oldContentId'] = $oldRevisionId;
      $sql = sprintf('
        UPDATE `%1$s` SET
          `value` = :value
        WHERE `id` = :oldContentId',
          $this->revisionDataTable
      );
      return $db->executeUpdate($sql, $args);
    }
  }

  /**
   * @param integer $revisonNumber revision's revision number
   * @param string $message revision message
   * @param string $createdBy person who edited the revision
   * @return integer insertId
   */
  private function saveRevisionContent($message = "", $createdBy = "")
  {
    $db = $this->getDB();
    $args = array(
      ':table'          => $this->table,
      ':rowId'          => $this->rowId,
      ':message'        => $message,
      ':createdBy'      => $createdBy,
    );
    $sql = sprintf('
      INSERT INTO `%1$s` (
        `table`,
        `rowId`,
        `revisionNumber`,
        `message`,
        `createdBy`,
        `createdOn`
      ) SELECT
          :table,
          :rowId,
          COUNT(revisionNumber) + 1,
          :message,
          :createdBy,
          %2$s
        FROM `%1$s`
        WHERE `table` = :table
          AND `rowId` = :rowId;
        ',
        $this->revisionsTable,
        $db->getDatabasePlatform()->getNowExpression()
    );
    $db->executeUpdate($sql, $args);
    return (int) $db->lastInsertId();
  }

  /**
   * function to save a revision.
   * @param array $revisionInfo json revision info keyed by column
   * @param array $newContent keyed by column
   * @param array $oldRevisionDataArray keyed by column
   * @param string $message
   * @param string $createdBy
   * @return void
   */
  protected function saveRevision($revisionInfo, $newContent, $oldRevisionData = array(), $message = "", $createdBy = "")
  {
    $revisionInfo = array_filter($revisionInfo);
    if (empty($revisionInfo)) {
      // revisions are the same as they used to be
      // don't do anything
      return false;
    }
    $revisionId = $this->saveRevisionContent($message, $createdBy);
    $revision = $this->getRevisions(null, null, $revisionId);
    $revisionNum = $revision[0]['revisionNumber'];
    if ((int) $revisionNum === 1) {
      // first revision, also add the new content's revision
      // for adding the current data in to know dates
      $newRevisionId = $this->saveRevisionContent($message, $createdBy);
    } else {
      $newRevisionId = $revisionId;
    }
    foreach ($newContent as $key => $value) {
      if (isset($revisionInfo[$key])) {
        // revision content changed
        if (!isset($oldRevisionData[$key])) {
          // previous revision doesn't exist and needs to be pulled.
          if ($revisionNum > 1) {
            // previous revisions exist, so we might need to update one
            $oldRevisionData = array_merge($oldRevisionData, $this->getRevisionData(null, $key, true, 1));
          }
        }
        if (isset($oldRevisionData[$key])) {
          $latestRevisionData = array_shift($oldRevisionData[$key]);
          // update existing revisionData
          // $revisionInfo, $revisionId, $column, $oldRevisionId = null
          $this->saveRevisionData($revisionInfo[$key], $revisionId, $key, $latestRevisionData['id']);
        } else {
          $this->saveRevisionData($revisionInfo[$key], $revisionId, $key);
        }
      }
      // insert new content to db
      $this->saveRevisionData($value, $newRevisionId, $key);
    }
  }
}