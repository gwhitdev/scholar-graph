<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Reply</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #1a1a1a;">Staff Reply on Your Ticket</h2>

    <p>Hello {{ $ticket->user->name }},</p>

    <p>A staff member has replied to your support ticket: <strong>{{ $ticket->subject }}</strong></p>

    <div style="background: #f5f5f5; padding: 16px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0; font-style: italic;">From {{ $staff->name }} (Staff):</p>
        <p style="margin: 8px 0 0;">{{ $ticket->messages->last()->body }}</p>
    </div>

    <p>You can view the full conversation and reply at:</p>
    <a href="{{ route('support.tickets.show', $ticket) }}" style="display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">View Ticket</a>

    <p style="margin-top: 30px; color: #666; font-size: 14px;">
        This is an automated notification from ScholarGraph Support.
    </p>
</body>
</html>
