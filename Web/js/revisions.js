function unselectBox($item)
{
  $item.removeAttr('checked');
  $('.' + $item.val()).removeClass('selected');
}

function selectBox($item)
{
  $item.attr('checked', 'checked');
}

function unselectBoxes($item)
{
  var $checkedBoxes = $('input.compare:checked:not(#' + $item.attr('id') + ')');
  var firstVal      = parseInt($checkedBoxes.first().val());
  var lastVal       = parseInt($checkedBoxes.last().val());
  var itemVal       = parseInt($item.val());
  if (firstVal > itemVal) {
    unselectBox($checkedBoxes.first());
  } else if (lastVal < itemVal) {
    unselectBox($checkedBoxes.last());
    } else {
    var firstDistance = itemVal - firstVal;
    var lastDistance  = lastVal - itemVal;
    var middle        = $('input.compare').length / 2;
    if (firstDistance < lastDistance) {
      unselectBox($checkedBoxes.first());
    } else if (lastDistance < firstDistance) {
      unselectBox($checkedBoxes.last());
    } else if (itemVal < middle) {
      unselectBox($checkedBoxes.first());
    } else {
      unselectBox($checkedBoxes.last());
    }
  }
}

function enableCompareButton()
{
  if ($('td.old input.compare:checked, td.young input.compare:checked').length !== 2) {
      $('#compareButton').removeAttr('disabled');
  } else {
    $('#compareButton').attr('disabled', 'disabled');
  }
}

function makeVisibleRevisionsArray()
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
}

function replaceSectionsWithData($data)
{
  $('#revisionsForm').attr('method', $data.attr('method'));
  $data.children().each(function(i, element) {
    $('#' + $(element).attr('id')).html($(element).html());
    console.log($(element));

    if ($(element).attr('id') === 'formExtras') {
      restoreButtons = $(element).find('footer button')
      if (restoreButtons.length === 1) {
        $('.young').removeClass('young');
        $('.old').removeClass('old');
        $('input.compare:checked').each(function(i, element) {
          unselectBox($(element));
        })
        $('.' + restoreButtons.first().val()).addClass('young');
        //selectBox($('#revisionNum-' + restoreButtons.first().val()));
      } else if (restoreButtons.length === 2) {
        $('.old').removeClass('old');
        $('.' + restoreButtons.first().val()).addClass('old').removeClass('selected');
        selectBox($('#revisionNum-' + restoreButtons.first().val()));
        $('.young').removeClass('young');
        $('.' + restoreButtons.last().val()).addClass('young').removeClass('selected');
        selectBox($('#revisionNum-' + restoreButtons.last().val()));
      }
    }
    if ($(element).find('#revisionTimeline')) {
      Extend.apply('page', $('#revisionTimeline'));
    }
  });
}

function makeDataObject($element)
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
}

function makeAjaxRequest(url, data)
{
  var data = {};
  data['barebones'] = true;
  data['oldestRevisionInTimeline'] = $('tfoot td label input').first().val();
  data['visibleRevisions'] = makeVisibleRevisionsArray();

  console.log('ajax');
  if (url !== '') {
    $.ajax({
      url: url,
      type: 'GET',
      data: data,
      success: function(ajaxData) {
        replaceSectionsWithData($(ajaxData));
      }
    })
  }
}

function makeHistory($element)
{
  var data = makeDataObject($element);
  var url = window.location.origin + window.location.pathname + '?' + $.param(data);
  if (window.History.enabled) {
    window.History.pushState(data, null, url);
  } else {
    // history isn't enabled, so the statechange event wont get called
    makeAjaxRequest(url, data);
  }
}

window.History.Adapter.bind(window, 'statechange', function() {
  console.log('statechange');
  var State = window.History.getState();
  makeAjaxRequest(State.url, State.data);
});

function handleClickAction($element)
{
  // call make history to throw the new url to the stack and trigger the statechange event that calls makeAjaxRequest
  makeHistory($element);
  if ($element.hasClass('revision')) {
    $('.young').removeClass('young');
    $('.' + $element.val()).addClass('young');
  }
}

$('#revisionsForm').on('click', 'button', function() {
  if ($('#revisionsForm').attr('method') === 'GET') {
    handleClickAction($(this));
    return false;
  }
});

$('#revisionsForm').on('click', 'input.compare', function() {
  // disable compare button if 2 checkboxes aren't checked
  if ($('input.compare:checked').length === 2) {
    enableCompareButton();
  } else if ($('input.compare:checked').length > 2) {
    unselectBoxes($(this));
    enableCompareButton();
  } else {
    $('#compareButton').attr('disabled', 'disabled');
  }
  if (!$(this).parents('td').hasClass('old') && !$(this).parents('td').hasClass('young')) {
    if ($(this).is(':checked')) {
      $('.' + $(this).val()).addClass('selected');
    } else {
      unselectBox($(this));
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
});