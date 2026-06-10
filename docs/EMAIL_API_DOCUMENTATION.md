# Email API Documentation

## Base URL
`/api/emails` for all email endpoints

---

## 1. List Emails
**GET** `/emails`

Retrieves a paginated list of emails with optional filtering.

**Query Parameters:**
- `followup_business_id` (optional): Filter by follow-up business ID
- `type` (optional): Filter by email type
- `created_by` (optional): Filter by creator user ID
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "Emails retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "followup_business_id": 1,
        "to": ["contact@example.com", "info@example.com"],
        "cc": ["manager@example.com"],
        "bcc": null,
        "type": "Follow-up",
        "created_by": 1,
        "created_at": "2026-04-28T10:30:00.000000Z",
        "updated_at": "2026-04-28T10:30:00.000000Z",
        "followupBusiness": {
          "id": 1,
          "name": "ABC Corporation"
        },
        "creator": {
          "id": 1,
          "first_name": "Admin",
          "last_name": "User"
        }
      }
    ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 1,
    "last_page_url": "...",
    "next_page_url": null,
    "path": "...",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

---

## 2. Get All Emails (Role-Based Access)
**GET** `/emails/all-emails`

Retrieves emails based on user role hierarchy with role-based access control.

**Hierarchy Logic:**
- **Admin:** Can see all emails created by any user
- **Manager + Lead Generation dept + has team:** Can see team members' emails + own
- **Executive + Lead Generation dept:** Can see only own emails
- **Default:** Only own emails for safety

**Query Parameters:**
- `type` (optional): Filter by email type
- `followup_business_id` (optional): Filter by follow-up business ID
- `date_from` (optional): Filter emails created from this date (YYYY-MM-DD)
- `date_to` (optional): Filter emails created up to this date (YYYY-MM-DD)
- `search` (optional): Search by business name
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "All emails retrieved successfully",
  "data": {
    "emails": {
      "current_page": 1,
      "data": [...],
      "total": 25
    },
    "user_role": {
      "id": 1,
      "name": "Admin User",
      "user_type": "admin",
      "roles": ["Admin"],
      "departments": ["Management"],
      "teams": []
    },
    "access_level": "admin"
  }
}
```

---

## 3. Get My Emails
**GET** `/emails/my-emails`

Retrieves emails created only by the authenticated user.

**Query Parameters:**
- `type` (optional): Filter by email type
- `followup_business_id` (optional): Filter by follow-up business ID
- `date_from` (optional): Filter emails created from this date (YYYY-MM-DD)
- `date_to` (optional): Filter emails created up to this date (YYYY-MM-DD)
- `search` (optional): Search by business name
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "message": "My emails retrieved successfully",
  "data": {
    "emails": {
      "current_page": 1,
      "data": [...],
      "total": 5
    },
    "created_by": 1,
    "user_name": "Admin User"
  }
}
```

---

## 4. Create Email
**POST** `/emails`

Creates a new email record and sends the email using the configured mail settings from `.env` file.

**Request Body:**
```json
{
  "followup_business_id": 1,
  "to": ["contact@example.com", "info@example.com"],
  "cc": ["manager@example.com"],
  "bcc": ["ceo@example.com"],
  "type": "Follow-up",
  "template": "<h1>Hello {contact_name}</h1><p>We are following up regarding {business_name}. Please contact us at {business_phone}.</p>",
  "dynamic_data": {
    "custom_field": "custom_value"
  }
}
```

**Validation Rules:**
- `followup_business_id`: required, exists in followup_businesses table
- `to`: required, array, minimum 1 item
- `to.*`: required, email format
- `cc`: nullable, array
- `cc.*`: nullable, email format
- `bcc`: nullable, array
- `bcc.*`: nullable, email format
- `type`: required, string, max 255 characters
- `template`: required, string (HTML or plain text from frontend, used only for sending email, not stored)
- `dynamic_data`: nullable, array of additional dynamic data for template (used only for sending email, not stored)

**Note:** The subject is static and set to "Follow-up Email" in the backend. The `template` and `dynamic_data` fields are only used for sending the email and are NOT stored in the database. The data is already available in the `followup_business` and `auth_persons` tables.

**Dynamic Data Available in Template:**
The following dynamic data is automatically available for use in the template (use `{key}` or `{{key}}` format):

**Business Data:**
- `business_name`: Company name
- `business_email`: Primary authorized person email (from auth person)
- `business_phone`: Primary authorized person phone/mobile (from auth person)
- `business_category`: Business category
- `business_type`: Business type
- `business_website`: Company website

**Contact Person Data:**
- `contact_name`: Full name of primary contact
- `contact_email`: Primary contact email
- `contact_phone`: Primary contact phone
- `contact_mobile`: Primary contact mobile
- `contact_job_title`: Contact person job title

**Custom Data:**
- Any additional key-value pairs provided in `dynamic_data` field

**Email Configuration:**
The email is sent using the mail configuration from `.env` file:
- `MAIL_FROM_ADDRESS`: Sender email address
- `MAIL_FROM_NAME`: Sender name
- `MAIL_MAILER`: Mail driver (smtp, mailgun, etc.)
- Other standard Laravel mail configuration

**Response:**
```json
{
  "success": true,
  "message": "Email sent successfully",
  "data": {
    "id": 1,
    "followup_business_id": 1,
    "to": ["contact@example.com", "info@example.com"],
    "cc": ["manager@example.com"],
    "bcc": ["ceo@example.com"],
    "type": "Follow-up",
    "created_by": 1,
    "created_at": "2026-04-28T10:30:00.000000Z",
    "updated_at": "2026-04-28T10:30:00.000000Z",
    "followupBusiness": {
      "id": 1,
      "name": "ABC Corporation"
    },
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    }
  }
}
```

**Template Example (from Frontend):**
```html
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <h2 style="color: #333;">Follow-up for {business_name}</h2>
  <p>Dear {contact_name},</p>
  <p>Hope you are doing well. We wanted to follow up regarding our previous discussion.</p>
  <p><strong>Company Details:</strong></p>
  <ul>
    <li>Name: {business_name}</li>
    <li>Category: {business_category}</li>
    <li>Type: {business_type}</li>
    <li>Phone: {business_phone}</li>
    <li>Email: {business_email}</li>
    <li>Website: {business_website}</li>
  </ul>
  <p>Please feel free to contact us at {business_phone} or email us at {business_email}.</p>
  <p>Best regards,<br>Your Company Team</p>
</div>
```

**Error Response:**
```json
{
  "success": false,
  "message": "Failed to send email: Connection to smtp server failed",
  "data": {
    "error": "Detailed error message (in debug mode only)"
  }
}
```

---

## 5. Get Email Details
**GET** `/emails/{id}`

Retrieves details of a specific email.

**Response:**
```json
{
  "success": true,
  "message": "Email retrieved successfully",
  "data": {
    "id": 1,
    "followup_business_id": 1,
    "to": ["contact@example.com", "info@example.com"],
    "cc": ["manager@example.com"],
    "bcc": ["ceo@example.com"],
    "type": "Follow-up",
    "created_by": 1,
    "created_at": "2026-04-28T10:30:00.000000Z",
    "updated_at": "2026-04-28T10:30:00.000000Z",
    "followupBusiness": {
      "id": 1,
      "name": "ABC Corporation"
    },
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    }
  }
}
```

---

## 6. Update Email
**PUT** `/emails/{id}`

Updates an existing email record.

**Request Body:**
```json
{
  "followup_business_id": 2,
  "to": ["newcontact@example.com"],
  "cc": ["newmanager@example.com"],
  "bcc": null,
  "type": "Updated Follow-up"
}
```

**Validation Rules:**
- `followup_business_id`: sometimes required, exists in followup_businesses table
- `to`: sometimes required, array, minimum 1 item
- `to.*`: required, email format
- `cc`: nullable, array
- `cc.*`: nullable, email format
- `bcc`: nullable, array
- `bcc.*`: nullable, email format
- `type`: sometimes required, string, max 255 characters

**Response:**
```json
{
  "success": true,
  "message": "Email updated successfully",
  "data": {
    "id": 1,
    "followup_business_id": 2,
    "to": ["newcontact@example.com"],
    "cc": ["newmanager@example.com"],
    "bcc": null,
    "type": "Updated Follow-up",
    "created_by": 1,
    "created_at": "2026-04-28T10:30:00.000000Z",
    "updated_at": "2026-04-28T10:45:00.000000Z",
    "followupBusiness": {
      "id": 2,
      "name": "New Company"
    },
    "creator": {
      "id": 1,
      "first_name": "Admin",
      "last_name": "User"
    }
  }
}
```

---

## 7. Delete Email
**DELETE** `/emails/{id}`

Deletes an email record.

**Response:**
```json
{
  "success": true,
  "message": "Email deleted successfully",
  "data": null
}
```

---

## Database Schema

### Emails Table
```sql
CREATE TABLE emails (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    followup_business_id BIGINT NOT NULL,
    to JSON NOT NULL,
    cc JSON NULL,
    bcc JSON NULL,
    type VARCHAR(255) NOT NULL,
    created_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (followup_business_id) REFERENCES followup_businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## Relationships

- Email belongs to FollowupBusiness
- Email belongs to User (creator)

---

## Permissions

### Email Module
- **Read:** `Email,read`
- **Create:** `Email,create`
- **Update:** `Email,update`
- **Delete:** `Email,delete`

---

## Role-Based Access Details

### Access Levels
1. **Admin**: Full access to all emails regardless of creator
2. **Manager** (Lead Generation dept + has team): Can see emails from team members + own
3. **Executive** (Lead Generation dept): Can see only own emails
4. **Default**: Most restrictive - only own emails

### Department Requirements
- **Lead Generation**: Required for Manager and Executive access levels
- **Team Membership**: Required for Manager access level

### Role Detection
- Checks both `user_type` and assigned `roles`
- Supports variations: Admin/SuperAdmin, Manager/Team Manager, Executive/Sales Executive
- Department name variations: "Lead Generation", "lead_generation", "leadgeneration"

---

## SMTP setup and troubleshooting

### Brevo SMTP (`smtp-relay.brevo.com`) — current setup

If you use **Brevo** (Sendinblue), `535 Authentication failed` almost always means **`MAIL_USERNAME` is wrong**.

| `.env` key | Correct value |
|------------|----------------|
| `MAIL_HOST` | `smtp-relay.brevo.com` |
| `MAIL_PORT` | `587` (STARTTLS) or `465` (SSL) |
| `MAIL_USERNAME` | **Brevo SMTP login email** from [Brevo → Settings → SMTP & API](https://app.brevo.com/settings/keys/smtp) — **not** `admin@fastranking.net` |
| `MAIL_PASSWORD` | Brevo **SMTP key** (`xsmtpsib-...`) — not your Brevo account password |
| `MAIL_FROM_ADDRESS` | `admin@fastranking.net` (sender — must be verified as sender in Brevo) |
| `MAIL_EHLO_DOMAIN` | `fastranking.net` (optional, avoids `HELO localhost`) |

Example:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=you@your-brevo-signup-email.com
MAIL_PASSWORD=xsmtpsib-your-smtp-key-here
MAIL_EHLO_DOMAIN=fastranking.net
MAIL_FROM_ADDRESS=admin@fastranking.net
MAIL_FROM_NAME="Fast Ranking"
```

Do **not** set `MAIL_ENCRYPTION=ssl` on port 587. Use port `587` with no scheme override, or port `465` for SSL.

### cPanel / hosting mailbox (non-Brevo)

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.fastranking.net
MAIL_PORT=587
MAIL_USERNAME=admin@fastranking.net
MAIL_PASSWORD="your_actual_mailbox_password"
MAIL_FROM_ADDRESS=admin@fastranking.net
MAIL_FROM_NAME="Fastranking CRM"
```

Use the SMTP host and port from your hosting panel (cPanel / Plesk). Common pairs:

| Port | Encryption |
|------|------------|
| `587` | STARTTLS (TLS) |
| `465` | SSL (`MAIL_SCHEME` auto-uses `smtps` on port 465) |

After any `.env` change:

```bash
php artisan config:clear
php artisan cache:clear
```

### Error `535 5.7.8 Authentication failed`

The server rejected the username or password. This is **not** fixed in PHP code — fix credentials in `.env`.

1. **Use the real mailbox password** — the same password you use in Outlook / webmail. SMTP does **not** accept MD5 or any hashed password; only the plain mailbox password works.

2. **Password contains `%`, `#`, `$`, `@`, or spaces** — `.env` may truncate or corrupt the value. Always wrap the password in **double quotes**:

   ```env
   MAIL_PASSWORD="MyPass%word#2024"
   ```

   Without quotes, `#` starts a comment and everything after it is ignored. Unquoted `%` can also be parsed incorrectly.

3. **`MAIL_USERNAME`** must be the full email address: `admin@fastranking.net` (not just `admin`).

4. **`MAIL_FROM_ADDRESS`** should match the authenticated mailbox (`admin@fastranking.net`) on many hosts.

5. Confirm the account exists and SMTP is enabled in hosting email settings.

6. If 2FA is enabled on the mailbox, use an **app-specific password** from the provider, not the login password.

### Verify config (without printing the password)

```bash
php artisan tinker
>>> config('mail.mailers.smtp.username');
>>> strlen(config('mail.mailers.smtp.password'));
```

Compare password length with what you expect. If it is shorter than your real password, fix quoting in `.env` and run `config:clear` again.

---

## Notes

- All email fields (`to`, `cc`, `bcc`) are stored as JSON arrays to support multiple email addresses
- Email validation is performed on each email address in the arrays
- The `created_by` field is automatically set to the authenticated user's ID
- All endpoints are protected by JWT authentication middleware
- Email records are soft-deleted through Laravel's standard delete mechanism
- Role-based access control ensures users can only see emails they're authorized to view
- Comprehensive logging for access control and debugging
