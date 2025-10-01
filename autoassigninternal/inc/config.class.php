<?php
/**
 * Plugin AutoAssignInternal - Config Class
 * 
 * @copyright Copyright (c) 2024
 * @license   MIT License
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginAutoassigninternalConfig extends CommonDBTM {

   static $rightname = 'config';

   /**
    * Get configuration
    * 
    * @return array|false Configuration data or false if not found
    */
   public function getConfig() {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_autoassigninternal_configs',
         'WHERE' => ['id' => 1]
      ]);

      if (count($iterator) > 0) {
         $data = $iterator->next();
         return $data;
      }

      return false;
   }

   /**
    * Update configuration
    * 
    * @param array $input Configuration data
    * @return bool True on success
    */
   public function updateConfig($input) {
      global $DB;

      $result = $DB->update(
         'glpi_plugin_autoassigninternal_configs',
         [
            'internal_requesttype_id' => $input['internal_requesttype_id']
         ],
         [
            'id' => 1
         ]
      );

      return $result;
   }

   /**
    * Get tab name for item
    * 
    * @param CommonGLPI $item         Item
    * @param integer    $withtemplate Template option
    * @return string Tab name
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Config') {
         return __('Auto Assign Internal', 'autoassigninternal');
      }
      return '';
   }

   /**
    * Display tab content for item
    * 
    * @param CommonGLPI $item         Item
    * @param integer    $tabnum       Tab number
    * @param integer    $withtemplate Template option
    * @return bool True on success
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Config') {
         $config = new self();
         $config->showConfigForm();
         return true;
      }
      return false;
   }

   /**
    * Show configuration form
    */
   public function showConfigForm() {
      global $CFG_GLPI;

      if (!Session::haveRight('config', UPDATE)) {
         return false;
      }

      $config = $this->getConfig();
      $internal_requesttype_id = isset($config['internal_requesttype_id']) ? $config['internal_requesttype_id'] : 0;

      echo "<div class='center'>";
      echo "<form name='form' method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>".__('Auto Assign Internal Plugin Configuration', 'autoassigninternal')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Internal Request Type', 'autoassigninternal')."</td>";
      echo "<td>";
      
      // Get all request types
      $requesttype = new RequestType();
      $requesttype->dropdown([
         'name' => 'internal_requesttype_id',
         'value' => $internal_requesttype_id,
         'display_emptychoice' => true
      ]);
      
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2' class='center'>";
      echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
      echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }
}
