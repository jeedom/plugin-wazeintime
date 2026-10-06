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

	public function migrate() {
		for ($i = 1; $i <= 3; $i++) {
			$name = $this->getCmd(null, "routename{$i}");
			if (is_object($name)) {
				$name->setLogicalId("name{$i}");
				$name->save(true);
			}
			$duration = $this->getCmd(null, "time{$i}");
			if (is_object($duration)) {
				$duration->setLogicalId("duration{$i}");
				$duration->save(true);
			}
			$returnName = $this->getCmd(null, "routeretname{$i}");
			if (is_object($returnName)) {
				$returnName->setLogicalId("return_name{$i}");
				$returnName->save(true);
			}
			$returnDuration = $this->getCmd(null, "timeret{$i}");
			if (is_object($returnDuration)) {
				$returnDuration->setLogicalId("return_duration{$i}");
				$returnDuration->save(true);
			}
		}
	}

	public function postSave() {
		$this->migrate();

		$commandTypes = [
			'name' => [
				'subType' => 'string',
				'unit' => '',
				'names' => [
					'' => __('Trajet %s', __FILE__),
					'return_' => __('Trajet retour %s', __FILE__)
				]
			],
			'duration' => [
				'subType' => 'numeric',
				'unit' => 'min',
				'names' => [
					'' => __('Durée %s', __FILE__),
					'return_' => __('Durée retour %s', __FILE__)
				]
			],
			'distance' => [
				'subType' => 'numeric',
				'unit' => 'km',
				'names' => [
					'' => __('Distance %s', __FILE__),
					'return_' => __('Distance retour %s', __FILE__)
				]
			]
		];
		$directions = ['', 'return_'];
		foreach ($directions as $prefix) {
			for ($i = 1; $i <= 3; $i++) {
				foreach ($commandTypes as $type => $definition) {
					$logicalId = "{$prefix}{$type}{$i}";
					$command = $this->getCmd(null, $logicalId);
					if (!is_object($command)) {
						$command = new wazeintimeCmd();
					}
					$command->setLogicalId($logicalId);
					$command->setIsVisible(1);
					$command->setName(sprintf($definition['names'][$prefix], $i));
					$command->setType('info');
					$command->setSubType($definition['subType']);
					if ($definition['unit'] !== '') {
						$command->setUnite($definition['unit']);
					}
					$command->setEqLogic_id($this->getId());
					$command->save();
				}
			}
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
