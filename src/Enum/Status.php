<?php

namespace App\Enum;

enum Status: string
{
    case Preparation = 'preparation';
    case Ongoing = 'ongoing';
    case Achieved   = 'achieved';
}
?>