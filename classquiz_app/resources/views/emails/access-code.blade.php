<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Your Quiz Access Code</title></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
    <h2>Your Access Code for <em>{{ $quizTitle }}</em></h2>
    <p>Hi {{ $session->name ?? $session->email }},</p>
    <p>Use the following code to access your quiz:</p>
    <div style="font-size: 2rem; font-weight: bold; letter-spacing: 0.3em; padding: 16px; background: #f3f4f6; border-radius: 8px; text-align: center;">
        {{ $plainCode }}
    </div>
    <p style="color: #6b7280; font-size: 0.875rem;">This code expires in {{ config('quiz.access_code_ttl_minutes') }} minutes.</p>
    <p style="color: #6b7280; font-size: 0.875rem;">If you did not register for this quiz, you can safely ignore this email.</p>
</body>
</html>
