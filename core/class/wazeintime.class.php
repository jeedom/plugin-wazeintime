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
		if ($_eqlogic_id !== null) {
			$eqLogics = array(eqLogic::byId($_eqlogic_id));
		} else {
			$eqLogics = eqLogic::byType('wazeintime');
			sleep(rand(0, 120));
		}
		foreach ($eqLogics as $wazeintime) {
			if ($wazeintime->getIsEnable() == 1) {
				try {
					$start = $wazeintime->getPosition('start');
					$end = $wazeintime->getPosition('end');

					$row = ($wazeintime->getConfiguration('NOA')) ? '' : 'row-';

					$wazeRouteurl = 'https://www.waze.com/' . $row . 'RoutingManager/routingRequest?from=x%3A' . $start['lon'] . '+y%3A' . $start['lat'] . '&to=x%3A' . $end['lon'] . '+y%3A' . $end['lat'] . '&at=0&returnJSON=true&returnGeometries=true&returnInstructions=true&timeout=60000&nPaths=3&options=AVOID_TRAILS%3At';
					$request_http = new com_http($wazeRouteurl);
					$request_http->setUserAgent('User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0'.hex2bin('0A').'referer: https://www.waze.com ');
					$json = json_decode($request_http->exec(60,2), true);
					if (isset($json['error'])) {
						throw new Exception($json['error']);
					}
					$data = self::extractInfo($json);

					$wazeRoutereturl = 'https://www.waze.com/' . $row . 'RoutingManager/routingRequest?from=x%3A' . $end['lon'] . '+y%3A' . $end['lat'] . '&to=x%3A' . $start['lon'] . '+y%3A' . $start['lat'] . '&at=0&returnJSON=true&returnGeometries=true&returnInstructions=true&timeout=60000&nPaths=3&options=AVOID_TRAILS%3At';
					$request_http = new com_http($wazeRoutereturl);
					$request_http->setUserAgent('User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0'.hex2bin('0A').'referer: https://www.waze.com ');
					$json = json_decode($request_http->exec(60,2), true);
					if (isset($json['error'])) {
						throw new Exception($json['error']);
					}
					$data = array_merge($data, self::extractInfo($json, 'ret'));

					log::add('wazeintime', 'debug', 'Data : ' . print_r($data, true));

					foreach ($wazeintime->getCmd('info') as $cmd) {
						if ($cmd->getLogicalId() == 'lastrefresh') {
							$cmd->event(date('H:i'));
							continue;
						}
						if (!isset($data[$cmd->getLogicalId()])) {
							continue;
						}
						if ($cmd->formatValue($data[$cmd->getLogicalId()]) != $cmd->execCmd()) {
							$cmd->setCollectDate('');
							$cmd->event($data[$cmd->getLogicalId()]);
						}
					}
					$wazeintime->refreshWidget();
				} catch (Exception $e) {
					log::add('wazeintime', 'error', $e->getMessage());
				}
			}
		}
	}

	public static function extractInfo($_data, $_prefix = '') {
		$return = array();
		$return['route' . $_prefix . 'name1'] = (isset($_data['alternatives'][0]['response']['routeName'])) ? trim($_data['alternatives'][0]['response']['routeName']) : "NA";
		$return['route' . $_prefix . 'name2'] = (isset($_data['alternatives'][1]['response']['routeName'])) ? trim($_data['alternatives'][1]['response']['routeName']) : "NA";
		$return['route' . $_prefix . 'name3'] = (isset($_data['alternatives'][2]['response']['routeName'])) ? trim($_data['alternatives'][2]['response']['routeName']) : "NA";
		$return['time' . $_prefix . '1'] = 0;
		$return['time' . $_prefix . '2'] = 0;
		$return['time' . $_prefix . '3'] = 0;

		if (isset($_data['alternatives'][0]['response']['results'])) {
			foreach ($_data['alternatives'][0]['response']['results'] as $street) {
				$return['time' . $_prefix . '1'] += $street['crossTime'];
			}
		}

		if (isset($_data['alternatives'][1]['response']['results'])) {
			foreach ($_data['alternatives'][1]['response']['results'] as $street) {
				$return['time' . $_prefix . '2'] += $street['crossTime'];
			}
		}

		if (isset($_data['alternatives'][2]['response']['results'])) {
			foreach ($_data['alternatives'][2]['response']['results'] as $street) {
				$return['time' . $_prefix . '3'] += $street['crossTime'];
			}
		}

		$return['time' . $_prefix . '1'] = round($return['time' . $_prefix . '1'] / 60);
		$return['time' . $_prefix . '2'] = round($return['time' . $_prefix . '2'] / 60);
		$return['time' . $_prefix . '3'] = round($return['time' . $_prefix . '3'] / 60);
		return $return;
	}

	public function getPosition($_point = 'start') {
		$return = array();
		$point = ($_point == 'start') ? 'depart' : 'arrive';
		if ($this->getConfiguration('geoloc' . $_point, '') == 'none') {
			$return['lat'] = $this->getConfiguration('lat' . $point);
			$return['lon'] = $this->getConfiguration('lon' . $point);
		} else if ($this->getConfiguration('geoloc' . $_point, '') == 'jeedom') {
			$return['lat'] = config::byKey('info::latitude');
			$return['lon'] = config::byKey('info::longitude');
		} else {
			$geoloc = $this->getConfiguration('geoloc' . $_point, '');
			$typeId = explode('|', $geoloc);
			if ($typeId[0] == 'ios') {
				$geolocCmd = geoloc_iosCmd::byId($typeId[1]);
			} else {
				$geolocCmd = geolocCmd::byId($typeId[1]);
			}
			$geoloctab = explode(',', $geolocCmd->execCmd(null, 0));
			if (isset($geoloctab[0]) && isset($geoloctab[1])) {
				$return['lat'] = $geoloctab[0];
				$return['lon'] = $geoloctab[1];
			} else {
				throw new Exception(__('Position de départ invalide', __FILE__));
			}
		}
		return $return;
	}

	public function preUpdate() {
		if (($this->getConfiguration('latdepart') == '' || !is_numeric($this->getConfiguration('latdepart'))) && $this->getConfiguration('geolocstart') == 'none') {
			throw new Exception(__('La latitude de départ ne peut être vide et doit être un nombre valide', __FILE__));
		}
		if (($this->getConfiguration('latarrive') == '' || !is_numeric($this->getConfiguration('latarrive'))) && $this->getConfiguration('geolocend') == 'none') {
			throw new Exception(__('La latitude d\'arrivée ne peut être vide et doit être un nombre valide', __FILE__));
		}
		if (($this->getConfiguration('londepart') == '' || !is_numeric($this->getConfiguration('londepart'))) && $this->getConfiguration('geolocstart') == 'none') {
			throw new Exception(__('La longitude de départ ne peut être vide et doit être un nombre valide', __FILE__));
		}
		if (($this->getConfiguration('lonarrive') == '' || !is_numeric($this->getConfiguration('lonarrive'))) && $this->getConfiguration('geolocend') == 'none') {
			throw new Exception(__('La longitude d\'arrivée ne peut être vide et doit être un nombre valide', __FILE__));
		}
	}

	public function postUpdate() {
		$routename1 = $this->getCmd(null, 'routename1');
		if (!is_object($routename1)) {
			$routename1 = new wazeintimeCmd();
			$routename1->setLogicalId('routename1');
			$routename1->setIsVisible(1);
			$routename1->setName(__('Trajet 1', __FILE__));
		}
		$routename1->setType('info');
		$routename1->setSubType('string');
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
		$timeret3->setEventOnly(1);
		$timeret3->setEqLogic_id($this->getId());
		$timeret3->save();

		$lastrefresh = $this->getCmd(null, 'lastrefresh');
		if (!is_object($lastrefresh)) {
			$lastrefresh = new wazeintimeCmd();
			$lastrefresh->setLogicalId('lastrefresh');
			$lastrefresh->setIsVisible(1);
			$lastrefresh->setName(__('Dernier refresh', __FILE__));
		}
		$lastrefresh->setType('info');
		$lastrefresh->setSubType('string');
		$lastrefresh->setEventOnly(1);
		$lastrefresh->setEqLogic_id($this->getId());
		$lastrefresh->save();

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
		$hide1 = $this->getConfiguration('hide1');
		$hide2 = $this->getConfiguration('hide2');
		$hide3 = $this->getConfiguration('hide3');
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
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqlogic', 'wazeintime')));
	}
}

class wazeintimeCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = null) {
		if ($this->getLogicalId() == 'refresh') {
			wazeintime::cron30($this->getEqlogic_id());
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}
?>
