<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

class PluginAutoassigninternalConfig extends CommonDBTM {
    public static $rightname = 'config';

    const CONFIG_ID = 1;

    public $dohistory = true;

    public static function getTypeName($nb = 0) {
        return _n('Auto Assign Internal', 'Auto Assign Internal', $nb, 'autoassigninternal');
    }

    public static function getInstance() {
        $instance = new self();
        if (!$instance->getFromDB(self::CONFIG_ID)) {
            $instance->fields = [
                'id'              => self::CONFIG_ID,
                'requesttypes_id' => 0
            ];
        }

        return $instance;
    }

    public function getInternalRequestTypeId() {
        if (isset($this->fields['requesttypes_id'])) {
            return (int)$this->fields['requesttypes_id'];
        }
        return 0;
    }

    public function showConfigForm() {
        $this->initForm(self::CONFIG_ID);
        $this->showFormHeader(['formtitle' => self::getTypeName(1)]);

        echo "<tr class='tab_bg_1'>";
        echo '<td>' . __('Internal request type', 'autoassigninternal') . '</td>';
        echo '<td>';

        $value = 0;
        if (isset($this->fields['requesttypes_id'])) {
            $value = (int)$this->fields['requesttypes_id'];
        }

        RequestType::dropdown([
            'name'                 => 'requesttypes_id',
            'value'                => $value,
            'display_emptychoice'  => true
        ]);

        echo '</td>';
        echo '</tr>';

        $this->showFormButtons(['candel' => false]);

        return true;
    }

    public function prepareInputForAdd($input) {
        if (!isset($input['requesttypes_id'])) {
            $input['requesttypes_id'] = 0;
        }
        $input['requesttypes_id'] = (int)$input['requesttypes_id'];

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input) {
        if (!isset($input['requesttypes_id'])) {
            $input['requesttypes_id'] = 0;
        }
        $input['requesttypes_id'] = (int)$input['requesttypes_id'];

        return parent::prepareInputForUpdate($input);
    }
}
