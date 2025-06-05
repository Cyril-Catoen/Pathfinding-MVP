<?php 

namespace App\Enum;

enum AdventureFileType: string {
    case ITINERARY = 'itinerary';
    case EQUIPMENT = 'equipment';
    case README = 'readme';
    case OTHER = 'other';

    public function label(): string {
        return match($this) {
            self::ITINERARY => 'Itinéraire',
            self::EQUIPMENT => 'Équipement/Provisions',
            self::README => 'Readme/Note Libre',
            self::OTHER => 'Autre',
        };
    }

    public function icon(): string {
        return match($this) {
            self::ITINERARY => 'fa-route',
            self::EQUIPMENT => 'fa-toolbox',
            self::README => 'fa-info-circle',
            self::OTHER => 'fa-file',
        };
    }
}

?>