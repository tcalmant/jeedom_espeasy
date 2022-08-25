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

if (!jeedom::apiAccess(init('apikey'), 'espeasy_tcalmant')) {
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

/**
 * Ensures that the logical IDs we generate are SQL valid
 */
function escapeId($eid) {
	return str_replace("_", "-", $eid);
}

// Generate a logical ID based on the Unit name and its ID
$logicalId = escapeId($espUnitName . "-" . $espUnitId);
log::add('espeasy_tcalmant', 'info', 'Using logical ID: ' . $logicalId);

// Make sure we have a valid IP address
if(empty($espUnitIp)) {
	$ip = $espClientIp;
} else {
	$ip = $espUnitIp;
}

$elogic = espeasy_tcalmant::byLogicalId($logicalId, 'espeasy_tcalmant');
log::add('espeasy_tcalmant', 'info', 'Got object: ' . $elogic);
if (!is_object($elogic)) {
	// Unknown ESP
	if (config::byKey('include_mode','espeasy_tcalmant') != 1) {
		// Not in inclusion mode: reject the new equipment
		return false;
	}

	// Store the new ESP
	$elogic = new espeasy_tcalmant();
	$elogic->setEqType_name('espeasy_tcalmant');
	$elogic->setLogicalId($logicalId);
	$elogic->setName(escapeId($espUnitName) . " (" . $espUnitId . ")");
	$elogic->setIsEnable(true);
	$elogic->setConfiguration('ip', $ip);
	$elogic->setConfiguration('device', $logicalId);
	$res = $elogic->save();
	log::add('espeasy_tcalmant', 'info', 'New device stored: ' . $logicalId);
	event::add('espeasy_tcalmant::includeDevice', array('state' => 1));
} else {
	// Update IP if it changed
	log::add('espeasy_tcalmant', 'info', 'Known device: ' . $logicalId);

	if ($ip != $elogic->getConfiguration('ip')) {
		$elogic->setConfiguration('ip', $ip);
		$elogic->save();
		log::add('espeasy_tcalmant', 'info', 'Updated IP of ' . $logicalId . ' to ' . $ip);
	}
}

// Make sure we manage correctly different values with the same name
// but from different tasks
$cmdId = escapeId($taskid . "-" . $valueName);

// Look for the associated ESP task
$cmdlogic = espeasy_tcalmantCmd::byEqLogicIdAndLogicalId($elogic->getId(), $cmdId);
if (!is_object($cmdlogic)) {
	// Task is known
	$cmdlogic = new espeasy_tcalmantCmd();
	$cmdlogic->setLogicalId($cmdId);
	$cmdlogic->setName($valueName);
	$cmdlogic->setType('info');
	$cmdlogic->setSubType('numeric');
	$cmdlogic->setEqLogic_id($elogic->getId());
	$cmdlogic->setConfiguration('taskid', $taskid);
	$cmdlogic->setConfiguration('cmd', $valueName);
}
$cmdlogic->setConfiguration('value',$value);
$cmdlogic->event($value);
$cmdlogic->save();

return true;
?>
