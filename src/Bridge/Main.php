<?php

namespace Bridge;

use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};
use pocketmine\network\mcpe\protocol\{ModalFormRequestPacket, ModalFormResponsePacket};
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\{LevelEventPacket, AddEntityPacket};
use pocketmine\entity\Entity;
use pocketmine\{Player as pocketPlayer, Server};

use Bridge\Database\Entity\EntityJoin;
use Bridge\EventListener;
use Bridge\Database\{Player, Team};
use Bridge\{Arena\Arena, Command\TBCommand, Task\Rotation, Task\Respawn, Task\EntityTag, Task\RemovePlayers, Task\Game, Task\SearchArena};

class Main extends PluginBase implements Listener {
	
	public $arenas = [];
	public $respawn = [];
	public $spectators = [];
	public $players = [];
	public $search = [];
	public $data = [
	"arenas" => [],
	"lasthit" => [],
	"prefix" => TextFormat::YELLOW . "[TheBridge]"];
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info(TextFormat::GREEN . "The Bridge Enable");
		Entity::registerEntity(EntityJoin::class, true);
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "arenas");
		@mkdir($this->getDataFolder() . "backup");
		@mkdir($this->getDataFolder() . "zip");
		$color = $this->getConfiguration("colors");
		$color->save();
		$config = $this->getConfiguration("config");
		if($config->get("arenas") == null){
			$config->set("arenas", ["SW1"]);
			$config->save();
		}
		$scan = scandir($this->getDataFolder()."arenas/");
		foreach($scan as $file){
			if($file !== ".." and $file !== "."){
				$name = str_replace(".yml", "", $file);
				$this->arenas[] = $name;
				
				$data = [
				"name" => $name,
				"maxSlots" => $config->get($name . "Players"),
				"LobbyTime" => 10,
				"GameTime" => 1800,
				"EndTime" => 60,
				"Team" => [],
				"status" => "Lobby"];
				$this->data["arenas"][$name] = new Arena($this, $data);
				$teams = ["RED", "BLUE"];
				foreach($this->getArenas() as $arena){
					if($arena->getStatus() != "Editing"){
						if($name != "SW1"){
							$color->set("RED", "§c");
							$color->set("BLUE", "§9");
							$color->set("REDBlock", 14);
							$color->set("BLUEBlock", 3);
							$color->set("REDColor", [255, 0, 0, 14]);
							$color->set("BLUEColor", [0, 0, 255, 11]);
							$color->save();
							$color->reload();
							$spawn = $this->getConfiguration("spawns");
							$spawn->save();
							foreach($teams as $team){
								$datos = [
								"TeamName" => $team,
								"TeamColor" => $color->get($team),
								"TeamBlock" => $color->get($team . "Block"),
								"arena" => $arena->getName(),
								"maxSlots" => 1,
								"points" => 0,
								"ArmorColor" => $color->get($team . "Color"),
								"spawn" => $spawn->get($arena->getName() . $team)];
								$arena->data["Team"][$team] = new Team($this, $datos);
							}
							$arena->resetData();
							$levelArena = $this->getServer()->getLevelByName($arena->getName());
							$this->copymap($this->getDataFolder() . "backup/" . $arena->getName(), $this->getServer()->getDataPath() . "worlds/" . $arena->getName());
							$this->getServer()->loadLevel($arena->getName());
							$this->getServer()->getLevelByName($arena->getName())->setTime(0);
							$this->getServer()->getLevelByName($arena->getName())->stopTime();
							if(file_exists($this->getServer()->getDataPath() . "worlds/" . $arena->getName())) {
								$this->getServer()->loadLevel($arena->getName());
							}
						}
					}
				}
			}
		}
		$this->getServer()->getCommandMap()->register("tb", new TBCommand($this));
		$this->getScheduler()->scheduleRepeatingTask(new RemovePlayers($this), 1);
		$this->getScheduler()->scheduleRepeatingTask(new EntityTag($this), 1);
		$this->getScheduler()->scheduleRepeatingTask(new Game($this), 20);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}
	
	
	public function getArena(String $name){
		$arenareturn = false;
		if(isset($this->data["arenas"][$name])){
			$arenareturn = $this->data["arenas"][$name];
		}
		return $arenareturn;
	}
	
	public function addArena(String $name, array $data){
		$this->data["arenas"][$name] = new Arena($this, $data);
		return $this->data["arenas"][$name];
	}
	
	public function getArenas(){
		return $this->data["arenas"];
	}
	
	public function getPrefix(){
		return $this->data["prefix"];
	}
	
	
	public function addPlayer(String $name, array $data) {
		$this->players[strtolower($name)] = new Player($this, $data);
	}
	
	public function getPlayer(String $name){
		$return = false;
		if(isset($this->players[strtolower($name)])){
			$return = $this->players[strtolower($name)];
		}
		return $return;
	}
	
	public function getPlayers(): array {
		return $this->players;
	}
	
	public function removePlayer(String $name){
		$username = strtolower($name);
		unset($this->players[$username]);
	}
	
	public function isPlayer(String $name){
		$player = false;
		$username = strtolower($name);
		foreach($this->getPlayers() as $names){
			if($username == $names){
				$player = true;
			}
		}
		return $player;
	}
	
	
	
	
	public function copymap($src, $dst){
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir))) {
			if(( $file != "." ) && ( $file != ".." )){
				if(is_dir($src . "/" . $file)){
					$this->copymap($src . "/" . $file, $dst . "/" . $file);
				}else{
					copy($src . "/" . $file, $dst . "/" . $file);
				}
			}
		}
		closedir($dir);
	}
	
	public function zipper($player, $name){
  	$path = realpath($player->getServer()->getDataPath() . "worlds/" . $name);
  	$zip = new \ZipArchive;
  	@mkdir($this->getDataFolder() . "zip/", 0755);
  	$zip->open($this->getDataFolder() . "zip/" . $name . ".zip", $zip::CREATE | $zip::OVERWRITE);
  	$files = new \RecursiveIteratorIterator(
  	new \RecursiveDirectoryIterator($path),
  	\RecursiveIteratorIterator::LEAVES_ONLY
  	);
  	foreach ($files as $datos) {
  		if (!$datos->isDir()) {
  			$relativePath = $name . "/" . substr($datos, strlen($path) + 1);
  			$zip->addFile($datos, $relativePath);
  		}
  	}
  	$zip->close();
  	$player->getServer()->loadLevel($name);
  	unset($zip, $path, $files);
  }
  
  
	public function getConfiguration(String $name){
		return new Config($this->getDataFolder() . "{$name}.yml", Config::YAML);
	}
	
	
	public function getTask(pocketPlayer $player){
		$this->getScheduler()->scheduleRepeatingTask(new SearchArena($this, $player), 20);
	}
	
	public function respawnTask(pocketPlayer $player){
		$this->getScheduler()->scheduleRepeatingTask(new Respawn($this, $player), 20);
	}
	
	
	public function spawnEntityJoin(pocketPlayer $player){
		$nbt = new CompoundTag("", [
		new ListTag("Pos", [
		new DoubleTag("", $player->getX()),
		new DoubleTag("", $player->getY()),
		new DoubleTag("", $player->getZ())
		]),
		new ListTag("Motion", [
		new DoubleTag("", 0),
		new DoubleTag("", 0),
		new DoubleTag("", 0)
		]),
		new ListTag("Rotation", [
		new FloatTag("",$player->yaw),
		new FloatTag("",$player->pitch)
		]),
		new CompoundTag("Skin", [
		new StringTag("Data", $player->getSkin()->getSkinData()),
		new StringTag("Name", $player->getSkin()->getSkinId()),
		]),]);
		$humano = new EntityJoin($player->getLevel(), $nbt);
		$humano->setScale(1);
		$humano->setNametagVisible(true);
		$humano->setNameTagAlwaysVisible(true);
		$humano->setImmobile(true);
		$humano->spawnToAll();
	}
	
	
	public function getTime($int){
		$m = floor($int / 60);
		$s = floor($int % 60);
		return (($m < 10 ? "0" : "") . $m . ":" . ($s < 10 ? "0" : "") . $s);
	}
	
	
	public function addSound(pocketPlayer $player){
		$pk = new LevelEventPacket();
		$pk->evid = LevelEventPacket::EVENT_SOUND_ORB;
		$pk->data = 0;
		$pk->position = $player->getPosition()->asVector3()->subtract(0, 28);
		foreach($player->getLevel()->getPlayers() as $players){
			$players->dataPacket($pk);
		}
	}
	
	
	public function addStrike(pocketPlayer $player){
		$light = new AddEntityPacket();
		$light->type = 93;
		$light->entityRuntimeId = Entity::$entityCount++;
		$light->metadata = array();
		$light->yaw = $player->getYaw();
		$light->pitch = $player->getPitch();
		$light->position = $player->getPosition()->asVector3()->subtract(0, 28);
		foreach($player->getLevel()->getPlayers() as $players) {
			$players->dataPacket($light);
		}
	}
	
	
	
	public function selectOption(pocketPlayer $player){
		$pk = new ModalFormRequestPacket();
		$pk->formId = 5412;
		$data = [
		"type" => "modal",
		"title" => TextFormat::GRAY . "¿Salir de la arena?",
		"content" => TextFormat::RED . "¿Quieres salir de la arena?.",
		"button1" => TextFormat::DARK_RED . TextFormat::BOLD . "Salir de la arena",
		"button2" => TextFormat::YELLOW . TextFormat::BOLD . "Cancelar"];
		$pk->formData = json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE);
		$player->dataPacket($pk);
	}
}