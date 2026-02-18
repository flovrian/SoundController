<?php
declare(strict_types=1);

namespace floriiian\SoundController;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class SoundListener implements Listener{


    public function onPlayerLogin(PlayerLoginEvent $event): void {
        SoundController::$actorRuntimeIds[$event->getPlayer()->getId()] = $event->getPlayer()->getPlayerInfo()->getUuid()->getBytes();
    }

    public function onPlayerQuitEvent(PlayerQuitEvent $event) : void{
        $p = $event->getPlayer();
        SoundController::deleteSoundTrackPlayer($p);
        unset(SoundController::$actorRuntimeIds[$event->getPlayer()->getId()]);
    }

    public function onEntityTeleportEvent(EntityTeleportEvent $e): void {
        $soundControl = SoundController::getInstance();
        $player = $e->getEntity();
        if($player::getNetworkTypeId() !== Player::getNetworkTypeId()){
            return;
        }
        /**@var Player $player*/
        if( $e->getTo()->getWorld()->getFolderName() !== $soundControl::$ambientWorld) {
            $soundControl->getCurrentPlayerZone($player)?->removePlayerFromZone($player);
        }
    }

	public function onEntityDamageEvent(EntityDamageEvent $event) : void{
		$p = $event->getEntity();
		/* if($p instanceof Player && $p->getHealth() - $event->getFinalDamage() <= 0){
            SoundController::deleteSoundTrackPlayer($p);
		}*/
	}

    public function onPlayerMoveEvent(PlayerMoveEvent $e) : void{
        $p = $e->getPlayer();
        $pos = $p->getPosition();
        // Note that this will ignore players moving at very slow speeds, gives a very tiny p erformance increase.
        if($pos->distanceSquared($e->getFrom()) > 0.1 ** 2){
            SoundController::getInstance()->assignPlayerToAmbientZone($p);
        }
    }
}