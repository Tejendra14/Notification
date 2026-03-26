<?php
namespace App\Enums;

enum NotificationType: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
}