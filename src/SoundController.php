<?php

declare(strict_types=1);

namespace floriiian\SoundController;

use floriiian\SoundController\config\AmbientZone;
use floriiian\SoundController\lib\SoundHelper;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\World;

class SoundController extends PluginBase{
    private Config $config;
    public static self $instance;
    public static function getInstance() : self{
        return self::$instance;
    }
    const DEBUG_ENABLED = false;
    const MIN_DYNAMIC_INTERVAL = 80;
    const MAX_DYNAMIC_INTERVAL = 180;
    private int $dynamicSoundInterval = 0;
    public static array $soundtrackPlayers = [];
    public static array $actorRuntimeIds = [];
    private array $ambientZones = [];
    public array $ambientWorldIDs = [];
    public static string $ambientWorld = "SanAngelica";

	protected function onEnable() : void{
		self::$instance = $this;

        $this->saveResource("config.json");
        $this->config = new Config($this->getDataFolder() . "config.json", Config::JSON);
        $this->getServer()->getPluginManager()->registerEvents(new SoundListener(), $this);
        $world = $this->getServer()->getWorldManager()->getWorldByName(self::$ambientWorld);
        if($world instanceof World) {
            $this->ambientWorldIDs[$world->getId()] = self::$ambientWorld;
        }
        $this->loadAmbienceZones();
        $this->dynamicSoundInterval = mt_rand(self::MIN_DYNAMIC_INTERVAL, self::MAX_DYNAMIC_INTERVAL);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() {
            $this->checkSoundtracks();
            $this->playDynamicAmbienceSounds();
        }), 20);
	}

    public static function getSoundTrackPlayer($playerID) : ?array{
        return self::$soundtrackPlayers[$playerID] ?? null;
    }

    public static function addSoundTrackPlayer(Player $p, string $soundName, int $soundLength, bool $shouldLoop = false) : void{
        if(!$p->isOnline()){
            return;
        }
        $playerID = $p->getId();
        if(self::getSoundTrackPlayer($playerID)){
            self::deleteSoundTrackPlayer($p);
        }
        self::$soundtrackPlayers[$playerID] = [
            "soundName" => $soundName, "length" => $soundLength,
            "maxLength" => $soundLength, "loop" => $shouldLoop
        ];
        SoundHelper::playSound($p, $soundName);
    }

    /**
     * The soundtrack array is structured like a normal soundtrack
     * "soundName" => $soundName, "length" => $soundLength,
     * "maxLength" => $soundLength,
    */
    public static function addRandomizedSoundTrackPlayer(Player $p, array $soundTracks) : void{
        if(!$p->isOnline() || empty($soundTracks) ){
            return;
        }
        $playerID = $p->getId();
        if(self::getSoundTrackPlayer($playerID)){
            self::deleteSoundTrackPlayer($p);
        }
        $trackName = array_rand($soundTracks);
        $randomTrack = $soundTracks[$trackName];
        self::$soundtrackPlayers[$playerID] = [
            "is_randomized" => true,
            "sound_tracks" => $soundTracks,
            "current_track" => $trackName
        ];
        SoundHelper::playSound($p, $trackName);
    }

    public static function deleteSoundTrackPlayer(Player $player): void
    {
        $id = $player->getId();
        if(isset(self::$soundtrackPlayers[$id])){
            if(isset(self::$soundtrackPlayers[$id]["soundName"])){
                SoundHelper::stopSound($player, self::$soundtrackPlayers[$id]["soundName"]);
            }
            elseif(isset(self::$soundtrackPlayers[$id]["current_track"])){
                SoundHelper::stopSound($player,self::$soundtrackPlayers[$id]["current_track"]);
            }
            unset(self::$soundtrackPlayers[$id]);
        }
    }

    public static function sendSound(Player $player, string $soundName, $soundVolume = 1, $soundPitch = 1) : void{
        if(!$player->isOnline()){
            return;
        }
        $pk = PlaySoundPacket::create(
            $soundName, $player->getPosition()->getX(), $player->getPosition()->getY(), $player->getPosition()->getZ(), $soundVolume, $soundPitch
        );
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public static function broadcastSoundFromPosition(World $w, Position $pos, string $soundName, float $soundVolume = 1.0, float $soundPitch = 1.0) : void {
        if($w && $w->isLoaded()) {
            $w->broadcastPacketToViewers($pos, PlaySoundPacket::create($soundName, $pos->x, $pos->y, $pos->z,$soundVolume, $soundPitch));
        }
    }

    public static function stopSound(Player $player, string $soundName) : void {
        if(!$player->isOnline()){
            return;
        }
        $pk = StopSoundPacket::create($soundName, false,false);
        $player->getNetworkSession()->sendDataPacket($pk);
    }


    public function loadAmbienceZones() : void {
        $ambientZones = $this->config->getAll();
        if(!$ambientZones ){
            $this->getServer()->getLogger()->info("§cNo ambience data found, it must be very quiet in San Angelica.");
            return;
        }
        foreach($ambientZones as $zone){
            try{
                $this->ambientZones[] =  AmbientZone::fromJSON($zone);
            } catch(\Exception $e){
                $this->getLogger()->error($e->getMessage());
            }
        }
        $this->getServer()->getLogger()->info("§7Loaded §e" . count($this->ambientZones) . "§7 ambient zone(s).");
    }

    public function assignPlayerToAmbientZone(Player $p): void
    {

        if(!isset($this->ambientWorldIDs[$p->getWorld()->getId()])){
            return;
        }

        /**@var AmbientZone $zone*/
        $bestZone = null;
        $currentPriority = PHP_INT_MIN;

        foreach ($this->ambientZones as $zone) {
            if (!$zone->isPlayerInZoneParts($p)) {
                continue;
            }
            $priority = $zone->getPriority();
            if ($priority > $currentPriority) {
                $currentPriority = $priority;
                $bestZone = $zone;
            }
        }
        $currentZone = $this->getCurrentPlayerZone($p);
        if(!$bestZone){
            $currentZone?->removePlayerFromZone($p);
            return;
        }
        if($currentZone !== $bestZone){
            $currentZone?->removePlayerFromZone($p);
            $bestZone->addPlayerToZone($p);
        }
    }
    public function getCurrentPlayerZone(Player $p) : ?AmbientZone  {
        $id = $p->getId();
        foreach ($this->ambientZones as $zone) {
            if(isset($zone->getPlayersInZone()[$id])){
                return $zone;
            }
        }
        return null;
    }

    public function checkSoundtracks(): void {
    foreach (array_keys(self::$soundtrackPlayers) as $playerId) {
        $player = $this->getPlayerByID($playerId);

        if (!$player) {
            unset(self::$soundtrackPlayers[$playerId]);
            continue;
        }
        $data = &self::$soundtrackPlayers[$playerId];

        if (isset($data["is_randomized"], $data["current_track"], $data["sound_tracks"])) {
            if (empty($data["sound_tracks"])) {
                unset(self::$soundtrackPlayers[$playerId]);
                continue;
            }
            $currentTrack = &$data["sound_tracks"][$data["current_track"]];
            if (!isset($currentTrack["length"], $currentTrack["max_length"])) {
                unset(self::$soundtrackPlayers[$playerId]);
                continue;
            }
            if ($currentTrack["length"] > 20) {
                $currentTrack["length"] -= 20;
                continue;
            }
            $currentTrack["length"] = $currentTrack["max_length"];
            $newTrackKey = array_rand($data["sound_tracks"]);
            $data["current_track"] = $newTrackKey;
            SoundHelper::playSound($player, $newTrackKey);
            continue;
        }
        if (!isset($data["length"], $data["maxLength"], $data["soundName"], $data["loop"])) {
            unset(self::$soundtrackPlayers[$playerId]);
            continue;
        }
        if ($data["length"] > 20) {
            $data["length"] -= 20;
            continue;
        }
        if (!$data["loop"]) {
            $player->getNetworkSession()->sendDataPacket(StopSoundPacket::create($data["soundName"], false, false));
            unset(self::$soundtrackPlayers[$playerId]);
            continue;
        }

        $data["length"] = $data["maxLength"];
        SoundHelper::playSound($player, $data["soundName"]);
    }

    }
    public function playDynamicAmbienceSounds(): void {
        /**@var AmbientZone $ambientZone */
        if ($this->dynamicSoundInterval - 20 > 0) {
            $this->dynamicSoundInterval -= 20;
            return;
        }
        foreach ($this->ambientZones as $ambientZone) {
            $players = $ambientZone->getPlayersInZone();
            if (empty($players)) {
                continue;
            }
            $sounds = $ambientZone->getDynamicSounds();
            if (count($sounds) === 0) {
                continue;
            }
            $sound = $sounds[array_rand($sounds)];
            foreach ($players as $id => $x) {
                if ($p = $this->getPlayerByID($id)) {
                    SoundHelper::playSound($p, $sound);
                }
            }
        }
        $this->dynamicSoundInterval = mt_rand(self::MIN_DYNAMIC_INTERVAL, self::MAX_DYNAMIC_INTERVAL);
    }

    public function getPlayerByID(int $actorRuntimeId): ?Player {
        if (isset(self::$actorRuntimeIds[$actorRuntimeId])) {
            return $this->getServer()->getPlayerByRawUUID(self::$actorRuntimeIds[$actorRuntimeId]);
        }
        return null;
    }
}
