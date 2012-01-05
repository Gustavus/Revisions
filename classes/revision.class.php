<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;

/**
 * @package Revisions
 */
class Revision
{
  const REVISION_HIGHLIGHT_CLASS = 'highlight';
  /**
   * @var int revisionId
   */
  private $revisionId;

  /**
   * @var DateTime when revision was made
   */
  private $revisionDate;

  /**
   * @var string current cell content
   */
  private $currentContent;

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
    unset($this->currentContent, $this->revisionInfo);
  }

  /**
   * @return string
   */
  public function getRevisionId()
  {
    return $this->revisionId;
  }

  /**
   * @return string
   */
  public function getRevisionDate()
  {
    return $this->revisionDate;
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
  public function getRevisionInfo()
  {
    return $this->revisionInfo;
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
   * renders revision based on the current text
   * tries to save space by only working with the parts that were modified
   *
   * @param boolean $showChanges
   * @return string of revision
   */
  protected function renderRevision($showChanges = false)
  {
    $currContentArr = preg_split('`\b`', $this->getCurrentContent());
    array_shift($currContentArr);
    foreach ($this->getRevisionInfo() as $revision) {
      $revisionContent = ($showChanges) ? $this->renderContentChange($revision['revisionContent'], false) : $revision['revisionContent'];
      if (isset($revision['endIndex'])) {
        // content was deleted/replaced
        if ($showChanges) {
          $ins = '';
        }
        for ($i = $revision['startIndex']; $i <= $revision['endIndex']; ++$i) {
          if ($showChanges) {
            $ins .= $currContentArr[$i];
          }
          $currContentArr[$i] = '';
        }
        $currContentArr[$revision['startIndex']] = ($showChanges) ? $revisionContent.$this->renderContentChange($ins, true) : $revisionContent;
      } else {
        //content was added
        $space = ($showChanges) ? '' : ' ' ;
        $currText = (!empty($currContentArr[$revision['startIndex']])) ? $space.$currContentArr[$revision['startIndex']] : '';
        $currContentArr[$revision['startIndex']] = $revisionContent.$currText;
      }
    }
    return implode('', $currContentArr);
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
   * function to return diff information on how to roll back to a diff.
   * returns array of arrays of diffs.
   * returns info on how to get to revision from the new text
   *
   * @param string $newContent
   * @return array
   */
  protected function makeRevisionInfo($newContent)
  {
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

      $currDiff = array('startIndex' => $startInd, 'endIndex' => $endInd, 'revisionContent' => $revisionContent);
      $diffInfo[] = $currDiff;
    }
    $this->revisionInfo = $diffInfo;
    return $diffInfo;
  }
}