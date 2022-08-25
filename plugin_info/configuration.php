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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>


<form class="form-horizontal">
  <div class="form-group">
    <fieldset>

      <div class="form-group">
        <label for="ip-addresses" class="col-lg-4 control-label">{{IP Controleur à saisir dans ESPeasy (onglet config)}} :</label>
        <div class="col-lg-4">
          <input id="ip-addresses" class="configKey form-control" type="text" list="ipaddresses" data-l1key="espeasyIpAddr" />
          <datalist id="ipaddresses">
          <?php
            // Flag indicating if the current IP configuration is in our list
            $foundCurrentIp = false;

            // Jeedom default IP
            $ip_jeedom = config::byKey('internalAddr');

            // Current IP
            $currentIpConfig = config::byKey('espeasyIpAddr', 'espeasyTCalmant');
            if(empty($currentIpConfig)) {
              $currentIpConfig = $ip_jeedom;
              $foundCurrentIp = true;
            }

            // Bind to all interfaces
            $foundCurrentIp |= $currentIpConfig == "0.0.0.0";
            $selectedFlag = ($currentIpConfig == "0.0.0.0") ? "selected" : "";
            echo '<option value="0.0.0.0" ' . $selectedFlag . '>' . __("{{Toutes interfaces}}", __FILE__) . '</option>';

            // Bind to the Jeedom configured address (default)
            $foundCurrentIp |= $currentIpConfig == $ip_jeedom;
            $selectedFlag = ($currentIpConfig == $ip_jeedom) ? "selected" : "";
            echo '<option value="' . $ip_jeedom . '" ' . $selectedFlag . '>' . __("{{IP Jeedom}}", __FILE__) . '</option>';

            // List all (other) available system addresses
            $ip_shell = shell_exec("ip addr | awk '/inet / {gsub(/\/.*/,\"\",$2); print $2}'");
            $ip_array = preg_split('/\s+/', trim($ip_shell));

            foreach($ip_array as $ip)
            {
              if(empty($ip) || $ip == $ip_jeedom) {
                // Ignore empty IP / already marked IPs
                continue;
              }

              if($ip == $currentIpConfig) {
                $selectedFlag = "selected";
                $foundCurrentIp = true;
              } else {
                $selectedFlag = "";
              }
              echo '<option value="' . $ip . '" ' . $selectedFlag . '></option>';
            }

            if(!$foundCurrentIp) {
              echo '<option value="' . $currentIpConfig . '" selected>' . __("{{IP personnalisée}}", __FILE__) . '</option>';
            }
          ?>
          </datalist>
        </div>
      </div>

      <div class="form-group">
        <label class="col-lg-4 control-label">{{Port Controleur à saisir dans ESPeasy (onglet config)}} :</label>
        <div class="col-lg-4">
          8121
        </div>
      </div>

      <div class="form-group">
        <label class="col-lg-4 control-label">{{Publish template à saisir dans ESPeasy (onglet tools, puis bouton advanced)}} :</label>
        <div class="col-lg-4">
          <code>?name=%sysname%&unitId=%unit%&unitIp=%ip%&task=%tskname%&valueName=%valname%&value=%value%</code>
        </div>
      </div>



    </fieldset>
  </form>


</div>
