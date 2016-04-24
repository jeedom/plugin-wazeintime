<?php

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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class wazeintime extends eqLogic {
    /*     * *************************Attributs****************************** */
    
	public static $_widgetPossibility = array('custom' => true);
    
	public static function cron30($_eqlogic_id = null) {
		if($_eqlogic_id !== null){
			$eqLogics = array(eqLogic::byId($_eqlogic_id));
			$sleep = 0;
		}else{
			$eqLogics = eqLogic::byType('wazeintime');
			$sleep = rand(0,120);
		}
        foreach ($eqLogics as $wazeintime) {
			sleep($sleep);
			if ($wazeintime->getIsEnable() == 1) {
				log::add('wazeintime', 'debug', 'Pull Cron pour Waze in time');
				if ('none' == ($wazeintime->getConfiguration('geolocstart', ''))) {
					$latdepart=$wazeintime->getConfiguration('latdepart');
					$londepart=$wazeintime->getConfiguration('londepart');
				} else {
					$geoloc = $wazeintime->getConfiguration('geolocstart', '');
					$typeId = explode('|',$geoloc);
					if ($typeId[0] == 'ios') {
						$geolocCmd = geoloc_iosCmd::byId($typeId[1]);
					} else  {
						$geolocCmd = geolocCmd::byId($typeId[1]);
					}
					$geoloctab = explode(',', $geolocCmd->execCmd(null, 0));
					if (isset($geoloctab[0]) && isset($geoloctab[1])) {
						$latdepart = $geoloctab[0];
						$londepart = $geoloctab[1];
					} else {
						log::add('wazeintime', 'debug', 'Position de départ invalide');
						continue;
					}
				}
				if ('none' == ($wazeintime->getConfiguration('geolocend', ''))) {
					$latarrive=$wazeintime->getConfiguration('latarrive');
					$lonarrive=$wazeintime->getConfiguration('lonarrive');
				} else {
					$geoloc = $wazeintime->getConfiguration('geolocend', '');
					$typeId = explode('|',$geoloc);
					if ($typeId[0] == 'ios') {
						$geolocCmd = geoloc_iosCmd::byId($typeId[1]);
					} else  {
						$geolocCmd = geolocCmd::byId($typeId[1]);
					}
					$geoloctab = explode(',', $geolocCmd->execCmd(null, 0));
					if (isset($geoloctab[0]) && isset($geoloctab[1])) {
						$latarrive = $geoloctab[0];
						$lonarrive = $geoloctab[1];
					} else {
						log::add('wazeintime', 'debug', 'Position d\'arrivée invalide');
						continue;
					}
				}
				$route1retTotalTimeMin = 'old';
				$route2retTotalTimeMin = 'old';
				$route3retTotalTimeMin = 'old';
				$route1retName = 'old';
				$route2retName = 'old';
				$route3retName = 'old'; 
				$route1TotalTimeMin = 'old';
				$route2TotalTimeMin = 'old';
				$route3TotalTimeMin = 'old';
				$route1Name = 'old';
				$route2Name = 'old';
				$route3Name = 'old';
                if ($wazeintime->getConfiguration('NOA')){
                    $row='';
                } else {
                    $row='row-';
                }
                $wazeRouteurl = "https://www.waze.com/".$row."RoutingManager/routingRequest?from=x%3A$londepart+y%3A$latdepart&to=x%3A$lonarrive+y%3A$latarrive&at=0&returnJSON=true&returnGeometries=true&returnInstructions=true&timeout=60000&nPaths=3&options=AVOID_TRAILS%3At";
                log::add('wazeintime', 'debug', $wazeRouteurl);
				$wazeRoutereturl = "https://www.waze.com/".$row."RoutingManager/routingRequest?from=x%3A$lonarrive+y%3A$latarrive&to=x%3A$londepart+y%3A$latdepart&at=0&returnJSON=true&returnGeometries=true&returnInstructions=true&timeout=60000&nPaths=3&options=AVOID_TRAILS%3At";
                $opts = array(
                    'http'=>array(
                    'method'=>"GET",
                    'header'=>"Accept-language: en\r\n" .
                    "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0"
                    )
                );
                $context = stream_context_create($opts);
				$routeResponseText = @file_get_contents($wazeRouteurl, false, $context);
                if ($routeResponseText === FALSE) {
                     log::add('wazeintime', 'debug', 'Difficulté à contacter le serveur');
                } else {
                    $routeResponseJson = json_decode($routeResponseText,true);
                    $route1Name = (isset($routeResponseJson['alternatives'][0]['response']['routeName'])) ? $routeResponseJson['alternatives'][0]['response']['routeName'] : "NA";
                    $route2Name = (isset($routeResponseJson['alternatives'][1]['response']['routeName'])) ? $routeResponseJson['alternatives'][1]['response']['routeName'] : "NA";
                    $route3Name = (isset($routeResponseJson['alternatives'][2]['response']['routeName'])) ? $routeResponseJson['alternatives'][2]['response']['routeName'] : "NA";
					$route1 = (isset($routeResponseJson['alternatives'][0]['response']['results'])) ? $routeResponseJson['alternatives'][0]['response']['results'] : 0;
                    $route2 = (isset($routeResponseJson['alternatives'][1]['response']['results'])) ? $routeResponseJson['alternatives'][1]['response']['results'] : 0;
                    $route3 = (isset($routeResponseJson['alternatives'][2]['response']['results'])) ? $routeResponseJson['alternatives'][2]['response']['results'] : 0;
					$route1TotalTimeSec = 0;
					if ($route1 != 0) {
                    foreach ($route1 as $street){
                        $route1TotalTimeSec += $street['crossTime'];
                    }
					}
                    $route2TotalTimeSec = 0;
					if ($route2 != 0) {
                    foreach ($route2 as $street){
                        $route2TotalTimeSec += $street['crossTime'];
                    }
					}
                    $route3TotalTimeSec = 0;
                    if ($route3 != 0) {
					foreach ($route3 as $street){
                        $route3TotalTimeSec += $street['crossTime'];
                    }
					}
                    $route1TotalTimeMin = round($route1TotalTimeSec/60);
                    $route2TotalTimeMin = round($route2TotalTimeSec/60);
                    $route3TotalTimeMin = round($route3TotalTimeSec/60);
                }
                $routeretResponseText = @file_get_contents($wazeRoutereturl, false, $context);
                if ($routeretResponseText === FALSE) {
                     log::add('wazeintime', 'debug', 'Difficulté à contacter le serveur');
                } else {
                    $routeretResponseJson = json_decode($routeretResponseText,true);
                    $route1retName = (isset($routeretResponseJson['alternatives'][0]['response']['routeName'])) ? $routeretResponseJson['alternatives'][0]['response']['routeName'] : "NA";
                    $route2retName = (isset($routeretResponseJson['alternatives'][1]['response']['routeName'])) ? $routeretResponseJson['alternatives'][1]['response']['routeName'] : "NA";
                    $route3retName = (isset($routeretResponseJson['alternatives'][2]['response']['routeName'])) ? $routeretResponseJson['alternatives'][2]['response']['routeName'] : "NA";
                    $routeret1 = (isset($routeretResponseJson['alternatives'][0]['response']['results'])) ? $routeretResponseJson['alternatives'][0]['response']['results'] : 0;
                    $routeret2 = (isset($routeretResponseJson['alternatives'][1]['response']['results'])) ? $routeretResponseJson['alternatives'][1]['response']['results'] : 0;
                    $routeret3 = (isset($routeretResponseJson['alternatives'][2]['response']['results'])) ? $routeretResponseJson['alternatives'][2]['response']['results'] : 0;
                    $route1retTotalTimeSec = 0;
					if ($routeret1 != 0) {
                    foreach ($routeret1 as $street){
                        $route1retTotalTimeSec += $street['crossTime'];
                    }
					}
                    $route2retTotalTimeSec = 0;
					if ($routeret2 != 0) {
                    foreach ($routeret2 as $street){
                        $route2retTotalTimeSec += $street['crossTime'];
                    }
					}
                    $route3retTotalTimeSec = 0;
					if ($routeret3 != 0) {
                    foreach ($routeret3 as $street){
                        $route3retTotalTimeSec += $street['crossTime'];
                    }
					}
                    $route1retTotalTimeMin = round($route1retTotalTimeSec/60);
                    $route2retTotalTimeMin = round($route2retTotalTimeSec/60);
                    $route3retTotalTimeMin = round($route3retTotalTimeSec/60);
                }
				foreach ($wazeintime->getCmd('info') as $cmd) {
					switch ($cmd->getName()) {
						case 'Durée 1':
							$value=$route1TotalTimeMin;
						break;
						case 'Durée 2':
							$value=$route2TotalTimeMin; break;
						case 'Durée 3':
							$value=$route3TotalTimeMin; break;
						case 'Trajet 1':
							$value=$route1Name; break;
						case 'Trajet 2':
							$value=$route2Name; break;
						case 'Trajet 3':
							$value=$route3Name; break;
                        case 'Dernier refresh':
                            if ($route1TotalTimeMin == 'old') {
								$value='old';break;
							} else {
								$value=date('H:i'); break;
							}
                        case 'Durée retour 1':
							$value=$route1retTotalTimeMin;
						break;
						case 'Durée retour 2':
							$value=$route2retTotalTimeMin; break;
						case 'Durée retour 3':
							$value=$route3retTotalTimeMin; break;
						case 'Trajet retour 1':
							$value=$route1retName; break;
						case 'Trajet retour 2':
							$value=$route2retName; break;
						case 'Trajet retour 3':
							$value=$route3retName; break;
                        case 'Dernier refresh retour':
                            if ($route1retTotalTimeMin == 'old') {
								$value='old';break;
							} else {
								$value=date('H:i'); break;
							}
					}
					if ($value != 'old'){
						$cmd->event($value);
						log::add('wazeintime','debug','set:'.$cmd->getName().' to '. $value);
					}
				}
				$wazeintime->refreshWidget();
			}
		}
	}
    
    public function preUpdate() {
       if (($this->getConfiguration('latdepart') == '' || !is_numeric($this->getConfiguration('latdepart'))) && $this->getConfiguration('geolocstart') == 'none') {
            throw new Exception(__('La latitude de départ ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if (($this->getConfiguration('latarrive') == '' || !is_numeric($this->getConfiguration('latarrive'))) && $this->getConfiguration('geolocend') == 'none') {
            throw new Exception(__('La latitude d\'arrivée ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if (($this->getConfiguration('londepart') == '' || !is_numeric($this->getConfiguration('londepart'))) && $this->getConfiguration('geolocstart') == 'none') {
            throw new Exception(__('La longitude de départ ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if (($this->getConfiguration('lonarrive') == '' || !is_numeric($this->getConfiguration('lonarrive'))) && $this->getConfiguration('geolocend') == 'none') {
            throw new Exception(__('La longitude d\'arrivée ne peut être vide et doit être un nombre valide',__FILE__));
	   }
    }
    
    public function postSave() {
		if (!$this->getId())
          return;
        
        $routename1 = $this->getCmd(null, 'routename1');
		if (!is_object($routename1)) {
			$routename1 = new wazeintimeCmd();
			$routename1->setLogicalId('routename1');
			$routename1->setIsVisible(1);
			$routename1->setName(__('Trajet 1', __FILE__));
		}
        $routename1->setType('info');
		$routename1->setSubType('string');
		$routename1->setConfiguration('onlyChangeEvent',1);
		$routename1->setEventOnly(1);
		$routename1->setEqLogic_id($this->getId());
		$routename1->save();
        
        $time1 = $this->getCmd(null, 'time1');
		if (!is_object($time1)) {
			$time1 = new wazeintimeCmd();
			$time1->setLogicalId('time1');
            $time1->setUnite('min');
			$time1->setIsVisible(1);
			$time1->setName(__('Durée 1', __FILE__));
		}
        $time1->setType('info');
		$time1->setSubType('numeric');
		$time1->setConfiguration('onlyChangeEvent',1);
		$time1->setEventOnly(1);
		$time1->setEqLogic_id($this->getId());
		$time1->save();
        
        $routename2 = $this->getCmd(null, 'routename2');
		if (!is_object($routename2)) {
			$routename2 = new wazeintimeCmd();
			$routename2->setLogicalId('routename2');
			$routename2->setIsVisible(1);
			$routename2->setName(__('Trajet 2', __FILE__));
		}
        $routename2->setType('info');
		$routename2->setSubType('string');
		$routename2->setConfiguration('onlyChangeEvent',1);
		$routename2->setEventOnly(1);
		$routename2->setEqLogic_id($this->getId());
		$routename2->save();
        
        $time2 = $this->getCmd(null, 'time2');
		if (!is_object($time2)) {
			$time2 = new wazeintimeCmd();
			$time2->setLogicalId('time2');
			$time2->setIsVisible(1);
			$time2->setName(__('Durée 2', __FILE__));
		}
        $time2->setType('info');
		$time2->setSubType('numeric');
        $time2->setUnite('min');
		$time2->setConfiguration('onlyChangeEvent',1);
		$time2->setEventOnly(1);
		$time2->setEqLogic_id($this->getId());
		$time2->save();
        
        $routename3 = $this->getCmd(null, 'routename3');
		if (!is_object($routename3)) {
			$routename3 = new wazeintimeCmd();
			$routename3->setLogicalId('routename3');
			$routename3->setIsVisible(1);
			$routename3->setName(__('Trajet 3', __FILE__));
		}
        $routename3->setType('info');
		$routename3->setSubType('string');
		$routename3->setConfiguration('onlyChangeEvent',1);
		$routename3->setEventOnly(1);
		$routename3->setEqLogic_id($this->getId());
		$routename3->save();
        
        $time3 = $this->getCmd(null, 'time3');
		if (!is_object($time3)) {
			$time3 = new wazeintimeCmd();
			$time3->setLogicalId('time3');
			$time3->setIsVisible(1);
			$time3->setName(__('Durée 3', __FILE__));
		}
        $time3->setType('info');
		$time3->setSubType('numeric');
        $time3->setUnite('min');
		$time3->setConfiguration('onlyChangeEvent',1);
		$time3->setEventOnly(1);
		$time3->setEqLogic_id($this->getId());
		$time3->save();
        
        $routeretname1 = $this->getCmd(null, 'routeretname1');
		if (!is_object($routeretname1)) {
			$routeretname1 = new wazeintimeCmd();
			$routeretname1->setLogicalId('routeretname1');
			$routeretname1->setIsVisible(1);
			$routeretname1->setName(__('Trajet retour 1', __FILE__));
		}
        $routeretname1->setType('info');
		$routeretname1->setSubType('string');
		$routeretname1->setConfiguration('onlyChangeEvent',1);
		$routeretname1->setEventOnly(1);
		$routeretname1->setEqLogic_id($this->getId());
		$routeretname1->save();
        
        $timeret1 = $this->getCmd(null, 'timeret1');
		if (!is_object($timeret1)) {
			$timeret1 = new wazeintimeCmd();
			$timeret1->setLogicalId('timeret1');
            $timeret1->setUnite('min');
			$timeret1->setIsVisible(1);
			$timeret1->setName(__('Durée retour 1', __FILE__));
		}
        $timeret1->setType('info');
		$timeret1->setSubType('numeric');
		$timeret1->setConfiguration('onlyChangeEvent',1);
		$timeret1->setEventOnly(1);
		$timeret1->setEqLogic_id($this->getId());
		$timeret1->save();
        
        $routeretname2 = $this->getCmd(null, 'routeretname2');
		if (!is_object($routeretname2)) {
			$routeretname2 = new wazeintimeCmd();
			$routeretname2->setLogicalId('routeretname2');
			$routeretname2->setIsVisible(1);
			$routeretname2->setName(__('Trajet retour 2', __FILE__));
		}
        $routeretname2->setType('info');
		$routeretname2->setSubType('string');
		$routeretname2->setConfiguration('onlyChangeEvent',1);
		$routeretname2->setEventOnly(1);
		$routeretname2->setEqLogic_id($this->getId());
		$routeretname2->save();
        
        $timeret2 = $this->getCmd(null, 'timeret2');
		if (!is_object($timeret2)) {
			$timeret2 = new wazeintimeCmd();
			$timeret2->setLogicalId('timeret2');
			$timeret2->setIsVisible(1);
			$timeret2->setName(__('Durée retour 2', __FILE__));
		}
        $timeret2->setType('info');
		$timeret2->setSubType('numeric');
        $timeret2->setUnite('min');
		$timeret2->setConfiguration('onlyChangeEvent',1);
		$timeret2->setEventOnly(1);
		$timeret2->setEqLogic_id($this->getId());
		$timeret2->save();
        
        $routeretname3 = $this->getCmd(null, 'routeretname3');
		if (!is_object($routeretname3)) {
			$routeretname3 = new wazeintimeCmd();
			$routeretname3->setLogicalId('routeretname3');
			$routeretname3->setIsVisible(1);
			$routeretname3->setName(__('Trajet retour 3', __FILE__));
		}
        $routeretname3->setType('info');
		$routeretname3->setSubType('string');
		$routeretname3->setConfiguration('onlyChangeEvent',1);
		$routeretname3->setEventOnly(1);
		$routeretname3->setEqLogic_id($this->getId());
		$routeretname3->save();
        
        $timeret3 = $this->getCmd(null, 'timeret3');
		if (!is_object($timeret3)) {
			$timeret3 = new wazeintimeCmd();
			$timeret3->setLogicalId('timeret3');
			$timeret3->setIsVisible(1);
			$timeret3->setName(__('Durée retour 3', __FILE__));
		}
        $timeret3->setType('info');
		$timeret3->setSubType('numeric');
        $timeret3->setUnite('min');
		$timeret3->setConfiguration('onlyChangeEvent',1);
		$timeret3->setEventOnly(1);
		$timeret3->setEqLogic_id($this->getId());
		$timeret3->save();
        
        $refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new wazeintimeCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();
        
        $lastrefresh = $this->getCmd(null, 'lastrefresh');
		if (!is_object($lastrefresh)) {
			$lastrefresh = new wazeintimeCmd();
			$lastrefresh->setLogicalId('lastrefresh');
			$lastrefresh->setIsVisible(1);
			$lastrefresh->setName(__('Dernier refresh', __FILE__));
		}
        $lastrefresh->setType('info');
		$lastrefresh->setSubType('string');
		$lastrefresh->setConfiguration('onlyChangeEvent',1);
		$lastrefresh->setEventOnly(1);
		$lastrefresh->setEqLogic_id($this->getId());
		$lastrefresh->save();
		
		$lastrefreshret = $this->getCmd(null, 'lastrefreshret');
		if (!is_object($lastrefreshret)) {
			$lastrefreshret = new wazeintimeCmd();
			$lastrefreshret->setLogicalId('lastrefreshret');
			$lastrefreshret->setIsVisible(1);
			$lastrefreshret->setName(__('Dernier refresh retour', __FILE__));
		}
        $lastrefreshret->setType('info');
		$lastrefreshret->setSubType('string');
		$lastrefreshret->setConfiguration('onlyChangeEvent',1);
		$lastrefreshret->setEventOnly(1);
		$lastrefreshret->setEqLogic_id($this->getId());
		$lastrefreshret->save();
        
    }
    
    public function postUpdate() {
		$this->cron30($this->getId());
	}
    
    public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
 		if (!is_array($replace)) {
 			return $replace;
  		}
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1) {
			return '';
		}
        $hide1=$this->getConfiguration('hide1');
        $hide2=$this->getConfiguration('hide2');
        $hide3=$this->getConfiguration('hide3');
		$replace['#hide1#'] = $hide1;
		$replace['#hide2#'] = $hide2;
		$replace['#hide3#'] = $hide3;
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_history#'] = '';
                $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
				$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
				$replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
				if ($cmd->getIsHistorized() == 1) {
					$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
				}
			
		}

		$refresh = $this->getCmd(null, 'refresh');
		$replace['#refresh_id#'] = $refresh->getId();

		$html = template_replace($replace, getTemplate('core', $version, 'eqlogic', 'wazeintime'));
		cache::set('widgetHtml' . $version . $this->getId(), $html, 0);
		return $html;
	}
}

class wazeintimeCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */
	
    public function execute($_options = null) {
		if ($this->getType() == '') {
			return '';
		}
		$eqLogic = $this->getEqlogic();
		$eqLogic->cron30($eqLogic->getId());
	}

    /*     * **********************Getteur Setteur*************************** */
}
?>
