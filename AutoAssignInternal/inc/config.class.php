<?php
if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

class PluginAutoAssignInternalConfig extends CommonDBTM {
    public static $rightname = 'config';

    /**
     * Render configuration page and handle submissions.
     *
     * @param array $options
     *
     * @return bool
     */
    public function display($options = []) {
        if (isset($_POST['update'])) {
            Session::checkRight(self::$rightname, UPDATE);
            $this->post_update($_POST);
        }

        return $this->showForm(0, $options);
    }

    /**
     * Show the configuration form.
     *
     * @param int   $ID
     * @param array $options
     *
     * @return bool
     */
    public function showForm($ID, $options = []) {
        $logEnabled        = $this->isDebugLogEnabled();
        $selectedRequestTs = $this->getConfiguredRequestTypeIds();
        $requestTypes      = $this->getAllRequestTypes();

        echo "<div class='center'>";
        echo "<form method='post' action=''>";
        echo Html::hidden('_glpi_csrf_token', Session::getNewCSRFToken());
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='2'>" . __('Configurações Gerais', 'autoassigninternal') . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Habilitar log de depuração', 'autoassigninternal') . "</td>";
        echo "<td>";
        echo "<label>";
        echo "<input type='checkbox' name='debug_log_enabled' value='1'" . ($logEnabled ? ' checked' : '') . ">";
        echo " " . __('Ativar', 'autoassigninternal');
        echo "</label>";
        echo "</td>";
        echo "</tr>";

        echo "<tr><th colspan='2'>" . __('Tipos de Chamado', 'autoassigninternal') . "</th></tr>";

        if (!empty($requestTypes)) {
            foreach ($requestTypes as $requestTypeId => $requestTypeData) {
                $name    = isset($requestTypeData['name']) ? $requestTypeData['name'] : sprintf(__('Tipo #%d', 'autoassigninternal'), $requestTypeId);
                $checked = in_array((int)$requestTypeId, $selectedRequestTs, true) ? ' checked' : '';

                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='2'>";
                echo "<label>";
                echo "<input type='checkbox' name='requesttypes_ids[]' value='" . (int)$requestTypeId . "'" . $checked . ">";
                echo " " . Html::cleanInputText($name);
                echo "</label>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr class='tab_bg_1'><td colspan='2'>" . __('Nenhum tipo de chamado disponível.', 'autoassigninternal') . "</td></tr>";
        }

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo Html::hidden('update', 1);
        echo "<input type='submit' name='submit' class='submit' value='" . _sx('button', 'Salvar') . "'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</form>";
        echo "</div>";

        return true;
    }

    /**
     * Persist configuration after submission.
     *
     * @param array $input
     */
    public function post_update(array $input = []) {
        global $DB;

        $settingsTable    = PLUGIN_AUTOASSIGNINTERNAL_TABLE_SETTINGS;
        $requesttypeTable = PLUGIN_AUTOASSIGNINTERNAL_TABLE_REQUESTTYPE_CONFIGS;

        $logEnabled = (isset($input['debug_log_enabled']) && (int)$input['debug_log_enabled'] === 1) ? '1' : '0';

        if ($DB->tableExists($settingsTable)) {
            $iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM'   => $settingsTable,
                'WHERE'  => ['setting_name' => 'debug_log_enabled'],
                'LIMIT'  => 1
            ]);

            if ($iterator instanceof DBmysqlIterator && $iterator->numrows()) {
                $DB->update(
                    $settingsTable,
                    ['setting_value' => $logEnabled],
                    ['setting_name' => 'debug_log_enabled']
                );
            } else {
                $DB->insert(
                    $settingsTable,
                    [
                        'setting_name'  => 'debug_log_enabled',
                        'setting_value' => $logEnabled
                    ]
                );
            }
        }

        $selectedRequestTypes = [];
        if (isset($input['requesttypes_ids']) && is_array($input['requesttypes_ids'])) {
            foreach ($input['requesttypes_ids'] as $requestTypeId) {
                $requestTypeId = (int)$requestTypeId;
                if ($requestTypeId > 0) {
                    $selectedRequestTypes[] = $requestTypeId;
                }
            }
            $selectedRequestTypes = array_values(array_unique($selectedRequestTypes));
        }

        if ($DB->tableExists($requesttypeTable)) {
            $DB->queryOrDie("TRUNCATE TABLE `$requesttypeTable`", 'Unable to truncate AutoAssignInternal request type table');

            foreach ($selectedRequestTypes as $requestTypeId) {
                $DB->insert($requesttypeTable, ['requesttypes_id' => $requestTypeId]);
            }
        }

        Session::addMessageAfterRedirect(__('Configurações atualizadas com sucesso.', 'autoassigninternal'), true, INFO, true);
    }

    /**
     * Check if debug logging is enabled.
     *
     * @return bool
     */
    private function isDebugLogEnabled() {
        global $DB;

        if (!$DB->tableExists(PLUGIN_AUTOASSIGNINTERNAL_TABLE_SETTINGS)) {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => ['setting_value'],
            'FROM'   => PLUGIN_AUTOASSIGNINTERNAL_TABLE_SETTINGS,
            'WHERE'  => ['setting_name' => 'debug_log_enabled'],
            'LIMIT'  => 1
        ]);

        if ($iterator instanceof DBmysqlIterator) {
            foreach ($iterator as $row) {
                return !empty($row['setting_value']) && (int)$row['setting_value'] === 1;
            }
        }

        return false;
    }

    /**
     * Retrieve configured request type identifiers.
     *
     * @return array
     */
    private function getConfiguredRequestTypeIds() {
        global $DB;

        if (!$DB->tableExists(PLUGIN_AUTOASSIGNINTERNAL_TABLE_REQUESTTYPE_CONFIGS)) {
            return [];
        }

        $ids      = [];
        $iterator = $DB->request([
            'SELECT' => ['requesttypes_id'],
            'FROM'   => PLUGIN_AUTOASSIGNINTERNAL_TABLE_REQUESTTYPE_CONFIGS
        ]);

        if ($iterator instanceof DBmysqlIterator) {
            foreach ($iterator as $row) {
                if (isset($row['requesttypes_id'])) {
                    $ids[] = (int)$row['requesttypes_id'];
                }
            }
        }

        return array_values(array_unique(array_filter($ids, function ($value) {
            return $value > 0;
        })));
    }

    /**
     * Fetch all available request types.
     *
     * @return array
     */
    private function getAllRequestTypes() {
        $requestType = new RequestType();

        return $requestType->find([], 'name');
    }
}
