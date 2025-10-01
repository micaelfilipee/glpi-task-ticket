<?php
if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/_define.php';
require_once __DIR__ . '/inc/config.class.php';
require_once __DIR__ . '/hook.php';

function plugin_version_AutoAssignInternal() {
    return [
        'name'           => __('AutoAssignInternal', 'autoassigninternal'),
        'version'        => PLUGIN_AUTOASSIGNINTERNAL_VERSION,
        'author'         => 'AutoAssignInternal Team',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'minGlpiVersion' => '9.5.5',
        'maxGlpiVersion' => '9.5.x'
    ];
}

function plugin_init_AutoAssignInternal() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['config_page']['AutoAssignInternal'] = 'front/config.form.php';
    $PLUGIN_HOOKS['csrf_compliant']['AutoAssignInternal'] = true;
    $PLUGIN_HOOKS['item_add']['AutoAssignInternal']['TicketTask'] = 'plugin_AutoAssignInternal_assign_tech_on_task_add';

    if (class_exists('Plugin')) {
        Plugin::registerClass('PluginAutoAssignInternalConfig');
    }
}
