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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'espeasyTCalmant')) {
	echo __('Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (espeasy)', __FILE__);
	die();
}

// Read parameters from the URL query string
$espUnitId = init('unitId');
$espUnitName = init('name');
$espClientIp = init('ip');
$espUnitIp = init('unitIp');
$taskid = init('task');
$valueName = init('valueName');
$value = init('value');

// Generate a logical ID based on the Unit name and its ID
$logicalId = $espUnitName . "-" . $espUnitId;
log::add('espeasyTCalmant', 'debug', 'Using logical ID: ' . $logicalId);

// Make sure we have a valid IP address
if(empty($espUnitIp)) {
	$ip = $espClientIp;
} else {
	$ip = $espUnitIp;
}

$elogic = espeasyTCalmant::byLogicalId($logicalId, 'espeasyTCalmant');
if (!is_object($elogic)) {
	// Unknown ESP
	if (config::byKey('include_mode','espeasyTCalmant') != 1) {
		// Not in inclusion mode: reject the new equipment
		return false;
	}

	// Store the new ESP
	$elogic = new espeasyTCalmant();
	$elogic->setEqType_name('espeasyTCalmant');
	$elogic->setLogicalId($logicalId);
	$elogic->setName($espUnitName . " (" . $espUnitId . ")");
	$elogic->setIsEnable(true);
	$elogic->setConfiguration('ip', $ip);
	$elogic->setConfiguration('device', $logicalId);
	$res = $elogic->save();
	log::add('espeasyTCalmant', 'info', 'New device stored: ' . $logicalId);
	event::add('espeasyTCalmant::includeDevice', array('state' => 1));
} else {
	// Update IP if it changed
	log::add('espeasyTCalmant', 'debug', 'Known device: ' . $logicalId);

	if ($ip != $elogic->getConfiguration('ip')) {
		$elogic->setConfiguration('ip', $ip);
		$elogic->save();
		log::add('espeasyTCalmant', 'info', 'Updated IP of ' . $logicalId . ' to ' . $ip);
	}
}

// Make sure we manage correctly different values with the same name
// but from different tasks
$cmdId = $taskid . "-" . $valueName;

// Look for the associated ESP task
$cmdlogic = espeasyTCalmantCmd::byEqLogicIdAndLogicalId($elogic->getId(), $cmdId);
if (!is_object($cmdlogic)) {
	// Task is known
	log::add('espeasyTCalmant', 'info', 'Registering new command ' . $cmdId);
	$cmdlogic = new espeasyTCalmantCmd();
	$cmdlogic->setLogicalId($cmdId);
	$cmdlogic->setName($valueName);
	$cmdlogic->setType('info');
	$cmdlogic->setSubType('numeric');
	$cmdlogic->setEqLogic_id($elogic->getId());
	$cmdlogic->setConfiguration('taskid', $taskid);
	$cmdlogic->setConfiguration('cmd', $valueName);
}
$cmdlogic->setConfiguration('value', $value);
$cmdlogic->event($value);
$cmdlogic->save();

return true;
?>
