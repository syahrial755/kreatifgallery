<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /** Use case: "Menghapus komentar sendiri"; admin boleh menghapus komentar apa pun. */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->is_active && ($user->isAdmin() || $comment->user_id === $user->id);
    }
}
