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

function noip_install() {
    $threshold = config::byKey('renewThreshold', 'noip');
    if (empty($threshold)) {
        config::save('renewThreshold', 7, 'noip');
    }
    
	$cron = cron::byClassAndFunction('noip', 'autoCheck');
	if ( ! is_object($cron)) {
        $randMinute = rand(3, 59);
        $randHour = rand(2, 22);
        $cronExpr = $randMinute . ' ' . $randHour . ' * * *';
		$cron = new cron();
		$cron->setClass('noip');
		$cron->setFunction('autoCheck');
		$cron->setEnable(1);
		$cron->setDeamon(0);
		$cron->setSchedule($cronExpr);
		$cron->save();
	}
}

function noip_update() {
    $threshold = config::byKey('renewThreshold', 'noip');
    if (empty($threshold)) {
        config::save('renewThreshold', 7, 'noip');
    }
	$cron = cron::byClassAndFunction('noip', 'autoCheck');
	if (is_object($cron)) {
		$randMinute = rand(3, 59);
		$randHour = rand(2, 22);
		$cronExpr = $randMinute . ' ' . $randHour . ' * * *';
		$cron->setSchedule($cronExpr);
		$cron->save();
	}
	foreach (eqLogic::byType('noip') as $eqLogic) {
		if ($eqLogic->getConfiguration('type') == 'account') {
			$eqLogic->checkAndUpdateCmd('nextcheck', $cron->getNextRunDate());
			log::add('noip', 'debug', "Prochaine vérification automatique pour ".$eqLogic->getName()." : ". $cron->getNextRunDate());
		}
	}
}

function noip_remove() {
	$cron = cron::byClassAndFunction('noip', 'autoCheck');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
}
