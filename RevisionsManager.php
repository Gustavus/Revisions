<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * Interacts with the database
 *
 * @package Revisions
 */
class RevisionsManager extends RevisionsBase
{
  /**
   * @var string database name where revisions are stored
   */
  protected $dbName;

  /**
   * @var string database table name where revisions are stored
   */
  protected $revisionsTable;

  /**
   * @var string database table name where revisionData is stored
   */
  protected $revisionDataTable;

  /**
   * @var string database table name
   */
  protected $table;

  /**
   * @var int of the rowId in the table
   */
  protected $rowId;

  /**
   * @var DBAL connection
   */
  protected $dbal;

  /**
   * @var integer limit of how many revisions to pull
   */
  protected $limit = 1;

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
    unset($this->dbName, $this->revisionsTable, $this->revisionDataTable, $this->table, $this->rowId, $this->dbal, $this->limit);
  }

  /**
   * @param integer $limit
   * @return void
   */
  public function setLimit($limit = 1)
  {
    $this->limit = $limit;
  }

  /**
   * @return integer
   */
  public function getLimit()
  {
    return $this->limit;
  }

  /**
   * @return /Doctrine/DBAL connection
   */
  protected function getDB()
  {
    if (!isset($this->dbal)) {
      $this->dbal = \Gustavus\Doctrine\DBAL::getDBAL($this->dbName);
    }
    return $this->dbal;
  }

  /**
   * Looks in the database for revisions
   *
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
    $qb = $db->createQueryBuilder();
    $qb->addSelect('rDB.`id`, rDB.`contentHash`, rDB.`table`, rDB.`rowId`, rDB.`revisionNumber`, rDB.`message`, rDB.`createdBy`, rDB.`createdOn`')
      ->from("`{$this->revisionsTable}`", 'rDB');
    if ($revisionId === null) {
      $args = array(
        ':table' => $this->table,
        ':rowId' => $this->rowId,
      );
      $qb->where('rDB.`table` = :table')
        ->andWhere('rDB.`rowId` = :rowId')
        ->orderBy('rDB.`id`', 'DESC')
        ->setMaxResults($limit);

      if ($prevRevisionNum !== null) {
        $qb->andWhere('rDB.`revisionNumber` < :revNum');
        $args[':revNum'] = $prevRevisionNum;
      }
    } else {
      $qb->where('rDB.`id` = :revId');
      $args = array(':revId' => $revisionId);
    }
    return $db->fetchAll($qb->getSQL(), $args);
  }

  /**
   * Looks in the database for revisionData
   *
   * @param integer $revisionId
   * @param string $column
   * @param boolean $revisionsHaveBeenPulled
   * @param integer $limit
   * @param integer $prevRevisionNum
   * @param  boolean $forceSingleDimension  Whether to format as a 2 dimensional array or not
   * @return array of revisionData keyed by column
   */
  protected function getRevisionData($revisionId, $column = null, $revisionsHaveBeenPulled = false, $limit = null, $prevRevisionNum = null, $forceSingleDimension = false)
  {
    $db = $this->getDB();
    $qb = $db->createQueryBuilder();
    $qb->select('dataDB.`id`, dataDB.`contentHash`, dataDB.`revisionId`, dataDB.`revisionNumber`, dataDB.`key`, dataDB.`value`, rDB.`revisionNumber` as `revisionRevisionNumber`')
      ->from("`{$this->revisionDataTable}`", 'dataDB');

    $qb = $qb->leftJoin('dataDB', "`{$this->revisionsTable}`", 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId');
    $args = array(
      ':table' => $this->table,
      ':rowId' => $this->rowId,
    );
    if ($revisionId === null && $column !== null) {
      if ($limit === null) {
        if ($revisionsHaveBeenPulled) {
          $limit = $this->limit;
        } else {
          // first revision will be the current content and not an actual revision
          $limit = $this->limit + 1;
        }
      }
      $qb->where('dataDB.`key` = :key')
        ->orderBy('dataDB.`id`', 'DESC')
        ->setMaxResults($limit);
      $args[':key'] = $column;
      if ($prevRevisionNum !== null) {
        $qb->andWhere('dataDB.`revisionNumber` < :revNum');
        $args[':revNum'] = $prevRevisionNum;
      }
    } else {
      $qb->orderBy('`key`', 'ASC')
        ->where('`revisionId` = :revisionId');
      $args[':revisionId'] = $revisionId;
    }
    return $this->parseDataResult($db->fetchAll($qb->getSQL(), $args), $column, $forceSingleDimension);
  }

  /**
   * Gets all of the columns stored
   *
   * @return array
   */
  protected function getRevisionDataColumns()
  {
    $db = $this->getDB();
    $args = array(
      ':table'      => $this->table,
      ':rowId'      => $this->rowId,
    );
    $qb = $db->createQueryBuilder();
    $qb->select('dataDB.`key`')
      ->from("`{$this->revisionDataTable}`", 'dataDB')
      ->leftJoin('dataDB', "`{$this->revisionsTable}`", 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId')
      ->groupBy('dataDB.`key`');
    return $db->fetchAll($qb->getSQL(), $args);
  }

  /**
   * Parses fetchAll result into an array we can work with a bit better
   *
   * @param  array  $resultArray fetchAll result
   * @param  string $column      column name we are looking for
   * @param  boolean $forceSingleDimension  Whether to format as a 2 dimensional array or not
   * @return array keyed always keyed by column and revision number only if column is set
   */
  private function parseDataResult(array $resultArray, $column = null, $forceSingleDimension = false)
  {
    $return = array();
    foreach ($resultArray as $result) {
      if ($column === null || $forceSingleDimension) {
        $return[$result['key']] = array(
          'id'             => $result['id'],
          'contentHash'    => $result['contentHash'],
          'revisionId'     => $result['revisionId'],
          'revisionNumber' => $result['revisionNumber'],
          'value'          => json_decode($result['value']),
          'revisionRevisionNumber' => $result['revisionRevisionNumber'],
        );
      } else {
         $return[$result['key']][$result['revisionNumber']] = array(
          'id'          => $result['id'],
          'contentHash' => $result['contentHash'],
          'revisionId'  => $result['revisionId'],
          'value'       => json_decode($result['value']),
          'revisionRevisionNumber' => $result['revisionRevisionNumber'],
        );
      }
    }

    return $return;
  }

  /**
   * Saves revisionData into DB
   *
   * @param json $revisionInfo
   * @param integer $revisionId id of the revision in the revision db
   * @param string $revisionNumber current cells revision number
   * @param string $column column of the cell
   * @param string $revisionContent content the revision was. Used to generate a hash
   * @param integer $oldRevisionId
   * @return void
   */
  private function saveRevisionData($revisionInfo, $revisionId, $column, $revisionContent, $oldRevisionId = null)
  {
    $db = $this->getDB();
    $args = array(
      ':value' => $revisionInfo,
    );
    if ($oldRevisionId === null) {
      $args = array_merge($args, array(
        ':revisionId'     => $revisionId,
        ':key'            => $column,
        ':hash'           => md5($revisionContent),
      ));

      $qb = $db->createQueryBuilder();
      $qb->select(':hash, :revisionId, COUNT(dataDB.`revisionNumber`), :key, :value')
        ->from("`{$this->revisionDataTable}`", 'dataDB');
      $qb = $qb->leftJoin('dataDB', "`{$this->revisionsTable}`", 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId')
        ->where('`key` = :key');
      $select = $qb->getSQL();

      $sql = sprintf('
        INSERT INTO `%1$s` (
          `contentHash`,
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
   * Saves revision into DB
   *
   * @param array $revisonContent revision's full content containing all of the columns keyed by column
   * @param string $message revision message
   * @param string $createdBy person who edited the revision
   * @return integer insertId
   */
  private function saveRevisionContent(array $revisionContent, $message = null, $createdBy = null)
  {
    $db = $this->getDB();
    $args = array(
      ':table'          => $this->table,
      ':rowId'          => $this->rowId,
      ':message'        => $message,
      ':createdBy'      => $createdBy,
      ':contentHash'    => $this->generateHashFromArray($revisionContent),
    );
    $nowExpression = ($db->getDatabasePlatform()->getName() === 'sqlite') ? 'datetime("now", "localtime")' : 'NOW()';
    $sql = sprintf('
      INSERT INTO `%1$s` (
        `contentHash`,
        `table`,
        `rowId`,
        `revisionNumber`,
        `message`,
        `createdBy`,
        `createdOn`
      ) SELECT
          :contentHash,
          :table,
          :rowId,
          COUNT(revisionNumber),
          :message,
          :createdBy,
          %2$s
        FROM `%1$s`
        WHERE `table` = :table
          AND `rowId` = :rowId;
        ',
        $this->revisionsTable,
        $nowExpression
    );
    $db->executeUpdate($sql, $args);
    return (int) $db->lastInsertId();
  }

  /**
   * Saves revision and revisionData into DB
   *
   * @param array $revisionInfo array of json revision info keyed by column
   * @param array $newContent array of full row keyed by column
   * @param array $oldContent array of full row keyed by column
   * @param array $oldRevisionData keyed by column
   * @param string $message
   * @param string $createdBy
   * @param array $brandNewColumns columns that do not exist in db
   * @return boolean
   */
  protected function saveRevision(array $revisionInfo, array $newContent, array $oldContent, array $oldRevisionData = array(), $message = null, $createdBy = null, array $brandNewColumns = array())
  {
    $affectedRows = 0;
    $revisionInfo = array_filter($revisionInfo);
    if (empty($revisionInfo)) {
      // revisions are the same as they used to be
      // don't do anything
      return true;
    }
    $oldContentFiltered = array_filter($oldContent);
    if (!empty($brandNewColumns) && !empty($oldContentFiltered)) {
      // new columns exist, and revision isn't the first. Need to get their initial revision in before continuing
      $revisionId = $this->saveRevisionContent($oldContent, $message, $createdBy);
      foreach ($brandNewColumns as $key) {
        $affectedRows += $this->saveRevisionData($revisionInfo[$key], $revisionId, $key, $oldContent[$key]);
      }
    }
    if (empty($oldContentFiltered)) {
      // no old content, so revision is the first one. Also add the new content in
      $revContent = array_merge($newContent, $oldContent);
      $revisionId = $this->saveRevisionContent($revContent, $message, $createdBy);
      $newRevisionId = $this->saveRevisionContent($newContent, $message, $createdBy);
    } else {
      // revision exists already
      $newRevisionId = $this->saveRevisionContent($newContent, $message, $createdBy);
      $revisionId = $newRevisionId;
    }
    foreach ($revisionInfo as $key => $value) {
      if (!isset($oldRevisionData[$key])) {
        // previous revision not included and needs to be pulled.
        if ($revisionId === $newRevisionId && !in_array($key, $brandNewColumns)) {
          // previous revisions that aren't brand new exist, so we might need to update one
          $oldRevisionData = array_merge($oldRevisionData, $this->getRevisionData(null, $key, true, 1));
        }
      }
      if (isset($oldRevisionData[$key])) {
        $latestRevisionData = array_shift($oldRevisionData[$key]);
        // update existing revisionData
        $affectedRows += $this->saveRevisionData($value, $latestRevisionData['revisionId'], $key, $oldContent[$key], $latestRevisionData['id']);
      } else {
        // no existing revision exists so insert a new one
        $affectedRows += $this->saveRevisionData($value, $revisionId, $key, $oldContent[$key]);
      }
      // insert new content to db
      $affectedRows += $this->saveRevisionData(json_encode($newContent[$key]), $newRevisionId, $key, $newContent[$key]);
    }
    return ($affectedRows !== 0);
  }

  /**
   * Takes an associative array and turn it into a json encoded md5 hash
   *
   * @param  array $array array keyed by column
   * @return string
   */
  protected function generateHashFromArray(array $array = array())
  {
    ksort($array);
    return md5(json_encode($array));
  }
}