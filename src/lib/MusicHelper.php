<?php

declare(strict_types=1);

namespace floriiian\SoundController\lib;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventGenericPacket;
use pocketmine\player\Player;

class  MusicHelper
{
	const  MUSIC_QUEUE = 1900;
	const  MUSIC_PLAY = 1901;
	const  MUSIC_STOP = 1902;
	const  MUSIC_VOLUME = 1903;
	const  MUSIC_REPEAT_ONCE = 0;
	const  MUSIC_REPEAT_LOOP = 1;

	public static function queueMusic(Player $p, string $name, float $vol = 1, float $fade = 0, int $type = self::MUSIC_REPEAT_ONCE ) : void{
		$pk = LevelEventGenericPacket::create(
			self::MUSIC_QUEUE,
			CompoundTag::create()
				->setFloat("fadeSeconds", $fade)
				->setString("trackName", $name)
                ->setByte("repeatMode", $type)
				->setFloat("volume", $vol)
		);
		$p->getNetworkSession()->sendDataPacket($pk);
	}

	public static function playMusic(Player $p, string $name, float $vol = 1, float $fade = 0, int $type = self::MUSIC_REPEAT_ONCE) : void{
		$pk = LevelEventGenericPacket::create(self::MUSIC_PLAY,
			CompoundTag::create()
				->setFloat("fadeSeconds", $fade)
				->setByte("repeatMode", $type)
				->setString("trackName", $name)
				->setFloat("volume", $vol)
		);
		$p->getNetworkSession()->sendDataPacket($pk);
	}

    public static function stopMusic(Player $p, float $fade = 0) : void{
		$pk = LevelEventGenericPacket::create(self::MUSIC_STOP,
			CompoundTag::create()->setFloat("fadeSeconds", $fade));
		$p->getNetworkSession()->sendDataPacket($pk);
	}

	public static function musicVolume(Player $p, float $vol = 1) : void{
		$pk = LevelEventGenericPacket::create(self::MUSIC_VOLUME,
			CompoundTag::create()->setFloat("volume", $vol));
		$p->getNetworkSession()->sendDataPacket($pk);
	}

}
