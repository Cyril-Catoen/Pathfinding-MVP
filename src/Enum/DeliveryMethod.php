<?php 

namespace App\Enum;

enum DeliveryMethod: string {
    case EMAIL = 'email';
    case SMS = 'sms';
    case BOTH = 'both';
}
?>