<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/inc/config.class.php';
require_once __DIR__ . '/inc/logging.php';

function plugin_init_autoassigninternal() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['post_item_add']['autoassigninternal'] = 'plugin_autoassigninternal_post_item_add';
    $PLUGIN_HOOKS['post_item_update']['autoassigninternal'] = 'plugin_autoassigninternal_post_item_update';
    $PLUGIN_HOOKS['config_page']['autoassigninternal'] = 'front/config.form.php';
    $PLUGIN_HOOKS['csrf_compliant']['autoassigninternal'] = true;

    if (class_exists('Plugin')) {
        Plugin::registerClass('PluginAutoassigninternalConfig');
    }

    plugin_autoassigninternal_log('Plugin AutoAssignInternal inicializado.');
}

function plugin_version_autoassigninternal() {
    return [
        'name'           => __('Atribuição Interna Automática', 'autoassigninternal'),
        'version'        => '1.1.0',
        'author'         => 'OpenAI ChatGPT',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'minGlpiVersion' => '9.5.5',
        'maxGlpiVersion' => '9.5.x'
    ];
}

function plugin_autoassigninternal_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '9.5.5', '<')) {
        echo __('This plugin requires GLPI 9.5.5 or higher.', 'autoassigninternal');
        return false;
    }
    return true;
}

function plugin_autoassigninternal_check_config($verbose = false) {
    return true;
}

function plugin_autoassigninternal_install() {
    global $DB;

    $table = 'glpi_plugin_autoassigninternal_configs';

    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `requesttypes` text NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $DB->queryOrDie($query, 'Failed to create plugin_autoassigninternal configuration table');
    }

    if ($DB->tableExists($table) && !$DB->fieldExists($table, 'requesttypes')) {
        $DB->queryOrDie("ALTER TABLE `$table` ADD `requesttypes` TEXT NOT NULL", 'Failed to add requesttypes column');

        if ($DB->fieldExists($table, 'requesttypes_id')) {
            $configData = $DB->request([
                'SELECT' => ['id', 'requesttypes_id'],
                'FROM'   => $table
            ]);

            foreach ($configData as $row) {
                $ids = [];
                if (isset($row['requesttypes_id']) && (int)$row['requesttypes_id'] > 0) {
                    $ids[] = (int)$row['requesttypes_id'];
                }
                $DB->update($table, [
                    'requesttypes' => json_encode($ids)
                ], [
                    'id' => $row['id']
                ]);
            }

            $DB->query("ALTER TABLE `$table` DROP COLUMN `requesttypes_id`");
        }
    }

    $config = new PluginAutoassigninternalConfig();
    if (!$config->getFromDB(1)) {
        $config->add([
            'id'           => 1,
            'requesttypes' => json_encode([])
        ]);
    }

    return true;
}

function plugin_autoassigninternal_uninstall() {
    global $DB;

    $table = 'glpi_plugin_autoassigninternal_configs';
    if ($DB->tableExists($table)) {
        $DB->query("DROP TABLE `$table`");
    }

    return true;
}
