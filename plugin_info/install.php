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
	$threshold = config::byKey('renewThreshold', 'noip', null);
	if (is_null($threshold)) {
		config::save('renewThreshold', 7, 'noip');
	}

	config::save('daemonLog', '200', 'noip');
	config::save('hourStart', '3', 'noip');
	config::save('hourEnd', '22', 'noip');
	
	if (config::byKey('forcepath', 'noip') == '') {
		config::save('forcepath', 0, 'noip');
	}

	noip::createCheckCron();
	noip::createIpUpdateCron();
}

function noip_update() {
	$threshold = config::byKey('renewThreshold', 'noip', null);
	if (is_null($threshold)) {
		config::save('renewThreshold', 7, 'noip');
	}

	if (config::byKey('forcepath', 'noip') == '') {
		config::save('forcepath', 0, 'noip');
	}

	noip::createIpUpdateCron();

	$cron = noip::createCheckCron();
	noip::updateNextCron($cron);
}

function noip_remove() {
	$cron = cron::byClassAndFunction('noip', 'autoCheck');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}

	$cron = cron::byClassAndFunction('noip', 'ipCheckAndUpdate');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
}
