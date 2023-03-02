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
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<form class="form-horizontal">
	<fieldset>
		<legend>
			<i class="fa fa-list-alt"></i> {{Paramètres}}
		</legend>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Renouvellement auto dès }}
				<sup>
					<i class="fas fa-question-circle floatright" title="Les noms de domaine peuvent être automatiquement renouvellés lorsqu'ils arrivent à expiration dans les X jours"></i>
				</sup>
			</label>
			<div class=" col-sm-2">
				<select id="sel_days" class="configKey form-control" data-l1key="renewThreshold">
					<option value="7">7 {{jours}}</option>
					<option value="6">6 {{jours}}</option>
					<option value="5">5 {{jours}}</option>
					<option value="4">4 {{jours}}</option>
					<option value="3">3 {{jours}}</option>
					<option value="2">2 {{jours}}</option>
					<option value="1">1 {{jour}}</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Pièce par défaut pour les nouveaux domaines}}</label>
			<div class="col-lg-3">
				<select id="sel_object" class="configKey form-control" data-l1key="defaultParentObject">
					<option value="">{{Aucune}}</option>
					<?php
					$options = '';
					foreach ((jeeObject::buildTree(null, false)) as $object) {
						$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
					}
					echo $options;
					?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Niveau de log Python}}
				<sup>
					<i class="fas fa-question-circle floatright" title="Vous pouvez choisir un autre niveau de log pour le script Python"></i>
				</sup>
			</label>
			<div class="col-lg-3">
				<select class="form-control configKey" data-l1key="daemonLog">
					<option value="parent">Même que le plugin</option>
					<option value="100">Debug (à utiliser seulement à la demande du dev)</option>
					<option value="200" selected>Infos</option>
					<option value="300">Warning</option>
					<option value="400">Erreur</option>
				</select>
			</div>
		</div>
	</fieldset>
</form>