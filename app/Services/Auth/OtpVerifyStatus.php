<?php

namespace App\Services\Auth;

enum OtpVerifyStatus
{
    case Verified;
    case InvalidCode;
    case Expired;
    case Locked;
}
