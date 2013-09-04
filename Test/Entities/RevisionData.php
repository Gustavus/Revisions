<?php
/**
 * @package Revisions
 * @subpackage Test
 * @author  Billy Visto
 */
namespace Gustavus\Revisions\Test\Entities;
/**
 * Entity representing a RevisionData object
 *
 * @package Revisions
 * @subpackage Test
 * @author  Billy Visto
 *
 * @Table(name="revisionData")
 * @Entity
 */
class RevisionData
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
   * @var integer $revisionnumber
   *
   * @Column(name="revisionNumber", type="integer", nullable=true)
   */
  private $revisionnumber;

  /**
   * @var string $key
   *
   * @Column(name="key", type="string", length=45, nullable=true)
   */
  private $key;

  /**
   * @var text $value
   *
   * @Column(name="value", type="text", nullable=true)
   */
  private $value;

  /**
   * @var Revision
   *
   * @ManyToOne(targetEntity="Revision")
   * @JoinColumns({
   *   @JoinColumn(name="revisionId", referencedColumnName="id")
   * })
   */
  private $revisionid;


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
   * @return Revisiondata
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
   * Set revisionnumber
   *
   * @param integer $revisionnumber
   * @return Revisiondata
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
   * Set key
   *
   * @param string $key
   * @return Revisiondata
   */
  public function setKey($key)
  {
    $this->key = $key;
    return $this;
  }

  /**
   * Get key
   *
   * @return string
   */
  public function getKey()
  {
    return $this->key;
  }

  /**
   * Set value
   *
   * @param text $value
   * @return Revisiondata
   */
  public function setValue($value)
  {
    $this->value = $value;
    return $this;
  }

  /**
   * Get value
   *
   * @return text
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * Set revisionid
   *
   * @param Revision $revisionid
   * @return Revisiondata
   */
  public function setRevisionid(\Revision $revisionid = null)
  {
    $this->revisionid = $revisionid;
    return $this;
  }

  /**
   * Get revisionid
   *
   * @return Revision
   */
  public function getRevisionid()
  {
    return $this->revisionid;
  }
}