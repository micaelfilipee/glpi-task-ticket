<?php
/**
 * Plugin AutoAssignInternal - Hook Functions
 * 
 * @copyright Copyright (c) 2024
 * @license   MIT License
 */

/**
 * Hook called when a TicketTask is updated
 * 
 * @param CommonDBTM $item The TicketTask item being updated
 * @return void
 */
function plugin_autoassigninternal_item_update(CommonDBTM $item) {
   
   if (!($item instanceof TicketTask)) {
      return;
   }

   // Check if the task has a user assigned
   if (!isset($item->fields['users_id_tech']) || $item->fields['users_id_tech'] <= 0) {
      return;
   }

   // Get the ticket ID from the task
   if (!isset($item->fields['tickets_id']) || $item->fields['tickets_id'] <= 0) {
      return;
   }

   $tickets_id = $item->fields['tickets_id'];
   $users_id_tech = $item->fields['users_id_tech'];

   // Load the ticket
   $ticket = new Ticket();
   if (!$ticket->getFromDB($tickets_id)) {
      return;
   }

   // Get configured internal request type
   $config = new PluginAutoassigninternalConfig();
   $configData = $config->getConfig();
   
   if (!$configData || !isset($configData['internal_requesttype_id']) || $configData['internal_requesttype_id'] <= 0) {
      return;
   }

   $internal_requesttype_id = $configData['internal_requesttype_id'];

   // Check if ticket's request type matches the configured internal type
   if (!isset($ticket->fields['requesttypes_id']) || $ticket->fields['requesttypes_id'] != $internal_requesttype_id) {
      return;
   }

   // Check if the ticket already has this user assigned
   $ticket_user = new Ticket_User();
   $existing_assignment = $ticket_user->find([
      'tickets_id' => $tickets_id,
      'users_id' => $users_id_tech,
      'type' => CommonITILActor::ASSIGN
   ]);

   if (count($existing_assignment) > 0) {
      // User already assigned, no need to do anything
      return;
   }

   // Assign the user to the ticket
   $ticket_user_input = [
      'tickets_id' => $tickets_id,
      'users_id' => $users_id_tech,
      'type' => CommonITILActor::ASSIGN
   ];

   $ticket_user->add($ticket_user_input);
   
   // Log the action
   if (function_exists('Toolbox::logDebug')) {
      Toolbox::logDebug(sprintf(
         'AutoAssignInternal: Assigned user %d to ticket %d based on task assignment',
         $users_id_tech,
         $tickets_id
      ));
   }
}
