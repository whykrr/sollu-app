---
paths:
  - 'app/{Notifications,Services/App/Notification,Console/Commands}/**'
---

# Commands

## Send billing and subscription notifications exclusively to root user
Billing renewal, subscription alerts, and payment activation notifications must be sent directly to the business root user (is_root_user = true) using NotificationDispatcherService::sendToRootUser(), instead of looping all business users or generic staff.
