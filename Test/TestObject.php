<?php
namespace Gustavus\Revisions\Test;
class TestObject{
  public $name;

  public function __construct($value)
  {
    $this->name = $value;
  }

  public function __toString()
  {
    return $this->name;
  }
}