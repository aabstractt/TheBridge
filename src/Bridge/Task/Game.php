<?php

namespace Bridge\Task;

use Scoreboards\Scoreboards;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\entity\{Effect, EffectInstance};
use pocketmine\level\{Level, Position};
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\{Server, Player as pocketPlayer};

use Bridge\Database\Player;
use Bridge\Arena\Arena;
use Bridge\Main;

class Game extends Task {
	
	private $plugin;
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function onRun(int $tick){
		foreach($this->plugin->getArenas() as $arena){
			if(count($this->plugin->getArenas()) != 0){
				$lobbytime = $arena->getLobbyTime();
				$gametime = $arena->getGameTime();
				$endtime = $arena->getEndTime();
				$levelArena = $this->plugin->getServer()->getLevelByName($arena->getName());
				if($levelArena instanceof Level){
					if($arena->getStatus() == "Lobby"){
						if(count($arena->getPlayers()) < 2){
							$arena->setLobbyTime(10);
							$arena->setEndTime(20);
							$arena->setStatus("Lobby");
							if((Time() % 20) == 0){
								foreach($arena->getPocketPlayers() as $player){
									$player->sendMessage(TextFormat::RED . "Esperando jugadores..");
								}
							}
						}else{
							$lobbytime--;
							$arena->setLobbyTime($lobbytime);
							if($arena->getLobbyTime() >= 0 && $arena->getLobbyTime() <= 10){
								foreach($arena->getPocketPlayers() as $player){
									$player->addTitle(TextFormat::GREEN . $arena->getLobbyTime(), "", 1);
									$player->sendMessage(TextFormat::YELLOW . "¡Empezando partida en " . TextFormat::GRAY . $arena->getLobbyTime() . TextFormat::YELLOW . " segundos!");
								}
							}
							if($arena->getLobbyTime() == 0){
								foreach($arena->getPocketPlayers() as $p){
									$p->teleport($levelArena->getSafeSpawn());
									$this->plugin->data["lasthit"][$p->getName()] = "null";
									unset($this->plugin->search[$p->getName()]);
								}
								foreach($arena->getPlayers() as $player){
									$player->getInstance()->setNameTag($player->getDisplayName());
									$player->teleportToSlot();
									$player->setBattle();
								}
								$arena->setStatus("InGame");
							}
						}
					}
					if($arena->getStatus() == "InGame"){
						$gametime--;
						$arena->setGameTime($gametime);
						foreach($arena->getPocketPlayers() as $player){
							$sender = $this->plugin->getPlayer(strtolower($player->getName()));
							$api = Scoreboards::getInstance();
							$api->new($player, "BRIDGE", TextFormat::YELLOW . TextFormat::BOLD . "THE BRIDGE");
							$api->setLine($player, 12, TextFormat::GRAY . date("d/m/Y"));
							$api->setLine($player, 11, TextFormat::GOLD . "");
							$api->setLine($player, 10, TextFormat::GRAY . $arena->getTeam("BLUE")->getColor() . "Blue Team: " . TextFormat::YELLOW . $arena->getTeam("BLUE")->getPoints() . TextFormat::GRAY . "/5");
							$api->setLine($player, 9, TextFormat::GRAY . $arena->getTeam("RED")->getColor() . "Red Team: " . TextFormat::YELLOW . $arena->getTeam("RED")->getPoints() . TextFormat::GRAY . "/5");
							$api->setLine($player, 8, TextFormat::BLUE . "");
							$api->setLine($player, 7, TextFormat::WHITE . "Goals: " . TextFormat::GREEN . $sender->getTeam()->getPoints());
							$api->setLine($player, 6, TextFormat::WHITE . "Kills: " . TextFormat::GREEN . $sender->getKills());
							$api->setLine($player, 5, TextFormat::DARK_GRAY . "");
							$api->setLine($player, 4, TextFormat::WHITE . "Modo: " . TextFormat::YELLOW . "Solo");
							$api->setLine($player, 3, TextFormat::WHITE . "Mapa: " . TextFormat::GREEN . $arena->getName());
							$api->setLine($player, 2, TextFormat::RED . "");
							$api->setLine($player, 1, TextFormat::YELLOW . "mc.playover.cf");
							$api->getObjectiveName($player);
						}
						if($arena->getGameTime() == 0){
							$this->plugin->getServer()->broadcastMessage($this->plugin->getPrefix() . TextFormat::RESET . TextFormat::GRAY . " > " . TextFormat::RED . "No hay ganadores en la arena: " . $arena->getName());
							$arena->setStatus("Restarting");
						}
						if(count($arena->getPlayers()) == 0 or count($arena->getPlayers()) == 1){
							$this->plugin->getServer()->broadcastMessage($this->plugin->getPrefix() . TextFormat::RESET . TextFormat::GRAY . " > " . TextFormat::RED . "No hay ganadores en la arena: " . $arena->getName());
							$arena->setStatus("Restarting");
						}
						if($arena->getTeam("BLUE")->getPoints() == 5){
							foreach($arena->getTeam("RED")->getPlayers() as $player){
								foreach($arena->getTeam("BLUE")->getPlayers() as $sender){
									$this->plugin->getServer()->broadcastMessage($this->plugin->getPrefix() . TextFormat::GRAY . " > " . $sender->getDisplayName() . TextFormat::YELLOW . " gano la partida en: " . TextFormat::GREEN . $arena->getName() . TextFormat::YELLOW . ", contra: " . $player->getDisplayName());
									$this->plugin->removePlayer($player->getName());
									$this->plugin->removePlayer($sender->getName());
									$arena->setStatus("Restarting");
								}
							}
						}
						if($arena->getTeam("RED")->getPoints() == 5){
							foreach($arena->getTeam("RED")->getPlayers() as $player){
								foreach($arena->getTeam("BLUE")->getPlayers() as $sender){
									$this->plugin->getServer()->broadcastMessage($this->plugin->getPrefix() . TextFormat::GRAY . " > " . $player->getDisplayName() . TextFormat::YELLOW . " gano la partida en: " . TextFormat::GREEN . $arena->getName() . TextFormat::YELLOW . ", contra: " . $sender->getDisplayName());
									$this->plugin->removePlayer($player->getName());
								  $this->plugin->removePlayer($sender->getName());
									$arena->setStatus("Restarting");
								}
							}
						}
					}
					if($arena->getStatus() == "Restarting"){
						$endtime--;
						$arena->setEndTime($endtime);
						if($arena->getEndTime() == 15){
							foreach($levelArena->getPlayers() as $player){
								$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
								$this->plugin->getServer()->getPluginManager()->getPlugin("Core")->getGadgets()->give($player);
							}
						}
						if($arena->getEndTime() == 0){
							$this->plugin->getServer()->unloadLevel($levelArena);
       				$this->plugin->copymap($this->plugin->getDataFolder() . "backup/" . $arena->getName(), $this->plugin->getServer()->getDataPath() . "worlds/" . $arena->getName());
       				$this->plugin->getServer()->loadLevel($arena->getName());
       				$arena->getTeam("BLUE")->setPoints(0);
       				$arena->getTeam("RED")->setPoints(0);
       				$arena->resetData();
						}
					}
				}
			}
		}
	}
}