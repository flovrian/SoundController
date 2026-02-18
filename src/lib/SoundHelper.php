<?php

declare(strict_types=1);

namespace floriiian\SoundController\lib;

use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\player\Player;

class SoundHelper {

    public static function playSound(Player $player, string $sound, float $vol = 1.0, float $pitch = 1.0): void
    {
        if ($player->isOnline()) {
            $pos = $player->getPosition();
            $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create($sound, $pos->x, $pos->y, $pos->z, $vol, $pitch));
        }
    }

    public static function stopSound(Player $p, string $sound) : void {
        $p->getNetworkSession()->sendDataPacket(StopSoundPacket::create($sound, false,false));
    }

    public static function broadcastSound($p, string $sound, float $vol = 1.0, float $pitch = 1.0): void
    {
        NetworkBroadcastUtils::broadcastPackets($p->getWorld()->getPlayers(), [PlaySoundPacket::create($sound, $p->getLocation()->getX(), $p->getLocation()->getY(), $p->getLocation()->getZ(), $vol, $pitch)]);
    }

}
