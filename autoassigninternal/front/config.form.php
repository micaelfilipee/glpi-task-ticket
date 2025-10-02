<?php
/**
 * Plugin AutoAssignInternal - Config Form Handler
 * 
 * @copyright Copyright (c) 2024
 * @license   MIT License
 */

include ('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

$config = new PluginAutoassigninternalConfig();

if (isset($_POST['update'])) {
   // CSRF token check
   Session::checkCSRF($_POST);
   
   $config->updateConfig($_POST);
   
   Html::back();
} else {
   Html::header(__('Auto Assign Internal', 'autoassigninternal'), $_SERVER['PHP_SELF'], 'config', 'plugins');
   
   $config->showConfigForm();
   
   Html::footer();
}
