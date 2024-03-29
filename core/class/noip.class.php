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
require_once __DIR__ . '/../../3rdparty/autoload.php';
require_once __DIR__ . '/noipTools.class.php';

class noip extends eqLogic {
    use tomitomasEqLogicTrait;

    /* * *************************Attributs****************************** */

    /* * ***********************Methode static*************************** */

    /* * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom */
    public static function createIpUpdateCron() {

        $cron = cron::byClassAndFunction(__CLASS__, 'ipCheckAndUpdate');
        if (!is_object($cron)) {
            $cron = new cron();
            $cron->setClass(__CLASS__);
            $cron->setFunction('ipCheckAndUpdate');
            $cron->setEnable(1);
            $cron->setDeamon(0);
            $cron->setSchedule('*/15 * * * *');
            $cron->save();
        }
    }

    public static function ipCheckAndUpdate() {
        noipTools::makeIpUpdate();
    }

    public static function autoCheck() {
        foreach (self::byType(__CLASS__) as $eqLogic) {
            $eqLogic->scan(1);
        }

        $cron = self::createCheckCron();
        self::updateNextCron($cron);
    }

    public static function updateNextCron($cron) {

        foreach (self::byType(__CLASS__) as $eqLogic) {
            if ($eqLogic->getConfiguration('type') != 'account') continue;

            $nextRun = $cron->getNextRunDate();
            $eqLogic->checkAndUpdateCmd('nextcheck', $nextRun);
            self::debug("Prochaine vérification automatique pour " . $eqLogic->getName() . " : " . $nextRun);
        }
    }

    public static function createCheckCron() {

        $cron = cron::byClassAndFunction(__CLASS__, 'autoCheck');
        if (!is_object($cron)) {
            $cron = new cron();
            $cron->setClass(__CLASS__);
            $cron->setFunction('autoCheck');
            $cron->setEnable(1);
            $cron->setDeamon(0);
        }

        $randMinute = rand(3, 59);

        $hourStart = config::byKey('hourStart', __CLASS__, 3);
        $hourEnd = config::byKey('hourEnd', __CLASS__, 22);
        $randHour = rand($hourStart, $hourEnd);
        // self::debug('checking rand between ' . $hourStart . ' and ' . $hourEnd . ' ==> ' . $randHour);

        $cronExpr = $randMinute . ' ' . $randHour . ' * * *';
        $cron->setSchedule($cronExpr);
        $cron->save();

        return $cron;
    }

    public static function nameExists($name) {
        $allNoIp = eqLogic::byType('noip');
        /** @var eqLogic $eq */
        foreach ($allNoIp as $eq) {
            if ($name == $eq->getName()) return true;
        }
        return false;
    }

    public static function createDomain($domain, $login) {
        $eqLogicClient = new noip();
        $defaultRoom = intval(config::byKey('defaultParentObject', 'noip', '', true));
        $name = '';
        if (self::nameExists($domain->hostname)) {
            $name = $domain->hostname . '_' . time();
            self::debug("Nom en double " . $domain->hostname . " renommé en " . $name);
        } else {
            $name = $domain->hostname;
        }
        $eqLogicClient->setName($name);
        $eqLogicClient->setIsEnable(1);
        $eqLogicClient->setIsVisible(0);
        $eqLogicClient->setLogicalId($domain->hostname);
        $eqLogicClient->setEqType_name('noip');
        if ($defaultRoom) $eqLogicClient->setObject_id($defaultRoom);
        $eqLogicClient->setConfiguration('type', 'domain');
        $eqLogicClient->setConfiguration('login', $login);
        $eqLogicClient->setConfiguration('image', $eqLogicClient->getImage());
        $eqLogicClient->save();
        self::info("Domaine créé : " . $name);
    }

    public static function syncNoIp() {
        self::info("Début de synchronisation");

        $eqLogics = eqLogic::byType('noip');
        /** @var noip $eqLogic */
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getConfiguration('type', '') != 'account' || $eqLogic->getIsEnable() != 1) {
                continue;
            }

            // retrieve all DNS for each account
            $obj = $eqLogic->executeNoIpScript($eqLogic->getConfiguration('login'), $eqLogic->getConfiguration('password'), 0);

            // if dns available, then create a dedicated domain obj and update data
            if (!is_null($obj)) {
                foreach ($obj as $domain) {
                    $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
                    if (!is_object($existingDomain)) {
                        // new domain
                        noip::createDomain($domain, $eqLogic->getConfiguration('login'));
                        $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
                        event::add('jeedom::alert', array(
                            'level' => 'warning',
                            'page' => 'noip',
                            'message' => __('Domaine inclus avec succès : ' . $domain->hostname, __FILE__),
                        ));
                    }
                }
                $eqLogic->recordData($obj);
            }
        }

        self::info("Fin de la synchronisation");
    }

    public static function removeAllDomains($login) {
        $eqLogics = eqLogic::byType('noip');
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getConfiguration('type') == 'domain' && $eqLogic->getConfiguration('login') == $login) {
                $eqLogic->remove();
            }
        }
    }

    public function preUpdate() {
    }

    public function preSave() {

        // check if IP set is well formatted
        $value = $this->getConfiguration('ipLinked', null);

        if (!is_null($value) && !noipTools::isIpAddress($value)) {
            self::error($value . ' is not a valid IP v4 address - not saving data');
            $this->setConfiguration('ipLinked', null);
        }
    }

    public function postSave() {
        $type = $this->getConfiguration('type', 'account');
        $this->createCommands(__DIR__  . '/../config/params.json', $type);

        if ($type == 'account') {
            $cron = cron::byClassAndFunction('noip', 'autoCheck');
            $this->checkAndUpdateCmd('nextcheck', $cron->getNextRunDate());
        }
    }

    public function preInsert() {
        if ($this->getConfiguration('type', '') == 'account') {
            $this->setIsVisible(1);
            $this->setConfiguration('widgetTemplate', 1);
            $this->setConfiguration('breakLine', 'n');
        } else {
            $this->setIsVisible(0);
        }
        $this->setDisplay('height', '150px');
        $this->setDisplay('width', '340px');
        $this->setIsEnable(1);
    }

    public function preRemove() {
        if ($this->getConfiguration('type') == "account") {
            self::removeAllDomains($this->getConfiguration('login'));
        }
    }

    public function getImage() {
        if ($this->getConfiguration('type') == 'domain') {
            return 'plugins/noip/core/assets/domain_icon.png';
        }
        return 'plugins/noip/plugin_info/noip_icon.png';
    }

    public function scan($renew) {
        if ($this->getIsEnable() && $this->getConfiguration('type') == 'account') {
            $this->refreshInfo($renew);
        }
    }

    public function executeNoIpScript($login, $password, $renew) {
        $noip_path = dirname(__FILE__) . '/../..';

        array_map('unlink', glob("$noip_path/data/*.png"));
        array_map('unlink', glob("$noip_path/data/*.json"));

        $daemonLogConfig = config::byKey('daemonLog', __CLASS__, '200');
        $daemonLog = ($daemonLogConfig == 'parent') ? log::getLogLevel(__CLASS__) : $daemonLogConfig;

        $cmd = system::getCmdSudo() . 'python3 ' . $noip_path . '/resources/noip-renew.py ';
        $cmd .= ' --loglevel ' . log::convertLogLevel($daemonLog);
        $cmd .= ' --user ' . $login;
        $cmd .= ' --pwd "' . $password . '"';
        $cmd .= ' --threshold ' . config::byKey('renewThreshold', 'noip', 7);
        $cmd .= ' --renew ' . $renew;
        $cmd .= ' --noip_path ' . $noip_path;
        $cmd .= ' --force_path ' . config::byKey('forcepath', __CLASS__, false);
        self::info('Starting daemon with cmd >>' . str_replace($password, str_repeat('*', strlen($password)), $cmd) . '<<');
        exec($cmd . ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1');

        $string = file_get_contents($noip_path . '/data/output.json');
        self::debug($this->getHumanName() . ' file content: ' . $string);
        if ($string === false) {
            self::error($this->getHumanName() . ' file content empty');
            return null;
        }
        $json_a = json_decode($string);
        if ($json_a === null) {
            self::error($this->getHumanName() . ' JSON decode impossible');
            return null;
        }
        if (isset($json_a->msg)) {
            self::error($this->getHumanName() . ' error while executing Python script: ' . $json_a->msg);
            return null;
        }
        return $json_a;
    }

    public static function refreshInfoEq($_options) {
        /** @var noip $eqLogic */
        self::trace('starting refreshInfoEq - ' . json_encode($_options));
        $eqId = $_options['eqId'] ?? null;
        $eqLogic = self::byId($eqId);
        if (!is_object($eqLogic)) {
            self::debug('no eq found with id [' . $eqId . ']');
            return;
        }

        self::trace('running scan');
        $eqLogic->scan(1);
    }

    public function refreshInfo($renew) {
        $obj = $this->executeNoIpScript($this->getConfiguration('login'), $this->getConfiguration('password'), $renew);
        if (!is_null($obj)) {
            $this->recordData($obj);
            $this->checkAndUpdateCmd('refreshStatus', 'ok');
        } else {
            $this->checkAndUpdateCmd('refreshStatus', 'error');
            if ($this->getConfiguration('refreshOnError', 0)) {
                self::debug('Set a new refresh in 5min');
                self::executeAsync('refreshInfoEq', array("eqId" => $this->getId()), date('Y-m-d H:i:s', strtotime("+5 minutes")));
            }
        }
    }

    public static function getAllDomainsName() {
        $result = array();

        $eqLogics = eqLogic::byType('noip');
        /** @var noip $eqLogic */
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getConfiguration('type', '') == 'account') continue;

            $result[] = $eqLogic->getLogicalId();
        }

        return $result;
    }

    public function recordData($obj) {
        $allItems = array();

        $allDomainsInfo = array();

        foreach ($obj as $domain) {
            self::debug("will update domain with following data : " . json_encode($domain));
            $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
            if (!is_object($existingDomain)) {
                // new domain
                noip::createDomain($domain, $this->getConfiguration('login'));
                $existingDomain = noip::byLogicalId($domain->hostname, 'noip');
            }
            if (is_object($existingDomain)) {
                if ($existingDomain->getIsEnable()) {
                    $existingDomain->checkAndUpdateCmd('hostname', $domain->hostname);
                    $existingDomain->checkAndUpdateCmd('expiration', $domain->expirationdays);
                    $existingDomain->checkAndUpdateCmd('iplinked', $domain->ip);
                    $existingDomain->checkAndUpdateCmd('renew', $domain->renewed);
                    $endDate = date('d/m/Y', strtotime($domain->expirationdays . " days"));
                    $existingDomain->checkAndUpdateCmd('endDate', $endDate);
                }
                $existingDomain->setConfiguration('parentId', $this->getId());
                $existingDomain->save(true);
                $allItems[] = $domain->hostname;
                $allDomainsInfo[] = $domain->hostname . ' : ' . $domain->expirationdays . ' jour' . self::getPlurial($domain->expirationdays);
            }
        }

        $breakLine = self::getBreakLine($this->getConfiguration('breakLine', 'n'));
        $infoTxt = implode($breakLine, $allDomainsInfo);
        // self::debug('all details => ' . $infoTxt);
        $this->checkAndUpdateCmd('domainsDetails', $infoTxt);
        $this->removeUnexistingDomain($allItems);
    }

    public function removeUnexistingDomain($existingItems) {
        $autoRemove = $this->getConfiguration('autoRemove', false);
        if (!$autoRemove) return;

        $allEq = self::getAllDomainsName();
        self::trace('All existing items in plugin : ' . json_encode($allEq));
        self::trace('Items currently existing in NoIp website : ' . json_encode($existingItems));

        foreach ($existingItems as $item) {
            if (($key = array_search($item, $allEq)) !== false) {
                unset($allEq[$key]);
            }
        }

        foreach ($allEq as $eq) {
            self::info('Domain "' . $eq . '" does not exist anymore - autoRemove is on -> removing object');

            $domain = noip::byLogicalId($eq, __CLASS__);
            /** @var noip $remove */
            if (is_object($domain)) $domain->remove();
        }
    }

    public static function getBreakLine($br) {
        switch ($br) {
            case 'br':
                return "<br/>";
                break;

            case ',':
                return ", ";
                break;

            case 'n':
            default:
                return "\n";
                break;
        }
    }

    public function toHtml($_version = 'dashboard') {
        if ($this->getConfiguration('widgetTemplate') != 1 || $this->getConfiguration('type') == 'domain') {
            return parent::toHtml($_version);
        }
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $version = jeedom::versionAlias($_version);

        $list = "";

        $eqLogics = eqLogic::byType('noip');
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getConfiguration('type') == 'domain' && $eqLogic->getConfiguration('login') == $this->getConfiguration('login') && $eqLogic->getIsEnable() != 0) {
                $hostnameCmd = $eqLogic->getCmd(null, 'hostname');
                $expirationCmd = $eqLogic->getCmd(null, 'expiration');
                $deadline = $expirationCmd->execCmd();
                $renewCmd = $eqLogic->getCmd(null, 'renew');
                $status = $renewCmd->execCmd();
                $icon = "<div class='cursor tooltipstered' title=";
                $renew = $deadline - config::byKey('renewThreshold', 'noip', 7);
                if ($status === "ko") {
                    $icon = $icon . "\"" . __("Le renouvellement automatique a échoué. Rendez-vous sur votre espace no-ip.com pour effectuer le renouvellement manuellement", __FILE__) . "\"><i class='icon_red fas fa-minus-circle'></i></div>";
                } else if ($status === "warning") {
                    $icon = $icon . "\"" . __("La date d'expiration est proche. Le renouvellement automatique se fera dans ", __FILE__) . $renew . " " . __("jour(s)", __FILE__) . "\"><i class='icon_orange fas fa-exclamation-triangle'></div>";
                } else {
                    $icon = $icon . "\"" . __("Le renouvellement n'est pas nécesaire pour l'instant", __FILE__) . "\"><i class='icon_green far fa-check-circle'></i></div>";
                }
                $list = $list . "<tr><td><a href='" . $eqLogic->getLinkToConfiguration() . "' class='reportModeHidden'>" . $hostnameCmd->execCmd() . "</a></td><td>" . $deadline . " " . __("jour(s)", __FILE__) . "</td><td>" . $icon . "</td></tr>";
            }
        }

        $replace['#domains#'] = $list;
        $nextcheckCmd = $this->getCmd(null, 'nextcheck');
        $replace['#nextcheck#'] = $nextcheckCmd->execCmd();

        $html = template_replace($replace, getTemplate('core', $version, 'noip.template', __CLASS__));
        cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
        return $html;
    }

    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
        return array('script' => dirname(__FILE__) . '/../../resources/install_apt.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'noip_update';
        $return['progress_file'] = '/tmp/jeedom/noip/dependency';
        $cmd = system::getCmdSudo() . '/bin/bash ' . dirname(__FILE__) . '/../../resources/install_check.sh';
        if (exec($cmd) == "ok") {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        return $return;
    }
}

class noipCmd extends cmd {

    public function dontRemoveCmd() {
        return true;
    }

    public function execute($_options = null) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
        noip::debug('Execution de la commande ' . $this->getLogicalId());
        switch ($this->getLogicalId()) {
            case "refresh":
                $eqLogic->scan(1);
                break;
        }
    }
}
