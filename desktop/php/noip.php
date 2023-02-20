<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'noip');
$eqLogics = eqLogic::byType('noip');

$has = ["account" => false, "domain" => false];

foreach ($eqLogics as $eqLogic) {
    if ($eqLogic->getConfiguration('type') == '') {
        $eqLogic->setConfiguration('type', 'account');
        $eqLogic->save();
    }
    $type = $eqLogic->getConfiguration('type', '');
    if ($type) {
        $has[$type] = true;
    }
}
?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <div class="row">
            <div class="col-sm-10" style="min-height:200px">
                <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
                <div class="eqLogicThumbnailContainer">
                    <div class="cursor eqLogicAction logoPrimary" data-action="add" title="{{Ajouter un compte No-Ip}}">
                        <i class="fas fa-plus-circle" style="color:rgb(0,25,132);"></i>
                        <br>
                        <span style="color:var(--txt-color)">{{Ajouter}}</span>
                    </div>
                    <div class="cursor eqLogicAction logoPrimary" data-action="discover" data-action2="repos" title="{{Scanner les domaines}}">
                        <i class="fas fa-bullseye" style="color:rgb(0,25,132);"></i>
                        <br>
                        <span style="color:var(--txt-color)">{{Scanner}}</span>
                    </div>
                    <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                        <i class="fas fa-wrench"></i>
                        <br>
                        <span style="color:var(--txt-color)">{{Configuration}}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-2" style="min-height:200px">
                <legend><i class="fas fa-search"></i> {{Debug}}</legend>
                <div class="eqLogicThumbnailContainer">
                    <div class="cursor eqLogicAction logoSecondary" id="bt_getScreenshot">
                        <i class="fas fa-bug" style="color:rgb(0,25,132);"></i>
                        <br>
                        <span style="color:var(--txt-color)">{{Screenshots}}</span>
                    </div>
                </div>
            </div>
        </div>
        <legend><i class="fas fa-table"></i>{{Mes Comptes No-Ip}}
        </legend>
        <div class="panel">
            <div class="panel-body">
                <div class="eqLogicThumbnailContainer ">
                    <?php
                    if ($has['account']) {
                        foreach ($eqLogics as $eqLogic) {
                            if ($eqLogic->getConfiguration('type', '') != 'account') {
                                continue;
                            }
                            $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                            echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                            echo '<img src="' . $eqLogic->getImage() . '"/>';
                            echo '<br>';
                            echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                            echo '</div>';
                        }
                    } else {
                        echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez pas encore de compte No-Ip, cliquez sur Ajouter un équipement pour commencer}}</span></center>";
                    }
                    ?>

                </div>
            </div>
        </div>
        <legend><i class="fas fa-table"></i> {{Mes Domaines}} </legend>
        <div class="input-group" style="margin-bottom:5px;">
            <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic2" />
            <div class="input-group-btn">
                <a id="bt_resetEqlogicSearch2" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
            </div>
        </div>
        <div class="panel">
            <div class="panel-body">
                <div class="eqLogicThumbnailContainer  second">
                    <?php
                    if ($has['domain']) {
                        foreach ($eqLogics as $eqLogic) {
                            if ($eqLogic->getConfiguration('type', '') != 'domain') {
                                continue;
                            }
                            $opacity = '';
                            if ($eqLogic->getIsEnable() != 1) {
                                $opacity = ' disableCard';
                            }

                            echo '<div class="eqLogicDisplayCard cursor  second ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                            echo '<img src="' . $eqLogic->getImage() . '"/>';
                            echo '<br>';
                            echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                            echo '</div>';
                        }
                    } else {
                        echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Scannez les domaines pour les créer}}</span></center>";
                    }
                    ?>

                </div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>
        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br />
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value=""></option>
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
                            <label class="col-sm-3 control-label">{{Catégorie}}</label>
                            <div class="col-sm-3">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Options}}</label>
                            <div class="col-sm-3">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
                            </div>
                        </div>
                        <br>
                        <div class="form-group" id="div_loginNoIp">
                            <label class="col-sm-3 control-label">{{Login No-Ip}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="login" placeholder="Login No-Ip" />
                            </div>
                        </div>
                        <div class="form-group" id="div_passNoIp">
                            <label class="col-sm-3 control-label">{{Mot de passe No-Ip}}</label>
                            <div class="col-sm-3">
                                <input type="password" autocomplete="new-password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="Mot de passe No-Ip" />
                            </div>
                        </div>
                        <div class="form-group" id="div_widgetNoIp">
                            <label class="col-sm-3 control-label help" data-help="{{Cocher la case pour utiliser le template de widget}}">{{Template de widget}}</label>
                            <div class="col-sm-3">
                                <input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="widgetTemplate" />
                            </div>
                        </div>

                        <div class="form-group" id="div_refreshNoIp">
                            <label class="col-sm-3 control-label help" data-help="{{Permet de relance un nouveau rafraichissement automatique 5min plus tard, si le dernier s'est terminé en erreur}}">{{Refresh si erreur}}</label>
                            <div class="col-sm-3">
                                <input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="refreshOnError" />
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <!--<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;">
<i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/> -->
                <br />
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th style="width:50px;">{{Id}}</th>
                            <th style="width:300px;">{{Nom}}</th>
                            <th>{{Type}}</th>
                            <th>{{Etat}}</th>
                            <th class="col-xs-3">{{Options}}</th>
                            <th class="col-xs-2">{{Action}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>


<?php include_file('desktop', 'noip', 'js', 'noip'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>