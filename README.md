# SoundController
A PocketMine-MP plugin that adds an easy-to-use Sound API alongside functional ambience.
> An actual use case can be seen [here](https://youtu.be/Sh31MUrw3yY?si=MQkrd9yIFanscQDL).
## Concept
The entire functionality lies in its ability of providing an easier-to-use Sound API layer.
While I'm not denying that PocketMine-MP's API is very simple and well-made,
one has to acknowledge its lack of providing easy access to custom music and sounds.

The internal functionality of the music was discovered by [LuciaDX](https://github.com/LuciaDX), 
I merely implemented it based on her findings.
The plugin offers soundtracks, in terms of adding looping or non-looping music to players.
It also offers Ambient Zones.

### Ambient Zones
Ambient Zones are sound-zones defined in bounding boxes, whenever a player enters them he will be assigned
to that exact zone, therefore whenever he leaves he's either removed or assigned to the next one.

That's not everything, the sound-zones work in multiple layers, in fact they allow you to overwrite the
current zone e.g. one position at a lower Y Coordinate will be the 'Forest Sound' but the same position with a
higher Y Coordinate will play a 'Wind Sound'.

Ambient Zones also include Dynamic Ambience Sounds which are essentially everything you'd hear while walking
inside a city, a forest or even just while hovering above the ocean.
They will play at a random frequency (4 - 16 Seconds). 

## Internals
Minecraft Bedrock's Music is used to play the permanent ambient sounds in form of looping music.
This is necessary since you **cannot** efficiently loop sounds with network latency.

For the so said soundtracks, normal UI-Sounds are used; actually this is where your own
resource-pack work is required.
Read the [Bedrock Docs](https://wiki.bedrock.dev/concepts/sounds) for more information about Sounds, specifically UI sounds.

The ambient zones are currently only enabled for one single world, you can change the world by modifying
the `$ambientWorld` inside `SoundController.php`; though it technically does allow for multiple worlds.

The system is highly performant due to its caching of Actor Runtime IDs instead of names or XUIDs, thereby providing O(1) lookup.

## Usage

### Terminology
- Sound name: The sound name specified in your Minecraft Bedrock Resource pack e.g. `gtbe.cheesecake_explode`
- Sound Length: The length of the sound in ticks. (1 Second = 20 Ticks in PocketMine-MP) 


#### To add a soundtrack to a player,
Call the static ``addSoundTrackPlayer()`` function and specify the player, the sound name,
sound length and whenever the sound should loop or not; it will not by default.
````php
SoundController::addSoundTrackPlayer($playerObject, 'gtbe.cheesecake_explode', 40);
````

#### Remove a soundtrack from a player  
Call the static ``deleteSoundTrackPlayer()`` function and specify the player.
```php
SoundController::deleteSoundTrackPlayer($playerObject);
```

#### Send a sound **to** the player 
Use the static ``sendSound()`` function and specify the player, sound name, sound volume and sound pitch.
```php
SoundController::sendSound($playerObject, 'gtbe.tamagochi_ring', 1, 1);
```

#### Stop a sound
Call the static ``stopSound()`` function, specify player and sound name.
```php
SoundController::stopSound($playerObject, 'gtbe.inu_bark');
```

#### Broadcast a sound **from** a position.
Use the static ``broadcastSoundFromPosition()`` function and specify the world, position, sound name, volume and pitch.
```php
SoundController::broadcastSoundFromPosition($world, $pos, 'gtbe.umi_nami', 1, 1 );
```

### Music 
The music functions are used by the ambience system, it shouldn't be used except if you're okay with interrupting 
the ambience.
For music such as soundtracks please use the Soundtrack system; it's literally in the name.

> **Music Types**:
There are two music types available to use, either ``MUSIC_REPEAT_ONCE`` or ``MUSIC_REPEAT_LOOP`` which do
exactly what they indicate.

#### Queueing music
Use the static ``queueMusic()`` function, specify player, music name, volume, fade (seconds) and type.
```php
MusicHelper::queueMusic($playerObject, 'gtbe.sakana_munch', 1, 0, self::MUSIC_REPEAT_ONCE);
```

#### Playing music 
Use the static ``playMusic()`` function, specify player, music name, volume, fade (seconds) and type.
```php
MusicHelper::playMusic($playerObject, 'gtbe.tonkatsu_burp', 1, 0, self::MUSIC_REPEAT_ONCE);
```

#### Stop music
Use the static ``stopMusic()`` function, specify player and fade out time, in seconds.
```php
MusicHelper::stopMusic($playerObject, 5);
```

