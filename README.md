# Auto Assign Internal - GLPI Plugin

## Description

A GLPI 9.5.5 plugin that automatically assigns ticket users based on task assignments for internal request types.

## Features

- Intercepts updates to TicketTask (using GLPI hooks)
- Checks if a task has a user assigned (`users_id_tech`)
- Gets the linked ticket via `tickets_id`
- Verifies if the ticket's RequestType matches the configured "Internal Request Type"
- Automatically assigns the task's user to the ticket
- CSRF compliant for security
- Configuration UI to select the internal RequestType

## Requirements

- GLPI >= 9.5.0 and < 10.0.0
- PHP >= 7.2

## Installation

1. Extract the plugin archive to the `/plugins` directory of your GLPI installation
2. The plugin folder should be named `autoassigninternal`
3. Go to **Setup > Plugins** in GLPI interface
4. Click **Install** on the "Auto Assign Internal" plugin
5. Click **Activate** to enable the plugin

## Configuration

1. Go to **Setup > General** in GLPI
2. Click on the **Auto Assign Internal** tab
3. Select the RequestType that represents "Internal Request" (e.g., "Formulário Chamado Interno")
4. Click **Save**

## How It Works

When a task is updated and assigned to a user:
1. The plugin checks if the task has a user assigned (`users_id_tech`)
2. It retrieves the associated ticket
3. If the ticket's RequestType matches the configured internal type
4. The plugin automatically assigns the task's user to the ticket (if not already assigned)

## Files Structure

```
autoassigninternal/
├── setup.php                  # Plugin setup and metadata
├── hook.php                   # Hook implementation for task updates
├── front/
│   └── config.form.php       # Configuration form handler
├── inc/
│   └── config.class.php      # Configuration class
└── locales/
    ├── en_GB.php             # English localization
    └── pt_BR.php             # Portuguese (Brazil) localization
```

## License

MIT License

## Support

For issues and feature requests, please use the GitHub issue tracker.