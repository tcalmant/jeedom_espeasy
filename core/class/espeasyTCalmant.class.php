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


class espeasyTCalmant extends eqLogic {

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
    $return['log'] = 'espeasyTCalmant_daemon';
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
    log::add('espeasyTCalmant', 'info', 'Lancement du démon espeasyTCalmant');

    $url = network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/espeasyTCalmant/core/api/jeeEspeasy.php?apikey=' . jeedom::getApiKey('espeasyTCalmant');

    $log = log::getLogLevel('espeasyTCalmant');
    $sensor_path = realpath(dirname(__FILE__) . '/../../resources');

    $cmd = 'nice -n 19 python3 ' . $sensor_path . '/espeasy_daemon.py';
    $cmd = $cmd . ' --jeedom ' . $url;
    $cmd = $cmd . ' --address ' . config::byKey('espeasyIpAddr', 'espeasyTCalmant');

    log::add('espeasyTCalmant', 'debug', 'Lancement démon espeasyTCalmant : ' . $cmd);

    $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('espeasyTCalmant_daemon') . ' 2>&1 &');
    if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
      log::add('espeasyTCalmant', 'error', $result);
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
      log::add('espeasyTCalmant', 'error', 'Impossible de lancer le démon espeasyTCalmant, vérifiez le port', 'unableStartDeamon');
      return false;
    }
    message::removeAll('espeasyTCalmant', 'unableStartDeamon');
    log::add('espeasyTCalmant', 'info', 'Démon espeasyTCalmant lancé');
    return true;
  }

  /**
   * Stopping daemon
   */
  public static function deamon_stop() {
    exec('kill $(ps aux | grep "/espeasy_daemon.py" | awk \'{print $2}\')');
    log::add('espeasyTCalmant', 'info', 'Arrêt du service espeasyTCalmant');
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
}

class espeasyTCalmantCmd extends cmd {
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

      espeasyTCalmant::sendCommand(
      $eqLogic->getConfiguration('ip') ,
      $request );

      return $request;
    }
    return true;
  }

  public function preSave() {
    if ($this->getType() == "action") {
      $eqLogic = $this->getEqLogic();
      log::add('espeasyTCalmant','info','http://' . $eqLogic->getConfiguration('ip') . '/control?cmd=' . $this->getConfiguration('request'));
      $this->setConfiguration('value', 'http://' . $eqLogic->getConfiguration('ip') . '/control?cmd=' . $this->getConfiguration('request'));
      //$this->save();
    }
  }
}
