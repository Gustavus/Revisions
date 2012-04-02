var revisions = {

  applicationQueryString: '',

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

  oldData: {},

  oldestRevisionNumber: null,

  unselectBox: function($item)
  {
    $item.removeAttr('checked');
    $('#revisionTimeline .' + $item.val()).removeClass('selected');
  },

  selectBox: function($item)
  {
    $item.attr('checked', 'checked');
  },

  unselectClosestBox: function($item)
  {
    var $checkedBoxes = $('input.compare:checked:not(#' + $item.attr('id') + ')');
    var firstVal      = parseInt($checkedBoxes.first().val());
    var lastVal       = parseInt($checkedBoxes.last().val());
    var itemVal       = parseInt($item.val());
    if (firstVal > itemVal) {
      revisions.unselectBox($checkedBoxes.first());
    } else if (lastVal < itemVal) {
      revisions.unselectBox($checkedBoxes.last());
      } else {
      var firstDistance = itemVal - firstVal;
      var lastDistance  = lastVal - itemVal;
      var middle        = $('input.compare').length / 2;
      if (firstDistance < lastDistance) {
        revisions.unselectBox($checkedBoxes.first());
      } else if (lastDistance < firstDistance) {
        revisions.unselectBox($checkedBoxes.last());
      } else if (itemVal < middle) {
        revisions.unselectBox($checkedBoxes.first());
      } else {
        revisions.unselectBox($checkedBoxes.last());
      }
    }
  },

  enableCompareButton: function()
  {
    if ($('td.old input.compare:checked, td.young input.compare:checked').length !== 2) {
        $('#compareButton').removeAttr('disabled');
    } else {
      $('#compareButton').attr('disabled', 'disabled');
    }
  },

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
        }, 250, function() {
          $sections
            .removeClass('slide-' + direction)
            .not(':last-child').remove().end() // Remove all but the last section
            .children().unwrap();
          $selector.unwrap();
        }
      );
  },

  replaceSectionsWithData: function($data, direction)
  {
    $('#revisionsForm').attr('method', $data.attr('method'));

    $data.children().each(function(i, element) {
      switch ($(element).attr('id')) {
        case 'revisionTimeline':
          // make sure the oldest revisionNumber pulled in is less than the oldestRevisionNumber we have asked for incase the ajax call takes time.
          if (revisions.oldestRevisionNumber === null || $data.find('#hiddenFields #oldestRevisionNumber').val() <= revisions.oldestRevisionNumber) {
            if ($(element).html() !== '' && $('#revisionTimeline').html() !== '') {
              $('#revisionTimeline table').html($(element).find('table').html(), direction);
              Extend.apply('page', $('#revisionTimeline'));
            } else {
              // completely replace timeline
              $('#revisionTimeline').html($(element).html());
              if ($(element).html() !== '') {
                // timeline is being replaced. Set up viewport to drag and scroll
                Extend.add('page', revisions.setUpViewport());
              }
            }
          }
            break;

        case 'formExtras':
          revisions.animateAndReplaceData($('#formExtras'), $(element).html(), direction);
          restoreButtons = $(element).find('footer button');
          // unselect all boxes
          $('input.compare:checked').each(function(i, element) {
            revisions.unselectBox($(element));
          })
          if (restoreButtons.length === 1) {
            // revisionData
            $('.young').removeClass('young');
            $('.old').removeClass('old');
            $('.' + restoreButtons.first().val()).addClass('young');
            revisions.selectBox($('#revisionNum-' + restoreButtons.first().val()));
          } else if (restoreButtons.length === 2) {
            // revisionData comparison
            $('.old').removeClass('old');
            $('.' + restoreButtons.first().val()).addClass('old').removeClass('selected');
            revisions.selectBox($('#revisionNum-' + restoreButtons.first().val()));
            $('.young').removeClass('young');
            $('.' + restoreButtons.last().val()).addClass('young').removeClass('selected');
            revisions.selectBox($('#revisionNum-' + restoreButtons.last().val()));
          } else {
            // revisionData was removed from the page
            $('.young').removeClass('young');
            $('.old').removeClass('old');
          }
            break;

          default:
            $('#' + $(element).attr('id')).html($(element).html(), direction);
      }

      $('#compareButton').attr('disabled', 'disabled');
    });
  },

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
        if ($('input.compare:checked').length > 1) {
          data['revisionNumbers'] = Array();
          $('input.compare:checked').each(function(i, element) {
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

  getSlideDirection: function(revData)
  {
    if (revisions.oldData.revisionNumber !== undefined && revData.revisionNumber !== undefined) {
      // figure out old revisionNumber
      if (revisions.oldData.revisionNumber === "false") {
        // set oldRevNum to be the average of the revisionNumbers you are comparing
        if (revisions.oldData.revisionNumbers !== undefined && revisions.oldData.revisionNumbers.length > 1) {
          var oldRevNum = (parseInt(revisions.oldData.revisionNumbers[0]) + parseInt(revisions.oldData.revisionNumbers[1])) / 2;
        } else {
          var oldRevNum = 0;
        }
      } else {
        var oldRevNum = revisions.oldData.revisionNumber;
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
    revisions.oldData = revData;
    return direction
  },

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
    data['visibleRevisions'] = revisions.makeVisibleRevisionsArray();

    if (url !== '') {
      $.ajax({
        url: url,
        type: 'GET',
        data: data,
        success: function(ajaxData) {
          revisions.replaceSectionsWithData($(ajaxData), revisions.getSlideDirection(revData));
          // Track this event in Google Analytics
          window._gaq = window._gaq || [];
          _gaq.push(['_trackPageview', url.replace(/^https?:\/\/[^\/]+/, '')]);
        }
      });
    }
  },

  makeHistory: function($element, shouldMakeHistory)
  {
    var revData = revisions.makeDataObject($element);
    var url = '?' + $.param(revData);
    if (window.History.enabled && shouldMakeHistory) {
      window.History.pushState(revData, null, url);
    } else {
      // history isn't enabled, so the statechange event wont get called
      revisions.makeAjaxRequest(revData, url);
    }
  },

  handleClickAction: function($element, shouldMakeHistory)
  {
    // call make history to throw the new url to the stack and trigger the statechange event that calls revisions.makeAjaxRequest
    revisions.makeHistory($element, shouldMakeHistory);
  },

  triggerShowMoreClick: function(oldestRevNumToPull)
  {
    if ($('.compare input:first-child').val() > oldestRevNumToPull) {
      revisions.oldestRevisionNumber = oldestRevNumToPull;
      // trigger click on button to pull more revisions
      // don't push to history stack
      revisions.handleClickAction($('td.bytes.' + oldestRevNumToPull).first(), false);
    }
  },

  convertNegativeAndZero: function(number)
  {
    if (number <= 0) {
      return 1;
    } else {
      return number;
    }
  },

  loadVisibleRevisionsIntoTimeline: function()
  {
    if ($('.compare input:first-child').val() > 1) {
      var $lastRevisionTh   = $('.viewport table thead tr th:last-child');
      var revisionWidth     = $lastRevisionTh.outerWidth();
      var latestRevisionNum = $lastRevisionTh.data('revision-number');
      var $viewport         = $('#revisionTimeline .viewport');
      var visibleTableWidth = $viewport.outerWidth();
      var offset            = $viewport.viewport('content').position().left;

      var numberOfRevisionsVisible  = Math.floor((visibleTableWidth + offset) / revisionWidth);
      // +6 to pull in an extra 6 so we cache the scrolling a little
      var oldestRevNumToPull        = revisions.convertNegativeAndZero(latestRevisionNum - (numberOfRevisionsVisible + 6));
      if (revisions.oldestRevisionNumber === null) {
        revisions.triggerShowMoreClick(oldestRevNumToPull);
      } else {
        // -3 so we only pull in groups of three or more to avoid a lot of requests
        var oldestRevNumAllowedToPull = revisions.convertNegativeAndZero(revisions.oldestRevisionNumber - 3);
        if (revisions.oldestRevisionNumber > 1 && oldestRevNumAllowedToPull >= oldestRevNumToPull) {
          revisions.triggerShowMoreClick(oldestRevNumToPull);
        }
      }
    }
  },

  setUpViewport: function()
  {
    var $table = $('#revisionTimeline table');

    var dimensions = {
      height: $table.height() + 'px',
      width: $('#revisionTimeline').width() - parseInt($('#revisionTimeline .viewport').css('marginLeft')) + 'px'
    }

    if ($('#revisionTimeline .viewport table').outerWidth() > $('#revisionTimeline').width()) {
      $('#revisionTimeline .viewport')
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
          .scraggable({
            containment: 'parent'
          })
          .on('drag', function() {
            revisions.loadVisibleRevisionsIntoTimeline();
          });
    }
  }
}

window.History.Adapter.bind(window, 'statechange', function(e, i) {
  var State = window.History.getState();

  revisions.makeAjaxRequest(State.data, State.url);
});

$('#revisionsForm').on('click', 'thead th, tbody td', function() {
  revisions.handleClickAction($(this), true);
  return false;
}).on('click', 'button', function() {
  if ($('#revisionsForm').attr('method') === 'GET') {
    revisions.handleClickAction($(this), true);
    return false;
  }
}).on('click', 'input.compare', function() {
  // disable compare button if 2 checkboxes aren't checked
  if ($('input.compare:checked').length === 2) {
    revisions.enableCompareButton();
  } else if ($('input.compare:checked').length > 2) {
    revisions.unselectClosestBox($(this));
    revisions.enableCompareButton();
  } else {
    $('#compareButton').attr('disabled', 'disabled');
  }

  if (!$(this).parents('td').hasClass('old') && !$(this).parents('td').hasClass('young')) {
    if ($(this).is(':checked')) {
      $('.' + $(this).val()).addClass('selected');
    } else {
      revisions.unselectBox($(this));
    }
  }
}).on('mouseenter', '#revisionTimeline th, #revisionTimeline td', function() {
  // Highlight the hovered column
  $('#revisionTimeline .hover').removeClass('hover');
  $('#revisionTimeline tr :nth-child(' + ($(this).prevAll().length + 1) + ')').addClass('hover');
}).on('mouseleave', '#revisionTimeline table', function() {
  $('th.hover, td.hover').removeClass('hover');
});

/* Set up viewport */
revisions.setUpViewport()

$(document).ready(function() {
  $('#compareButton').attr('disabled', 'disabled');
  revisions.loadVisibleRevisionsIntoTimeline();
});