
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
$('.eqLogicAttr[data-l1key=configuration][data-l2key=geolocend]').on('change', function () {
  $('.manualEnd').hide();
  $('.customCmdEnd').hide();
  if ($(this).value() === 'none') {
    $('.manualEnd').show();
  } else if ($(this).value() === 'cmd') {
    $('.customCmdEnd').show();
  }
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=geolocstart]').on('change', function () {
  $('.manualStart').hide();
  $('.customCmdStart').hide();
  if ($(this).value() === 'none') {
    $('.manualStart').show();
  } else if ($(this).value() === 'cmd') {
    $('.customCmdStart').show();
  }
});

$(".listCmdInfo").on('click', function () {
  var el = $(this).closest('div').find('.eqLogicAttr[data-l1key=configuration]');
  jeedom.cmd.getSelectModal({ cmd: { type: 'info' } }, function (result) {
    el.val(result.human);
  });
});

$("#table_cmd").sortable({ axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true });

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} };
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td style="width:60px;">';
  tr += '<span class="cmdAttr" data-l1key="id"></span>';
  tr += '</td>';
  tr += '<td style="min-width:300px;">';
  tr += '<div class="row">';
  tr += '<div class="col-xs-6">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
  tr += '</div>';
  tr += '</div>';
  tr += '</td>';
  tr += '<td style="min-width:80px;width:150px;">';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
  tr += '</td>';
  tr += '<td style="min-width:80px;width:200px;">';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
  tr += '</tr>';
  $('#table_cmd tbody').append(tr);
  var tr = $('#table_cmd tbody tr').last();
  jeedom.eqLogic.builSelectCmd({
    id: $('.eqLogicAttr[data-l1key=id]').value(),
    filter: { type: 'info' },
    error: function (error) {
      $('#div_alert').showAlert({ message: error.message, level: 'danger' });
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result);
      tr.setValues(_cmd, '.cmdAttr');
      jeedom.cmd.changeType(tr, init(_cmd.subType));
    }
  });
}
