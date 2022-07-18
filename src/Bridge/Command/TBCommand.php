<?php

namespace Bridge\Command;

use Bridge\Main;
use pocketmine\{Server};
use pocketmine\command\{CommandSender, PluginCommand};
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\TextFormat;

class TBCommand extends PluginCommand {
	
	private $plugin;
	
	public function __construct(Main $plugin){
		parent::__construct("tb", $plugin);
		$this->setDescription(TextFormat::YELLOW . "TheBridge Command");
		$this->plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, String $label, array $args) : bool {
		if(isset($args[0])){
			$config = $this->plugin->getConfiguration("config");
			if(!$sender->hasPermission("tb.owner")){
				$sender->sendMessage(TextFormat::RED . "You dont have permission.");
				return true;
			}
			if($args[0] == "create"){
				if(isset($args[1]) && isset($args[2])){
					if(file_exists($this->plugin->getServer()->getDataPath() . "worlds/" . $args[1])){
						if(!$this->plugin->getArena($args[1])){
							$arena = $args[1];
							$this->plugin->data["arenas"][] = $arena;
							$this->plugin->arenas[] = $arena;
							$config->set("arenas", $this->plugin->arenas);
							$config->set($arena . "Players", 2);
							$config->set($arena . "BossId", (int) $args[2]);
							$config->save();
							$data = [
							"name" => $arena,
							"maxSlots" => $config->get($arena . "Players"),
							"BossBar" => $config->get($arena . "BossID"),
							"LobbyTime" => 10,
							"GameTime" => 1800,
							"EndTime" => 60,
							"Team" => [],
							"status" => "Lobby"];
							$this->plugin->addArena($arena, $data);
							$this->plugin->copymap($this->plugin->getServer()->getDataPath() . "worlds/" . $arena, $this->plugin->getDataFolder() . "backup/" . $arena);
							$player = $sender;
							$name = $arena;
						  $this->plugin->zipper($player, $name);
							$levelarena = $this->plugin->getArena($name);
							$levelarena->resetData();
							$sender->sendMessage($this->plugin->data["prefix"] . TextFormat::GREEN . " Ahora registra los spawns con /tb setspawn <arena> <spawn>");
							$ac = $this->plugin->getConfiguration("arenas/" . $arena);
							$ac->set("Arena: $arena\nPlayersMax: $args[2]");
							$ac->save();
						}else{
							$sender->sendMessage(TextFormat::RED . "ERROR... esta arena ya se encuentra registrada.");
						}
					}
				}
			}
			if($args[0] == "spawn"){
				if(isset($args[1])){
					if(isset($args[2])){
						if($args[2] == "RED"){
							$name = $args[1];
							if(!isset($this->plugin->players[strtolower($sender->getName())])){
								$data = [
								"name" => $sender->getName(),
								"arena" => $name,
								"TeamName" => "RED",
								"OtherTeam" => "BLUE",
								"register" => true];
								$this->plugin->addPlayer($sender->getName(), $data);
								$player = $this->plugin->getPlayer($sender->getName());
								if($player->getArena()->getStatus() != "InGame"){
									$player->getArena()->setStatus("Editing");
								}
								$sender->sendMessage("Rompe un bloque donde sera la posicion de spawneo del equipo " . $player->getTeam()->getColor() . $player->getTeam()->getTeamName() . TextFormat::RESET);
							}
						}elseif($args[2] == "BLUE"){
							$name = $args[1];
							if(!isset($this->plugin->players[strtolower($sender->getName())])){
								$data = [
								"name" => $sender->getName(),
								"arena" => $name,
								"TeamName" => "BLUE",
								"OtherTeam" => "RED",
								"register" => true];
								$this->plugin->addPlayer($sender->getName(), $data);
								$player = $this->plugin->getPlayer($sender->getName());
								if($player->getArena()->getStatus() != "InGame"){
									$player->getArena()->setStatus("Editing");
								}
								$sender->sendMessage("Rompe un bloque donde sera la posicion de spawneo del equipo " . $player->getTeam()->getColor() . $player->getTeam()->getTeamName() . TextFormat::RESET);
							}
						}
					}
				}
			}elseif($args[0] == "npc"){
				$this->plugin->spawnEntityJoin($sender);
				$sender->sendMessage(TextFormat::YELLOW . "Has colocado la NPC");
			}elseif($args[0] == "uno"){
				$this->plugin->selectorArena($sender);
			}
			if($args[0] == "list"){
				$names = [];
				foreach($this->plugin->getArenas() as $arena){
					$names[] = $arena->getName();
				}
				$sender->sendMessage(TextFormat::GREEN . "Arenas list:\n" . TextFormat::GRAY . "-" . TextFormat::YELLOW . join("\n" . TextFormat::GRAY . "-" . TextFormat::YELLOW, $names) . TextFormat::GRAY . ".");
			}
			if($args[0] == "help"){
				$sender->sendMessage(TextFormat::YELLOW . "TheBridge " . TextFormat::RED . "Commands");
				$sender->sendMessage(TextFormat::YELLOW . "/tb create <arena> <bossbar id>: " . TextFormat::GRAY . "Create new arena.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb spawn <team>: " . TextFormat::GRAY . "Select spawn team in your position.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb players <arena>: " . TextFormat::GRAY . "Show arena players.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb spectators <arena>: " . TextFormat::GRAY . "Show arena spectators.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb list: " . TextFormat::GRAY . "List arena.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb join <arena>: " . TextFormat::GRAY . "Join arena.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb joinrandom: " . TextFormat::GRAY . "Join random arena.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb leave: " . TextFormat::GRAY . "Leave arena.");
				$sender->sendMessage(TextFormat::YELLOW . "/tb npc: " . TextFormat::GRAY . "Spawn entity join in your position.");
			}
		}
		return true;
	}
}