<?php
/**
 * Plugin AutoAssignInternal
 * 
 * @copyright Copyright (c) 2024
 * @license   MIT License
 */

define('PLUGIN_AUTOASSIGNINTERNAL_VERSION', '1.0.0');

/**
 * Init hooks of the plugin
 */
function plugin_init_autoassigninternal() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['autoassigninternal'] = true;
   
   $plugin = new Plugin();
   if ($plugin->isActivated('autoassigninternal')) {
      
      Plugin::registerClass('PluginAutoassigninternalConfig', [
         'addtabon' => ['Config']
      ]);

      // Hook into task updates
      $PLUGIN_HOOKS['item_update']['autoassigninternal'] = [
         'TicketTask' => 'plugin_autoassigninternal_item_update'
      ];

      // Add configuration menu
      if (Session::haveRight('config', UPDATE)) {
         $PLUGIN_HOOKS['config_page']['autoassigninternal'] = 'front/config.form.php';
      }
   }
}

/**
 * Get the name and the version of the plugin
 */
function plugin_version_autoassigninternal() {
   return [
      'name'           => 'Auto Assign Internal',
      'version'        => PLUGIN_AUTOASSIGNINTERNAL_VERSION,
      'author'         => 'AutoAssignInternal Team',
      'license'        => 'MIT',
      'homepage'       => 'https://github.com/micaelfilipee/glpi-task-ticket',
      'requirements'   => [
         'glpi' => [
            'min' => '9.5.0',
            'max' => '9.5.99'
         ]
      ]
   ];
}

/**
 * Check pre-requisites before install
 */
function plugin_autoassigninternal_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.5.0', 'lt') || version_compare(GLPI_VERSION, '9.5.99', 'gt')) {
      echo "This plugin requires GLPI >= 9.5.0 and < 10.0.0";
      return false;
   }
   return true;
}

/**
 * Check configuration process
 */
function plugin_autoassigninternal_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      echo __('Installed / not configured', 'autoassigninternal');
   }
   return false;
}

/**
 * Install process for plugin : need to return true if succeeded
 */
function plugin_autoassigninternal_install() {
   global $DB;

   // Create config table
   if (!$DB->tableExists('glpi_plugin_autoassigninternal_configs')) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_autoassigninternal_configs` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `internal_requesttype_id` int(11) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      
      $DB->queryOrDie($query, $DB->error());

      // Insert default config
      $query = "INSERT INTO `glpi_plugin_autoassigninternal_configs` (`id`, `internal_requesttype_id`) VALUES (1, 0);";
      $DB->queryOrDie($query, $DB->error());
   }

   return true;
}

/**
 * Uninstall process for plugin
 */
function plugin_autoassigninternal_uninstall() {
   global $DB;

   // Drop config table
   if ($DB->tableExists('glpi_plugin_autoassigninternal_configs')) {
      $query = "DROP TABLE `glpi_plugin_autoassigninternal_configs`;";
      $DB->queryOrDie($query, $DB->error());
   }

   return true;
}
