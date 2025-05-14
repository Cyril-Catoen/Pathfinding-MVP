<?php 

namespace App\Enum;

enum AdventureFileType: string
{
    case Itinerary = 'itinerary';   // GPX, PDF, KML…
    case Gear = 'gear';             // matériel/provisions/équipement
    case Readme = 'readme';         // note libre
}
?>