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
}