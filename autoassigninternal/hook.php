<?php

if (!defined('GLPI_ROOT')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/inc/config.class.php';

function plugin_autoassigninternal_post_item_update(CommonDBTM $item) {
    if (!($item instanceof TicketTask)) {
        return;
    }

    if (!isset($item->input) || !is_array($item->input)) {
        return;
    }

    if (!isset($item->input['users_id'])) {
        return;
    }

    $taskUserId = (int)$item->input['users_id'];
    if ($taskUserId <= 0) {
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
        return;
    }

    $config = PluginAutoassigninternalConfig::getInstance();
    $internalRequestTypeId = $config->getInternalRequestTypeId();
    if ($internalRequestTypeId <= 0) {
        return;
    }

    $ticket = new Ticket();
    if (!$ticket->getFromDB($ticketId)) {
        return;
    }

    if (!isset($ticket->fields['requesttypes_id']) || (int)$ticket->fields['requesttypes_id'] !== (int)$internalRequestTypeId) {
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
        return;
    }

    $updateInput = [
        'id'             => $ticketId,
        $assignmentField => $taskUserId
    ];

    $ticket->update($updateInput);
}
