<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('wazeintime');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>
<div class="row row-overflow">
 <div class="col-lg-12 eqLogicThumbnailDisplay">
	 <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br/>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br/>
				<span>{{Configuration}}</span>
			</div>
		</div>	 
  <legend><i class="fa fa-car"></i>  {{Mes Trajets}}</legend>
  <div class="eqLogicThumbnailContainer">
  <?php
	foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br/>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
?>
</div>
</div>

<div class="col-lg-12 eqLogic" style="display: none;">
 	<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
 <ul class="nav nav-tabs" role="tablist">
  <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
  <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
  <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
</ul>
<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
  <div role="tabpanel" class="tab-pane active" id="eqlogictab">
    <br/>
    <form class="form-horizontal">
      <fieldset>
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
foreach (jeeObject::all() as $object) {
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
    <div class="form-group">
      <label class="col-lg-1 control-label"><i class="icon maison-house109"></i> {{Départ}}</label>
      <label class="col-lg-1 control-label">{{Géolocalisation}}</label>
      <div class="col-lg-2">
        <select class="form-control eqLogicAttr configuration geolocstart" id="geoloc" data-l1key="configuration" data-l2key="geolocstart">
          <option value="jeedom">Configuration Jeedom</option>
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
      <option value="jeedom">Configuration Jeedom</option>
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
  <br/>
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
