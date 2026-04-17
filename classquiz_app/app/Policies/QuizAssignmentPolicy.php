<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;

class QuizAssignmentPolicy
{
    public function viewAny(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin() || $quiz->creator_id === $user->id;
    }

    public function view(User $user, QuizAssignment $assignment): bool
    {
        return $user->isAdmin() || $assignment->quiz->creator_id === $user->id;
    }

    public function create(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin() || $quiz->creator_id === $user->id;
    }

    public function update(User $user, QuizAssignment $assignment): bool
    {
        return $user->isAdmin() || $assignment->quiz->creator_id === $user->id;
    }

    public function delete(User $user, QuizAssignment $assignment): bool
    {
        return $user->isAdmin() || $assignment->quiz->creator_id === $user->id;
    }
}
