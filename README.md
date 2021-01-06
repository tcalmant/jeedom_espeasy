# Jeedom ESP Easy

(English further down)

## Présentation (FR)

Ce dépôt est un fork du plugin
[Jeedom ESP Easy de Lunarok](https://github.com/lunarok/jeedom_espeasy),
qui ajoute la gestion des périphériques
[ESP Easy](https://www.letscontrolit.com/wiki/index.php?title=ESPEasy)
dans [Jeedom](https://www.jeedom.com/site/fr/index.html).

Les principaux changements par rapport à la version de Lunarok:
* L'identifiant logique utilisé dans Jeedom pour reconnaître un ESP est
désormais construit à partir du nom et de l'ID de l'ESP.
La version de Lunarok s'appuie uniquement sur l'adresse IP de l'ESP.
Ce changement permet de reconnaître et travailler avec des ESP d'un autre
réseau (NAT, Docker, ...).
**Attention:** les ESP ajoutés à Jeedom avec le plugin de Lunarok ne seront plus
reconnus.
* Le format de l'URL utilisée par l'ESP pour contacter Jeedom a été modifié.
**Attention:** il est nécessaire de mettre à jour les ESP existants
* Le démon du plugin accepte l'addresse IP donnée explicitement par l'ESP

### Configuration de l'ESP

**IMPORTANT:** L'URL utilisée par l'ESP est différente de celle utilisée dans
la version de Lunarok du plugin.

1. Ouvrir l'interface de configuration de l'ESP
1. Aller dans `Controllers`
1. *Editer* ou ajouter une configuration, avec les paramètres suivants:
   * Protocol: Generic HTTP
   * Locate Controller: Use IP Address
   * Controller IP: *l'IP indiquée dans la page de configuration du plugin dans Jeedom*
   * Controller port: 8121
   * Controller Publish: `?name=%sysname%&unitId=%unit%&task=%tskname%&valuename=%valname%&value=%value%`
   * Enabled: *coché*

### Cas particulier: Docker / NAT

Cette version du dépôt permet de communiquer avec les ESP pour ceux qui ont
installé Jeedom dans un conteneur Docker.

1. Trouver l'addresse IP que l'ESP doit utiliser pour contacter Jeedom
   (par exemple, `192.168.1.250`)
1. Exposer le port 8121 de Jeedom pour que l'ESP puisse y accéder.
   (`docker run -p XXXX:8121 ...`, règle de routeur, ...)
1. Configurer l'ESP comme décris précédemment, avec en plus:
   * Controller IP: l'IP trouvée en étape 1
   * Controller port: le port trouvé en étape 2
   * Controller Publish: `?name=%sysname%&unitId=%unit%&ip=%ip%&task=%tskname%&valuename=%valname%&value=%value%`
     (on a ajouté le champ `&ip=%ip%`).


## Description (EN)

This repository is a fork of
[Lunarok's Jeedom ESP Easy](https://github.com/lunarok/jeedom_espeasy),
which adds support for
[ESP Easy](https://www.letscontrolit.com/wiki/index.php?title=ESPEasy)
devices in [Jeedom](https://www.jeedom.com/site/en/index.html).

Main changes:
* Device logical ID used in Jeedom is based on the ESP unit name and ID instead
of its IP.
This allows to manage devices behind a NAT (and in Docker-based installs).
* Controller publish URL entries have been changed (**update your current devices**)
* Daemon accepts the IP explicitly given by the ESP easy, if any

### ESP Easy configuration

**IMPORTANT:** the ESP configuration string is different from the one used in
Lunarok's version.

1. Go to the ESP Easy device web interface
1. Go to the `Controllers`
1. Either *Edit* the controller previously used with Lunarok's plugin or *Add*
a new one.
1. Configure the controller as follows:
   * Protocol: Generic HTTP
   * Locate Controller: Use IP Address
   * Controller IP: *the IP shown in the plugin's configuration page in Jeedom*
   * Controller port: 8121
   * Controller Publish: `?name=%sysname%&unitId=%unit%&task=%tskname%&valuename=%valname%&value=%value%`
   * Enabled: *checked*

### Special case: Docker / NAT

This fork adds a trick for people using Docker to run Jeedom.

1. Find out the IP address the ESP device must use to contact Jeedom
   (for example, `192.168.1.250`)
1. Add a rule to expose TCP port 8121 (Jeedom-side daemon) to the ESP device
   network (`docker run -p XXXX:8121 ...`, router rule, ...)
1. Configure the ESP easy as described above, with the following changes:
   * Controller IP: the IP found in step 1
   * Controller port: the port found in step 2
   * Controller Publish: `?name=%sysname%&unitId=%unit%&ip=%ip%&task=%tskname%&valuename=%valname%&value=%value%`
     (here, we add the `&ip=%ip%` info).
