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
try {
    require_once __DIR__ . "/../../../../core/php/core.inc.php";

    if (!jeedom::apiAccess(init('apikey'), 'wazeintime')) {
        echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
        die();
    }

    if (init('test') != '') {
        echo 'OK';
        log::add('wazeintime', 'debug', 'test from daemon');
        die();
    }
    $messages = json_decode(file_get_contents("php://input"), true);
    if (!is_array($messages)) {
        die();
    }
    log::add('wazeintime', 'debug', "new messages from daemon:" . json_encode($messages));
    foreach ($messages as $key => $data) {
        /** @var wazeintime */
        $waze = wazeintime::byId($key);
        if (is_object($waze)) {
            $waze->updateInfo($data);
        }
    }

    echo 'OK';
} catch (Exception $e) {
    log::add('wazeintime', 'error', displayException($e));
}
