<?php

declare(strict_types=1);

namespace floriiian\SoundController\config;

use floriiian\SoundController\lib\MusicHelper;
use floriiian\SoundController\SoundController;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;

class AmbientZone {
    private string $startSound;
    private int $zonePriority;
    private string $backgroundSound;
    private array $dynamicSounds;
    private int $transitionTime;
    private array $soundAreas;
    private array $playersInZone = [];

    private function __construct(string $startSound, string $backgroundSound, array $dynamicSounds, array $soundAreas, int $transitionTime, int $zonePriority)
    {
        $this->startSound = $startSound;
        $this->backgroundSound = $backgroundSound;
        $this->dynamicSounds = ["", "",...$dynamicSounds, "", "",];
        $this->transitionTime = $transitionTime;
        $this->zonePriority = $zonePriority;
        $this->soundAreas = $soundAreas;
    }

    public function getPlayersInZone() : array
    {
        return $this->playersInZone;
    }

    public function getBackgroundSound(): string
    {
        return $this->backgroundSound;
    }

    public function getDynamicSounds(): array
    {
        return $this->dynamicSounds;
    }

    public function getPriority() : int {
        return $this->zonePriority;
    }

    public function addPlayerToZone(Player $p) : void {
        MusicHelper::queueMusic($p, $this->backgroundSound, 1, $this->transitionTime, MusicHelper::MUSIC_REPEAT_LOOP);
        $this->playersInZone[ $p->getId()] = 0;
        if(SoundController::DEBUG_ENABLED) echo("Adding player to zone: {$this->getBackgroundSound()}\n");
    }

    public function removePlayerFromZone(Player $p) : void {
        $id = $p->getId();
        if (isset($this->playersInZone[$id])) {
            unset($this->playersInZone[$id]);
            MusicHelper::stopMusic($p, 1);
            if(SoundController::DEBUG_ENABLED){
                echo("Removing player from zone: {$this->getBackgroundSound()}\n");
            }
        }
    }

    public function isPlayerInZoneParts(Player $p): bool {
        /** @var AxisAlignedBB $area*/
        foreach($this->soundAreas as $area){
            if($area->isVectorInside($p->getPosition())){
                return true;
            }
        }
        return false;
    }

    public static function fromJSON(array $data): self
    {
        return new self(
            $data["startSound"],
            $data['backgroundSound'],
            $data['dynamicSounds'],
            self::getAxisAlignedBBs($data['soundAreas']),
            $data['transitionTime'],
            $data['zonePriority']
        );
    }

    private static function getAxisAlignedBBs(array $boundingBoxes): array {
        return array_map(fn($boundingBox) => new AxisAlignedBB(...$boundingBox), $boundingBoxes);
    }
}