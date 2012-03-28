<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * Gets extended by other classes to use common functions
 *
 * @package Revisions
 */
abstract class RevisionsBase
{
  /**
   * Populate object with an associative array.
   *
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
   * Convert number to its respective type
   *
   * @param  mixed $number
   * @return mixed         either an integer or float
   */
  protected function toNumber($number)
  {
    if (gettype($number) === 'string') {
      return $this->stringToNumber($number);
    } else {
      return $number;
    }
  }

  /**
   * Convert a string to an integer or float depending on if it has a decimal or not
   *
   * @param  string $number
   * @return mixed         either an integer or float
   */
  protected function stringToNumber($number)
  {
    if (preg_match('`.*\.[\d]+$`', $number) !== 0) {
      return (float) $number;
    } else {
      return (int) $number;
    }
  }
}