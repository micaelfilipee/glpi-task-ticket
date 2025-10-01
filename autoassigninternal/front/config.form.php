<?php

include '../../../inc/includes.php';

Session::checkRight('config', UPDATE);

$config = new PluginAutoassigninternalConfig();
if (!$config->getFromDB(PluginAutoassigninternalConfig::CONFIG_ID)) {
    $config->add([
        'id'           => PluginAutoassigninternalConfig::CONFIG_ID,
        'requesttypes' => json_encode([])
    ]);
    $config->getFromDB(PluginAutoassigninternalConfig::CONFIG_ID);
}

if (isset($_POST['update'])) {
    $input = [
        'id' => PluginAutoassigninternalConfig::CONFIG_ID
    ];

    if (isset($_POST['requesttypes_ids']) && is_array($_POST['requesttypes_ids'])) {
        $input['requesttypes'] = $_POST['requesttypes_ids'];
    } else {
        $input['requesttypes'] = [];
    }

    $config->update($input);
    Session::addMessageAfterRedirect(__('Configuração atualizada com sucesso.', 'autoassigninternal'), true, INFO, true);
    Html::back();
    exit;
}

Html::header(__('Atribuição Interna Automática', 'autoassigninternal'), '', 'plugins', 'autoassigninternal');

$config->showConfigForm();

Html::footer();
