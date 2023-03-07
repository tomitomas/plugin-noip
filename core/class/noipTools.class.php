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
require_once __DIR__ . '/noip.class.php';

class noipTools {

    public static $_ip_url =  'https://ipecho.net/plain';
    public static $_noip_update =  'http://dynupdate.no-ip.com/nic/update';

    public function makeCurlRequest(string $url, array $headers = array(), array $data = array(), array $credentials = array('', ''), string $type = 'GET') {

        try {
            if (count($data) > 0 && $type == 'GET') {
                $encodedData = http_build_query($data);
                $url .= '?' . $encodedData;
            }

            list($username, $pwd) = $credentials;
            $request_http = new com_http($url, $username, $pwd);

            if (count($headers) > 0) {
                $request_http->setHeader($headers);
            }

            if (count($data) > 0 && $type == 'POST') {
                $encodedData = http_build_query($data);
                $request_http->setPost($encodedData);
            }

            $data = $request_http->exec(30, 1);
            $result = is_json($data, $data);

            noip::trace('makeCurlRequest : ' . json_encode($result));

            // $http_code = $request_httpcurl_getinfo($ch, CURLINFO_HTTP_CODE);
            // if ($http_code == intval(200)) {
            return $result;
        } catch (Exception $ex) {
            noip::error('Exception during curl request : ' . $ex->getMessage());
            throw $ex;
        }
    }

    public static function getDomainToUpdate() {

        // get the current public IP
        $myCurrentIp = self::getCurrentIp();
        $myUpdates = array();

        /** @var noip $domain */
        foreach (eqLogic::byType('noip') as $domain) {
            // if eq type not a domain -> skip
            if ($domain->getConfiguration('type', 'account') != 'domain') continue;
            //no refresh or disable -> skip
            if ($domain->getConfiguration('makeIpRefresh', '0') == '0' || $domain->getIsEnable() != 1) continue;

            // get the last IP setup from noip and save in the eqlogic cmd
            $cmdIp = $domain->getCmd('info', 'iplinked');
            $domainIp = !is_object($cmdIp) ? '' : $cmdIp->execCmd();

            // get the ip defined by the user on the eq, otherwise the current real ip
            $ipToSave = $domain->getConfiguration('ipLinked', $myCurrentIp);

            // if IPs are different, then make an update
            if ($ipToSave != $domainIp) {
                $parentId = $domain->getConfiguration('parentId');

                if (!in_array($parentId, array_keys($myUpdates))) $myUpdates[$parentId] = array();

                if (!in_array($ipToSave, array_keys($myUpdates[$parentId]))) $myUpdates[$parentId][$ipToSave] = array();
                $myUpdates[$parentId][$ipToSave][$domain->getId()] = $domain->getLogicalId();
            }
        }

        noip::trace('all items to update => ' . json_encode($myUpdates));
        return $myUpdates;
    }

    public static function makeIpUpdate() {
        $allUpdates = self::getDomainToUpdate();

        if (count($allUpdates) == 0) noip::debug(__('Pas de mise à jour d\'IP à réaliser', __FILE__));

        foreach ($allUpdates as $eqId => $infos) {

            $domain = eqLogic::byId($eqId);
            if (!is_object($domain)) {
                noip::warning('no equipment found with id [' . $eqId . ']');
                continue;
            }

            $credentials = self::getCredentials($eqId);
            $headers = self::getHeadersNoIp($credentials[0] ?? '');

            foreach ($infos as $ip => $ddns) {
                $ddnsArr = array();
                $cmdIpArr = array();
                foreach ($ddns as $key => $value) {
                    $cmdIpArr[] = $key;
                    $ddnsArr[] = $value;
                }

                noip::trace('list of cmdId : ' . json_encode($cmdIpArr));
                noip::trace('list of ddnsArr : ' . json_encode($ddnsArr));

                $data = array(
                    "hostname" => implode(',', $ddnsArr),
                    "myip" => $ip,
                );

                noip::trace(' will make a curl request : ');
                noip::trace('     data : ' . json_encode($data));
                noip::trace('     headers : ' . json_encode($headers));

                $result = self::makeCurlRequest(self::$_noip_update, $headers, $data, $credentials);
                noip::trace(__('==> Update result : ', __FILE__) . $result);
                if (strpos($result, 'good') === false && strpos($result, 'nochg') === false) {
                    noip::error(__('Erreur de mise à jour : ', __FILE__) . $result);
                    continue;
                }

                foreach ($cmdIpArr as $domainId) {
                    $cmdIp = cmd::byEqLogicIdAndLogicalId($domainId, 'iplinked');
                    if (is_object($cmdIp)) $cmdIp->event($ip);
                }
                noip::info(__('Mise à jour effectué pour : ', __FILE__) . json_encode($data));
            }
        }
    }

    public static function getCurrentIp() {
        return self::makeCurlRequest(self::$_ip_url);
    }


    public static function isIpAddress($ip) {
        return (filter_var($ip, FILTER_VALIDATE_IP) !== false);
    }

    public static function getHeadersNoIp($user) {
        if (strlen($user) > 50) noip::warning(__('Le login est supérieur à 50 caractères, la mise à jour risque de ne pas fonctionner', __FILE__));

        return array(
            "content-type" => "application/json",
            "User-Agent" => "noipJeedom/0.0.1 " . $user
        );
    }

    public static function getCredentials($eqId) {

        $user = null;
        $pwd = null;

        $account = eqLogic::byId($eqId);
        if (is_object($account)) {
            $user = $account->getConfiguration('login', null);
            $pwd = $account->getConfiguration('password', null);
        }

        return array($user, $pwd);
    }
}
