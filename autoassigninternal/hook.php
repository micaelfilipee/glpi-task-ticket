<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/inc/config.class.php';

if (!function_exists('plugin_autoassigninternal_log')) {
    function plugin_autoassigninternal_log($message) {
        Toolbox::logInFile('autoassigninternal', '[AutoAssignInternal] ' . $message);
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

    $assignmentField = 'users_id';
    if (!isset($ticket->fields[$assignmentField]) && isset($ticket->fields['users_id_assign'])) {
        $assignmentField = 'users_id_assign';
    }

    $currentTicketUserId = 0;
    if (isset($ticket->fields[$assignmentField])) {
        $currentTicketUserId = (int)$ticket->fields[$assignmentField];
    }

    if ($currentTicketUserId === $taskUserId) {
        plugin_autoassigninternal_log(sprintf('Chamado %d já está atribuído ao usuário %d.', $ticketId, $taskUserId));
        return;
    }

    $updateInput = [
        'id'             => $ticketId,
        $assignmentField => $taskUserId
    ];

    if ($ticket->update($updateInput)) {
        plugin_autoassigninternal_log(sprintf('Chamado %d atribuído automaticamente ao usuário %d.', $ticketId, $taskUserId));
    } else {
        plugin_autoassigninternal_log(sprintf('Falha ao atribuir automaticamente o chamado %d ao usuário %d.', $ticketId, $taskUserId));
    }
}
