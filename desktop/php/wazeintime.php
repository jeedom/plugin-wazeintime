<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

sendVarToJS('eqType', 'wazeintime');
$eqLogics = eqLogic::byType('wazeintime');
?>
<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un trajet}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
foreach ($eqLogics as $eqLogic) {
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
           </ul>
       </div>
   </div>
   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend><i class="fa fa-car"></i>  {{Mes Trajets}}
    </legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
         <center>
            <i class="fa fa-plus-circle" style="font-size : 7em;color:#94ca02;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;;color:#94ca02"><center>Ajouter</center></span>
    </div>
    <?php
foreach ($eqLogics as $eqLogic) {
	$opacity = '';
	if ($eqLogic->getIsEnable() != 1) {
		$opacity = 'opacity:0.3;';
	}
	echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
	echo "<center>";
	echo '<img src="plugins/wazeintime/doc/images/wazeintime_icon.png" height="105" width="95" />';
	echo "</center>";
	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
	echo '</div>';
}
?>
</div>
</div>

<div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>

    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
        <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>

    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
        <div role="tabpanel" class="tab-pane active" id="eqlogictab">
            <form class="form-horizontal">
                <fieldset>
                    <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}<i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">{{Nom de l'équipement}}</label>
                        <div class="col-lg-2">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                            <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                        </div>

                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label" >{{Objet parent}}</label>
                        <div class="col-lg-2">
                            <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                <option value="">{{Aucun}}</option>
                                <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
                           </select>
                       </div>
                   </div>
                   <div class="form-group">
                    <label class="col-lg-2 control-label">{{Catégorie}}</label>
                    <div class="col-lg-9">
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
               <label class="col-lg-2 control-label"></label>
                <div class="col-sm-9">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
              </div>
          </div>
          <legend><i class="fa fa-wrench"></i>  {{Configuration}}</legend>

          <div class="form-group">
            <label class="col-lg-1 control-label"><i class="icon maison-house109"></i> {{Départ}}</label>
            <label class="col-lg-1 control-label">{{Géolocalisation}}</label>
            <div class="col-lg-2">
                <select class="form-control eqLogicAttr configuration geolocstart" id="geoloc" data-l1key="configuration" data-l2key="geolocstart">
                  <option value="none">{{Manuel}}</option>
                  <?php
foreach (eqLogic::byType('geoloc') as $geoloc) {
	foreach (geolocCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
		if ($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') {
			echo '<option value="normal|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
		}
	}
}
foreach (eqLogic::byType('geoloc_ios') as $geoloc) {
	foreach (geoloc_iosCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
		if (($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') && $geoinfo->getName() != 'Refresh') {
			echo '<option value="ios|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
		}
	}
}
?>
     </select>
 </div>
 <label class="col-lg-1 control-label hidestart">{{Latitude}}</label>
 <div class="col-lg-2">
    <input type="text" class="eqLogicAttr form-control hidestart" data-l1key="configuration" data-l2key="latdepart" placeholder="{{48.856614}}"/>
</div>
<label class="col-lg-1 control-label hidestart">{{Longitude}}</label>
<div class="col-lg-2">
    <input type="text" class="eqLogicAttr form-control hidestart" data-l1key="configuration" data-l2key="londepart" placeholder="{{2.3522219000000177}}"/>
</div>
</div>
<div class="form-group">
    <label class="col-lg-1 control-label"><i class="fa fa-location-arrow"></i> {{Arrivée}}</label>
    <label class="col-lg-1 control-label">{{Géolocalisation}}</label>
    <div class="col-lg-2">
        <select class="form-control eqLogicAttr configuration geolocend" id="geoloc" data-l1key="configuration" data-l2key="geolocend">
          <option value="none">{{Manuel}}</option>
          <?php
foreach (eqLogic::byType('geoloc') as $geoloc) {
	foreach (geolocCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
		if ($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') {
			echo '<option value="normal|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
		}
	}
}
foreach (eqLogic::byType('geoloc_ios') as $geoloc) {
	foreach (geoloc_iosCmd::byEqLogicId($geoloc->getId()) as $geoinfo) {
		if (($geoinfo->getConfiguration('mode') == 'fixe' || $geoinfo->getConfiguration('mode') == 'dynamic') && $geoinfo->getName() != 'Refresh') {
			echo '<option value="ios|' . $geoinfo->getId() . '">' . $geoinfo->getHumanName(true) . '</option>';
		}
	}
}
?>
</select>
</div>
<label class="col-lg-1 control-label hideend">{{Latitude}}</label>
<div class="col-lg-2">
    <input type="text" class="eqLogicAttr form-control hideend" data-l1key="configuration" data-l2key="latarrive" placeholder="{{48.856614}}"/>
</div>
<label class="col-lg-1 control-label hideend">{{Longitude}}</label>
<div class="col-lg-2">
    <input type="text" class="eqLogicAttr form-control hideend" data-l1key="configuration" data-l2key="lonarrive" placeholder="{{2.3522219000000177}}"/>
</div>
</div>
<div class="form-group">
 <label class="col-lg-3 control-label">{{Amérique du Nord :}}</label>
 <div class="col-lg-2">
     <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="NOA" checked/>
 </div>
</div>
<a class="col-lg-4 control-label" href="http://www.coordonnees-gps.fr/" target="_blank"><i class="icon nature-planet5"></i> Cliquez-ici pour retrouver vos coordonnées</a>
<legend><i class="fa fa-wrench"></i>  {{Affichage}}</legend>
<div class="form-group">
 <label class="col-lg-2 control-label">{{Masquer trajet :}}</label>
 <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hide1" checked/>1</label>
 <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hide2" checked/>2</label>
 <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="hide3" checked/>3</label>
</div>
</fieldset>

</form>

</div>
<div role="tabpanel" class="tab-pane" id="commandtab">
    <table id="table_cmd" class="table table-bordered table-condensed">
       <thead>
        <tr>
            <th>{{Nom}}</th><th>{{Options}}</th><th>{{Action}}</th>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>

</div>
</div>

</div>
</div>

<?php include_file('desktop', 'wazeintime', 'js', 'wazeintime');?>
<?php include_file('core', 'plugin.template', 'js');?>
