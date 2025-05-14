<?php

namespace App\Enum;

enum SafetyAlertReason: string {
    case MANUAL = 'manual';
    case TIMER = 'timer';
    case SYSTEM = 'system'; // optionnel
}

?>