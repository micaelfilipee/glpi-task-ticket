# Installation Guide - Auto Assign Internal Plugin

## Prerequisites

- GLPI version 9.5.0 to 9.5.99
- PHP 7.2 or higher
- MySQL or MariaDB database
- Administrator access to GLPI

## Installation Steps

### 1. Download and Extract

Download the plugin and extract it to your GLPI plugins directory:

```bash
cd /path/to/glpi/plugins/
unzip autoassigninternal.zip
```

Or if cloning from repository:

```bash
cd /path/to/glpi/plugins/
git clone https://github.com/micaelfilipee/glpi-task-ticket.git autoassigninternal
```

### 2. Set Permissions

Ensure proper permissions:

```bash
cd /path/to/glpi/plugins/
chown -R www-data:www-data autoassigninternal
chmod -R 755 autoassigninternal
```

### 3. Install via GLPI Interface

1. Log in to GLPI as an administrator
2. Go to **Setup** > **Plugins**
3. Find "Auto Assign Internal" in the plugin list
4. Click the **Install** button
5. After installation completes, click **Activate**

### 4. Configure the Plugin

1. Go to **Setup** > **General**
2. Click on the **Auto Assign Internal** tab
3. Select the RequestType that represents internal requests (e.g., "Formulário Chamado Interno")
4. Click **Save**

## Verification

To verify the plugin is working:

1. Create a ticket with the configured internal RequestType
2. Create a task on that ticket
3. Assign a technician to the task
4. The technician should automatically be assigned to the ticket

## Troubleshooting

### Plugin doesn't appear in plugin list

- Check that the plugin folder is named exactly `autoassigninternal`
- Verify file permissions are correct
- Check GLPI logs in `files/_log/` for errors

### Configuration page doesn't show

- Ensure you're logged in with administrator rights
- Check that the plugin is activated, not just installed

### Auto-assignment not working

- Verify the RequestType is correctly configured
- Check that the ticket has the correct RequestType set
- Ensure the task has a technician assigned (`users_id_tech`)
- Review GLPI logs for any errors

## Uninstallation

To remove the plugin:

1. Go to **Setup** > **Plugins**
2. Click **Deactivate** on Auto Assign Internal
3. Click **Uninstall**
4. Optionally, remove the plugin folder:

```bash
rm -rf /path/to/glpi/plugins/autoassigninternal
```

**Note:** Uninstalling will remove all plugin configuration data from the database.

## Database Tables

The plugin creates the following table:

- `glpi_plugin_autoassigninternal_configs` - Stores plugin configuration

This table is automatically removed during uninstallation.
