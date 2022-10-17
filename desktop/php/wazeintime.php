<?php
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('wazeintime');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>
<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i>
        <br />
        <span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br />
        <span>{{Configuration}}</span>
      </div>
    </div>
    <legend><i class="fa fa-car"></i> {{Mes Trajets}}</legend>
    <?php
    if (count($eqLogics) == 0) {
      echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
    } else {
      echo '<div class="input-group" style="margin:5px;">';
      echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
      echo '<div class="input-group-btn">';
      echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
      echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
      echo '</div>';
      echo '</div>';
      echo '<div class="eqLogicThumbnailContainer">';
      foreach ($eqLogics as $eqLogic) {
        $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
        echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
        echo '<img src="' . $plugin->getPathImgIcon() . '">';
        echo '<br>';
        echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
        echo '<span class="hiddenAsCard displayTableRight hidden">';
        echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
        echo '</span>';
        echo '</div>';
      }
      echo '</div>';
    }
    ?>
  </div>

  <div class="col-xs-12 eqLogic" style="display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
      <span class="input-group-btn">
        <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
        </a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
        </a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
        </a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i><span class="hidden-xs"> {{Supprimer}}</span>
        </a>
      </span>
    </div>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <form class="form-horizontal">
          <fieldset>
            <div class="col-lg-7">
              <legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                <div class="col-sm-7">
                  <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                  <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Objet parent}}</label>
                <div class="col-sm-7">
                  <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                    <option value="">{{Aucun}}</option>
                    <?php
                    $options = '';
                    foreach ((jeeObject::buildTree(null, false)) as $object) {
                      $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                    }
                    echo $options;
                    ?>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Catégorie}}</label>
                <div class="col-sm-7">
                  <?php
                  foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                    echo '<label class="checkbox-inline">';
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                    echo '</label>';
                  }
                  ?>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Options}}</label>
                <div class="col-sm-7">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
                </div>
              </div>
              <br />
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Auto-actualisation (cron)}}</label>
                <div class="col-sm-3">
                  <div class="input-group">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" placeholder="" />
                    <span class="input-group-btn">
                      <a class="btn btn-default cursor jeeHelper" data-helper="cron">
                        <i class="fas fa-question-circle"></i>
                      </a>
                    </span>
                  </div>
                </div>
              </div>
              <legend><i class="fas fa-cogs"></i> {{Paramètres du trajet}}</legend>
              <div class="form-group">
                <label class="col-sm-3 control-label"><i class="icon maison-house109"></i> {{Départ}}</label>
                <div class="col-sm-3">
                  <select class="form-control eqLogicAttr configuration geolocstart" id="geoloc" data-l1key="configuration" data-l2key="geolocstart">
                    <option value="none">{{Manuel}}</option>
                    <option value="cmd">{{Commande}}</option>
                    <?php
                    if ((config::byKey('info::latitude') != '') && (config::byKey('info::longitude') != '')) {
                      echo '<option value="jeedom|">Configuration Jeedom</option>';
                    }
                    foreach (eqLogic::byType('geoloc', true) as $geoloc) {
                      foreach (geolocCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
                        if ($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') {
                          echo '<option value="normal|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
                        }
                      }
                    }
                    foreach (eqLogic::byType('geoloc_ios', true) as $geoloc) {
                      foreach (geoloc_iosCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
                        if (($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') && $geoinfo->getName() != 'Refresh') {
                          echo '<option value="ios|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
                        }
                      }
                    }
                    ?>
                  </select>
                </div>
                <div class="manualStart">
                  <label class="col-sm-1 control-label">{{Latitude}}</label>
                  <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="latdepart" placeholder="{{48.856614}}" />
                  </div>
                  <label class="col-sm-1 control-label">{{Longitude}}</label>
                  <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="londepart" placeholder="{{2.3522219000000177}}" />
                  </div>
                </div>
                <div class="customCmdStart">
                  <label class="col-sm-2 control-label help" data-help="{{Sélectionnez une commande info retournant la localisation au format 'latitude,longitude'}}">{{Localisation}}</label>
                  <div class="col-sm-4">
                    <div class="input-group">
                      <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cmdGeoLocstart" />
                      <span class="input-group-btn">
                        <a class="btn btn-default cursor listCmdInfo" title="Rechercher une commande"><i class="fas fa-list-alt"></i></a>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label"><i class="fa fa-location-arrow"></i> {{Arrivée}}</label>
                <div class="col-sm-3">
                  <select class="form-control eqLogicAttr configuration geolocend" id="geoloc" data-l1key="configuration" data-l2key="geolocend">
                    <option value="none">{{Manuel}}</option>
                    <option value="cmd">{{Commande}}</option>
                    <?php
                    if ((config::byKey('info::latitude') != '') && (config::byKey('info::longitude') != '')) {
                      echo '<option value="jeedom|">Configuration Jeedom</option>';
                    }

                    foreach (eqLogic::byType('geoloc', true) as $geoloc) {
                      foreach (geolocCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
                        if ($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') {
                          echo '<option value="normal|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
                        }
                      }
                    }
                    foreach (eqLogic::byType('geoloc_ios', true) as $geoloc) {
                      foreach (geoloc_iosCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
                        if (($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') && $geoinfo->getName() != 'Refresh') {
                          echo '<option value="ios|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
                        }
                      }
                    }
                    ?>
                  </select>
                </div>
                <div class="manualEnd">
                  <label class="col-sm-1 control-label">{{Latitude}}</label>
                  <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="latarrive" placeholder="{{48.856614}}" />
                  </div>
                  <label class="col-sm-1 control-label">{{Longitude}}</label>
                  <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="lonarrive" placeholder="{{2.3522219000000177}}" />
                  </div>
                </div>
                <div class="customCmdEnd">
                  <label class="col-sm-2 control-label help" data-help="{{Sélectionnez une commande info retournant la localisation au format 'latitude,longitude'}}">{{Localisation}}</label>
                  <div class="col-sm-4">
                    <div class="input-group">
                      <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cmdGeoLocend" />
                      <span class="input-group-btn">
                        <a class="btn btn-default cursor listCmdInfo" title="Rechercher une commande"><i class="fas fa-list-alt"></i></a>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Amérique du Nord}}</label>
                <div class="col-sm-2">
                  <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="NOA" checked />
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label help" data-help="Indiquez les abonnements que vous voulez activer, liste d'élements séparés par une virgule (* pour tout activer)">{{Abonnements}}</label>
                <div class="col-sm-3">
                  <input type="text" class="eqLogicAttr form-control help" data-l1key="configuration" data-l2key="subscription" />
                </div>
              </div>
              <legend><i class="fa fa-wrench"></i> {{Paramètres d'affichage}}</legend>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Masquer trajet}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hide1" checked />1</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hide2" checked />2</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hide3" checked />3</label>
              </div>
            </div>

            <div class="col-lg-5">
              <legend><i class="fas fa-info"></i> {{Informations}}</legend>
              <div class="form-group">
                <div class="pull-left">
                  <a class="col-lg-12 control-label" href="http://www.coordonnees-gps.fr/" target="_blank"><i class="icon nature-planet5"></i> Cliquez-ici pour retrouver vos coordonnées</a>
                </div>
              </div>
            </div>
          </fieldset>

        </form>

      </div>
      <div role="tabpanel" class="tab-pane" id="commandtab">
        <br />
        <div class="table-responsive">
          <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
                <th style="min-width:200px;width:350px;">{{Nom}}</th>
                <th style="min-width:140px;width:200px;">{{Type}}</th>
                <th style="min-width:260px;">{{Options}}</th>
                <th>{{Etat}}</th>
                <th style="min-width:80px;width:140px;">{{Actions}}</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include_file('desktop', 'wazeintime', 'js', 'wazeintime'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>