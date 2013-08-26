<?php
/**
 * @package Revisions
 * @subpackage Test
 * @author  Billy Visto
 */
namespace Gustavus\Revisions\Test\Entities;

/**
 * Entity representing a Revision object
 *
 * @package Revisions
 * @subpackage Test
 * @author  Billy Visto
 *
 * @Table(name="revision")
 * @Entity
 */
class Revision
{
  /**
   * @var integer $id
   *
   * @Column(name="id", type="integer", nullable=false)
   * @Id
   * @GeneratedValue(strategy="IDENTITY")
   */
  private $id;

  /**
   * @var string $contenthash
   *
   * @Column(name="contentHash", type="string", length=45, nullable=true)
   */
  private $contenthash;

  /**
   * @var string $table
   *
   * @Column(name="table", type="string", length=45, nullable=true)
   */
  private $table;

  /**
   * @var integer $rowid
   *
   * @Column(name="rowId", type="integer", nullable=true)
   */
  private $rowid;

  /**
   * @var integer $revisionnumber
   *
   * @Column(name="revisionNumber", type="integer", nullable=true)
   */
  private $revisionnumber;

  /**
   * @var string $message
   *
   * @Column(name="message", type="string", length=45, nullable=true)
   */
  private $message;

  /**
   * @var string $createdby
   *
   * @Column(name="createdBy", type="string", length=45, nullable=true)
   */
  private $createdby;

  /**
   * @var datetime $createdon
   *
   * @Column(name="createdOn", type="datetime", nullable=true)
   */
  private $createdon;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set contenthash
   *
   * @param string $contenthash
   * @return Revision
   */
  public function setContenthash($contenthash)
  {
    $this->contenthash = $contenthash;
    return $this;
  }

  /**
   * Get contenthash
   *
   * @return string
   */
  public function getContenthash()
  {
    return $this->contenthash;
  }

  /**
   * Set table
   *
   * @param string $table
   * @return Revision
   */
  public function setTable($table)
  {
    $this->table = $table;
    return $this;
  }

  /**
   * Get table
   *
   * @return string
   */
  public function getTable()
  {
    return $this->table;
  }

  /**
   * Set rowid
   *
   * @param integer $rowid
   * @return Revision
   */
  public function setRowid($rowid)
  {
    $this->rowid = $rowid;
    return $this;
  }

  /**
   * Get rowid
   *
   * @return integer
   */
  public function getRowid()
  {
    return $this->rowid;
  }

  /**
   * Set revisionnumber
   *
   * @param integer $revisionnumber
   * @return Revision
   */
  public function setRevisionnumber($revisionnumber)
  {
    $this->revisionnumber = $revisionnumber;
    return $this;
  }

  /**
   * Get revisionnumber
   *
   * @return integer
   */
  public function getRevisionnumber()
  {
    return $this->revisionnumber;
  }

  /**
   * Set message
   *
   * @param string $message
   * @return Revision
   */
  public function setMessage($message)
  {
    $this->message = $message;
    return $this;
  }

  /**
   * Get message
   *
   * @return string
   */
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * Set createdby
   *
   * @param string $createdby
   * @return Revision
   */
  public function setCreatedby($createdby)
  {
    $this->createdby = $createdby;
    return $this;
  }

  /**
   * Get createdby
   *
   * @return string
   */
  public function getCreatedby()
  {
    return $this->createdby;
  }

  /**
   * Set createdon
   *
   * @param datetime $createdon
   * @return Revision
   */
  public function setCreatedon($createdon)
  {
    $this->createdon = $createdon;
    return $this;
  }

  /**
   * Get createdon
   *
   * @return datetime
   */
  public function getCreatedon()
  {
    return $this->createdon;
  }
}