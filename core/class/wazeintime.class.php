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

	public static function cron() {
		/** @var wazeintime */
		foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
			$autorefresh = $eqLogic->getConfiguration('autorefresh', '');
			$cronIsDue = false;
			if ($autorefresh == '')  continue;
			try {
				$cron = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
				$cronIsDue = $cron->isDue();
			} catch (Exception $e) {
				log::add(__CLASS__, 'error', __('Expression cron non valide: ', __FILE__) . $autorefresh);
			}

			if ($cronIsDue) {
				$eqLogic->refreshRoutes();
			}
		}
	}

	private static function getSocketPort() {
		return config::byKey('socketport', __CLASS__, 42043);;
	}

	private static function sendToDaemon(array $params) {
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] != 'ok') {
			throw new RuntimeException("Le démon n'est pas démarré");
		}
		log::add(__CLASS__, 'debug', 'params to send to daemon:' . json_encode($params));
		$params['apikey'] = jeedom::getApiKey(__CLASS__);
		$payLoad = json_encode($params);
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_connect($socket, '127.0.0.1', self::getSocketPort());
		socket_write($socket, $payLoad, strlen($payLoad));
		socket_close($socket);
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = '';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
			}
		}
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}

		$path = realpath(__DIR__ . '/../../resources');
		$cmd = system::getCmdPython3(__CLASS__) . " {$path}/wazed.py";
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
		$cmd .= ' --socketport ' . self::getSocketPort();
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/wazeintime/core/php/jeewaze.php';
		$cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__, 'localhost');
		$cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		log::add(__CLASS__, 'info', 'Lancement démon');
		$result = exec($cmd . ' >> ' . log::getPathToLog(__CLASS__ . '_daemon') . ' 2>&1 &');
		$i = 0;
		while ($i < 10) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 10) {
			log::add(__CLASS__, 'error', __('Impossible de lancer le démon', __FILE__), 'unableStartDeamon');
			return false;
		}
		message::removeAll(__CLASS__, 'unableStartDeamon');

		return true;
	}

	public static function deamon_stop() {
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		if (file_exists($pid_file)) {
			log::add(__CLASS__, 'info', 'Arrêt démon');
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		sleep(1);
		system::kill('wazed.py');
		// system::fuserk(config::byKey('socketport', __CLASS__));
	}

	public function refreshRoutes() {
		if (!$this->getIsEnable() == 1) return;

		try {
			log::add(__CLASS__, 'info', __('Refresh routes pour: ', __FILE__) . $this->getName());

			$start = $this->getPosition('start');
			$end = $this->getPosition('end');

			self::sendToDaemon([
				'id' => $this->getId(),
				'start' => "{$start['lat']},{$start['lon']}",
				'end' => "{$end['lat']},{$end['lon']}",
				'vehicle_type' => $this->getConfiguration('vehicle_type'),
				'avoid_toll_roads' => (bool)$this->getConfiguration('avoid_toll_roads', false),
				'avoid_subscription_roads' => (bool)$this->getConfiguration('avoid_subscription_roads', false),
				'avoid_ferries' => (bool)$this->getConfiguration('avoid_ferries', false)
			]);
		} catch (Exception $e) {
			log::add(__CLASS__, 'error', $e->getMessage());
		}
	}

	public function updateInfo(array $alternatives) {
		log::add(__CLASS__, 'debug', 'Updating info with data: ' . json_encode($alternatives));
		foreach ($alternatives as $id => $data) {
			foreach ($data as $key => $value) {
				$this->checkAndUpdateCmd($key, $value);
			}
		}
		$this->checkAndUpdateCmd('lastrefresh', date('H:i'));
		$this->refreshWidget();
	}

	private function getPosition($_point = 'start'): array {
		$return = array();
		$point = ($_point == 'start') ? 'depart' : 'arrive';
		if ($this->getConfiguration('geoloc' . $_point, '') == 'none') {
			$return['lat'] = $this->getConfiguration('lat' . $point);
			$return['lon'] = $this->getConfiguration('lon' . $point);
		} else {
			$geoloc = $this->getConfiguration('geoloc' . $_point, '');
			$typeId = explode('|', $geoloc);
			if ($typeId[0] == 'jeedom') {
				if ((config::byKey('info::latitude') != '') && (config::byKey('info::longitude') != '')) {
					$return['lat'] = config::byKey('info::latitude');
					$return['lon'] = config::byKey('info::longitude');
				} else {
					throw new Exception(__('Configuration Jeedom invalide', __FILE__));
				}
			} elseif ($typeId[0] == 'cmd') {
				log::add(__CLASS__, 'debug', 'geoloc:' . 'cmdGeoLoc' . $_point);
				$localisation = $this->extractLocalisation($this->getConfiguration('cmdGeoLoc' . $_point));
				if ($localisation === false) {
					throw new Exception(__('La commande donnant la localisation ne contient pas une localisation valide (latitude,longitude)', __FILE__));
				}
				$return['lat'] = $localisation[0];
				$return['lon'] = $localisation[1];
			} else {
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
					throw new Exception(__('Position invalide', __FILE__));
				}
			}
		}
		return $return;
	}

	public function preInsert() {
		$this->setConfiguration('hide1', 0);
		$this->setConfiguration('hide2', 0);
		$this->setConfiguration('hide3', 0);
		$this->setConfiguration('autorefresh', '*/30 * * * *');
	}

	private function extractLocalisation(string $cmdId) {
		$cmdId = trim(str_replace('#', '', $cmdId));
		if ($cmdId == '') return false;

		$cmd = cmd::byId($cmdId);
		if (!is_object($cmd)) return false;

		$value = $cmd->execCmd();
		log::add(__CLASS__, 'debug', "localisation?: {$value}");
		$localisation = explode(',', $value, 3);
		if ($localisation === false || count($localisation) < 2) {
			return false;
		}
		return $localisation;
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

		if ($this->getConfiguration('geolocstart') == 'cmd') {
			if ($this->getConfiguration('cmdGeoLocstart') == '') {
				throw new Exception(__('Vous devez sélectionner une commande donnant la localisation de départ', __FILE__));
			}
			$localisation = $this->extractLocalisation($this->getConfiguration('cmdGeoLocstart'));
			if ($localisation === false) {
				throw new Exception(__('La commande donnant la localisation de départ ne contient pas une localisation valide (latitude,longitude)', __FILE__));
			}
		}
		if ($this->getConfiguration('geolocend') == 'cmd') {
			if ($this->getConfiguration('cmdGeoLocend') == '') {
				throw new Exception(__('Vous devez sélectionner une commande donnant la localisation d\'arrivée', __FILE__));
			}
			$localisation = $this->extractLocalisation($this->getConfiguration('cmdGeoLocend'));
			if ($localisation === false) {
				throw new Exception(__('La commande donnant la localisation de départ ne contient pas une localisation valide (latitude,longitude)', __FILE__));
			}
		}
	}

	public function postSave() {
		$routename1 = $this->getCmd(null, 'routename1');
		if (!is_object($routename1)) {
			$routename1 = new wazeintimeCmd();
			$routename1->setLogicalId('routename1');
			$routename1->setIsVisible(1);
			$routename1->setName(__('Trajet 1', __FILE__));
			$routename1->setType('info');
			$routename1->setSubType('string');
			$routename1->setEqLogic_id($this->getId());
			$routename1->save();
		}

		$time1 = $this->getCmd(null, 'time1');
		if (!is_object($time1)) {
			$time1 = new wazeintimeCmd();
			$time1->setLogicalId('time1');
			$time1->setUnite('min');
			$time1->setIsVisible(1);
			$time1->setName(__('Durée 1', __FILE__));
			$time1->setType('info');
			$time1->setSubType('numeric');
			$time1->setEqLogic_id($this->getId());
			$time1->save();
		}

		$routename2 = $this->getCmd(null, 'routename2');
		if (!is_object($routename2)) {
			$routename2 = new wazeintimeCmd();
			$routename2->setLogicalId('routename2');
			$routename2->setIsVisible(1);
			$routename2->setName(__('Trajet 2', __FILE__));
			$routename2->setType('info');
			$routename2->setSubType('string');
			$routename2->setEqLogic_id($this->getId());
			$routename2->save();
		}

		$time2 = $this->getCmd(null, 'time2');
		if (!is_object($time2)) {
			$time2 = new wazeintimeCmd();
			$time2->setLogicalId('time2');
			$time2->setIsVisible(1);
			$time2->setName(__('Durée 2', __FILE__));
			$time2->setType('info');
			$time2->setSubType('numeric');
			$time2->setUnite('min');
			$time2->setEqLogic_id($this->getId());
			$time2->save();
		}

		$routename3 = $this->getCmd(null, 'routename3');
		if (!is_object($routename3)) {
			$routename3 = new wazeintimeCmd();
			$routename3->setLogicalId('routename3');
			$routename3->setIsVisible(1);
			$routename3->setName(__('Trajet 3', __FILE__));
			$routename3->setType('info');
			$routename3->setSubType('string');
			$routename3->setEqLogic_id($this->getId());
			$routename3->save();
		}

		$time3 = $this->getCmd(null, 'time3');
		if (!is_object($time3)) {
			$time3 = new wazeintimeCmd();
			$time3->setLogicalId('time3');
			$time3->setIsVisible(1);
			$time3->setName(__('Durée 3', __FILE__));
			$time3->setType('info');
			$time3->setSubType('numeric');
			$time3->setUnite('min');
			$time3->setEqLogic_id($this->getId());
			$time3->save();
		}

		$routeretname1 = $this->getCmd(null, 'routeretname1');
		if (!is_object($routeretname1)) {
			$routeretname1 = new wazeintimeCmd();
			$routeretname1->setLogicalId('routeretname1');
			$routeretname1->setIsVisible(1);
			$routeretname1->setName(__('Trajet retour 1', __FILE__));
			$routeretname1->setType('info');
			$routeretname1->setSubType('string');
			$routeretname1->setEqLogic_id($this->getId());
			$routeretname1->save();
		}

		$timeret1 = $this->getCmd(null, 'timeret1');
		if (!is_object($timeret1)) {
			$timeret1 = new wazeintimeCmd();
			$timeret1->setLogicalId('timeret1');
			$timeret1->setUnite('min');
			$timeret1->setIsVisible(1);
			$timeret1->setName(__('Durée retour 1', __FILE__));
			$timeret1->setType('info');
			$timeret1->setSubType('numeric');
			$timeret1->setEqLogic_id($this->getId());
			$timeret1->save();
		}

		$routeretname2 = $this->getCmd(null, 'routeretname2');
		if (!is_object($routeretname2)) {
			$routeretname2 = new wazeintimeCmd();
			$routeretname2->setLogicalId('routeretname2');
			$routeretname2->setIsVisible(1);
			$routeretname2->setName(__('Trajet retour 2', __FILE__));
			$routeretname2->setType('info');
			$routeretname2->setSubType('string');
			$routeretname2->setEqLogic_id($this->getId());
			$routeretname2->save();
		}

		$timeret2 = $this->getCmd(null, 'timeret2');
		if (!is_object($timeret2)) {
			$timeret2 = new wazeintimeCmd();
			$timeret2->setLogicalId('timeret2');
			$timeret2->setIsVisible(1);
			$timeret2->setName(__('Durée retour 2', __FILE__));
			$timeret2->setType('info');
			$timeret2->setSubType('numeric');
			$timeret2->setUnite('min');
			$timeret2->setEqLogic_id($this->getId());
			$timeret2->save();
		}

		$routeretname3 = $this->getCmd(null, 'routeretname3');
		if (!is_object($routeretname3)) {
			$routeretname3 = new wazeintimeCmd();
			$routeretname3->setLogicalId('routeretname3');
			$routeretname3->setIsVisible(1);
			$routeretname3->setName(__('Trajet retour 3', __FILE__));
			$routeretname3->setType('info');
			$routeretname3->setSubType('string');
			$routeretname3->setEqLogic_id($this->getId());
			$routeretname3->save();
		}

		$timeret3 = $this->getCmd(null, 'timeret3');
		if (!is_object($timeret3)) {
			$timeret3 = new wazeintimeCmd();
			$timeret3->setLogicalId('timeret3');
			$timeret3->setIsVisible(1);
			$timeret3->setName(__('Durée retour 3', __FILE__));
			$timeret3->setType('info');
			$timeret3->setSubType('numeric');
			$timeret3->setUnite('min');
			$timeret3->setEqLogic_id($this->getId());
			$timeret3->save();
		}


		$lastrefresh = $this->getCmd(null, 'lastrefresh');
		if (!is_object($lastrefresh)) {
			$lastrefresh = new wazeintimeCmd();
			$lastrefresh->setLogicalId('lastrefresh');
			$lastrefresh->setIsVisible(1);
			$lastrefresh->setName(__('Dernier refresh', __FILE__));
			$lastrefresh->setType('info');
			$lastrefresh->setSubType('string');
			$lastrefresh->setEqLogic_id($this->getId());
			$lastrefresh->save();
		}

		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new wazeintimeCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setName(__('Rafraichir', __FILE__));
			$refresh->setType('action');
			$refresh->setSubType('other');
			$refresh->setEqLogic_id($this->getId());
			$refresh->save();
		}

		$this->refreshRoutes();
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
	public function execute($_options = null) {
		if ($this->getLogicalId() == 'refresh') {
			/** @var wazeintime */
			$eqlogic = $this->getEqLogic();
			$eqlogic->refreshRoutes();
		}
	}
}
