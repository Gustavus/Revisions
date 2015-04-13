<?php
/**
 * @package Revisions
 * @author  Billy Visto
 */
namespace Gustavus\Revisions;

/**
 * Creates a RevisionData object
 *
 * @package Revisions
 * @author  Billy Visto
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
    unset($this->number, $this->nextContent, $this->content, $this->id, $this->diffInfo, $this->error);
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
    $diffInfo = $this->getDiffInfo();
    if ($diffInfo === null) {
      // revisionInfo will be null if the current content is the same as the new content
      return $this->getNextContent();
    }
    if (!is_array($diffInfo)) {
      // revision info is the latest content of a revision
      return $diffInfo;
    }
    $nextContent = $this->getNextContent();
    if (empty($diffInfo) && isset($nextContent)) {
      // brand new content was added
      if ($showChanges) {
        if (is_string($nextContent)) {
          return $this->renderContentChange($nextContent, true);
        } else {
          return $this->renderNonStringRevision(null, true);
        }
      } else {
        $this->removedContentSize = strlen($nextContent);
        return '';
      }
    }
    return $this->putRevisionContentTogether($diffInfo, $showChanges);
  }

  /**
   * Helper for render revision that puts the revision together
   * The addedContentSize and removedContentSize are working backwards. So the added content from the currentContent to this revision.
   *
   * @param  array $revisionInfo   instructions on how to built the revision
   * @param  boolean $showChanges    whether to render a diff or not
   * @return string
   */
  private function putRevisionContentTogether($revisionInfo, $showChanges)
  {
    $nextContentArr = $this->splitString($this->getNextContent());
    foreach ($revisionInfo as $diffInfo) {
      $revisionContent = ($showChanges) ? $this->renderContentChange($diffInfo->getInfo(), false) : $diffInfo->getInfo();
      $endIndex   = $diffInfo->getEndIndex();
      $startIndex = $diffInfo->getStartIndex();
      if (isset($endIndex)) {
        // content was deleted/replaced
        $ins = '';
        for ($i = $startIndex; $i <= $endIndex; ++$i) {
          $ins .= $nextContentArr[$i];
          $nextContentArr[$i] = '';
        }
        if (!$showChanges) {
          // we don't want to add this twice
          $this->addedContentSize   += strlen($revisionContent);
          $this->removedContentSize += strlen($ins);
        }
        $nextContentArr[$startIndex] = ($showChanges) ? $revisionContent.$this->renderContentChange($ins, true) : $revisionContent;
      } else if (!isset($startIndex) && count($revisionInfo) === 1) {
        // full content change. As of now, this signifies a non string revision
        return $this->renderNonStringRevision($diffInfo->getInfo(), $showChanges);
      } else {
        //content was added
        $currText = (!empty($nextContentArr[$startIndex])) ? $nextContentArr[$startIndex] : '';
        // add added content size to added content size
        if (!$showChanges) {
          // we don't want to add this twice
          $this->addedContentSize += strlen($revisionContent);
        }
        $nextContentArr[$startIndex] = $revisionContent.$currText;
      }
    }
    $revisionContent = implode('', $nextContentArr);
    $this->setContent($revisionContent);
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
    $nextContent = $this->getNextContent();
    if ($showChanges) {
      $nextContent = $this->getNextContent();
      if (is_bool($revisionContent)) {
        $revisionContent = ($revisionContent) ? 'true' : 'false';
      }
      if (is_bool($nextContent)) {
        $nextContent = ($nextContent) ? 'true' : 'false';
      }
      $oldText = $this->renderContentChange((string) $nextContent, true);
      $newText = ($revisionContent !== null) ? $this->renderContentChange((string) $revisionContent, false) : '';
      $currText = $newText.$oldText;
    } else {
      if (!$showChanges) {
        // we don't want to add this twice
        $this->addedContentSize   += strlen($revisionContent);
        $this->removedContentSize += strlen($nextContent);
      }
      $currText = $revisionContent;
    }
    return $currText;
  }

  /**
   * Renders changes to get from $nextContent to $newContent
   *
   * @param string $newContent
   * @param boolean $showChanges whether to show the changes or not
   * @return string
   */
  public function makeDiff($newContent, $showChanges = false)
  {
    $diffInfo = $this->getDiffInfo();
    if (empty($diffInfo)) {
      $diffInfo = $this->makeDiffInfo($newContent);
      $this->setDiffInfo($diffInfo);
    }
    $this->setNextContent($newContent);

    return $this->getContent($showChanges);
  }

  /**
   * Makes RevisionInfo and sets the revisionData properties to be their respective values
   *
   * @param  mixed $newContent
   * @return void
   */
  public function makeRevisionDataInfo($newContent)
  {
    if (empty($this->diffInfo) && isset($this->nextContent)) {
      if ($newContent === $this->nextContent) {
        $this->setDiffInfo(null);
      } else {
        $this->makeDiffInfo($newContent);
      }
      $this->setContent($this->getNextContent());
      $this->setNextContent($newContent);
    }
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
    if ($newContent === $this->getNextContent()) {
      return null;
    } else {
      $return = array();
      foreach ($this->makeDiffInfo($newContent) as $diffInfo) {
        if (is_object($diffInfo)) {
          $return[] = array($diffInfo->getStartIndex(), $diffInfo->getEndIndex(), $diffInfo->getInfo());
        }
      }
      return json_encode($return);
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
   * @param array $old
   * @param array $new
   * @return array
   *
   * @link https://github.com/paulgb/simplediff/blob/master/simplediff.php
   */
  private static function diff(array $old, array $new)
  {
    $maxlen = 0;
    $matrix = [];
    foreach ($old as $oindex => $oldv) {
      $nkeys = array_keys($new, $oldv);
      foreach ($nkeys as $nindex) {
        $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;

        // we don't need this one any more. Delete it to save memory
        unset($matrix[$oindex - 1][$nindex - 1]);

        if ($matrix[$oindex][$nindex] > $maxlen) {
          $maxlen = $matrix[$oindex][$nindex];
          $omax = $oindex + 1 - $maxlen;
          $nmax = $nindex + 1 - $maxlen;
        }
      }
      // nothing will use this index in up-coming iterations. Delete it to save memory
      unset($matrix[$oindex - 1]);
    }
    unset($matrix);
    if ($maxlen === 0) {
      return array(array('d' => $old, 'i' => $new));
    }
    return array_merge(
        self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
    );
  }

  /**
   * Checks to see if any important skipped values exist so we can thrown consecutive changes together
   *
   * @param  integer $prevKey
   * @param  integer $key
   * @param  array $diff
   * @return boolean
   */
  private function skippedValuesExist($prevKey, $key, $diff)
  {
    return (
      count($prevKey) > 0 &&
      is_array($diff[$prevKey[0]]) &&
      $key > 1 &&
      ($key - 1 === end($prevKey) ||
        ($key - 2 === end($prevKey) &&
          preg_match('`^\s+$`', $diff[$key - 1]) !== 0
        )
      )
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
    $diff = self::diff($old, $new);
    // remove empty data from diff()
    if (empty($diff[0]['d']) && empty($diff[0]['i'])) {
      // if the text starts with punctuation the first item will not be empty
      // the diff returns a difference from the start of the content so we want to get rid of the empty diff
      array_shift($diff);
    }
    if (empty($diff[count($diff) - 1]) || (empty($diff[count($diff) - 1]['d']) && empty($diff[count($diff) - 1]['i']))) {
      // if the text ends with punctuation the last item will not be empty
      array_pop($diff);
    }
    $return    = array();
    $prevKey   = array();
    $oldOffset = 0;
    $offset    = 0;
    foreach ($diff as $key => $value) {
      if (is_array($value)) {
        if ($this->skippedValuesExist($prevKey, $key, $diff)) {
          // $value is a continuation of a previous diff's deletion and can be thrown on top of it.
          // find skipped spaces between the last index and this one.
          $skipped = ($key - 2 === end($prevKey) && preg_match('`^(\s+)$`', $diff[$key - 1], $spaces) !== 0)  ? array($spaces[0]) : array();
          // figure out what the deletion we are adding on to is
          $prevD = (isset($return[$prevKey[0] + $oldOffset]['d'])) ? $return[$prevKey[0] + $oldOffset]['d'] : array();
          // add the deletions together to make a chained deletion.
          $return[$prevKey[0] + $oldOffset]['d'] = array_merge($prevD, $skipped, $value['d']);

          // figure out what the insertion we are adding onto is
          $prevI = (isset($return[$prevKey[0] + $oldOffset]['i'])) ? $return[$prevKey[0] + $oldOffset]['i'] : array();
          // add the insertions together to make a chained insertion
          $return[$prevKey[0] + $oldOffset]['i'] = array_merge($prevI, $skipped, $value['i']);
          if ($key > 0) {
            // add the current key to the array of keys so we can continue chaining things together if needed
            $prevKey[] = $key;
          }
        } else {
          // we need the old offset so we know where the items live in the return array since offset gets set with the new offset
          $oldOffset = $offset;
          $return[$key + $oldOffset] = $value;
          // new potential chain, reset the array with only the current key so we can see if anything can be chained on top of it
          $prevKey = array($key);
        }
        // offset of the total number of items we have chained together so we maintain proper keys
        $offset += count($value['i']) - 1;
      } else if (preg_match('`^\s+$`', $value) === 0) {
        // reset $prevKey
        $prevKey = array();
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
  private function makeNonStringDiffInfo($newContent)
  {
    if ($this->getNextContent() === $newContent) {
      $diffInfo = array();
    } else {
      $diffInfo = new DiffInfo(array('startIndex' => null, 'endIndex' => null, 'info' => $this->getNextContent()));
      $diffInfo = array($diffInfo);
    }
    $this->setDiffInfo($diffInfo);
    return $this->getDiffInfo();
  }

  /**
   * Splits the string using the appropriate strategy
   *
   * @param  string $content Content to split
   * @return array
   */
  private function splitString($content)
  {
    switch ($this->splitStrategy) {
      case 'sentenceOrTag':
          return $this->splitSentenceOrTag($content);
      case 'words':
      default:
          return $this->splitWords($content);
    }
  }

  /**
   * Splits a string at word boundaries
   *
   * @param  string $content
   * @return array
   */
  private function splitWords($content)
  {
    $split = preg_split('`(\b|\s+)`', $content, null, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
    return $split;
  }

  /**
   * Splits a string at sentence or html tag boundaries
   *
   * @param  string $content
   * @return array
   */
  private function splitSentenceOrTag($content)
  {
    $sentenceEnd = '[\.|\?|\!]';
    $tag         = '<[^>]+?>';
    $regex       = sprintf('`((?:%1$s%2$s)|%1$s|(?:%2$s))`', $sentenceEnd, $tag);

    $split = preg_split($regex, $content, null, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
    return $split;
  }

  /**
   * Makes information on how to roll back to a revision.
   * Returns an array of arrays of diffs.
   *
   * @param string $newContent
   * @return array
   */
  protected function makeDiffInfo($newContent)
  {
    if (!is_string($newContent)) {
      return $this->makeNonStringDiffInfo($newContent);
    }
    $nextContentArr = $this->splitString($this->getNextContent());
    $newContentArr  = $this->splitString($newContent);
    $diffArr        = $this->myArrayDiff($nextContentArr, $newContentArr);
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

      $currDiff   = new DiffInfo(array('startIndex' => $startInd, 'endIndex' => $endInd, 'info' => $revisionContent));
      $diffInfo[] = $currDiff;
    }
    $this->setDiffInfo($diffInfo);
    return $diffInfo;
  }
}