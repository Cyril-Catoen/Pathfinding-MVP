<?php

namespace App\Enum;

enum ViewAuthorization: string
{
    case Public = 'public';
    case Private = 'private';
    case Selection = 'selection';
}
?>