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
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class noip extends eqLogic {

    
	/* * *************************Attributs****************************** */

	/* * ***********************Methode static*************************** */

	public static function pull() {
		foreach (self::byType('noip') as $eqLogic) {
			$eqLogic->scan(1);
		}
	}

	public static function nameExists($name) {
			$allNoIp=eqLogic::byType('noip');
			foreach($allNoIp as $u) {
				if($name == $u->getName()) return true;
			}
			return false;
	}

	public static function createDomain($domain, $login) {
		$eqLogicClient = new noip();
		$defaultRoom = intval(config::byKey('defaultParentObject','noip','',true));
		$name = '';
        if(self::nameExists($domain->hostname)) {
            $name = $domain->hostname.'_'.time();
			log::add('noip', 'debug', "Nom en double ".$domain->hostname." renommé en ".$name);
		} else {
            $name = $domain->hostname;
        }
		log::add('noip', 'info', "Domaine créé : ".$name);
		$eqLogicClient->setName($name);
		$eqLogicClient->setIsEnable(1);
		$eqLogicClient->setIsVisible(1);
		$eqLogicClient->setLogicalId($name);
		$eqLogicClient->setEqType_name('noip');
		if($defaultRoom) $eqLogicClient->setObject_id($defaultRoom);
		$eqLogicClient->setConfiguration('type', 'domain');
		$eqLogicClient->setConfiguration('login', $login);
		$eqLogicClient->setConfiguration('image',$eqLogicClient->getImage());
		$eqLogicClient->save();
	}

	public static function syncNoIp() {
		log::add('noip', 'info', "syncNoIp");

        $eqLogics = eqLogic::byType('noip');
        foreach ($eqLogics as $eqLogic) {
            if($eqLogic->getConfiguration('type','') != 'account' || $eqLogic->getIsEnable() != 1) {
                continue;
            }
            $obj = $eqLogic->executeNoIpScript($eqLogic->getConfiguration('login'), $eqLogic->getConfiguration('password'), 0);

            if (isset($obj->message)) {
                log::add(__CLASS__, 'error', $eqLogic->getHumanName() . ' users/'.$eqLogic->getConfiguration('login').'/repos:' . $obj->message);
            } 
            else {
                foreach ($obj as $domain) {
                    $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
                    if (!is_object($existingDomain)) {
                        // new domain
                        noip::createDomain($domain, $eqLogic->getConfiguration('login'));
                        $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
                        event::add('jeedom::alert', array(
                            'level' => 'warning',
                            'page' => 'noip',
                            'message' => __('Domaine inclus avec succès : ' .$existingDomain->hostname, __FILE__),
                        ));
                    }
                }
                $eqLogic->recordData($obj);
            }
        }
	}

	public static function removeAllDomains($login) {
		$eqLogics = eqLogic::byType('noip');
		foreach ($eqLogics as $eqLogic) {
			if($eqLogic->getConfiguration('type') == 'domain' && $eqLogic->getConfiguration('login') == $login) {
				$eqLogic->remove();
			}
		}
	}

	public function preUpdate()
	{
	}

	public function preSave()
	{
	}

	public function postSave() {
		if ($this->getConfiguration('type','') == 'domain') {
            if ( $this->getIsEnable() ) {
                $cmd = $this->getCmd(null, 'hostname');
                if ( ! is_object($cmd)) {
                    $cmd = new noipCmd();
                    $cmd->setName('Hostname');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('hostname');
                    $cmd->setType('info');
                    $cmd->setSubType('string');
                    $cmd->setGeneric_type('GENERIC_INFO');
                    $cmd->setIsVisible(1);
                    $cmd->save();
                }
                $cmd = $this->getCmd(null, 'expiration');
                if ( ! is_object($cmd)) {
                    $cmd = new noipCmd();
                    $cmd->setName('Days before expiration');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('expiration');
                    $cmd->setType('info');
                    $cmd->setSubType('numeric');
                    $cmd->setIsHistorized(0);
                    $cmd->setTemplate('dashboard','tile');
                    $cmd->setTemplate('mobile','tile');
                    $cmd->setIsVisible(1);
                    $cmd->save();
                }
            }
		}
	}
    
    public function preInsert() {
      if ($this->getConfiguration('type','') == 'account') {
          $this->setDisplay('height','75px');
      } else {
          $this->setDisplay('height','225px');
      }
        
      $this->setDisplay('width', '280px');
      $this->setIsEnable(1);
      $this->setIsVisible(1);
    }        

	public function preRemove() {
		if ($this->getConfiguration('type') == "account") {
			self::removeAllDomains($this->getConfiguration('login'));
		}
	}

	public function getImage() {
		if($this->getConfiguration('type') == 'domain'){
			return 'plugins/noip/core/assets/repo_icon.png';
		}
		return 'plugins/noip/plugin_info/noip_icon.png';
	}

	public function scan($renew) {
		if ( $this->getIsEnable() && $this->getConfiguration('type') == 'account') {
            $this->refreshInfo($renew);
		}
	}
    
    public function executeNoIpScript($login, $password, $renew) {
        $noip_path = dirname(__FILE__) . '/../..';
        $debug = 0;
        if ()
        $cmd = 'sudo python3 ' . $noip_path . '/resources/noip-renew.py "' . $login . '" "' . $password . '" "' . config::byKey('renewThreshold','noip',7) . '" "' . $renew . '" "2"';
		
		log::add(__CLASS__, 'info', 'Lancement script No-Ip : ' . $cmd);
		exec($cmd . ' >> ' . log::getPathToLog('noip') . ' 2>&1'); 
        $string = file_get_contents($noip_path . '/data/output.json');
        if ($string === false) {
            // deal with error...
        }
        $json_a = json_decode($string, true);
        if ($json_a === null) {
            // deal with error...
        }
        return $json_a;
    }

	public function refreshInfo($renew) {
		$obj = $this->executeNoIpScript($this->getConfiguration('login'), $this->getConfiguration('password'), $renew);
        $this->recordData($obj);
	}
    
    public function recordData($obj) {
        if (isset($obj->message)) {
            log::add(__CLASS__, 'error', $this->getHumanName() . ' users/'.$this->getConfiguration('login').'/repos:' . $obj->message);
        } 
        else {
            foreach ($obj as $domain) {
                $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
                if (!is_object($existingDomain)) {
                    // new domain
                    noip::createDomain($domain, $this->getConfiguration('login'));
                    $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
                }
                if (is_object($existingDomain)) {
                    if ($existingDomain->getIsEnable()) {
                        $existingDomain->checkAndUpdateCmd('hostname', $domain->hostname);
                        $existingDomain->checkAndUpdateCmd('expiration', $repo->expirationdays);
                    }
                }
            }
        }
    }
}

class noipCmd extends cmd
{
	/*	   * *************************Attributs****************************** */


	/*	   * ***********************Methode static*************************** */


	/*	   * *********************Methode d'instance************************* */

	/*	   * **********************Getteur Setteur*************************** */
	public function execute($_options = null) {
	}
}
?>
