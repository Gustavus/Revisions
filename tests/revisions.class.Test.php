<?php
/**
 * @package Revisions
 * @subpackage Tests
 */

use Gustavus\Revisions;

require_once '/cis/lib/test/test.class.php';
require_once 'revisions/classes/revisions.class.php';

/**
 * @package Revisions
 * @subpackage Tests
 */
class RevisionsTest extends \Gustavus\Test\Test
{
  /**
   * @var \Gustavus\Revisions\Revisions
   */
  private $revisions;

  /**
   * sets up the object for each test
   * @return void
   */
  public function setUp()
  {
    $this->revision = new Revisions\Revisions();
  }

  /**
   * destructs the object after each test
   * @return void
   */
  public function tearDown()
  {
    unset($this->revisions);
  }

  /**
   * @test
   */
  public function renderDiff()
  {
    //$this->assertSame('revisionTest', $revision->getDBName());
  }
}