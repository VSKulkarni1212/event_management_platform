<?php
/**
 * Email Notification Functions
 * Uses PHP's built-in mail() function
 */

/**
 * Send email notification
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @return bool True if sent successfully
 */
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">" . "\r\n";
    $headers .= "Reply-To: " . MAIL_FROM_ADDRESS . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send RSVP confirmation email
 * @param string $email Attendee email
 * @param string $name Attendee name
 * @param array $event Event details
 * @return bool
 */
function sendRSVPConfirmation($email, $name, $event) {
    $subject = "RSVP Confirmation - " . $event['title'];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .event-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>RSVP Confirmed!</h1>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                <p>Your RSVP has been confirmed for the following event:</p>
                
                <div class='event-details'>
                    <h2>" . htmlspecialchars($event['title']) . "</h2>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($event['date'])) . "</p>
                    <p><strong>Location:</strong> " . htmlspecialchars($event['location']) . "</p>
                    <p><strong>Description:</strong> " . htmlspecialchars($event['description']) . "</p>
                </div>
                
                <p>We look forward to seeing you there!</p>
                <p>If you need to cancel your RSVP, please log in to your account.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from " . APP_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * Send RSVP cancellation email
 * @param string $email Attendee email
 * @param string $name Attendee name
 * @param array $event Event details
 * @return bool
 */
function sendRSVPCancellation($email, $name, $event) {
    $subject = "RSVP Cancelled - " . $event['title'];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f44336; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .event-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #f44336; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>RSVP Cancelled</h1>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                <p>Your RSVP has been cancelled for the following event:</p>
                
                <div class='event-details'>
                    <h2>" . htmlspecialchars($event['title']) . "</h2>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($event['date'])) . "</p>
                    <p><strong>Location:</strong> " . htmlspecialchars($event['location']) . "</p>
                </div>
                
                <p>You can RSVP again anytime before the event if you change your mind.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from " . APP_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * Send event creation notification to organizer
 * @param string $email Organizer email
 * @param string $name Organizer name
 * @param array $event Event details
 * @return bool
 */
function sendEventCreatedNotification($email, $name, $event) {
    $subject = "Event Created - " . $event['title'];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .event-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Event Created Successfully!</h1>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                <p>Your event has been created and is now pending admin approval:</p>
                
                <div class='event-details'>
                    <h2>" . htmlspecialchars($event['title']) . "</h2>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($event['date'])) . "</p>
                    <p><strong>Location:</strong> " . htmlspecialchars($event['location']) . "</p>
                    <p><strong>Max Attendees:</strong> " . $event['max_attendees'] . "</p>
                </div>
                
                <p>Once approved by an administrator, your event will be visible to attendees.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from " . APP_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * Send event approval notification to organizer
 * @param string $email Organizer email
 * @param string $name Organizer name
 * @param array $event Event details
 * @return bool
 */
function sendEventApprovedNotification($email, $name, $event) {
    $subject = "Event Approved - " . $event['title'];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .event-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Event Approved!</h1>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                <p>Great news! Your event has been approved and is now visible to attendees:</p>
                
                <div class='event-details'>
                    <h2>" . htmlspecialchars($event['title']) . "</h2>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($event['date'])) . "</p>
                    <p><strong>Location:</strong> " . htmlspecialchars($event['location']) . "</p>
                </div>
                
                <p>Attendees can now RSVP for your event!</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from " . APP_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * Send event rejection notification to organizer
 * @param string $email Organizer email
 * @param string $name Organizer name
 * @param array $event Event details
 * @param string $reason Rejection reason
 * @return bool
 */
function sendEventRejectedNotification($email, $name, $event, $reason = '') {
    $subject = "Event Not Approved - " . $event['title'];
    
    $reasonText = $reason ? "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>" : "";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f44336; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .event-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #f44336; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Event Not Approved</h1>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                <p>Unfortunately, your event was not approved:</p>
                
                <div class='event-details'>
                    <h2>" . htmlspecialchars($event['title']) . "</h2>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($event['date'])) . "</p>
                    " . $reasonText . "
                </div>
                
                <p>Please contact the administrator if you have any questions.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from " . APP_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}
?>
