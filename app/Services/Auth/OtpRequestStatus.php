<?php

namespace App\Services\Auth;

enum OtpRequestStatus
{
    case Sent;
    case Cooldown;
    case LimitReached;
}
