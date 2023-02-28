<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

enum Source: int
{
    case UnknownSource                               = 0;
    case BasicRules                                  = 1;
    case PlayersHandbook                             = 2;
    case PrincesoftheApocalypse                      = 10;
    case DungeonMastersGuide                         = 3;
    case MonsterManual                               = 5;
    case StormKingsThunder                           = 12;
    case CurseofStrahd                               = 6;
    case TalesfromtheYawningPortal                   = 14;
    case LostMineofPhandelver                        = 8;
    case OutoftheAbyss                               = 9;
    case TombofAnnihilation                          = 25;
    case XanatharsGuidetoEverything                  = 27;
    case WaterdeepDragonHeist                        = 35;
    case WaterdeepDungeonoftheMadMage                = 36;
    case GhostsofSaltmarsh                           = 43;
    case DragonofIcespirePeak                        = 41;
    case BaldursGateDescentintoAvernus               = 48;
    case EberronRisingfromtheLastWar                 = 49;
    case ExplorersGuidetoWildemount                  = 59;
    case MythicOdysseysofTheros                      = 61;
    case IcewindDaleRimeoftheFrostmaiden             = 66;
    case TashasCauldronofEverything                  = 67;
    case CandlekeepMysteries                         = 68;
    case VanRichtensGuidetoRavenloft                 = 69;
    case TheWildBeyondtheWitchlight                  = 79;
    case StrixhavenACurriculumofChaos                = 80;
    case FizbansTreasuryofDragons                    = 81;
    case CriticalRoleCalloftheNetherdeep             = 83;
    case MordenkainenPresentsMonstersoftheMultiverse = 85;
    case JourneysthroughtheRadiantCitadel            = 87;
    case SpelljammerAdventuresinSpace                = 90;
    case DragonsofStormwreckIsle                     = 94;
    case DragonlanceShadowoftheDragonQueen           = 95;
    case OneDDPlaytest                               = 100;
    case TyrannyofDragons                            = 102;

    public function name(): string
    {
        return match ($this) {
            self::UnknownSource                               => 'Unknown',
            self::BasicRules                                  => 'Basic Rules',
            self::PlayersHandbook                             => 'Player\'s Handbook',
            self::PrincesoftheApocalypse                      => 'Princes of the Apocalypse',
            self::DungeonMastersGuide                         => "Dungeon Master's Guide",
            self::MonsterManual                               => 'Monster Manual',
            self::StormKingsThunder                           => "Storm King's Thunder",
            self::CurseofStrahd                               => 'Curse of Strahd',
            self::TalesfromtheYawningPortal                   => 'Tales from the Yawning Portal',
            self::LostMineofPhandelver                        => 'Lost Mine of Phandelver',
            self::OutoftheAbyss                               => 'Out of the Abyss',
            self::TombofAnnihilation                          => 'Tomb of Annihilation',
            self::XanatharsGuidetoEverything                  => "Xanathar's Guide to Everything",
            self::WaterdeepDragonHeist                        => 'Waterdeep: Dragon Heist',
            self::WaterdeepDungeonoftheMadMage                => 'Waterdeep: Dungeon of the Mad Mage',
            self::GhostsofSaltmarsh                           => 'Ghosts of Saltmarsh',
            self::DragonofIcespirePeak                        => 'Dragon of Icespire Peak',
            self::BaldursGateDescentintoAvernus               => 'Baldur\'s Gate: Descent into Avernus',
            self::EberronRisingfromtheLastWar                 => 'Eberron: Rising from the Last War',
            self::ExplorersGuidetoWildemount                  => "Explorer's Guide to Wildemount",
            self::MythicOdysseysofTheros                      => 'Mythic Odysseys of Theros',
            self::IcewindDaleRimeoftheFrostmaiden             => 'Icewind Dale: Rime of the Frostmaiden',
            self::TashasCauldronofEverything                  => 'Tasha\'s Cauldron of Everything',
            self::CandlekeepMysteries                         => 'Candlekeep Mysteries',
            self::VanRichtensGuidetoRavenloft                 => 'Van Richten\'s Guide to Ravenloft',
            self::TheWildBeyondtheWitchlight                  => 'The Wild Beyond the Witchlight',
            self::StrixhavenACurriculumofChaos                => 'Strixhaven: A Curriculum of Chaos',
            self::FizbansTreasuryofDragons                    => "Fizban's Treasury of Dragons",
            self::CriticalRoleCalloftheNetherdeep             => 'Critical Role: Call of the Netherdeep',
            self::MordenkainenPresentsMonstersoftheMultiverse => 'Mordenkainen Presents: Monsters of the Multiverse',
            self::JourneysthroughtheRadiantCitadel            => 'Journeys through the Radiant Citadel',
            self::SpelljammerAdventuresinSpace                => 'Spelljammer: Adventures in Space',
            self::DragonsofStormwreckIsle                     => 'Dragons of Stormwreck Isle',
            self::DragonlanceShadowoftheDragonQueen           => 'Dragonlance: Shadow of the Dragon Queen',
            self::OneDDPlaytest                               => 'One D&D Playtest',
            self::TyrannyofDragons                            => 'Tyranny of Dragons'
        };
    }
}
