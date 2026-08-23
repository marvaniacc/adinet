<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Documents are private to the consultation: the client who owns the
     * request and the lawyer of that request - nobody else (admins included,
     * unless explicitly granted a dedicated admin policy later).
     */
    public function download(User $user, Document $document): bool
    {
        $request = $document->consultationRequest;

        if ($user->id === $request->client_id) {
            return true;
        }

        return $user->isLawyer() && $user->id === $request->lawyerProfile?->user_id;
    }

    /** Only the uploader may remove their own document. */
    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->uploaded_by && $this->download($user, $document);
    }
}
