$('#in_searchEqlogic2').off('keyup').keyup(function () {
  var search = $(this).value().toLowerCase();
  search = search.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
  if (search == '') {
    $('.eqLogicDisplayCard.second').show();
    $('.eqLogicThumbnailContainer.second').packery();
    return;
  }
  $('.eqLogicDisplayCard.second').hide();
  $('.eqLogicDisplayCard.second .name').each(function () {
    var text = $(this).text().toLowerCase();
    text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
    if (text.indexOf(search) >= 0) {
      $(this).closest('.eqLogicDisplayCard.second').show();
    }
  });
  $('.eqLogicThumbnailContainer.second').packery();
});
$('#bt_resetEqlogicSearch2').on('click', function () {
  $('#in_searchEqlogic2').val('')
  $('#in_searchEqlogic2').keyup()
})

$('.eqLogicAttr[data-l1key=configuration][data-l2key=type]').on('change', function () {
  if ($(this).value() == 'account') {
    $('.onlyAccount').show();
    $('.onlyDomain').hide();
  } else {
    $('.onlyAccount').hide();
    $('.onlyDomain').show();
  }
});

// fonction executée par jeedom lors de l'affichage des details d'un eqlogic
function printEqLogic(_eqLogic) {
  if (!isset(_eqLogic)) {
    var _eqLogic = { configuration: {} };
  }
  if (!isset(_eqLogic.configuration)) {
    _eqLogic.configuration = {};
  }
  if (_eqLogic.configuration.type == "account") {
    $('.onlyAccount').show();
    $('.onlyDomain').hide();
  }
  if (_eqLogic.configuration.type == "domain") {
    $('.onlyAccount').hide();
    $('.onlyDomain').show();
  }
}

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} };
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="id"></span>';
  tr += '</td>';
  tr += '<td>';
  tr += '<div class="row">';
  tr += '<div class="col-sm-6">';
  tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> Icône</a>';
  tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
  tr += '</div>';
  tr += '<div class="col-sm-6">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
  tr += '</div>';
  tr += '</div>';
  tr += '</td>';
  tr += '<td>';
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '</td>';
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
  tr += '</td>';
  tr += '<td>';
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;display:inline-block;">';
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;display:inline-block;">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;display:inline-block;margin-left:2px;">';
  tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
  tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
  tr += '</td>';
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
  tr += '</td>';
  tr += '</tr>';
  $('#table_cmd tbody').append(tr);
  $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
  if (isset(_cmd.type)) {
    $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
  }
  jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

$('.eqLogicAction[data-action=discover]').on('click', function (e) {
  $('#div_alert').showAlert({ message: 'La synchronisation est en cours et peut prendre un certain temps. A suivre... ', level: 'warning' });
  $.post({// fonction permettant de faire de l'ajax
    url: "plugins/noip/core/ajax/noip.ajax.php", // url du fichier php
    data: {
      action: "syncNoIp"
    },
    dataType: 'json'
  });
});

$("#table_cmd").sortable({ axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true });

$('#bt_getScreenshot').on('click', function () {
  $('#md_modal').dialog({ title: "{{Visualisation des screenshots}}" });
  $('#md_modal').load('index.php?v=d&plugin=noip&modal=noip.screenshots').dialog('open');
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=makeIpRefresh]').on('change', function () {
  var elt = $('.eqLogicAttr[data-l1key=configuration][data-l2key=ipLinked]');
  if ($(this).is(':checked')) {
    elt.show();
  }
  else {
    elt.hide();
  }
});