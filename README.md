# ESP Easy for Jeedom, @tcalmant's edition

This project is a fork of @lunarok's original work, which can be found here:
<https://github.com/lunarok/jeedom_espeasy>.

The main purpose of this fork is to make the plugin work with controllers from
another network, as it is the case when Jeedom is running in a Docker container
with a bridge network.

## Features

* Automatically registers ESP tasks as the daemon receives notifications while
in inclusion mode.
* Allows to send commands by created new action tasks in Jeedom.


## Changes of this fork

* The ID of this fork is different from the original one, so both can coexist
  in a Jeedom instance.

* The logical identification of an ESP in Jeedom is now based on its unit name
  and ID instead of its IP. This allows to have ESP configured with non-static
  DHCP addresses, but also to reuse the same Jeedom object when replacing a
  faulty controller.

  As a result:

  * The URL template to set in the ESP Easy controller configuration has to be
  replaced by:
  `?name=%sysname%&unitId=%unit%&unitIp=%ip%&task=%tskname%&valueName=%valname%&value=%value%`
  (see the Configuration page of the plugin in Jeedom)

  * Each ESP controller must have a different unit name and/or ID, as the pair
  is used to identify a controller in Jeedom.

  This allows to receive notifications and send commands even when being a NAT,
  for example when Jeedom is running in Docker, in a bridge network.

* The Node.js daemon has been rewritten in Python.
  Mainly because I'm more fluent in that language, but I also wanted a better
  handling of URL as such instead of treating them as strings.

* The configuration page allows to use custom daemon binding address and port.

## License

This project, as the original one, is licensed under the terms of the
GNU General Public License v3.0.
See the LICENSE file for more details.
