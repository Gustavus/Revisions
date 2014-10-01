// Make sure our Gustavus pseudo namespace exists
if(!window.Gustavus) {
  window.Gustavus = {};
}

/**
 * Revisions
 * @class Gustavus.Revisions
 * @singleton
 * @author Billy Visto
 */
Gustavus.Revisions = {
  /**
   * Timeline functionality
   * @class Gustavus.Revisions.Timeline
   * @singleton
   * @author Billy Visto
   */
  Timeline: {
    /**
     * Number of extra revisions we want to load to the timeline so we don't have to keep pulling them in one at a time while the person is scrolling
     * @type {Number}
     */
    scrollingCache: 6,

    /**
     * Minimum number of revisions scrolled until we pull in more revisions to the timeline
     * @type {Number}
     */
    autoLoadGroupSize: 3,

    /**
     * How many revisions to pad with when scrolling the timeline
     * @type {Number}
     */
    padding: 3,

    /**
     * How many revisions to scroll each time the timeline is scrolled
     * @type {Number}
     */
    revisionsToScrollThrough: 3,

    /**
     * How many milliseconds per pixel to slide when hovering over a scrolling hotspot
     * @type {Number}
     */
    hotspotMSPerPixel: 10

    /**
     * Scrollable viewport. Should only be used by getViewport().
     * @type {jQuery}
     */
    $viewport: null,

    /**
     * Gets the scrollable viewport for the timeline
     * @return {jQuery}
     */
    getViewport: function() {
      if (Gustavus.Revisions.Timeline.$viewport === null || Gustavus.Revisions.Timeline.$viewport.length === 0) {
        Gustavus.Revisions.Timeline.$viewport = $('#revisionTimeline .viewport');
      }
      return Gustavus.Revisions.Timeline.$viewport;
    },

    /**
     * Checks to see if the scrollable viewport is needed.
     * @return {Boolean}
     */
    isViewportNeeded: function()
    {
      return $('#revisionTimeline .viewport table').outerWidth() > $('#revisionTimeline').width();
    },

    /**
     * Table header of the last revision. Should only be used with getLastRevisionTh()
     * @type {jQuery}
     */
    $lastRevisionTh: null,

    /**
     * Gets the table header for the last revision
     * @return {jQuery}
     */
    getLastRevisionTh: function() {
      if (Gustavus.Revisions.Timeline.$lastRevisionTh === null) {
        Gustavus.Revisions.Timeline.$lastRevisionTh = $('#revisionTimeline thead th:last-child');
      }
      return Gustavus.Revisions.Timeline.$lastRevisionTh;
    },

    /**
     * Width of each individual revision. Should only be used with getRevisionWidth()
     * @type {Number}
     */
    revisionWidth: null,

    /**
     * Gets the revision width
     * @return {Number}
     */
    getRevisionWidth: function() {
      if (Gustavus.Revisions.Timeline.revisionWidth === null) {
        // .prev() because the last item has a border right that gets included in outerWidth in firefox but not in chrome, so we will resort to the second to last item
        Gustavus.Revisions.Timeline.revisionWidth = (Gustavus.Revisions.Timeline.getLastRevisionTh().prev()) ? Gustavus.Revisions.Timeline.getLastRevisionTh().prev().outerWidth() : Gustavus.Revisions.Timeline.getLastRevisionTh().outerWidth();
      }
      return Gustavus.Revisions.Timeline.revisionWidth;
    },

    /**
     * Latest revision number. Should only be used with getLatestRevisionNumber().
     * @type {Number}
     */
    latestRevisionNumber: null,

    /**
     * Gets the latest revision number
     * @return {Number}
     */
    getLatestRevisionNumber: function() {
      if (Gustavus.Revisions.Timeline.latestRevisionNumber === null) {
        Gustavus.Revisions.Timeline.latestRevisionNumber = Gustavus.Revisions.Timeline.getLastRevisionTh().data('revision-number');
      }
      return Gustavus.Revisions.Timeline.latestRevisionNumber;
    },

    /**
     * Width of the table that is within the current viewport. Should only be used in getVisibleTableWidth().
     * @type {Number}
     */
    visibleTableWidth: null,

    /**
     * Gets the width of the table that is within the current viewport.
     * @return {Number}
     */
    getVisibleTableWidth: function() {
      if (Gustavus.Revisions.Timeline.visibleTableWidth === null) {
        Gustavus.Revisions.Timeline.visibleTableWidth = Gustavus.Revisions.Timeline.getViewport().width();
      }
      return Gustavus.Revisions.Timeline.visibleTableWidth;
    },

    // number of pixels in the table that are not visible
    /**
     * Number of pixels in the table that are not visible. Should only be used in getPixelsHidden().
     * @type {Number}
     */
    pixelsHidden: null,

    /**
     * Gets the number of pixels in the table that are not visible.
     * @return {Number}
     */
    getPixelsHidden: function() {
      if (Gustavus.Revisions.Timeline.pixelsHidden === null) {
        Gustavus.Revisions.Timeline.pixelsHidden = Gustavus.Revisions.Timeline.getLatestRevisionNumber() * Gustavus.Revisions.Timeline.getRevisionWidth() - Gustavus.Revisions.Timeline.getVisibleTableWidth();
      }
      return Gustavus.Revisions.Timeline.pixelsHidden;
    },

    /**
     * Gets the left offset of the table within the viewport.
     * @return {Number}
     */
    getLeftOffset: function() {
      if (Gustavus.Revisions.Timeline.isViewportNeeded() && Gustavus.Revisions.Timeline.getViewport().length !== 0 && Gustavus.Revisions.Timeline.getViewport().viewport('content').length === 0) {
        // set up viewport if timeline exists but not yet a viewport
        Gustavus.Revisions.setUpViewport();
      }
      if (Gustavus.Revisions.Timeline.isViewportNeeded() && Gustavus.Revisions.Timeline.getViewport().viewport('content').length !== 0) {
        // the viewport exists, so we can get the position of it
        return Gustavus.Revisions.Timeline.getViewport().viewport('content').position().left;
      } else {
        // if the viewport doesn't exist, there will be no position
        return 0;
      }
    },

    /**
     * Gets the number of revisions visible in the viewport as well as those hidden to the right.
     * @return {Number}
     */
    getNumberOfRevisionsVisible: function() {
      return Math.floor((Gustavus.Revisions.Timeline.getVisibleTableWidth() + Gustavus.Revisions.Timeline.getLeftOffset()) / Gustavus.Revisions.Timeline.getRevisionWidth());
    },
  },

  /**
   * Duration of the sliding animation when sliding in revisionData.
   * @member Gustavus.Revisions
   * @type {Number}
   */
  contentSlideSpeed: 250,

  /**
   * Query string of the application
   * @member Gustavus.Revisions
   * @type {String}
   */
  applicationQueryString: '',

  /**
   * Arguments that the revision system uses.
   * @member Gustavus.Revisions
   * @type {Array}
   */
  revisionsArgs: Array(
    'revisionsAction',
    'revisionNumber',
    'revisionNumbers',
    'columns',
    'limit',
    'oldestRevisionNumber',
    'barebones',
    'visibleRevisions',
    'oldestRevisionInTimeline'
  ),

  /**
   * Storage for oldData
   * @member Gustavus.Revisions
   * @type {Object}
   */
  oldData: {},

  /**
   * Oldest revision number currently pulled.
   * @member Gustavus.Revisions
   * @type {Number}
   */
  oldestRevisionNumber: null,

  /**
   * Unselects the specified item.
   * @member Gustavus.Revisions
   * @param  {jQuery} $item
   * @return {undefined}
   */
  unselectBox: function($item)
  {
    $item.removeAttr('checked');
    $('#revisionTimeline .' + $item.val()).removeClass('selected');
  },

  /**
   * Marks the specified item as selected.
   * @member Gustavus.Revisions
   * @param  {jQuery} $item
   * @return {undefined}
   */
  selectBox: function($item)
  {
    $item.attr('checked', 'checked');
  },

  /**
   * Unselect the box closest to the specified item.
   * @member Gustavus.Revisions
   * @param  {jQuery} $item
   * @return {undefined}
   */
  unselectClosestBox: function($item)
  {
    var $checkedBoxes = $('input.compare:checked:not(#' + $item.attr('id') + ')');
    var firstVal      = parseInt($checkedBoxes.first().val());
    var lastVal       = parseInt($checkedBoxes.last().val());
    var itemVal       = parseInt($item.val());
    if (firstVal > itemVal) {
      // newly checked box should replace the first checked box
      Gustavus.Revisions.unselectBox($checkedBoxes.first());
    } else if (lastVal < itemVal) {
      // newly checked box should replace the last checked box
      Gustavus.Revisions.unselectBox($checkedBoxes.last());
    } else {
      // it is somewhere in the middle, so we need to figure out which one the new box is closer to.
      var firstDistance = itemVal - firstVal;
      var lastDistance  = lastVal - itemVal;
      var middle        = $('input.compare').length / 2;
      if (firstDistance < lastDistance) {
        // new box is closer to the first box
        Gustavus.Revisions.unselectBox($checkedBoxes.first());
      } else if (lastDistance < firstDistance) {
        // new box is closer to the last box
        Gustavus.Revisions.unselectBox($checkedBoxes.last());
      } else if (itemVal < middle) {
        // item is in the middle, so if it is in the first half of the timeline, unselect the first box
        Gustavus.Revisions.unselectBox($checkedBoxes.first());
      } else {
        // item is in the middle, so if it is in the last half of the timeline, unselect the last box
        Gustavus.Revisions.unselectBox($checkedBoxes.last());
      }
    }
  },

  /**
   * Enables the compare button if we have 2 revisions selected.
   * @member Gustavus.Revisions
   * @return {undefined}
   */
  enableCompareButton: function()
  {
    if ($('td.old input.compare:checked, td.young input.compare:checked').length !== 2) {
        $('#compareButton').removeAttr('disabled');
    } else {
      $('#compareButton').attr('disabled', 'disabled');
    }
  },

  /**
   * Makes an array of the visible revision diffs.
   * @member Gustavus.Revisions
   * @return {Array}
   */
  makeVisibleRevisionsArray: function()
  {
    var visibleLength = $('tfoot td.old label input, tfoot td.young label input').length;
    if (visibleLength === 1) {
      var visibleRevisions = Array($('tfoot td.old label input, tfoot td.young label input').first().val());
    } else if (visibleLength === 2) {
      var visibleRevisions = Array($('tfoot td.old label input, tfoot td.young label input').first().val(), $('tfoot td.old label input, tfoot td.young label input').last().val());
    } else {
      visibleRevisions = Array();
    }
    return visibleRevisions;
  },

  /**
   * Animates the replacement of html data for the specified selector
   *
   * @member Gustavus.Revisions
   * @param  {jQuery} $selector Selector to animate data replacement in
   * @param  {String} html      HTML to replace the current data with
   * @param  {String} direction Direction of the animation
   * @return {undefined}
   */
  animateAndReplaceData: function($selector, html, direction)
  {
    // Prevents animations (and their callbacks) from overlapping
    $selector.parent('.slideViewport').stop(true, true);

    if (!$selector.parent().hasClass('slideViewport')) {
      $selector
        .wrap('<div class="slideViewport" />')
        .children(':not(.slideSection)').wrapAll('<div class="slideSection" />');
    }

    // Append the new content to the element
    $selector.append('<div class="slideSection">'+html+'</div>');

    var $sections = $selector.children('.slideSection');
    $sections.last().addClass('slide-' + direction);

    $selector.parent() // .slideViewport
      .height(Math.max($selector.outerHeight(true), $sections.last().outerHeight(true)))
      .hide('slide', {
          direction: direction,
          distance: $sections.last().outerWidth(true)
        }, Gustavus.Revisions.contentSlideSpeed, function() {
          $sections
            .removeClass('slide-' + direction)
            .not(':last-child').remove().end() // Remove all but the last section
            .children().unwrap();
          $selector.unwrap();
        }
      );
  },

  /**
   * Slides the timeline within the viewport.
   * @member Gustavus.Revisions
   * @param  {Number} pos           Position to slide the timeline to.
   * @param  {Number} maxPos        Maximum position to slide
   * @param  {Boolean} shouldAnimate Whether or not to animate the slide or not
   * @param  {Number} speed         Speed of the animation
   * @return {undefined}
   */
  slideTimeline: function(pos, maxPos, shouldAnimate, speed)
  {
    if (pos > maxPos) {
      // we don't want it to overscroll
      pos = maxPos;
    }
    if (pos < 0) {
      // we don't want negatives
      pos = 0;
    }
    if (shouldAnimate) {
      Gustavus.Revisions.Timeline.getViewport().viewport('content').stop(true, true).animate({'left': pos + 'px'}, speed, 'easeInOutSine', function() {
        // make sure revisions are visible in timeline
        Gustavus.Revisions.loadVisibleRevisionsIntoTimeline();
      });
    } else {
      Gustavus.Revisions.Timeline.getViewport().viewport('content').css('left', pos + 'px');
      // make sure revisions are visible in timeline
      Gustavus.Revisions.loadVisibleRevisionsIntoTimeline();
    }
  },

  /**
   * Slides the timeline to make sure visible revisions are in view
   * @member Gustavus.Revisions
   * @param  {jQuery} $formExtras   jQuery object representing the section containing the diffs
   * @param  {Boolean} shouldAnimate Whether to animate showing revisions or not
   * @return {undefined}
   */
  showVisibleRevisionInTimeline: function($formExtras, shouldAnimate)
  {
    if (!Gustavus.Revisions.Timeline.isViewportNeeded()) {
      return;
    }
    if ($('#revisionTimeline').html() !== '' && $formExtras.find('footer button:first-child').html() !== '') {
      // revision timeline and revisionData exist
      var oldestRevDataShown = $formExtras.find('footer button:first-child').val();
      var latestRevDataShown = $formExtras.find('footer button:last-child').val();

      // range of revisions in the visible viewport
      var visibleRevisionsRange = Array(Gustavus.Revisions.Timeline.getLatestRevisionNumber() - Gustavus.Revisions.Timeline.getNumberOfRevisionsVisible(), Gustavus.Revisions.Timeline.getLatestRevisionNumber() - Math.floor(Gustavus.Revisions.Timeline.getLeftOffset() / Gustavus.Revisions.Timeline.getRevisionWidth()));

      if (visibleRevisionsRange[0] >= oldestRevDataShown) {
        // timeline needs to scroll left;
        var newLeftPos = ((visibleRevisionsRange[0] - oldestRevDataShown) + Gustavus.Revisions.Timeline.padding) * Gustavus.Revisions.Timeline.getRevisionWidth();

        Gustavus.Revisions.slideTimeline(newLeftPos, Gustavus.Revisions.Timeline.getPixelsHidden(), shouldAnimate, Gustavus.Revisions.contentSlideSpeed);
      } else if (latestRevDataShown >= visibleRevisionsRange[1]) {
        // timeline needs to scroll right;
        var newLeftPos = ((Gustavus.Revisions.Timeline.getLatestRevisionNumber() - latestRevDataShown - Gustavus.Revisions.Timeline.padding) * Gustavus.Revisions.Timeline.getRevisionWidth());

        Gustavus.Revisions.slideTimeline(newLeftPos, Gustavus.Revisions.Timeline.getPixelsHidden(), shouldAnimate, Gustavus.Revisions.contentSlideSpeed);
      }
    }
  },

  /**
   * Replaces sections data with the specified data.
   * @member Gustavus.Revisions
   * @param  {jQuery} $data     jQuery object containing children of sections to replace
   * @param  {String} direction Direction of animation
   * @return {undefined}
   */
  replaceSectionsWithData: function($data, direction)
  {
    $('#revisionsForm').attr('method', $data.attr('method'));

    $data.children().each(function(i, element) {
      switch ($(element).attr('id')) {
        case 'revisionTimeline':
          // make sure the oldest revisionNumber pulled in is less than the oldestRevisionNumber we have asked for incase the ajax call takes time.
          var $revisionTimeline = $('#revisionTimeline');
          if (Gustavus.Revisions.oldestRevisionNumber === null || $data.find('#hiddenFields #oldestRevisionNumber').val() <= Gustavus.Revisions.oldestRevisionNumber) {
            if ($(element).html() !== '' && $revisionTimeline.html() !== '') {
              $('#revisionTimeline table').html($(element).find('table').html());
              // make sure revisionTimeline is visible
              $revisionTimeline.show();
              Extend.apply('page', $revisionTimeline);
            } else {
              if ($revisionTimeline.html() === '') {
                // timeline was empty, so completely replace it
                $revisionTimeline.html($(element).html());
                // make sure revisionTimeline is visible
                $revisionTimeline.show();
              }
              if ($(element).html() !== '') {
                // timeline is being replaced. Set up viewport to drag and scroll
                Extend.add('page', Gustavus.Revisions.setUpViewport());
              }
            }
          }
            break;

        case 'formExtras':
          if ($('#revisionTimeline').html() !== '' && $(element).find('#restoreButton').length === 0) {
            // if coming from restore page, we want to show the timeline
            $('#revisionTimeline').show();
          } else if ($(element).find('#restoreButton').length !== 0) {
            $('#revisionTimeline').hide();
          }
          Gustavus.Revisions.animateAndReplaceData($('#formExtras'), $(element).html(), direction);
          Gustavus.Revisions.showVisibleRevisionInTimeline($(element), true);
          restoreButtons = $(element).find('footer button');
          // unselect all boxes
          $('input.compare:checked').each(function(i, element) {
            Gustavus.Revisions.unselectBox($(element));
          })
          if (restoreButtons.length === 1) {
            // revisionData
            $('.young').removeClass('young');
            $('.old').removeClass('old');
            $('.' + restoreButtons.first().val()).addClass('young');
            Gustavus.Revisions.selectBox($('#revisionNum-' + restoreButtons.first().val()));
          } else if (restoreButtons.length === 2) {
            // revisionData comparison
            $('.old').removeClass('old');
            $('.' + restoreButtons.first().val()).addClass('old').removeClass('selected');
            Gustavus.Revisions.selectBox($('#revisionNum-' + restoreButtons.first().val()));
            $('.young').removeClass('young');
            $('.' + restoreButtons.last().val()).addClass('young').removeClass('selected');
            Gustavus.Revisions.selectBox($('#revisionNum-' + restoreButtons.last().val()));
          } else {
            // revisionData was removed from the page
            $('.young').removeClass('young');
            $('.old').removeClass('old');
          }
            break;

          default:
            $('#' + $(element).attr('id')).html($(element).html());
      }

      $('#compareButton').attr('disabled', 'disabled');
    });
  },

  /**
   * Makes an object of data for ajax requests.
   * @member Gustavus.Revisions
   * @param  {jQuery} $element Element the ajax request was triggered with
   * @return {Object} Object of data for ajax.
   */
  makeDataObject: function($element)
  {
    var data = {};

    // get hidden fields
    $('#hiddenFields input').each(function(i, element) {
      data[$(element).attr('name')] = $(element).val();
    });

    if ($element.is('button')) {
      // A button was clicked, so we need to get that button's data
      data[$element.attr('name')] = $element.val();

      if ($element.attr('id') === 'compareButton') {
        // User wants to compare two revisions, so get checkbox data
        if ($('#revisionTimeline input.compare:checked').length > 1) {
          data['revisionNumbers'] = Array();
          $('#revisionTimeline input.compare:checked').each(function(i, element) {
            data['revisionNumbers'][i] = $(element).val();
          })
        }
      }
    } else {
      // A non-button element was clicked, (table cell) so we need to get
      // that table cell's data
      if ($element.data('revision-number') !== undefined) {
        // User clicked on a single revision to view it
        data['revisionNumber'] = $element.data('revision-number');
      } else if ($element.data('oldest-revision-number') !== undefined) {
        // User clicked on a non-visible revision to load the timeline further back
        data['oldestRevisionNumber'] = $element.data('oldest-revision-number');
        if ($('input.compare:checked').length > 1) {
          // User was already comparing two revisions, so we want to keep that
          // comparison going
          data['revisionNumbers'] = Array();
          $('#formExtras footer button').each(function(i, element) {
            data['revisionNumbers'][i] = $(element).val();
          })
        }
      }
    }
    return data;
  },

  /**
   * Gets the direction of the slide animation during the content replacement
   * @member Gustavus.Revisions
   * @param  {Object} revData Object of revisionsData to decide which direction to slide
   * @return {string}         Direction to slide the animation. Either left or right.
   */
  getSlideDirection: function(revData)
  {
    if (Gustavus.Revisions.oldData.revisionNumber !== undefined && revData.revisionNumber !== undefined) {
      // figure out old revisionNumber
      if (Gustavus.Revisions.oldData.revisionNumber === "false") {
        // set oldRevNum to be the average of the revisionNumbers you are comparing
        if (Gustavus.Revisions.oldData.revisionNumbers !== undefined && Gustavus.Revisions.oldData.revisionNumbers.length > 1) {
          var oldRevNum = (parseInt(Gustavus.Revisions.oldData.revisionNumbers[0]) + parseInt(Gustavus.Revisions.oldData.revisionNumbers[1])) / 2;
        } else {
          var oldRevNum = 0;
        }
      } else {
        var oldRevNum = Gustavus.Revisions.oldData.revisionNumber;
      }
      // figure out new revisionNumber
      if (revData.revisionNumber === "false") {
        if (revData.revisionNumbers !== undefined && revData.revisionNumbers.length > 1) {
          var newRevNum = (parseInt(revData.revisionNumbers[0]) + parseInt(revData.revisionNumbers[1])) / 2;
        } else {
          // we want this to default to left, so add one to the oldRevNum
          var newRevNum = oldRevNum + 1;
        }
      } else {
        var newRevNum = revData.revisionNumber;
      }
      if (newRevNum >= oldRevNum) {
        var direction = 'left';
      } else {
        var direction = 'right';
      }
    } else {
      var direction = 'left';
    }
    Gustavus.Revisions.oldData = revData;
    return direction
  },

  /**
   * Performs the ajax request with the specified data to the specified url
   * @member Gustavus.Revisions
   * @param  {Object} revData Data we are replacing to determine the slide direction
   * @param  {String} url     URL to make the request to
   * @return {undefined}
   */
  makeAjaxRequest: function(revData, url)
  {
    // This is triggered when the history state changes
    var data = {};
    data['barebones'] = true;
    var oldestRevisionInTimeline = $('tfoot td label input').first().val();
    if (oldestRevisionInTimeline) {
      // only set this if the timeline exists
      data['oldestRevisionInTimeline'] = oldestRevisionInTimeline;
    }
    data['visibleRevisions'] = Gustavus.Revisions.makeVisibleRevisionsArray();

    if (url !== '') {
      $.ajax({
        url: url,
        type: 'GET',
        data: data,
        success: function(ajaxData) {
          Gustavus.Revisions.replaceSectionsWithData($(ajaxData), Gustavus.Revisions.getSlideDirection(revData));
          // Track this event in Google Analytics
          window._gaq = window._gaq || [];
          _gaq.push(['_trackPageview', url.replace(/^https?:\/\/[^\/]+/, '')]);
        }
      });
    }
  },

  /**
   * Makes history for navigating if enabled or performs the ajax request to get the new data
   * @member Gustavus.Revisions
   * @param  {jQuery} $element          Element that triggered our makeHistory event
   * @param  {Boolean} shouldMakeHistory Whether we should add to our history stack or just make the ajax request.
   * @return {undefined}
   */
  makeHistory: function($element, shouldMakeHistory)
  {
    var revData = Gustavus.Revisions.makeDataObject($element);
    var url = Gustavus.Utility.URL.urlify('?', revData, true, false);

    if (window.History.enabled && shouldMakeHistory) {
      window.History.pushState(revData, null, url);
    } else {
      // history isn't enabled, so the statechange event wont get called
      Gustavus.Revisions.makeAjaxRequest(revData, url);
    }
  },

  /**
   * Handles click actions
   * @member Gustavus.Revisions
   * @param  {jQuery} $element          Element the click was triggered on.
   * @param  {Boolean} shouldMakeHistory Whether we should add to our history stack or not
   * @return {undefined}
   */
  handleClickAction: function($element, shouldMakeHistory)
  {
    // call make history to throw the new url to the stack and trigger the statechange event that calls Gustavus.Revisions.makeAjaxRequest
    Gustavus.Revisions.makeHistory($element, shouldMakeHistory);
  },

  /**
   * Triggers a click action on the show more revisions button.
   * @member Gustavus.Revisions
   * @param  {Number} oldestRevNumToPull Oldest revision number we want loaded
   * @return {undefined}
   */
  triggerShowMoreClick: function(oldestRevNumToPull)
  {
    if ($('.compare input:first-child').val() > oldestRevNumToPull) {
      Gustavus.Revisions.oldestRevisionNumber = oldestRevNumToPull;
      // trigger click on button to pull more revisions
      // don't push to history stack
      Gustavus.Revisions.handleClickAction($('td.bytes.' + oldestRevNumToPull).first(), false);
    }
  },

  /**
   * Converts negative numbers and zero to 1.
   * @member Gustavus.Revisions
   * @param  {Number} number Number to convert.
   * @return {Number} Adjusted number
   */
  convertNegativeAndZero: function(number)
  {
    if (number <= 0) {
      return 1;
    } else {
      return number;
    }
  },

  /**
   * Loads all visible revisions into the timeline so we have data to look at.
   * @member Gustavus.Revisions
   * @return {undefined}
   */
  loadVisibleRevisionsIntoTimeline: function()
  {
    if ($('.compare input:first-child').val() > 1) {
      // pull in extra revisions specified in timelineScrollingCache
      var oldestRevNumToPull        = Gustavus.Revisions.convertNegativeAndZero(Gustavus.Revisions.Timeline.getLatestRevisionNumber() - (Gustavus.Revisions.Timeline.getNumberOfRevisionsVisible() + Gustavus.Revisions.Timeline.scrollingCache));

      if (Gustavus.Revisions.oldestRevisionNumber === null) {
        Gustavus.Revisions.triggerShowMoreClick(oldestRevNumToPull);
      } else {
        // pull in by group size set in timelineAutoLoadGroupSize
        var oldestRevNumAllowedToPull = Gustavus.Revisions.convertNegativeAndZero(Gustavus.Revisions.oldestRevisionNumber - Gustavus.Revisions.Timeline.autoLoadGroupSize);
        if (Gustavus.Revisions.oldestRevisionNumber > 1 && oldestRevNumAllowedToPull >= oldestRevNumToPull) {
          Gustavus.Revisions.triggerShowMoreClick(oldestRevNumToPull);
        }
      }
    }
  },

  /**
   * Handles scrolling events triggered by the mouse
   * @member Gustavus.Revisions
   * @param  {Number} delta  Total distance the mouse has moved
   * @param  {Number} deltaX Total distance the mouse has moved over the X axis
   * @param  {Number} deltaY Total distance the mouse has moved over the Y axis
   * @return {undefined}
   */
  scrollOnMouseWheel: function(delta, deltaX, deltaY)
  {
    if (delta) {
      var wheelScrollAmount = (deltaX !== 0) ? 0 - deltaX : delta;

      if (wheelScrollAmount < 0) {
        var newPos = Gustavus.Revisions.Timeline.getLeftOffset() + (Gustavus.Revisions.Timeline.getRevisionWidth() * Gustavus.Revisions.Timeline.revisionsToScrollThrough) * wheelScrollAmount;
      } else {
        var newPos = Gustavus.Revisions.Timeline.getLeftOffset() + (Gustavus.Revisions.Timeline.getRevisionWidth() * Gustavus.Revisions.Timeline.revisionsToScrollThrough) * wheelScrollAmount;
      }

      var hoverRevisionNumber = $('#revisionTimeline th.hover').data('revision-number');

      if (newPos > Gustavus.Revisions.Timeline.getPixelsHidden()) {
        var revisionsToScroll = (newPos - Gustavus.Revisions.Timeline.getPixelsHidden()) / (Gustavus.Revisions.Timeline.revisionsToScrollThrough * Gustavus.Revisions.Timeline.getRevisionWidth());
      } else if (newPos < 0) {
        var revisionsToScroll = (0 - newPos) / (Gustavus.Revisions.Timeline.revisionsToScrollThrough * Gustavus.Revisions.Timeline.getRevisionWidth());
      } else {
        var revisionsToScroll = Gustavus.Revisions.Timeline.revisionsToScrollThrough;
      }
      if (revisionsToScroll === 1) {
        // this means that it wants to scroll over by the width of the revision * the number of revisions to scroll through
        revisionsToScroll = 0;
      }

      if (wheelScrollAmount < 0) {
        var newHoverRevisionNumber = hoverRevisionNumber + (revisionsToScroll);
      } else {
        var newHoverRevisionNumber = hoverRevisionNumber - (revisionsToScroll);
      }

      $('#revisionTimeline tr th.' + newHoverRevisionNumber).mouseenter();
      Gustavus.Revisions.slideTimeline(newPos, Gustavus.Revisions.Timeline.getPixelsHidden(), false, 1);
    }
  },

  /**
   * Figure out our slide duration based off of the distance of our slide.
   * @member Gustavus.Revisions
   * @param  {Number} newPos Position we want to move to
   * @return {Number} Duration of our slide animation
   */
  findSlideDuration: function(newPos)
  {
    // pixels needed to slide until we hit our target
    var pixelsUntilEnd = Math.abs(Gustavus.Revisions.Timeline.getLeftOffset() - newPos);
    return pixelsUntilEnd * Gustavus.Revisions.Timeline.hotspotMSPerPixel;
  },

  /**
   * Sets up the scrollable viewport for the timeline to slide arount in.
   * @member Gustavus.Revisions
   * @return {undefined}
   */
  setUpViewport: function()
  {
    var $table = $('#revisionTimeline table');

    var dimensions = {
      height: $table.height() + 'px',
      width: $('#revisionTimeline').width() - parseInt(Gustavus.Revisions.Timeline.getViewport().css('marginLeft')) + 'px'
    }
    if (Gustavus.Revisions.Timeline.isViewportNeeded()) {
      Gustavus.Revisions.Timeline.getViewport()
        .css(dimensions)
        .viewport({
          content: $table,
          position: 'right'
        })
        .viewport('content')
          .draggable({
            containment: 'parent',
            axis: 'x'
          })
          .on('drag', function(e) {
            Gustavus.Revisions.loadVisibleRevisionsIntoTimeline();
          })
          .on('mousewheel', function(e, delta, deltaX, deltaY) {
            Gustavus.Revisions.scrollOnMouseWheel(delta, deltaX, deltaY);
            return false;
          })
          .ready(function() {
            // make sure the visible revision is also visible in the timeline
            Gustavus.Revisions.showVisibleRevisionInTimeline($('#formExtras'), false);
          });
      $('#revisionTimeline')
        .find('.scrollHotspot').removeClass('disabled').end()
        .on('mouseenter', '.scrollHotspot.scrollRight', function() {
          // slide timeline right
          Gustavus.Revisions.slideTimeline(0, 1, true, Gustavus.Revisions.findSlideDuration(0));
        }).on('mouseleave', '.scrollHotspot.scrollRight', function() {
          // stop animation
          Gustavus.Revisions.Timeline.getViewport().viewport('content').stop();
        }).on('mouseenter', '.scrollHotspot.scrollLeft', function() {
          // slide timeline left
          Gustavus.Revisions.slideTimeline(Gustavus.Revisions.Timeline.getPixelsHidden(), Gustavus.Revisions.Timeline.getPixelsHidden(), true, Gustavus.Revisions.findSlideDuration(Gustavus.Revisions.Timeline.getPixelsHidden()));
        }).on('mouseleave', '.scrollHotspot.scrollLeft', function() {
          // stop animation
          Gustavus.Revisions.Timeline.getViewport().viewport('content').stop();
        });
    }
  }
}

// bind our history statechange event
window.History.Adapter.bind(window, 'statechange', function(e, i) {
  var State = window.History.getState();

  Gustavus.Revisions.makeAjaxRequest(State.data, State.url);
});

$('#revisionsForm').on('click', 'thead th, tbody td', function() {
  Gustavus.Revisions.handleClickAction($(this), true);
  return false;
}).on('click', 'button', function() {
  if ($('#revisionsForm').attr('method') === 'GET') {
    // we only want to do an ajax request if the form method is get.
    Gustavus.Revisions.handleClickAction($(this), true);
    return false;
  }
}).on('click', 'input.compare', function() {
  // disable compare button if 2 checkboxes aren't checked
  if ($('input.compare:checked').length === 2) {
    Gustavus.Revisions.enableCompareButton();
  } else if ($('input.compare:checked').length > 2) {
    Gustavus.Revisions.unselectClosestBox($(this));
    Gustavus.Revisions.enableCompareButton();
  } else {
    $('#compareButton').attr('disabled', 'disabled');
  }

  if (!$(this).parents('td').hasClass('old') && !$(this).parents('td').hasClass('young')) {
    if ($(this).is(':checked')) {
      $('.' + $(this).val()).addClass('selected');
    } else {
      Gustavus.Revisions.unselectBox($(this));
    }
  }
}).on('mouseenter', '#revisionTimeline th, #revisionTimeline td', function() {
  // Highlight the hovered column
  $('#revisionTimeline .hover').removeClass('hover');
  $('#revisionTimeline tr :nth-child(' + ($(this).prevAll().length + 1) + ')').addClass('hover');
}).on('mouseleave', '#revisionTimeline table', function() {
  // remove hovered column class
  $('th.hover, td.hover').removeClass('hover');
});

/* Set up viewport */
Gustavus.Revisions.setUpViewport();

$(document).ready(function() {
  $('#compareButton').attr('disabled', 'disabled');
  Gustavus.Revisions.loadVisibleRevisionsIntoTimeline();
});