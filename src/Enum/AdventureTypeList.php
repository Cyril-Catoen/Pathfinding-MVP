<?php 

namespace App\Enum;

enum AdventureTypeList: string
{
    case Hiking = 'hiking';  
    case Camping = 'camping';
    case Alpinism = 'alpinism';
    case Climbing = 'climbing';
    case Whitewater = 'whitewater';
    case Skiing = 'skiing';
    case Caving = 'caving';
    case Hunting = 'hunting';
    case Fishing = 'fishing';
    case OffRoad = 'off road';
    case Biking = 'biking';
    case Other = 'other(s)';                     
}
?>