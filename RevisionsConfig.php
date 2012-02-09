<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * Config class to get application's revision information
 *
 * @package Revisions
 */
class RevisionsConfig
{
  /**
   * Returns application's revision info
   *
   * @param string $appName
   * @return array
   */
  public static function getRevisionInfo($appName)
  {
    switch ($appName) {
      case 'test':
        return array(
          'dbName' => 'person-revision',
          'revisionsTable' => 'person-revision',
          'revisionDataTable' => 'revisionData',
          'table'  => 'person',
          'rowId'  => 1,
        );

      default:
        throw new \RuntimeException('Unrecognized Application: ' . $appName);
    }
  }
}