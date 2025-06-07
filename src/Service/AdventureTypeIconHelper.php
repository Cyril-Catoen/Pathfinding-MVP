<?php

namespace App\Service;

use App\Enum\AdventureTypeList;

class AdventureTypeIconHelper
{
    public static function getIconMap(): array
    {
        return [
            AdventureTypeList::Hiking->value      => ['icon' => 'fas fa-person-hiking',   'isImg' => false],
            AdventureTypeList::Camping->value     => ['icon' => 'fas fa-campground',      'isImg' => false],
            AdventureTypeList::Alpinism->value    => ['icon' => 'icon_ice_axe.png',       'isImg' => true],
            AdventureTypeList::Climbing->value    => ['icon' => 'icon_climber.png',       'isImg' => true],
            AdventureTypeList::Whitewater->value  => ['icon' => 'fas fa-water',           'isImg' => false],
            AdventureTypeList::Skiing->value      => ['icon' => 'fas fa-person-skiing',   'isImg' => false],
            AdventureTypeList::Caving->value      => ['icon' => 'icon_cave2.png',         'isImg' => true],
            AdventureTypeList::Hunting->value     => ['icon' => 'fas fa-crosshairs',      'isImg' => false],
            AdventureTypeList::Fishing->value     => ['icon' => 'icon_fishing.svg',       'isImg' => true],
            AdventureTypeList::OffRoad->value     => ['icon' => 'fas fa-motorcycle',      'isImg' => false],
            AdventureTypeList::Biking->value      => ['icon' => 'fas fa-bicycle',         'isImg' => false],
            AdventureTypeList::Other->value       => ['icon' => 'fas fa-pen',             'isImg' => false],
        ];
    }
}

