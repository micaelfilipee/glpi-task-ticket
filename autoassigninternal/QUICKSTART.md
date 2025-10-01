# Quick Start Guide

## What does this plugin do?

Automatically assigns technicians to tickets when they are assigned to tasks, but **only** for internal request types.

## 5-Minute Setup

### 1. Install Plugin
- Go to **Setup** → **Plugins**
- Find "Auto Assign Internal"
- Click **Install**, then **Activate**

### 2. Configure
- Go to **Setup** → **General**
- Click **Auto Assign Internal** tab
- Select your internal RequestType (e.g., "Formulário Chamado Interno")
- Click **Save**

### 3. Done!
The plugin is now active.

## How to Use

### Example Workflow

**Before the plugin:**
1. Create ticket with RequestType = "Formulário Chamado Interno"
2. Create task on ticket
3. Assign technician "John" to task
4. **Manually** assign "John" to ticket ❌

**After the plugin:**
1. Create ticket with RequestType = "Formulário Chamado Interno"
2. Create task on ticket
3. Assign technician "John" to task
4. Plugin **automatically** assigns "John" to ticket ✅

### When Does Auto-Assignment Happen?

✅ **Yes** - Ticket has configured internal RequestType  
✅ **Yes** - Task is assigned to a technician  
✅ **Yes** - Ticket exists and is valid  

❌ **No** - Ticket has different RequestType  
❌ **No** - Task has no technician assigned  
❌ **No** - Technician already assigned to ticket  

## Common Questions

**Q: What if I don't want a technician assigned to the ticket?**  
A: Don't assign them to the task, or use a different RequestType.

**Q: Can I use this for external tickets?**  
A: No, only for the configured internal RequestType.

**Q: What happens if the technician is already assigned?**  
A: Nothing. The plugin prevents duplicate assignments.

**Q: Can I assign multiple technicians?**  
A: Yes, each technician assigned to a task will be added to the ticket.

## Troubleshooting

### Auto-assignment not working?

Check these:
- [ ] Plugin is **activated** (not just installed)
- [ ] Internal RequestType is **configured**
- [ ] Ticket has the **correct RequestType**
- [ ] Task has a **technician assigned**
- [ ] Technician is not **already assigned** to ticket

### Can't see configuration page?

- You need **administrator** rights to access configuration
- Go to **Setup** → **General** → **Auto Assign Internal** tab

## Need Help?

- Check [INSTALL.md](INSTALL.md) for detailed installation
- Check [TECHNICAL.md](TECHNICAL.md) for technical details
- Report issues on GitHub

## Version

Plugin Version: 1.0.0  
Compatible with: GLPI 9.5.x
