<?php
namespace Pyncer\Snyppet\Access\Totp;

enum TotpMethod: string
{
    case APP = 'app';
    case EMAIL = 'email';
    case PHONE = 'phone';
}
