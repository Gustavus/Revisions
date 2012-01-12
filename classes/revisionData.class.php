<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * @package Revisions
 */
class RevisionData
{
  /**
   * @var int revisionNumber
   */
  private $revisionNumber;

  /**
   * @var string current cell content
   */
  private $currentContent;

  /**
   * @var string revision cell content
   */
  private $revisionContent;

  /**
   * @var integer of revision's revisionId
   */
  private $revisionId;

  /**
   * startIndex, endIndex, revisionContent are keys for each revision
   *
   * @var array of revision information
   */
  private $revisionInfo = array();

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
    unset($this->currentContent, $this->revisionInfo, $this->revisionNumber, $this->revisionContent, $this->revisionId);
  }

  /**
   * @param array $array
   * @return void
   */
  private function populateObjectWithArray(Array $array)
  {
    foreach ($array as $key => $value) {
      if (property_exists($this, $key)) {
        $this->$key = $value;
      }
    }
  }

  /**
   * @return integer
   */
  public function getRevisionNumber()
  {
    return (int) $this->revisionNumber;
  }

  /**
   * @return string
   */
  public function getCurrentContent()
  {
    return $this->currentContent;
  }

  /**
   * @return string
   */
  public function getRevisionContent()
  {
    return $this->revisionContent;
  }

  /**
   * @return string
   */
  public function getRevisionInfo()
  {
    return $this->revisionInfo;
  }

  /**
   * @return string
   */
  public function getRevisionId()
  {
    return $this->revisionId;
  }

  /**
   * @param string $content
   * @return void
   */
  public function setRevisionContent($content)
  {
    $this->revisionContent = $content;
  }

  /**
   * renders revision based on the current text
   * tries to save space by only working with the parts that were modified
   *
   * @param boolean $showChanges
   * @return string of revision
   */
  protected function renderRevision($showChanges = false)
  {
    $revisionInfo = $this->getRevisionInfo();
    $currentContent = $this->getCurrentContent();
    if (empty($revisionInfo) && isset($currentContent)) {
      // brand new content was added
      if ($showChanges) {
        if (is_string($currentContent)) {
          return $this->renderContentChange($currentContent, true);
        } else {
          return $this->renderNonStringRevision(null, true);
        }
      } else {
        return '';
      }
    }
    $currContentArr = preg_split('`\b`', $currentContent);
    array_shift($currContentArr);
    foreach ($revisionInfo as $revision) {
      $revisionContent = ($showChanges) ? $this->renderContentChange($revision[2], false) : $revision[2];
      if (isset($revision[1])) {
        // content was deleted/replaced
        if ($showChanges) {
          $ins = '';
        }
        for ($i = $revision[0]; $i <= $revision[1]; ++$i) {
          if ($showChanges) {
            $ins .= $currContentArr[$i];
          }
          $currContentArr[$i] = '';
        }
        $currContentArr[$revision[0]] = ($showChanges) ? $revisionContent.$this->renderContentChange($ins, true) : $revisionContent;
      } else if (!isset($revision[0]) && count($revisionInfo) === 1) {
        // full content change. As of now, this signifies a non string revision
        return $this->renderNonStringRevision($revision[2], $showChanges);
        //$currContentArr[$revision[0]] = $revisionContent.$currText;
      } else {
        //content was added
        $space = ($showChanges) ? '' : ' ' ;
        $currText = (!empty($currContentArr[$revision[0]])) ? $space.$currContentArr[$revision[0]] : '';
        $currContentArr[$revision[0]] = $revisionContent.$currText;
      }
    }
    return implode('', $currContentArr);
  }

  /**
   * function to render a revision for non strings
   * @param  mixed $revisionContent
   * @param  boolean $showChanges
   * @return mixed
   */
  private function renderNonStringRevision($revisionContent, $showChanges = false)
  {
    if ($showChanges) {
      $currentContent = $this->getCurrentContent();
      if (is_bool($revisionContent)) {
        $revisionContent = ($revisionContent) ? 'true' : 'false';
      }
      if (is_bool($currentContent)) {
        $currentContent = ($currentContent) ? 'true' : 'false';
      }
      $oldText = $this->renderContentChange((string) $currentContent, true);
      $newText = ($revisionContent !== null) ? $this->renderContentChange((string) $revisionContent, false) : '';
      $currText = $newText.$oldText;
    } else {
      $currText = $revisionContent;
    }
    return $currText;
  }

  /**
   * function to render changes to get from $currentContent to $newContent
   *
   * @param string $newContent
   * @return string
   */
  public function makeDiff($newContent)
  {
    if (empty($this->revisionInfo)) {
      $revisionInfo = $this->makeRevisionInfo($newContent);
      $this->revisionInfo = $revisionInfo;
    }
    $this->currentContent = $newContent;

    return $this->renderRevision(true);
  }

  /**
   * renders revision content
   *
   * @param boolean $showChanges
   * @return string of revision
   */
  public function makeRevisionContent($showChanges = false)
  {
    return $this->renderRevision($showChanges);
  }

  /**
   * highlights changes from revision to current
   *
   * @param array $content
   * @param boolean $isInsert
   * @return string
   */
  private function renderContentChange($content, $isInsert)
  {
    if (empty($content)) {
      return '';
    }
    if ($isInsert) {
      $return = sprintf('<ins>%1$s</ins>',
          $content
      );
    } else {
      $return = sprintf('<del>%1$s</del>',
          $content
      );
    }
    return $return;
  }

  /**
   * tries to save space by only saving the part that was modified
   *
   * @param string $newContent
   * @return array of revision info or false if it's the same
   */
  public function renderRevisionForDB($newContent)
  {
    if ($newContent === $this->getCurrentContent()) {
      return null;
    } else {
      return json_encode($this->makeRevisionInfo($newContent));
    }
  }

  /**
   * Paul's Simple Diff Algorithm v 0.1
   * (C) Paul Butler 2007 <http://www.paulbutler.org/>
   * May be used and distributed under the zlib/libpng license.
   * This code is intended for learning purposes; it was written with short
   * code taking priority over performance. It could be used in a practical
   * application, but there are a few ways it could be optimized.
   * Given two arrays, the function diff will return an array of the changes.
   * I won't describe the format of the array, but it will be obvious
   * if you use print_r() on the result of a diff on some test data.
   *
   * @link https://github.com/paulgb/simplediff/blob/master/simplediff.php
   *
   * @param array $old
   * @param array $new
   * @return array
   */
  private function diff($old, $new)
  {
    $maxlen = 0;
    foreach ($old as $oindex => $oldv) {
      $nkeys = array_keys($new, $oldv);
      foreach ($nkeys as $nindex) {
        $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
        if ($matrix[$oindex][$nindex] > $maxlen) {
          $maxlen = $matrix[$oindex][$nindex];
          $omax = $oindex + 1 - $maxlen;
          $nmax = $nindex + 1 - $maxlen;
        }
      }
    }
    if ($maxlen === 0) {
      return array(array('d'=>$old, 'i'=>$new));
    }
    return array_merge(
        $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
    );
  }

  /**
   * function to strip irrelevent information while maintaining keys for making the revision information
   *
   * @param array $old
   * @param array $new
   * @return array
   */
  private function myArrayDiff($old, $new)
  {
    $diff = $this->diff($old, $new);
    //remove empty garbage from beginning and end
    array_shift($diff);
    array_shift($diff);
    array_pop($diff);
    $return  = array();
    $prevKey = null;
    $offset  = 0;
    foreach ($diff as $key => $value) {
      if (is_array($value)) {
        if ($key - 1 === $prevKey || ($key - 2 === $prevKey && $diff[$key - 1] === ' ')) {
          $skipped = ($key - 2 === $prevKey && $diff[$key - 1] === ' ') ? array(' ') : array();
          $return[$prevKey]['d'] = array_merge($return[$prevKey]['d'], $skipped, $value['d']);
          $return[$prevKey]['i'] = array_merge($return[$prevKey]['i'], $skipped, $value['i']);
        } else {
          $key += $offset;
          $return[$key] = $value;
        }
        $prevKey = $key;
        $offset += count($value['i']) - 1;
      }
    }
    return $return;
  }

  /**
   * function to mave revision information for non strings
   * @param  mixed $newContent
   * @return array
   */
  private function makeNonStringRevisionInfo($newContent)
  {
    if ($this->getCurrentContent() === $newContent) {
      $diffInfo = array();
    } else {
      $diffInfo = array(array(null, null, $this->getCurrentContent()));
    }
    $this->revisionInfo = $diffInfo;
    return $diffInfo;
  }

  /**
   * function to return diff information on how to roll back to a diff.
   * returns array of arrays of diffs.
   * returns info on how to get to revision from the new text
   *
   * @param string $newContent
   * @return array
   */
  protected function makeRevisionInfo($newContent)
  {
    if (!is_string($newContent)) {
      return $this->makeNonStringRevisionInfo($newContent);
    }
    $currContentArr = preg_split('`\b`', $this->getCurrentContent());
    $newContentArr  = preg_split('`\b`', $newContent);
    $diffArr        = $this->myArrayDiff($currContentArr, $newContentArr);
    $diffInfo       = array();
    foreach ($diffArr as $key => $value) {
      if (count($value['d']) === 0) {
        // content was added from the current text
        $startInd        = $key;
        $endInd          = $key + count($value['i']) - 1;
        $revisionContent = '';
      } else if (count($value['i']) === 0) {
        // content was removed from the current text
        $startInd        = $key;
        $endInd          = null;
        $revisionContent = implode('', $value['d']);
      } else {
        // content was replaced
        $startInd = $key;
        $endInd = $key + count($value['i']) - 1;
        $revisionContent = implode('', $value['d']);
      }

      $currDiff = array($startInd, $endInd, $revisionContent);
      $diffInfo[] = $currDiff;
    }
    $this->revisionInfo = $diffInfo;
    return $diffInfo;
  }
}