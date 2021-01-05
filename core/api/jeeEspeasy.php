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

if (!jeedom::apiAccess(init('apikey'), 'espeasy')) {
	http_response_code(403);
	echo __('Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (espeasy)', __FILE__);
 	die();
}


$sourceIp = init('ip');
$unitName = init('name');
$unitId = init('unitId', "0");
$taskid = init('taskid');
$cmd = init('valuename');
$value = init('value');

// Compute a logical ID
$logicalId = $unitName . "-" . $unitId;

$elogic = espeasy::byLogicalId($logicalId, 'espeasy');
if (!is_object($elogic)) {
	if (config::byKey('include_mode','espeasy') != 1) {
		return false;
	}

	$elogic = new espeasy();
	$elogic->setEqType_name('espeasy');
	$elogic->setLogicalId($logicalId);
	$elogic->setObject_id($unitId);
	$elogic->setName($unitName);
	$elogic->setIsEnable(true);
	$elogic->setConfiguration('logicalId', $logicalId);
	$elogic->setConfiguration('ip', $sourceIp);
	$elogic->setConfiguration('device', $unitName);
	$elogic->save();

	event::add('espeasy::includeDevice',
		array(
			'state' => 1
		)
	);
}

$cmdlogic = espeasyCmd::byEqLogicIdAndLogicalId($elogic->getId(), $cmd);
if (!is_object($cmdlogic)) {
	$cmdlogic = new espeasyCmd();
	$cmdlogic->setLogicalId($cmd);
	$cmdlogic->setName($cmd);
	$cmdlogic->setType('info');
	$cmdlogic->setSubType('numeric');
	$cmdlogic->setEqLogic_id($elogic->getId());
	$cmdlogic->setConfiguration('taskid',$taskid);
	$cmdlogic->setConfiguration('cmd',$cmd);
}
$cmdlogic->setConfiguration('value',$value);
$cmdlogic->event($value);
$cmdlogic->save();

return true;
?>
