<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /** Only the two parties of the consultation may open the conversation. */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->involves($user);
    }

    /** Same parties may send messages inside it. */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
