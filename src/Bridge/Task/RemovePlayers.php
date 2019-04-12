<?php

namespace Bridge\Task;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use Bridge\{Main, Arena\Arena, Database\Player};
use pocketmine\Player as pocketPlayer;
use pocketmine\entity\Entity;
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\block\Air;
use pocketmine\block\Glass;
use pocketmine\math\Vector3;

class RemovePlayers extends Task {
	
	private $plugin;
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function onRun(int $currentTick){
		foreach($this->plugin->getPlayers() as $player){
			$arena = $player->getArena();
			$allpockets = $arena->getPocketEveryone();
			$pocketPlayers = $arena->getPocketPlayers();
			if(isset($this->plugin->players[strtolower($player->getName())])){
				if(!$player->getInstance() instanceof pocketPlayer){
					foreach($allpockets as $playerArena){
						$playersCount = count($pocketPlayers) - 1;
						$playerArena->sendMessage(TextFormat::GRAY . "{$player->getName()}" . TextFormat::YELLOW . " se ha salido " . TextFormat::GRAY . "(" . $playersCount . "/" . $arena->getMaxSlots() . ")");
						$this->plugin->removePlayer($player->getName());
					}
				}else{
					$player = $player->getInstance();
					if($player->getLevel()->getFolderName() != $arena->getName() && $arena->getStatus() != "Lobby"){
						foreach($allpockets as $playerArena){
							if($arena->getStatus() == "InGame"){
								$playersCount = count($pocketPlayers) - 1;
								$player->getInventory()->clearAll();
								$player->getArmorInventory()->clearAll();
								$playerArena->sendMessage(TextFormat::GRAY . "{$player->getName()}" . TextFormat::YELLOW . " se ha salido " . TextFormat::GRAY . "(" . $playersCount . "/" . $arena->getMaxSlots() . ")");
								$this->plugin->removePlayer($player->getName());
							}
						}
					}
				}
			}/*elseif($this->plugin->isSpectactor($player->getName())){
				if(!$player->getInstance() instanceof pocketPlayer) {
					$this->plugin->removeSpectator($player->getName());
				}else{
					$player = $player->getInstance();
					if($player->getLevel()->getFolderName() != $arena->getName() && $arena->getStatus() != "Lobby" or $player->getLevel() != $this->plugin->getServer()->getDefaultLevel() && $arena->getStatus() != "Lobby"){
						$this->plugin->removeSpectator($player->getName());
					}
				}
			}*/
		}
	}
}