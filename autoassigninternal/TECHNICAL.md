# Technical Documentation - Auto Assign Internal Plugin

## Overview

This plugin automatically assigns ticket users based on task technician assignments for tickets with a specific internal RequestType.

## Architecture

### Plugin Flow

```
1. User creates/updates a TicketTask and assigns a technician
   ↓
2. GLPI triggers 'item_update' hook
   ↓
3. plugin_autoassigninternal_item_update() is called
   ↓
4. Plugin checks:
   - Is task assigned to a technician? (users_id_tech)
   - Does task have a linked ticket? (tickets_id)
   - Does ticket exist?
   ↓
5. Plugin loads configuration
   ↓
6. Plugin checks:
   - Is internal RequestType configured?
   - Does ticket's RequestType match configured type?
   ↓
7. Plugin checks if user is already assigned to ticket
   ↓
8. If all checks pass: Assign technician to ticket
```

### Database Schema

#### Configuration Table: `glpi_plugin_autoassigninternal_configs`

| Column                    | Type    | Description                          |
|---------------------------|---------|--------------------------------------|
| id                        | int(11) | Primary key                          |
| internal_requesttype_id   | int(11) | ID of the internal RequestType       |

### Hook Implementation

The plugin uses GLPI's `item_update` hook to intercept TicketTask updates:

```php
$PLUGIN_HOOKS['item_update']['autoassigninternal'] = [
   'TicketTask' => 'plugin_autoassigninternal_item_update'
];
```

**Hook Function**: `plugin_autoassigninternal_item_update(CommonDBTM $item)`

**Triggered**: When a TicketTask is updated

**Logic**:
1. Validates item is TicketTask instance
2. Checks for `users_id_tech` (technician assigned to task)
3. Gets `tickets_id` from task
4. Loads associated Ticket
5. Retrieves plugin configuration
6. Compares ticket's `requesttypes_id` with configured internal type
7. Checks for existing assignment in `glpi_tickets_users`
8. Creates new assignment if needed

### Security Features

#### CSRF Protection

```php
$PLUGIN_HOOKS['csrf_compliant']['autoassigninternal'] = true;
```

All forms include CSRF token validation:

```php
// Token generation
Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

// Token validation
Session::checkCSRF($_POST);
```

#### Access Control

Configuration page requires `config` right with UPDATE permission:

```php
if (Session::haveRight('config', UPDATE)) {
   // Allow access
}
```

### Classes

#### PluginAutoassigninternalConfig

**Purpose**: Manages plugin configuration

**Key Methods**:

- `getConfig()` - Retrieves current configuration from database
- `updateConfig($input)` - Updates configuration
- `showConfigForm()` - Displays configuration UI
- `getTabNameForItem()` - Adds tab to Config page
- `displayTabContentForItem()` - Displays tab content

### Files Structure

```
autoassigninternal/
├── setup.php                      # Plugin bootstrap & registration
│   ├── plugin_init_autoassigninternal()
│   ├── plugin_version_autoassigninternal()
│   ├── plugin_autoassigninternal_check_prerequisites()
│   ├── plugin_autoassigninternal_install()
│   └── plugin_autoassigninternal_uninstall()
│
├── hook.php                       # Hook implementation
│   └── plugin_autoassigninternal_item_update()
│
├── inc/
│   └── config.class.php          # Configuration management
│       └── PluginAutoassigninternalConfig
│
├── front/
│   └── config.form.php           # Configuration form handler
│
└── locales/                      # Translations
    ├── en_GB.php
    └── pt_BR.php
```

## Integration Points

### GLPI Objects Used

1. **TicketTask** - Task object being monitored
   - Fields: `users_id_tech`, `tickets_id`

2. **Ticket** - Main ticket object
   - Fields: `requesttypes_id`

3. **Ticket_User** - Ticket-User association
   - Fields: `tickets_id`, `users_id`, `type`

4. **RequestType** - Request type definition
   - Used in configuration dropdown

5. **CommonITILActor** - Actor type constants
   - Used: `CommonITILActor::ASSIGN`

### Database Tables

- `glpi_tickettasks` - Task data (read)
- `glpi_tickets` - Ticket data (read)
- `glpi_tickets_users` - User assignments (read/write)
- `glpi_requesttypes` - Request types (read)
- `glpi_plugin_autoassigninternal_configs` - Plugin config (read/write)

## Configuration

### Setup Steps

1. Install and activate plugin
2. Go to Setup > General > Auto Assign Internal tab
3. Select the RequestType that represents internal requests
4. Save configuration

### Configuration Options

- **Internal Request Type**: Dropdown of all available RequestTypes in GLPI

## Error Handling

The plugin uses defensive programming:

- Early returns on validation failures
- No exceptions thrown (silent failures)
- Optional logging via `Toolbox::logDebug()`

## Performance Considerations

- Minimal database queries (2-3 per task update)
- Early exit on validation failures
- No recursive operations
- Single assignment check prevents duplicates

## Compatibility

- GLPI: 9.5.0 to 9.5.99
- PHP: 7.2+
- Database: MySQL/MariaDB (any version compatible with GLPI 9.5)

## Debugging

Enable GLPI debug mode and check logs:

```php
// In config/config_db.php
$CFG_GLPI['debug_sql'] = true;
$CFG_GLPI['debug_vars'] = true;
```

Log location: `files/_log/sql-errors.log`

Plugin logs: Look for "AutoAssignInternal:" prefix

## Future Enhancements

Potential improvements:

1. Support for multiple internal RequestTypes
2. Email notifications on auto-assignment
3. Configuration history/audit log
4. UI to view recent auto-assignments
5. Support for group assignments
6. Conditional rules engine
