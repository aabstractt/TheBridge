<?php

namespace Bridge\Task;

use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use pocketmine\{Player as pocketPlayer, Server};

use Bridge\Main;
use Bridge\Database\Player;

class SearchArena extends Task {
	
	private $plugin;
	private $p;
	private $time = 0;
	private $namearena;
	
	public function __construct(Main $plugin, pocketPlayer $p){
		$this->plugin = $plugin;
		$this->player = $p;
	}
	
	
	public function onRun(int $currentTick){
		$this->time++;
		$sender = $this->player;
		if($this->time == 4){
			$scan = scandir($this->plugin->getDataFolder()."arenas/");
			foreach($scan as $file){
				if($file !== ".." and $file !== "."){
					$name = str_replace(".yml", "", $file);
					$arena = $this->plugin->getArena($name);
					if($arena->getStatus() == "Lobby" and count($arena->getPlayers()) < $arena->getMaxSlots()){
						$arenas[$arena->getName()] = $arena->getName();
						if(count($arenas) != 0){
							if(count($arena->getPlayers()) < $arena->getMaxSlots()){
								$arenaName = $arena->getName();
								if(!isset($this->plugin->players[strtolower($sender->getName())])){
									$data = [
									"name" => $sender->getName(),
									"arena" => $arenaName,
									"TeamName" => "",
									"OtherTeam" => "",
									"kills" => 0,
									"register" => false];
									$this->plugin->addPlayer($sender->getName(), $data);
								}
							}
						}else{
							$sender->sendMessage(TextFormat::RED . "No se ha detectado ninguna arena, vuelve a intentarlo");
							unset($this->plugin->search[$sender->getName()]);
							$this->plugin->getScheduler()->cancelTask($this->getTaskId());
						}
					}
				}
			}
		}elseif($this->time == 5){
			$player = $this->plugin->getPlayer($sender->getName());
			$sender->getInventory()->clearAll();
			$sender->getArmorInventory()->clearAll();
			$sender->sendMessage(TextFormat::YELLOW . "Juego encontrado, enviándote a " . TextFormat::GRAY . $player->getArena()->getName() . TextFormat::YELLOW . "!");
			$item = Item::get(331);
			$item->setDamage(0);
			$item->setCustomName(TextFormat::DARK_RED . "Leave");
			$sender->getInventory()->setItem(8, $item);
			$player->searchTeam();
			foreach($player->getArena()->getPocketPlayers() as $pl){
				$pl->sendMessage(TextFormat::GRAY . $sender->getName() . TextFormat::YELLOW . " se ha unido al juego. " . TextFormat::GRAY . "(" . count($player->getArena()->getPlayers()) . "/" . $player->getArena()->getMaxSlots() . ")");
			}
		}elseif($this->time == 6){
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
		}
	}
}