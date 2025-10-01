<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/inc/config.class.php';
require_once __DIR__ . '/inc/logging.php';

function plugin_autoassigninternal_post_item_add(CommonDBTM $item) {
    plugin_autoassigninternal_handle_task_event($item, 'add');
}

function plugin_autoassigninternal_post_item_update(CommonDBTM $item) {
    plugin_autoassigninternal_handle_task_event($item, 'update');
}

function plugin_autoassigninternal_handle_task_event(CommonDBTM $item, $event) {
    if (!($item instanceof TicketTask)) {
        return;
    }

    $taskId = (int)$item->getID();
    plugin_autoassigninternal_log(sprintf('Processando tarefa %d após %s.', $taskId, $event));

    $taskUserId = plugin_autoassigninternal_resolve_task_user($item);
    if ($taskUserId <= 0) {
        plugin_autoassigninternal_log('Não foi possível identificar o técnico atribuído à tarefa.');
        return;
    }

    $ticketId = plugin_autoassigninternal_resolve_ticket_id($item);
    if ($ticketId <= 0) {
        plugin_autoassigninternal_log('Não foi possível identificar o chamado relacionado à tarefa.');
        return;
    }

    plugin_autoassigninternal_log(sprintf('Tarefa %d vinculada ao chamado %d com técnico %d.', $taskId, $ticketId, $taskUserId));

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

    $ticketRequestTypeId = (int)($ticket->fields['requesttypes_id'] ?? 0);
    if ($ticketRequestTypeId <= 0) {
        plugin_autoassigninternal_log(sprintf('Chamado %d sem tipo de origem definido.', $ticketId));
        return;
    }

    if (!in_array($ticketRequestTypeId, $internalRequestTypeIds, true)) {
        plugin_autoassigninternal_log(sprintf('Chamado %d com origem %d não está configurado para atribuição automática (configurados: %s).', $ticketId, $ticketRequestTypeId, implode(', ', $internalRequestTypeIds)));
        return;
    }

    $ticketUser = new Ticket_User();
    $assignType = CommonITILActor::ASSIGN;
    $existingAssignments = $ticketUser->find([
        'tickets_id' => $ticketId,
        'type'       => $assignType
    ], 'id ASC');

    if (is_array($existingAssignments)) {
        foreach ($existingAssignments as $assignment) {
            if ((int)$assignment['users_id'] === $taskUserId) {
                plugin_autoassigninternal_log(sprintf('Chamado %d já está atribuído ao usuário %d.', $ticketId, $taskUserId));
                return;
            }
        }
    } else {
        $existingAssignments = [];
    }

    $result = false;

    if (!empty($existingAssignments)) {
        $assignment    = reset($existingAssignments);
        $assignmentKey = key($existingAssignments);
        $assignmentId  = 0;

        if (isset($assignment['id'])) {
            $assignmentId = (int)$assignment['id'];
        }

        if ($assignmentId <= 0 && $assignmentKey !== null) {
            $assignmentId = (int)$assignmentKey;
        }

        if ($assignmentId <= 0) {
            plugin_autoassigninternal_log(sprintf('Não foi possível determinar o registro de atribuição existente para o chamado %d.', $ticketId));
            return;
        }

        $updateData = [
            'id'               => $assignmentId,
            'tickets_id'       => $ticketId,
            'type'             => $assignType,
            'users_id'         => $taskUserId,
            'use_notification' => isset($assignment['use_notification']) ? (int)$assignment['use_notification'] : 1
        ];

        $result = (bool)$ticketUser->update($updateData);
        if ($result) {
            plugin_autoassigninternal_log(sprintf('Chamado %d atualizado para o usuário %d (registro %d).', $ticketId, $taskUserId, $assignmentId));
        }
    } else {
        $addData = [
            'tickets_id'       => $ticketId,
            'users_id'         => $taskUserId,
            'type'             => $assignType,
            'use_notification' => 1
        ];

        $result = (bool)$ticketUser->add($addData);
        if ($result) {
            plugin_autoassigninternal_log(sprintf('Chamado %d atribuído automaticamente ao usuário %d (novo registro).', $ticketId, $taskUserId));
        }
    }

    if (!$result) {
        plugin_autoassigninternal_log(sprintf('Falha ao sincronizar a atribuição do chamado %d para o usuário %d.', $ticketId, $taskUserId));
    }
}

function plugin_autoassigninternal_resolve_task_user(TicketTask $task) {
    $candidates = [];

    if (isset($task->fields['users_id_tech'])) {
        $candidates[] = $task->fields['users_id_tech'];
    }
    if (isset($task->input['users_id_tech'])) {
        $candidates[] = $task->input['users_id_tech'];
    }
    if (isset($task->fields['users_id'])) {
        $candidates[] = $task->fields['users_id'];
    }
    if (isset($task->input['users_id'])) {
        $candidates[] = $task->input['users_id'];
    }

    foreach ($candidates as $candidate) {
        $candidate = (int)$candidate;
        if ($candidate > 0) {
            return $candidate;
        }
    }

    $taskId = (int)$task->getID();
    if ($taskId > 0) {
        $freshTask = new TicketTask();
        if ($freshTask->getFromDB($taskId)) {
            $candidate = (int)($freshTask->fields['users_id_tech'] ?? 0);
            if ($candidate > 0) {
                return $candidate;
            }
        }
    }

    return 0;
}

function plugin_autoassigninternal_resolve_ticket_id(TicketTask $task) {
    $candidates = [];

    if (isset($task->fields['tickets_id'])) {
        $candidates[] = $task->fields['tickets_id'];
    }
    if (isset($task->input['tickets_id'])) {
        $candidates[] = $task->input['tickets_id'];
    }

    foreach ($candidates as $candidate) {
        $candidate = (int)$candidate;
        if ($candidate > 0) {
            return $candidate;
        }
    }

    $taskId = (int)$task->getID();
    if ($taskId > 0) {
        $freshTask = new TicketTask();
        if ($freshTask->getFromDB($taskId)) {
            $candidate = (int)($freshTask->fields['tickets_id'] ?? 0);
            if ($candidate > 0) {
                return $candidate;
            }
        }
    }

    return 0;
}
