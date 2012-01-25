<?php
/**
 * @package Revisions
 */
namespace Gustavus\Revisions;
require_once 'revisions/classes/revisionData.class.php';

/**
 * @package Revisions
 */
class RevisionDataDiff extends RevisionData
{
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
    unset($this->revisionNumber, $this->currentContent, $this->revisionContent, $this->revisionId, $this->revisionInfo, $this->error);
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
   * Renders revision based on the current text
   * tries to save space by only working with the parts that were modified
   *
   * @param boolean $showChanges
   * @return string
   */
  protected function renderRevision($showChanges = false)
  {
    $revisionInfo = $this->getRevisionInfo();
    if (!is_array($revisionInfo)) {
      // revision info is the latest content of a revision
      return $revisionInfo;
    }
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
    return $this->putRevisionContentTogether($revisionInfo, $showChanges);
  }

  /**
   * Helper for render revision that puts the revision together
   * @param  array $revisionInfo   instructions on how to built the revision
   * @param  boolean $showChanges    whether to render a diff or not
   * @return string
   */
  private function putRevisionContentTogether($revisionInfo, $showChanges)
  {
    $currContentArr = $this->splitWords($this->getCurrentContent());
    // currContentArr has empty index at the beginning from splitWords, so get rid of it
    array_shift($currContentArr);
    foreach ($revisionInfo as $revision) {
      $revisionContent = ($showChanges) ? $this->renderContentChange($revision[self::REVISION_INFO], false) : $revision[self::REVISION_INFO];
      if (isset($revision[self::END_INDEX])) {
        // content was deleted/replaced
        if ($showChanges) {
          $ins = '';
        }
        for ($i = $revision[self::START_INDEX]; $i <= $revision[self::END_INDEX]; ++$i) {
          if ($showChanges) {
            $ins .= $currContentArr[$i];
          }
          $currContentArr[$i] = '';
        }
        $currContentArr[$revision[self::START_INDEX]] = ($showChanges) ? $revisionContent.$this->renderContentChange($ins, true) : $revisionContent;
      } else if (!isset($revision[self::START_INDEX]) && count($revisionInfo) === 1) {
        // full content change. As of now, this signifies a non string revision
        return $this->renderNonStringRevision($revision[self::REVISION_INFO], $showChanges);
      } else {
        //content was added
        $space = ($showChanges) ? '' : ' ' ;
        $currText = (!empty($currContentArr[$revision[self::START_INDEX]])) ? $space.$currContentArr[$revision[self::START_INDEX]] : '';
        $currContentArr[$revision[self::START_INDEX]] = $revisionContent.$currText;
      }
    }
    $revisionContent = implode('', $currContentArr);
    $this->setRevisionContent($revisionContent);
    return $revisionContent;
  }

  /**
   * Renders a revision for non strings
   *
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
   * Renders changes to get from $currentContent to $newContent
   *
   * @param string $newContent
   * @return string
   */
  public function makeDiff($newContent, $showChanges = false)
  {
    $revisionInfo = $this->getRevisionInfo();
    if (empty($revisionInfo)) {
      $revisionInfo = $this->makeRevisionInfo($newContent);
      $this->setRevisionInfo($revisionInfo);
    }
    $this->setCurrentContent($newContent);

    return $this->renderRevision($showChanges);
  }

  /**
   * Renders revision content
   *
   * @param boolean $showChanges
   * @return string of revision
   */
  public function makeRevisionContent($showChanges = false)
  {
    return $this->renderRevision($showChanges);
  }

  /**
   * Highlights changes from revision to current
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
      return sprintf('<ins>%1$s</ins>', $content);
    } else {
      return sprintf('<del>%1$s</del>', $content);
    }
  }

  /**
   * Renders revision info as json for DB storage
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
  private function diff(array $old, array $new)
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
      return array(array('d' => $old, 'i' => $new));
    }
    return array_merge(
        $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
    );
  }

  /**
   * Strips irrelevent information while maintaining keys for making the revision information
   *
   * @param array $old
   * @param array $new
   * @return array
   */
  private function myArrayDiff(array $old, array $new)
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
   * Makes revision information for non strings
   *
   * @param  mixed $newContent
   * @return array
   */
  private function makeNonStringRevisionInfo($newContent)
  {
    if ($this->getCurrentContent() === $newContent || $this->getCurrentContent() === '') {
      $diffInfo = array();
    } else {
      $diffInfo = array(array(null, null, $this->getCurrentContent()));
    }
    $this->setRevisionInfo($diffInfo);
    return $diffInfo;
  }

  /**
   * Splits a string at word boundaries
   *
   * @param  string $content
   * @return array
   */
  private function splitWords($content)
  {
    return preg_split('`\b`', $content);
  }

  /**
   * Makes information on how to roll back to a revision.
   * Returns an array of arrays of diffs.
   *
   * @param string $newContent
   * @return array
   */
  protected function makeRevisionInfo($newContent)
  {
    if (!is_string($newContent)) {
      return $this->makeNonStringRevisionInfo($newContent);
    }
    $currContentArr = $this->splitWords($this->getCurrentContent());
    $newContentArr  = $this->splitWords($newContent);
    $diffArr        = $this->myArrayDiff($currContentArr, $newContentArr);
    $diffInfo       = array();
    foreach ($diffArr as $key => $value) {
      $startInd        = $key;
      if (count($value['d']) === 0) {
        // content was added from the current text
        $endInd          = $key + count($value['i']) - 1;
        $revisionContent = '';
      } else if (count($value['i']) === 0) {
        // content was removed from the current text
        $endInd          = null;
        $revisionContent = implode('', $value['d']);
      } else {
        // content was replaced
        $endInd          = $key + count($value['i']) - 1;
        $revisionContent = implode('', $value['d']);
      }

      $currDiff   = array($startInd, $endInd, $revisionContent);
      $diffInfo[] = $currDiff;
    }
    $this->setRevisionInfo($diffInfo);
    return $diffInfo;
  }
}