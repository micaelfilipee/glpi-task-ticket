<?php
if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/_define.php';

/**
 * Plugin installation routine.
 *
 * @return bool
 */
function plugin_AutoAssignInternal_install() {
    global $DB;

    $requesttypeTable = PLUGIN_AUTOASSIGNINTERNAL_TABLE_REQUESTTYPE_CONFIGS;
    $settingsTable    = PLUGIN_AUTOASSIGNINTERNAL_TABLE_SETTINGS;

    if (!$DB->tableExists($requesttypeTable)) {
        $query = "CREATE TABLE `$requesttypeTable` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `requesttypes_id` INT(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_requesttypes_id` (`requesttypes_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $DB->queryOrDie($query, 'Unable to create AutoAssignInternal request type configuration table');
    }

    if (!$DB->tableExists($settingsTable)) {
        $query = "CREATE TABLE `$settingsTable` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `setting_name` VARCHAR(255) NOT NULL,
            `setting_value` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_setting_name` (`setting_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $DB->queryOrDie($query, 'Unable to create AutoAssignInternal settings table');
    }

    return true;
}

/**
 * Plugin uninstallation routine.
 *
 * @return bool
 */
function plugin_AutoAssignInternal_uninstall() {
    global $DB;

    $requesttypeTable = PLUGIN_AUTOASSIGNINTERNAL_TABLE_REQUESTTYPE_CONFIGS;
    $settingsTable    = PLUGIN_AUTOASSIGNINTERNAL_TABLE_SETTINGS;

    if ($DB->tableExists($requesttypeTable)) {
        $DB->queryOrDie("DROP TABLE `$requesttypeTable`", 'Unable to drop AutoAssignInternal request type configuration table');
    }

    if ($DB->tableExists($settingsTable)) {
        $DB->queryOrDie("DROP TABLE `$settingsTable`", 'Unable to drop AutoAssignInternal settings table');
    }

    return true;
}

/**
 * Assign the ticket to the technician of the task based on the hook event.
 *
 * @param TicketTask $item
 */
function plugin_AutoAssignInternal_assign_tech_on_task_add(TicketTask $item) {
    _AutoAssignInternal_assign_tech_from_task($item, 'create');
}

/**
 * Assign the ticket to the technician when a task is updated.
 *
 * @param TicketTask $item
 */
function plugin_AutoAssignInternal_assign_tech_on_task_update(TicketTask $item) {
    _AutoAssignInternal_assign_tech_from_task($item, 'update');
}

/**
 * Core logic to assign the ticket to the technician of the task.
 *
 * @param TicketTask $item
 * @param string     $event
 */
function _AutoAssignInternal_assign_tech_from_task(TicketTask $item, string $event) {
    static $logEnabled          = null;
    static $enabledRequestTypes = null;

    if ($logEnabled === null) {
        $logEnabled = _AutoAssignInternal_fetch_log_status();
    }

    if ($enabledRequestTypes === null) {
        $enabledRequestTypes = _AutoAssignInternal_fetch_enabled_requesttypes();
    }

    $taskId   = (int)$item->getID();
    $ticketId = 0;

    if (isset($item->fields['tickets_id'])) {
        $ticketId = (int)$item->fields['tickets_id'];
    } elseif (isset($item->input['tickets_id'])) {
        $ticketId = (int)$item->input['tickets_id'];
    }

    $eventLabel = ($event === 'update') ? 'atualização' : 'criação';

    if ($logEnabled) {
        _AutoAssignInternal_write_log(sprintf('Hook de %s acionado para tarefa %d do chamado %d.', $eventLabel, $taskId, $ticketId));
    }

    if ($ticketId <= 0) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log('ID do chamado não identificado. Saindo.');
        }
        return;
    }

    if (empty($enabledRequestTypes)) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log('Nenhum tipo de chamado habilitado para automação.');
        }
        return;
    }

    $technicianId = 0;
    if (isset($item->fields['users_id_tech'])) {
        $technicianId = (int)$item->fields['users_id_tech'];
    } elseif (isset($item->input['users_id_tech'])) {
        $technicianId = (int)$item->input['users_id_tech'];
    }

    if ($technicianId <= 0) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log(sprintf('Nenhum técnico atribuído à tarefa %d. Automação ignorada.', $taskId));
        }
        return;
    }

    if ($logEnabled) {
        _AutoAssignInternal_write_log(sprintf('Verificando chamado %d para automação.', $ticketId));
    }

    $ticket = new Ticket();
    if (!$ticket->getFromDB($ticketId)) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log(sprintf('Chamado %d não encontrado.', $ticketId));
        }
        return;
    }

    $requestTypeId = 0;
    if (isset($ticket->fields['requesttypes_id'])) {
        $requestTypeId = (int)$ticket->fields['requesttypes_id'];
    }

    if ($requestTypeId <= 0) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log(sprintf('Chamado %d sem tipo de chamado definido. Automação ignorada.', $ticketId));
        }
        return;
    }

    if (!in_array($requestTypeId, $enabledRequestTypes, true)) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log(sprintf('Chamado %d com tipo %d não está habilitado para automação.', $ticketId, $requestTypeId));
        }
        return;
    }

    if ($logEnabled) {
        _AutoAssignInternal_write_log(sprintf('Tipo de chamado %d habilitado. Prosseguindo com automação.', $requestTypeId));
    }

    $ticketUser = new Ticket_User();
    $criteria   = [
        'tickets_id' => $ticketId,
        'users_id'   => $technicianId,
        'type'       => CommonITILActor::ASSIGN
    ];

    if ($ticketUser->getFromDBByCrit($criteria)) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log(sprintf('Chamado %d já está atribuído ao técnico %d.', $ticketId, $technicianId));
        }
        return;
    }

    if ($logEnabled) {
        _AutoAssignInternal_write_log(sprintf('Atribuindo chamado %d ao técnico %d.', $ticketId, $technicianId));
    }

    $result = $ticketUser->add([
        'tickets_id' => $ticketId,
        'users_id'   => $technicianId,
        'type'       => CommonITILActor::ASSIGN
    ]);

    if ($result) {
        if ($logEnabled) {
            _AutoAssignInternal_write_log(sprintf('Atribuição realizada com sucesso para o técnico %d.', $technicianId));
        }
    } else {
        if ($logEnabled) {
            _AutoAssignInternal_write_log(sprintf('Falha ao atribuir o chamado %d ao técnico %d.', $ticketId, $technicianId));
        }
    }
}

/**
 * Retrieve the log status from the database.
 *
 * @return bool
 */
function _AutoAssignInternal_fetch_log_status() {
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
 * Retrieve the enabled request type identifiers from the database.
 *
 * @return array
 */
function _AutoAssignInternal_fetch_enabled_requesttypes() {
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
 * Helper to write plugin logs.
 *
 * @param string $message
 */
function _AutoAssignInternal_write_log($message) {
    if (!defined('GLPI_LOG_DIR')) {
        return;
    }

    $line = sprintf('[%s] %s%s', date('Y-m-d H:i:s'), $message, PHP_EOL);
    file_put_contents(GLPI_LOG_DIR . '/plugin_AutoAssignInternal.log', $line, FILE_APPEND);
}
