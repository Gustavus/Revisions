<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 *
 *
 * @package Revisions
 */
abstract class RevisionsBase
{
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
}