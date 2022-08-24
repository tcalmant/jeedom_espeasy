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


class espeasy_tcalmant extends eqLogic {

  /**
   * Sends a command to an ESP Easy
   *
   * @param string $ip Address of the ESP Easy
   * @param any $value Command value
   */
  public static function sendCommand( $ip, $value ) {
    $url = 'http://' . $ip . '/control?cmd=' . $value;
    $retour = file_get_contents($url);
  }

  /**
   * Checks the status of the daemon
   */
  public static function deamon_info() {
    $return = array();
    $return['log'] = 'espeasy_tcalmant_daemon';
    $return['state'] = 'nok';
    $pid = trim( shell_exec ('ps ax | grep "espeasy_daemon.py" | grep -v "grep" | wc -l') );
    if ($pid != '' && $pid != '0') {
      $return['state'] = 'ok';
    }
    $return['launchable'] = 'ok';
    return $return;
  }

  /**
   * Starts the daemon
   */
  public static function deamon_start() {
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    log::add('espeasy_tcalmant', 'info', 'Lancement du démon espeasy_tcalmant');

    $url = network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/espeasy_tcalmant/core/api/jeeEspeasy.php?apikey=' . jeedom::getApiKey('espeasy_tcalmant');

    $log = log::getLogLevel('espeasy_tcalmant');
    $sensor_path = realpath(dirname(__FILE__) . '/../../resources');

    $cmd = 'nice -n 19 python3 ' . $sensor_path . '/espeasy_daemon.py --jeedom ' . $url;

    log::add('espeasy_tcalmant', 'debug', 'Lancement démon espeasy_tcalmant : ' . $cmd);

    $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('espeasy_tcalmant_daemon') . ' 2>&1 &');
    if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
      log::add('espeasy_tcalmant', 'error', $result);
      return false;
    }

    $i = 0;
    while ($i < 30) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] == 'ok') {
        break;
      }
      sleep(1);
      $i++;
    }
    if ($i >= 30) {
      log::add('espeasy_tcalmant', 'error', 'Impossible de lancer le démon espeasy_tcalmant, vérifiez le port', 'unableStartDeamon');
      return false;
    }
    message::removeAll('espeasy_tcalmant', 'unableStartDeamon');
    log::add('espeasy_tcalmant', 'info', 'Démon espeasy_tcalmant lancé');
    return true;
  }

  /**
   * Stopping daemon
   */
  public static function deamon_stop() {
    exec('kill $(ps aux | grep "/espeasy_daemon.py" | awk \'{print $2}\')');
    log::add('espeasy_tcalmant', 'info', 'Arrêt du service espeasy_tcalmant');
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      sleep(1);
      exec('kill -9 $(ps aux | grep "/espeasy_daemon.py" | awk \'{print $2}\')');
    }
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      sleep(1);
      exec('sudo kill -9 $(ps aux | grep "/espeasy_daemon.py" | awk \'{print $2}\')');
    }
  }

  public function preUpdate() {
    if ($this->getConfiguration('ip') == '') {
      throw new Exception(__('L\'adresse ne peut etre vide',__FILE__));
    }
  }

  public function preSave() {
    $this->setLogicalId($this->getConfiguration('ip'));
  }
}

class espeasy_tcalmantCmd extends cmd {
  public function execute($_options = null) {
    switch ($this->getType()) {
      case 'info' :
      return $this->getConfiguration('value');
      break;
      case 'action' :
      $request = $this->getConfiguration('request');
      switch ($this->getSubType()) {
        case 'slider':
        $request = str_replace('#slider#', $_options['slider'], $request);
        break;
        case 'color':
        $request = str_replace('#color#', $_options['color'], $request);
        break;
        case 'message':
        if ($_options != null)  {
          $replace = array('#title#', '#message#');
          $replaceBy = array($_options['title'], $_options['message']);
          if ( $_options['title'] == '') {
            throw new Exception(__('Le sujet ne peuvent être vide', __FILE__));
          }
          $request = str_replace($replace, $replaceBy, $request);
        }
        else
        $request = 1;
        break;
        default : $request == null ?  1 : $request;
      }

      $eqLogic = $this->getEqLogic();

      espeasy_tcalmant::sendCommand(
      $eqLogic->getConfiguration('ip') ,
      $request );

      return $request;
    }
    return true;
  }

  public function preSave() {
    if ($this->getType() == "action") {
      $eqLogic = $this->getEqLogic();
      log::add('espeasy_tcalmant','info','http://' . $eqLogic->getConfiguration('ip') . '/control?cmd=' . $this->getConfiguration('request'));
      $this->setConfiguration('value', 'http://' . $eqLogic->getConfiguration('ip') . '/control?cmd=' . $this->getConfiguration('request'));
      //$this->save();
    }
  }
}
