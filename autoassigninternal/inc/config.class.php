<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

class PluginAutoassigninternalConfig extends CommonDBTM {
    public static $rightname = 'config';

    const CONFIG_ID = 1;

    public $dohistory = true;

    public static function getTypeName($nb = 0) {
        return _n('Atribuição Interna Automática', 'Atribuição Interna Automática', $nb, 'autoassigninternal');
    }

    public static function getInstance() {
        $instance = new self();
        if (!$instance->getFromDB(self::CONFIG_ID)) {
            $instance->fields = [
                'id'           => self::CONFIG_ID,
                'requesttypes' => json_encode([])
            ];
        }

        return $instance;
    }

    public function getInternalRequestTypeIds() {
        if (isset($this->fields['requesttypes'])) {
            $decoded = json_decode($this->fields['requesttypes'], true);
            if (is_array($decoded)) {
                return array_values(array_unique(array_map('intval', $decoded)));
            }
        }

        if (isset($this->fields['requesttypes_id'])) {
            $singleId = (int)$this->fields['requesttypes_id'];
            if ($singleId > 0) {
                return [$singleId];
            }
        }

        return [];
    }

    public function showConfigForm() {
        $this->initForm(self::CONFIG_ID);
        $this->showFormHeader(['formtitle' => self::getTypeName(1)]);

        echo "<tr class='tab_bg_1'>";
        echo '<td>' . __('Tipos de origem internos', 'autoassigninternal') . '</td>';
        echo '<td>';

        $value = $this->getInternalRequestTypeIds();

        $params = [
            'name'                => 'requesttypes_ids[]',
            'values'              => $value,
            'multiple'            => true,
            'display_emptychoice' => true
        ];

        if (!empty($value)) {
            $params['value'] = (string) reset($value);
        }

        RequestType::dropdown($params);

        echo '</td>';
        echo '</tr>';

        $this->showFormButtons(['candel' => false]);

        return true;
    }

    private function normalizeRequestTypesInput(array $input) {
        if (!isset($input['requesttypes'])) {
            $input['requesttypes'] = [];
        }

        if (!is_array($input['requesttypes'])) {
            $input['requesttypes'] = [$input['requesttypes']];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $input['requesttypes']), function ($value) {
            return $value > 0;
        })));

        $input['requesttypes'] = json_encode($ids);

        return $input;
    }

    public function prepareInputForUpdate($input) {
        $input = $this->normalizeRequestTypesInput($input);

        return parent::prepareInputForUpdate($input);
    }

    public function prepareInputForAdd($input) {
        $input = $this->normalizeRequestTypesInput($input);

        return parent::prepareInputForAdd($input);
    }
}
