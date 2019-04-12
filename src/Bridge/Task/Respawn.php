<?php

namespace Bridge\Task;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\{Player as pocketPlayer, Server};

use Bridge\Main;
use Bridge\Database\Player;

class Respawn extends Task {
	
	private $plugin;
	private $p;
	public $time = 6;
	private $namearena;
	
	public function __construct(Main $plugin, pocketPlayer $p){
		$this->plugin = $plugin;
		$this->player = $p;
	}
	
	
	public function onRun(int $currentTick){
		$this->time--;
		$sender = $this->player;
		$player = $this->plugin->getPlayer($sender->getName());
		$sender->addTitle(TextFormat::RED . TextFormat::BOLD . "YOU ARE MOVE IN", TextFormat::RESET . TextFormat::GRAY . $this->time);
		if($this->time == 5){
			$sender->setImmobile(true);
			$player->setBattle();
			$sender->setGamemode(0);
		}elseif($this->time == 0){
			$sender->setImmobile(false);
			$sender->setHealth(20);
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
		}
	}
}