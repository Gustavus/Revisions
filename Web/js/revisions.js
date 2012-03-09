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
  unselectBox: function($item)
  {
    $item.removeAttr('checked');
    $('.' + $item.val()).removeClass('selected');
  },
  selectBox: function($item)
  {
    $item.attr('checked', 'checked');
  },
  unselectBoxes: function($item)
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
  replaceSectionsWithData: function($data)
  {
    $('#revisionsForm').attr('method', $data.attr('method'));

    $data.children().each(function(i, element) {
      $('#' + $(element).attr('id')).html($(element).html());

      if ($(element).attr('id') === 'formExtras') {
        restoreButtons = $(element).find('footer button')
        if (restoreButtons.length === 1) {
          $('.young').removeClass('young');
          $('.old').removeClass('old');
          $('input.compare:checked').each(function(i, element) {
            revisions.unselectBox($(element));
          })
          $('.' + restoreButtons.first().val()).addClass('young');
          //selectBox($('#revisionNum-' + restoreButtons.first().val()));
        } else if (restoreButtons.length === 2) {
          $('.old').removeClass('old');
          $('.' + restoreButtons.first().val()).addClass('old').removeClass('selected');
          revisions.selectBox($('#revisionNum-' + restoreButtons.first().val()));
          $('.young').removeClass('young');
          $('.' + restoreButtons.last().val()).addClass('young').removeClass('selected');
          revisions.selectBox($('#revisionNum-' + restoreButtons.last().val()));
        }
      }
      if ($(element).find('#revisionTimeline')) {
        Extend.apply('page', $('#revisionTimeline'));
        $('#compareButton').attr('disabled', 'disabled');
      }
    });
  },
  makeDataObject: function($element)
  {
    var data = {};
    // get hidden fields
    $('#hiddenFields input').each(function(i, element) {
      data[$(element).attr('name')] = $(element).val();
    });
    // get button's info
    data[$element.attr('name')] = $element.val();
    if ($element.attr('id') === 'compareButton') {
      //get checkbox data
      if ($('input.compare:checked').length > 1) {
        data['revisionNumbers'] = Array();
        $('input.compare:checked').each(function(i, element) {
          data['revisionNumbers'][i] = $(element).val();
        })
      }
    } else if (!$element.hasClass('revision')) {
      if ($('input.compare:checked').length > 1) {
        data['revisionNumbers'] = Array();
        $('#formExtras footer button').each(function(i, element) {
          data['revisionNumbers'][i] = $(element).val();
        })
      }
    }
    return data;
  },
  makeAjaxRequest: function(url, data)
  {
    var data = {};
    data['barebones'] = true;
    data['oldestRevisionInTimeline'] = $('tfoot td label input').first().val();
    data['visibleRevisions'] = revisions.makeVisibleRevisionsArray();

    if (url !== '') {
      $.ajax({
        url: url,
        type: 'GET',
        data: data,
        success: function(ajaxData) {
          revisions.replaceSectionsWithData($(ajaxData));
        }
      })
    }
  },
  makeHistory: function($element)
  {
    var data = revisions.makeDataObject($element);
    var url = (revisions.applicationQueryString === '') ? '?' : revisions.applicationQueryString + '&';
    url += $.param(data);
    if (window.History.enabled) {
      window.History.pushState(data, null, url);
    } else {
      // history isn't enabled, so the statechange event wont get called
      revisions.makeAjaxRequest(url, data);
    }
  },
  handleClickAction: function($element)
  {
    // call make history to throw the new url to the stack and trigger the statechange event that calls revisions.makeAjaxRequest
    revisions.makeHistory($element);
  },
  makeApplicationQueryString: function()
  {
    var queryStringArray = window.location.search.slice(1).split('&');
    for (var i = 0; i < queryStringArray.length; ++i) {
      if (revisions.revisionsArgs.indexOf(queryStringArray[i].slice(0, queryStringArray[i].indexOf('='))) === -1) {
        if (revisions.applicationQueryString === '') {
          revisions.applicationQueryString = '?' + queryStringArray[i];
        } else {
          revisions.applicationQueryString += '&' + queryStringArray[i];
        }
      }
    }
  }
}

window.History.Adapter.bind(window, 'statechange', function() {
  var State = window.History.getState();
  revisions.makeAjaxRequest(State.url, State.data);
});

$('#revisionsForm').on('click', 'button', function() {
  if ($('#revisionsForm').attr('method') === 'GET') {
    revisions.handleClickAction($(this));
    return false;
  }
});

$('#revisionsForm').on('click', 'input.compare', function() {
  // disable compare button if 2 checkboxes aren't checked
  if ($('input.compare:checked').length === 2) {
    revisions.enableCompareButton();
  } else if ($('input.compare:checked').length > 2) {
    revisions.unselectBoxes($(this));
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
});

$('#revisionsForm').on('mouseenter', '#revisionTimeline table',
  function() {
    $('#revisionTimeline table').on('mouseenter', 'th, td',
      function() {
        if ($(this).attr('class')) {
          var revNum = $(this).attr('class').match(/[\d]+/);
          $('th.hover:not(' + revNum + '), td.hover:not(' + revNum + ')').removeClass('hover');
          $('.' + revNum).addClass('hover');
        } else {
          $('th.hover, td.hover').removeClass('hover');
        }
      }
    );
  }
).on('mouseleave', '#revisionTimeline table',
  function() {
    $('th.hover, td.hover').removeClass('hover');
  }
);

$(document).ready(function() {
  $('#compareButton').attr('disabled', 'disabled');
  revisions.makeApplicationQueryString();
});