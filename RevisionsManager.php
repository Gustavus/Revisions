<?php
/**
 * @package Revisions
 * @author  Billy Visto
 */
namespace Gustavus\Revisions;

use Gustavus\Revisions\RevisionData;

/**
 * Interacts with the database
 *
 * @package Revisions
 * @author  Billy Visto
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
  protected $limit = 10;

  /**
   * Strategy to use for splitting a string to make a diff
   *   Either a string to be used all the time, or an array of strategies per key
   *   ie.
   *   <code>
   *     array('firstName' => 'words', 'biography' => 'sentenceOrTag');
   *   </code>
   *
   * @var string|array
   */
  protected $splitStrategy;

  /**
   * Latest revision number used to verify that nothing got inserted while building a diff
   *
   * @var integer
   */
  protected $latestRevisionNumber = null;

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
   * Gets the database function for the current time
   *
   * @return string
   */
  protected function getNowExpression()
  {
    $db = $this->getDB();
    if ($db->getDatabasePlatform()->getName() === 'sqlite') {
      // can't use getNowExpression because it doesn't support the timezone parameter
      return 'datetime("now", "localtime")';
    } else {
      return $db->getDatabasePlatform()->getNowExpression();
    }
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
    $qb->select('dataDB.`id`, dataDB.`contentHash`, dataDB.`revisionId`, dataDB.`revisionNumber`, dataDB.`key`, dataDB.`value`, rDB.`revisionNumber` as `revisionRevisionNumber`, dataDB.`splitStrategy`')
      ->from("`{$this->revisionDataTable}`", 'dataDB')
      ->innerJoin('dataDB', "`{$this->revisionsTable}`", 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId');
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
      ->innerJoin('dataDB', "`{$this->revisionsTable}`", 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId')
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
          'id'                     => $result['id'],
          'contentHash'            => $result['contentHash'],
          'revisionId'             => $result['revisionId'],
          'revisionNumber'         => $result['revisionNumber'],
          'value'                  => json_decode($result['value']),
          'revisionRevisionNumber' => $result['revisionRevisionNumber'],
          'splitStrategy'          => $result['splitStrategy'],
        );
      } else {
         $return[$result['key']][$result['revisionNumber']] = array(
          'id'                     => $result['id'],
          'contentHash'            => $result['contentHash'],
          'revisionId'             => $result['revisionId'],
          'value'                  => json_decode($result['value']),
          'revisionRevisionNumber' => $result['revisionRevisionNumber'],
          'splitStrategy'          => $result['splitStrategy'],
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
   * @param string $column column of the cell
   * @param string $revisionContent content the revision was. Used to generate a hash
   * @param integer $oldRevisionId
   * @return void
   */
  private function saveRevisionData($revisionInfo, $revisionId, $column, $revisionContent, $oldRevisionId = null)
  {
    $db = $this->getDB();

    $splitStrategy = $this->getSplitStrategyForKey($column);
    $args = array(
      ':value'         => $revisionInfo,
      ':splitStrategy' => $splitStrategy,
    );
    if ($oldRevisionId === null) {
      $args = array_merge($args, array(
        ':revisionId'     => $revisionId,
        ':key'            => $column,
        ':hash'           => md5($revisionContent),
        ':table'          => $this->table,
        ':rowId'          => $this->rowId,
      ));

      $qb = $db->createQueryBuilder();
      $qb->select(':hash, :revisionId, COUNT(dataDB.`revisionNumber`), :key, :value, :splitStrategy')
        ->from("`{$this->revisionDataTable}`", 'dataDB')
        ->innerJoin('dataDB', "`{$this->revisionsTable}`", 'rDB', 'rDB.`id` = dataDB.`revisionId` AND rDB.`table` = :table AND rDB.`rowId` = :rowId')
        ->where('`key` = :key');
      $select = $qb->getSQL();

      $sql = sprintf('
        INSERT INTO `%1$s` (
          `contentHash`,
          `revisionId`,
          `revisionNumber`,
          `key`,
          `value`,
          `splitStrategy`
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
          `value` = :value,
          `splitStrategy` = :splitStrategy
        WHERE `id` = :oldContentId',
          $this->revisionDataTable
      );
      return $db->executeUpdate($sql, $args);
    }
  }

  /**
   * Saves revision into DB
   *
   * @param array $revisionContent revision's full content containing all of the columns keyed by column
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

    $qb = $db->createQueryBuilder();
    $qb->select(":contentHash, :table, :rowId, COUNT(`revisionNumber`), :message, :createdBy, {$this->getNowExpression()}")
      ->from("`{$this->revisionsTable}`", 'rDB')
      ->where('`table` = :table')
      ->andWhere('`rowId` = :rowId');
    $select = $qb->getSQL();

    $sql = sprintf('
      INSERT INTO `%1$s` (
        `contentHash`,
        `table`,
        `rowId`,
        `revisionNumber`,
        `message`,
        `createdBy`,
        `createdOn`
      ) %2$s
        ',
        $this->revisionsTable,
        $select
    );
    $db->executeUpdate($sql, $args);
    return (int) $db->lastInsertId();
  }

  /**
   * Function to filter an array while keeping empty values if it is a brand new column
   * Also json_encodes the new empty value
   *
   * @param  array  $revisionInfo
   * @param  array  $brandNewColumns
   * @return array
   */
  private function filterOldColumns(array $revisionInfo, array $brandNewColumns)
  {
    $return = array();
    array_walk($revisionInfo,
        function($value, $key, $array)
        {
          if (in_array($key, $array[0]) && empty($value)) {
            $array[1][$key] = '""'; // same as json_encode('')
          } else if (!empty($value)) {
            $array[1][$key] = $value;
          }
        },
        array($brandNewColumns, &$return)
    );
    return $return;
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
    // number to add on to $this->latestRevisionNumber to adjust how many new revisions we will be making here.
    $latestRevisionNumberAddition = 0;
    $revisionInfo = $this->filterOldColumns($revisionInfo, $brandNewColumns);
    if (empty($revisionInfo)) {
      // revisions are the same as they used to be
      // don't do anything
      return true;
    }
    // start our transaction so we can roll it back if we get a revision in between the two.
    $this->getDB()->beginTransaction();
    if (!empty($brandNewColumns)) {
      // new columns exist, and revision isn't the first. Need to get their initial revision in before continuing
      $revContent = array_merge($newContent, $oldContent);
      $revisionId = $this->saveRevisionContent($revContent, $message, $createdBy);
      ++$latestRevisionNumberAddition;
      foreach ($brandNewColumns as $key) {
        $affectedRows += $this->saveRevisionData($revisionInfo[$key], $revisionId, $key, $oldContent[$key]);
      }
    }

    $changes = array_diff_assoc($newContent, $oldContent);
    if (!empty($changes)) {
      // newContent is different from oldContent
      $newRevisionId = $this->saveRevisionContent($newContent, $message, $createdBy);
      ++$latestRevisionNumberAddition;
      if (!isset($revisionId)) {
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
        }
        // add full current content in as the last thing for this key in the db
        $affectedRows += $this->saveRevisionData(json_encode($newContent[$key]), $newRevisionId, $key, $newContent[$key]);
      }
    }
    $revision = $this->getRevisions(null, 1);
    if (isset($revision[0]['revisionNumber'])) {
      $latestRevisionNumber = (int) $revision[0]['revisionNumber'];
    } else {
      $latestRevisionNumber = null;
    }
    // check to verify that nothing got inserted while building our revisions.
    if (
      ($this->latestRevisionNumber === null && // no revisions should exist
        // make sure our latestRevisionNumber is the number of revisions saved - 1
        // since the revisionNumbers start at 0
        $latestRevisionNumber === $latestRevisionNumberAddition - 1) ||
      ($this->latestRevisionNumber !== null &&
        // our latestRevisionNumber should the current one plus the number of revisions we just saved
        $latestRevisionNumber === $this->latestRevisionNumber + $latestRevisionNumberAddition)) {
      // nothing got inserted into our db since we set $this->latestRevisionNumber
      $this->getDB()->commit();
      return ($affectedRows !== 0);
    }
    $this->getDB()->rollback();
    return false;
  }

  /**
   * Gets the split strategy to use based off of the key
   *
   * @param  string $key Key to search for a specific strategy for
   * @return string
   */
  protected function getSplitStrategyForKey($key)
  {
    if (is_array($this->splitStrategy) && isset($this->splitStrategy[$key])) {
      $splitStrategy = $this->splitStrategy[$key];
    } else {
      $splitStrategy = is_string($this->splitStrategy) ? $this->splitStrategy : 'words';
    }

    // make sure the strategy is valid
    return (in_array($splitStrategy, RevisionData::$validSplitStrategies)) ? $splitStrategy : 'words';
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