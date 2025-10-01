<?php
include '../../../inc/includes.php';

Session::checkRight('config', READ);

Html::header(__('AutoAssignInternal', 'autoassigninternal'), '', 'plugins', 'AutoAssignInternal');

$config = new PluginAutoAssignInternalConfig();
$config->display();

Html::footer();
