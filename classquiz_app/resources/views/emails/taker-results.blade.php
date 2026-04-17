<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your ClassQuiz Results</title>
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #111827;">

    <h2 style="margin-bottom: 4px;">Your ClassQuiz Results</h2>
    <p style="color: #6b7280; margin-top: 0;">Here's a summary of the quizzes you've completed.</p>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="text-align: left; padding: 10px 12px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Quiz</th>
                <th style="text-align: left; padding: 10px 12px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Submitted</th>
                <th style="text-align: right; padding: 10px 12px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Score</th>
                <th style="padding: 10px 12px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $session)
            @php
                $title      = $session->assignment->quiz->title;
                $showScore  = $session->assignment->setting('show_result') && $session->status === 'graded';
                $scoreText  = $showScore && $session->score !== null
                                ? number_format($session->score, 1) . ' / ' . number_format($session->max_score, 1)
                                : '—';
                $resultUrl  = route('quiz.result', $session->id);
                $submitted  = $session->submitted_at?->format('d M Y, H:i') ?? '—';
            @endphp
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px;">{{ $title }}</td>
                <td style="padding: 12px; color: #6b7280; font-size: 0.875rem;">{{ $submitted }}</td>
                <td style="padding: 12px; text-align: right; font-weight: 600;">{{ $scoreText }}</td>
                <td style="padding: 12px; text-align: right;">
                    @if($session->assignment->setting('show_result'))
                    <a href="{{ $resultUrl }}"
                       style="color: #6366f1; font-size: 0.875rem; text-decoration: underline;">View</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 28px; color: #9ca3af; font-size: 0.8rem;">
        You received this email because your address was used to complete quizzes on ClassQuiz.
        If this wasn't you, please disregard this email.
    </p>

</body>
</html>
