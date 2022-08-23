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

if (!jeedom::apiAccess(init('apikey'), 'espeasy-tcalmant')) {
	echo __('Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (espeasy)', __FILE__);
	die();
}

$espUnitId = init('unitId');
$espUnitName = init('name');
$espClientIp = init('ip');
$espUnitIp = init('unitIp');
$taskid = init('task');
$valueName = init('valueName');
$value = init('value');

// Generate a logical ID based on the Unit name and its ID
$logicalId = $espUnitName . "-" . $espUnitId;

// Make sure we have a valid IP address
if(empty($espUnitIp)) {
	$ip = $espClientIp;
} else {
	$ip = $espUnitIp;
}

$elogic = espeasy::byLogicalId($logicalId, 'espeasy-tcalmant');
if (!is_object($elogic)) {
	if (config::byKey('include_mode','espeasy-tcalmant') != 1) {
		return false;
	}
	$elogic = new espeasy();
	$elogic->setEqType_name('espeasy-tcalmant');
	$elogic->setLogicalId($logicalId);
	$elogic->setName($espUnitName);
	$elogic->setIsEnable(true);
	$elogic->setConfiguration('ip', $ip);
	$elogic->setConfiguration('device', $logicalId);
	$elogic->save();
	event::add('espeasy::includeDevice',
	array(
		'state' => 1
	)
);
} else {
	if ($device != $elogic->getConfiguration('device')) {
		$elogic->setConfiguration('device', $logicalId);
		$elogic->save();
	}
}

$cmdlogic = espeasyCmd::byEqLogicIdAndLogicalId($elogic->getId(), $valueName);
if (!is_object($cmdlogic)) {
	$cmdlogic = new espeasyCmd();
	$cmdlogic->setLogicalId($valueName);
	$cmdlogic->setName($valueName);
	$cmdlogic->setType('info');
	$cmdlogic->setSubType('numeric');
	$cmdlogic->setEqLogic_id($elogic->getId());
	$cmdlogic->setConfiguration('taskid',$taskid);
	$cmdlogic->setConfiguration('cmd',$valueName);
}
$cmdlogic->setConfiguration('value',$value);
$cmdlogic->event($value);
$cmdlogic->save();

return true;
?>
