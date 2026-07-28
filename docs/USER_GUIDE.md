# WCC User Guide

This guide is written for technicians, operators, supervisors, and managers using WCC on a daily basis.

## The Tickets Hub (Main Dashboard)

When you log in, you land on the **Tickets Hub**.

From here you can:
- Register a new event/ticket
- View active (open/pending) tickets
- Quickly resolve minor issues
- View closed event history
- Access overdue preventive maintenance alerts

## Registering a New Event / Ticket

1. Click **Register New Event** on the hub.
2. Select the equipment or production line.
3. Choose priority (normal / high / critical).
4. Describe the fault clearly.
5. Select who is announcing the issue (usually from the team directory).
6. Submit.

The ticket now appears in **Active Tickets**.

**Tip**: Be specific in the description — "Motor making grinding noise on left side" is much better than "machine broken".

## Working with Active Tickets

Go to **Active Tickets** from the hub or sidebar.

You will see:
- Ticket ID
- Equipment / Line
- Description
- Status (OPEN / PENDING)
- Priority
- Who announced it

### Taking Over a Ticket

1. Open the ticket.
2. Click **Takeover**.
3. You are now responsible for this intervention.

You can now log actions as you work.

### Logging Actions

While working on a ticket:
- Record start and end times.
- Note parts used.
- Add notes about what you did.
- Multiple actions can be logged on the same ticket.

Actions are the building blocks of your maintenance history and statistics.

### Closing a Ticket (Closeout)

When the work is finished:

1. Go to the ticket.
2. Click **Review / Close**.
3. Fill in the final details:
   - Total time spent
   - Parts consumed (these update inventory automatically)
   - Root cause notes (optional but very useful)
4. Change status to CLOSED.

The ticket moves to **Event History**.

## Preventive Maintenance (Work Orders & PM Calendar)

WCC helps you stay ahead of failures with scheduled work orders.

### Viewing the PM Calendar

Click **PM Calendar** in the sidebar.

- See all scheduled preventive maintenance.
- Overdue items are highlighted.
- Click a work order to take it over.

### Managing Work Orders

From the calendar or the Work Orders list:
- Take over a scheduled task.
- Update status (Scheduled → In Progress → Completed).
- Record time and parts used (same as tickets).
- Completed work orders feed into analytics.

**Tip for Supervisors**: Use the Admin Panel or App Settings to generate recurring PM schedules based on time or usage intervals.

## Equipment & Production Lines

### Browsing Assets

Go to **Equipment** (under Assets in the sidebar).

You can:
- See all machines and sub-assets.
- Filter by category or production line.
- View detailed asset information (purchase date, warranty, last PM, etc.).

### Production Lines

Production lines group related equipment under workshops/areas.

This hierarchy is used when registering tickets and viewing statistics.

## Inventory Management

Go to **Inventory** in the sidebar.

### Key Concepts
- **Parts Master**: Every spare part has an internal code, stock level, reorder threshold, lead times, etc.
- **Consumption**: Every time you use a part during a ticket or work order, it is recorded in the inventory ledger.
- **Reorder Logic**: When stock falls below the minimum threshold, the system can flag it for purchasing.

### Searching Parts
Use the search box to find parts by name or internal code quickly.

## Purchasing (Requests & Orders)

### Creating a Purchase Request

1. Go to **Purchase Requests**.
2. Fill in what you need, quantity, and justification.
3. Submit.

Supervisors can then review and convert approved requests into Purchase Orders.

### Purchase Orders

- Created from requests or directly.
- Sent to vendors.
- Track status through the full lifecycle (Draft → Issued → Received → Completed).
- When parts are received, inventory is automatically updated.

## Users & Your Profile

### My Profile

Click your name or the profile icon in the sidebar.

Here you can:
- Update your full name, email, phone.
- Change your password.
- View your role and permissions.

### For Administrators

See the **Admin Guide** for user creation, role management, and global settings.

## Reports & History

### Event History

All closed tickets are available in **Event History**.

Useful for:
- Finding repeat problems on the same asset.
- Reviewing past work for similar issues.
- Compliance and audits.

### Statistics / Analytics

Go to **Analytics** (Insights section).

You can see:
- Open vs closed tickets over time.
- Parts consumption.
- Technician workload.
- Mean Time To Repair (MTTR) trends.
- Overdue work orders.

These numbers help justify maintenance budgets and identify problem assets.

## Tips for Daily Use

- Be consistent with part numbers when logging consumption — it keeps your inventory accurate.
- Close tickets the same day when possible. It keeps data fresh.
- Use the search and filters — they are powerful.
- For minor fixes that don't need a full ticket, use **Instant Resolve**.
- Check the PM Calendar regularly — catching overdue items early saves downtime.

## Need Help?

- Hover over icons and buttons for tooltips.
- The **About WCC** modal (click the logo in the sidebar) gives a quick feature overview.
- Contact your administrator for permission or account issues.

For advanced users and developers, see the technical documentation in the `_ai_ctxt/` folder and `rest_api_core.md`.
