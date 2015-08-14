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

class wazeduration extends eqLogic {
    /*     * *************************Attributs****************************** */
    
    public static function cron30($_eqlogic_id = null) {
		if($_eqlogic_id !== null){
			$eqLogics = array(eqLogic::byId($_eqlogic_id));
		}else{
			$eqLogics = eqLogic::byType('wazeduration');
		}
        foreach ($eqLogics as $wazeduration) {
			if ($wazeduration->getIsEnable() == 1) {
				log::add('wazeduration', 'debug', 'Pull Cron pour Waze Duration');
                $latdepart=$wazeduration->getConfiguration('latdepart');
                $londepart=$wazeduration->getConfiguration('londepart');
                $latarrive=$wazeduration->getConfiguration('latarrive');
                $lonarrive=$wazeduration->getConfiguration('lonarrive');
                $wazeRouteurl = "https://www.waze.com/row-RoutingManager/routingRequest?from=x%3A$londepart+y%3A$latdepart&to=x%3A$lonarrive+y%3A$latarrive&at=0&returnJSON=true&returnGeometries=true&returnInstructions=true&timeout=60000&nPaths=3&options=AVOID_TRAILS%3At";
				$routeResponseText = file_get_contents($wazeRouteurl);
                $routeResponseJson = json_decode($routeResponseText,true);
                $route1Name = $routeResponseJson['alternatives'][0]['response']['routeName'];  
                $route2Name = $routeResponseJson['alternatives'][1]['response']['routeName'];
                $route1 = $routeResponseJson['alternatives'][0]['response']['results'];
                $route2 = $routeResponseJson['alternatives'][1]['response']['results'];
                $route1TotalTimeSec = 0;
                foreach ($route1 as $street){
                    $route1TotalTimeSec += $street['crossTime'];
                }
                $route2TotalTimeSec = 0;
                foreach ($route2 as $street){
                    $route2TotalTimeSec += $street['crossTime'];
                }
                $route1TotalTimeMin = round($route1TotalTimeSec/60);
                $route2TotalTimeMin = round($route2TotalTimeSec/60);
				foreach ($wazeduration->getCmd('info') as $cmd) {
					switch ($cmd->getName()) {
						case 'Durée 1':
							$value=$route1TotalTimeMin;
						break;
						case 'Durée 2':
							$value=$route2TotalTimeMin; break;
						case 'Trajet 1':
							$value=$route1Name; break;
						case 'Trajet 2':
							$value=$route2Name; break;
					}
					if ($value==0 ||$value != 'old'){
						$cmd->event($value);
						log::add('wazeduration','debug','set:'.$cmd->getName().' to '. $value);
					}
				}
			}
		}
	}
    
    public function preUpdate() {
       if ($this->getConfiguration('latdepart') == '' || !is_numeric($this->getConfiguration('latdepart'))) {
            throw new Exception(__('La latitude de départ ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if ($this->getConfiguration('latarrive') == '' || !is_numeric($this->getConfiguration('latarrive'))) {
            throw new Exception(__('La latitude d\'arrivée ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if ($this->getConfiguration('londepart') == '' || !is_numeric($this->getConfiguration('londepart'))) {
            throw new Exception(__('La longitude de départ ne peut être vide et doit être un nombre valide',__FILE__));
	   }
        if ($this->getConfiguration('lonarrive') == '' || !is_numeric($this->getConfiguration('lonarrive'))) {
            throw new Exception(__('La longitude d\'arrivée ne peut être vide et doit être un nombre valide',__FILE__));
	   }
    }
    
    public function postSave() {
		if (!$this->getId())
          return;
        
        $routename1 = $this->getCmd(null, 'routename1');
		if (!is_object($routename1)) {
			$routename1 = new wazedurationCmd();
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
			$time1 = new wazedurationCmd();
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
			$routename2 = new wazedurationCmd();
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
			$time2 = new wazedurationCmd();
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
        
        $refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new wazedurationCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
        $refresh->setOrder(5);
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();
    }
    
    public function postUpdate() {
		$this->cron30($this->getId());
	}
}

class wazedurationCmd extends cmd {
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