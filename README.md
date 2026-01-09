# Event Management Platform

A comprehensive web-based event management system with role-based access control, admin approval workflow, and email notifications.

##  Features

### For Attendees
- Browse approved events
- RSVP to events with capacity management
- Cancel RSVPs before event date
- Email confirmations for RSVPs
- View event details and organizer contact information

### For Organizers
- Create and manage events
- Upload event images
- Track attendee RSVPs
- View attendee lists
- Edit and delete events
- Email notifications for event status

### For Administrators
- Approve or reject events
- Manage users (view, change roles, delete)
- System statistics and analytics
- View recent activity
- User management dashboard

##  Security Features

- **CSRF Protection**: All forms protected against Cross-Site Request Forgery attacks
- **Password Security**: Strong password requirements (uppercase, lowercase, numbers)
- **Session Management**: Secure sessions with timeout and regeneration
- **Input Validation**: Comprehensive server-side validation
- **File Upload Security**: MIME type, extension, and size validation
- **SQL Injection Prevention**: PDO prepared statements throughout
- **XSS Protection**: All outputs sanitized with htmlspecialchars
- **Environment Variables**: Sensitive credentials stored in .env file

##  Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP extensions: PDO, PDO_MySQL, GD, mbstring

##  Installation

### 1. Clone or Download the Project

```bash
git clone <your-repo-url>
cd event_management_platform
```

### 2. Configure Environment

Copy the example environment file and configure it:

```bash
copy .env.example .env
```

Edit `.env` and update the database credentials:

```env
DB_HOST=localhost
DB_NAME=event_platform
DB_USER=root
DB_PASS=your_password
```

### 3. Create Database

Import the database schema:

```bash
mysql -u root -p < database/schema.sql
```

Or manually create the database:
1. Open phpMyAdmin or MySQL command line
2. Run the SQL file located at `database/schema.sql`

### 4. Configure Web Server

**For Apache:**

Ensure your document root points to the project directory and `.htaccess` is enabled.

**For XAMPP/WAMP:**

1. Place the project in `htdocs` folder
2. Access via `http://localhost/event_management_platform`

**For Production:**

Update `APP_URL` in `.env` to your domain name.

### 5. Set Permissions

Ensure the `uploads` directory is writable:

```bash
chmod 755 uploads
```

##  Default Credentials

After running the database schema, you can log in with:

**Admin Account:**
- Email: `admin@eventplatform.local`
- Password: `Admin@123`

**Organizer Account:**
- Email: `organizer@example.com`
- Password: `Organizer@123`

**Attendee Account:**
- Email: `attendee@example.com`
- Password: `Attendee@123`

> ⚠️ **Important:** Change these passwords immediately after first login!

## 📁 Project Structure

```
event_management_platform/
├── admin/                      # Admin panel files
│   ├── approve_events.php      # Event approval workflow
│   ├── manage_users.php        # User management
│   └── statistics.php          # System statistics
├── assets/
│   └── css/                    # Stylesheets
│       ├── admin.css
│       ├── attendee.css
│       ├── auth.css
│       ├── create_event.css
│       ├── edit_event.css
│       ├── organizer.css
│       └── view_attendees.css
├── attendee/                   # Attendee functionality
│   ├── rsvp.php               # RSVP to events
│   └── cancel_rsvp.php        # Cancel RSVP
├── auth/                       # Authentication
│   ├── login.php
│   └── register.php
├── database/                   # Database files
│   └── schema.sql             # Database schema
├── includes/                   # Shared utilities
│   ├── config.php             # Configuration loader
│   ├── csrf.php               # CSRF protection
│   ├── db.php                 # Database connection
│   ├── mailer.php             # Email notifications
│   ├── session.php            # Session management
│   └── validation.php         # Input validation
├── organizer/                  # Organizer functionality
│   ├── create_event.php       # Create events
│   ├── edit_event.php         # Edit events
│   ├── delete_event.php       # Delete events
│   └── view_attendees.php     # View attendee list
├── uploads/                    # Event images
├── .env                        # Environment configuration (not in git)
├── .env.example               # Environment template
├── .gitignore                 # Git ignore file
├── dashboard_admin.php        # Admin dashboard
├── dashboard_attendee.php     # Attendee dashboard
├── dashboard_organizer.php    # Organizer dashboard
├── index.php                  # Login page
├── logout.php                 # Logout handler
└── README.md                  # This file
```

##  Workflow

### Event Creation & Approval
1. Organizer creates an event → Status: **Pending**
2. Admin reviews and approves/rejects → Status: **Approved/Rejected**
3. Approved events become visible to attendees
4. Organizer receives email notification of approval/rejection

### RSVP Process
1. Attendee browses approved events
2. Attendee RSVPs (if capacity available)
3. RSVP confirmation email sent
4. Attendee can cancel RSVP before event date

##  Email Configuration

The system uses PHP's built-in `mail()` function. For production:

1. Configure SMTP in your server
2. Or update `includes/mailer.php` to use a service like SendGrid/Mailgun
3. Update email settings in `.env`:

```env
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=Your Event Platform
```

##  Configuration Options

Edit `.env` to customize:

| Setting | Description | Default |
|---------|-------------|---------|
| `DB_HOST` | Database host | localhost |
| `DB_NAME` | Database name | event_platform |
| `DB_USER` | Database username | root |
| `DB_PASS` | Database password | (empty) |
| `APP_ENV` | Environment (development/production) | development |
| `SESSION_LIFETIME` | Session timeout in seconds | 3600 |
| `MAX_FILE_SIZE` | Max upload size in bytes | 5242880 (5MB) |
| `PASSWORD_MIN_LENGTH` | Minimum password length | 8 |

##  Testing

### Manual Testing Checklist

**Authentication:**
- [ ] Register new user (organizer and attendee)
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Session timeout after inactivity
- [ ] Password strength validation

**Organizer:**
- [ ] Create event with image
- [ ] Edit event
- [ ] Delete event
- [ ] View attendee list

**Attendee:**
- [ ] Browse approved events only
- [ ] RSVP to event
- [ ] Cancel RSVP
- [ ] Cannot RSVP to full event
- [ ] Cannot RSVP to past event

**Admin:**
- [ ] Approve event
- [ ] Reject event
- [ ] Change user role
- [ ] Delete user
- [ ] View statistics

##  Troubleshooting

### Database Connection Failed
- Check `.env` credentials
- Ensure MySQL is running
- Verify database exists

### Images Not Displaying
- Check `uploads/` directory permissions (755)
- Verify image path in database
- Check file upload limits in `php.ini`

### Email Not Sending
- Verify server supports `mail()` function
- Check spam folder
- Configure SMTP for production

### Session Expired Immediately
- Check `SESSION_LIFETIME` in `.env`
- Verify server time is correct
- Clear browser cookies

##  Future Enhancements

- [ ] Event categories and tags
- [ ] Advanced search and filtering
- [ ] Pagination for large event lists
- [ ] Event calendar view
- [ ] QR code for event check-in
- [ ] Social media integration
- [ ] Event reminders
- [ ] Attendee feedback/ratings
- [ ] Export attendee lists (CSV/PDF)
- [ ] Multi-language support


##  Support

For issues or questions:
1. Check the troubleshooting section
2. Review the code comments
3. Check database schema

---

**Note:** This is a demonstration project showcasing web development skills including PHP, MySQL, security best practices, and role-based access control.
