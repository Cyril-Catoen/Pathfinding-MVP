<?php

namespace App\Enum;

enum SafetyAlertStatus: string {
    case ACTIVE = 'active';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';
    case ESCALATED = 'escalated';
}

?>