<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Resume Your Quiz</title></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
    <h2>Resume <em>{{ $quizTitle }}</em></h2>
    <p>Hi {{ $session->name ?? $session->email }},</p>
    <p>Click the button below to resume where you left off:</p>
    <p style="text-align: center; margin: 32px 0;">
        <a href="{{ $resumeUrl }}"
           style="display: inline-block; padding: 12px 32px; background: #4f46e5; color: #fff;
                  border-radius: 6px; text-decoration: none; font-weight: bold;">
            Resume Quiz
        </a>
    </p>
    <p style="color: #6b7280; font-size: 0.875rem;">
        Or copy this link: <a href="{{ $resumeUrl }}">{{ $resumeUrl }}</a>
    </p>
    <p style="color: #6b7280; font-size: 0.875rem;">This link expires in {{ config('quiz.resume_token_ttl_minutes') }} minutes.</p>
</body>
</html>
