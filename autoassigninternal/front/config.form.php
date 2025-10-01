<?php

include '../../../inc/includes.php';

Session::checkRight('config', UPDATE);

$config = new PluginAutoassigninternalConfig();
if (!$config->getFromDB(PluginAutoassigninternalConfig::CONFIG_ID)) {
    $config->add([
        'id'              => PluginAutoassigninternalConfig::CONFIG_ID,
        'requesttypes_id' => 0
    ]);
    $config->getFromDB(PluginAutoassigninternalConfig::CONFIG_ID);
}

if (isset($_POST['update'])) {
    $input = [
        'id' => PluginAutoassigninternalConfig::CONFIG_ID
    ];

    if (isset($_POST['requesttypes_id']) && $_POST['requesttypes_id'] !== '') {
        $input['requesttypes_id'] = (int)$_POST['requesttypes_id'];
    } else {
        $input['requesttypes_id'] = 0;
    }

    $config->update($input);
    Session::addMessageAfterRedirect(__('Configuration updated', 'autoassigninternal'), true, INFO, true);
    Html::back();
    exit;
}

Html::header(__('Auto Assign Internal', 'autoassigninternal'), '', 'plugins', 'autoassigninternal');

$config->showConfigForm();

Html::footer();
