<?php
/**
 * @package Revisions
 * @subpackage  Tests
 * @author  Billy Visto
 */
namespace Gustavus\Revisions\Test;

/**
 * Test object for testing making a revision of an object
 *
 * @package Revisions
 * @subpackage  Tests
 * @author  Billy Visto
 */
class TestObject
{
  /**
   * Test property
   * @var string
   */
  public $name;

  /**
   * Constructor
   * @param string $value
   */
  public function __construct($value)
  {
    $this->name = $value;
  }

  /**
   * Magical method to convert this object to a string
   * @return string
   */
  public function __toString()
  {
    return $this->name;
  }
}