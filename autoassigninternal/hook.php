<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/inc/config.class.php';

if (!function_exists('plugin_autoassigninternal_log')) {
    function plugin_autoassigninternal_log($message) {
        $prefix = '[AutoAssignInternal] ';
        $line   = $prefix . $message;

        $logDir = defined('GLPI_LOG_DIR') ? GLPI_LOG_DIR : GLPI_ROOT . '/files/_log';
        $logDir = rtrim($logDir, DIRECTORY_SEPARATOR);
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'autoassigninternal.log';

        $sizeBefore = null;
        if (file_exists($logFile)) {
            clearstatcache(true, $logFile);
            $sizeBefore = filesize($logFile);
        }

        $wroteWithToolbox = false;
        if (class_exists('Toolbox') && method_exists('Toolbox', 'logInFile')) {
            Toolbox::logInFile('autoassigninternal', $line);

            clearstatcache(true, $logFile);
            if (file_exists($logFile)) {
                $sizeAfter = filesize($logFile);
                $wroteWithToolbox = ($sizeBefore === null && $sizeAfter > 0)
                    || ($sizeBefore !== null && $sizeAfter !== $sizeBefore);
            }
        }

        if ($wroteWithToolbox) {
            return;
        }

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        if (is_dir($logDir) && is_writable($logDir)) {
            $timestampedLine = sprintf('%s %s%s', date('Y-m-d H:i:s'), $line, PHP_EOL);
            file_put_contents($logFile, $timestampedLine, FILE_APPEND);
        } elseif (function_exists('error_log')) {
            error_log($line);
        }
    }
}

function plugin_autoassigninternal_post_item_update(CommonDBTM $item) {
    if (!($item instanceof TicketTask)) {
        return;
    }

    $taskId = (int)$item->getID();
    plugin_autoassigninternal_log(sprintf('Tarefa %d atualizada.', $taskId));

    if (!isset($item->input) || !is_array($item->input)) {
        plugin_autoassigninternal_log('Nenhuma entrada disponível para a tarefa, atribuição ignorada.');
        return;
    }

    if (!isset($item->input['users_id'])) {
        plugin_autoassigninternal_log('Tarefa sem usuário atribuído, nada a fazer.');
        return;
    }

    $taskUserId = (int)$item->input['users_id'];
    if ($taskUserId <= 0) {
        plugin_autoassigninternal_log('Usuário atribuído inválido para a tarefa.');
        return;
    }

    $ticketId = 0;
    if (isset($item->fields) && isset($item->fields['tickets_id'])) {
        $ticketId = (int)$item->fields['tickets_id'];
    }
    if ($ticketId <= 0 && isset($item->input['tickets_id'])) {
        $ticketId = (int)$item->input['tickets_id'];
    }
    if ($ticketId <= 0) {
        plugin_autoassigninternal_log('Não foi possível identificar o chamado relacionado à tarefa.');
        return;
    }

    $config = PluginAutoassigninternalConfig::getInstance();
    $internalRequestTypeIds = $config->getInternalRequestTypeIds();
    if (empty($internalRequestTypeIds)) {
        plugin_autoassigninternal_log('Nenhum tipo de origem configurado para atribuição automática.');
        return;
    }

    $ticket = new Ticket();
    if (!$ticket->getFromDB($ticketId)) {
        plugin_autoassigninternal_log(sprintf('Chamado %d não encontrado.', $ticketId));
        return;
    }

    if (!isset($ticket->fields['requesttypes_id'])) {
        plugin_autoassigninternal_log(sprintf('Chamado %d sem tipo de origem definido.', $ticketId));
        return;
    }

    $ticketRequestTypeId = (int)$ticket->fields['requesttypes_id'];
    if (!in_array($ticketRequestTypeId, $internalRequestTypeIds, true)) {
        plugin_autoassigninternal_log(sprintf('Chamado %d com origem %d não está configurado para atribuição automática.', $ticketId, $ticketRequestTypeId));
        return;
    }

    $ticketUser = new Ticket_User();
    $assignType = CommonITILActor::ASSIGN;

    $existingAssignments = [];
    if (isset($ticket->fields['id'])) {
        $iterator = $ticketUser->find([
            'tickets_id' => $ticketId,
            'type'       => $assignType
        ]);

        if (is_array($iterator)) {
            $existingAssignments = $iterator;
        }
    }

    foreach ($existingAssignments as $assignment) {
        if ((int)$assignment['users_id'] === $taskUserId) {
            plugin_autoassigninternal_log(sprintf('Chamado %d já está atribuído ao usuário %d.', $ticketId, $taskUserId));
            return;
        }
    }

    $result = false;

    if (!empty($existingAssignments)) {
        $assignment = reset($existingAssignments);
        $assignmentId = isset($assignment['id']) ? (int)$assignment['id'] : (int)key($existingAssignments);

        $result = $ticketUser->update([
            'id'               => $assignmentId,
            'users_id'         => $taskUserId,
            'use_notification' => isset($assignment['use_notification']) ? $assignment['use_notification'] : 1
        ]);
    } else {
        $result = (bool)$ticketUser->add([
            'tickets_id'      => $ticketId,
            'users_id'        => $taskUserId,
            'type'            => $assignType,
            'use_notification'=> 1
        ]);
    }

    if ($result) {
        plugin_autoassigninternal_log(sprintf('Chamado %d atribuído automaticamente ao usuário %d.', $ticketId, $taskUserId));
    } else {
        plugin_autoassigninternal_log(sprintf('Falha ao adicionar o usuário %d como ator atribuído ao chamado %d.', $userId, $ticketId));
    }
}
