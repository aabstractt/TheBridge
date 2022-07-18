<?php

namespace Bridge\Database;

use Bridge\{Main};
use Bridge\BossAnnounce\API;
use pocketmine\{block\Block, item\Item, math\Vector3 as Vector, utils\TextFormat};
use pocketmine\{Server};
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

class Player {
	
	private $data;
	private $plugin;
	
	public function __construct(Main $plugin, array $data){
		$this->data = $data;
		$this->plugin = $plugin;
	}
	
	
	public function getName(){
		return $this->data["name"];
	}
	
	public function getDisplayName(){
		return $this->getTeam()->getColor() . $this->getName();
	}
	
	
	public function getArena() {
		return $this->plugin->getArena($this->getArenaName());
	}
	
	public function getArenaName(): String {
		return $this->data["arena"];
	}
	
	
	public function getTeamName(): String {
		return $this->data["TeamName"];
	}
	
	public function getOtherTeam() {
		if($this->getArena()->getTeam($this->data["OtherTeam"]) instanceof Team){
			$team = $this->getArena()->getTeam($this->data["OtherTeam"]);
		}
		return $team;
	}
	
	public function getTeam() {
		if($this->getArena()->getTeam($this->getTeamName()) instanceof Team){
			$team = $this->getArena()->getTeam($this->getTeamName());
		}
		return $team;
	}
	
	
	public function getInstance(){
		return $this->plugin->getServer()->getPlayer($this->getName());
	}
	
	public function getLevel(){
		return $this->plugin->getServer()->getLevelByName($this->getArenaName());
	}
	
	
	public function teleportToSlot(){
		$player = $this->getInstance();
		$spawn = $this->getTeam()->getSpawn();
		$player->teleport(new Vector($spawn[0], $spawn[1]+1, $spawn[2]));
	}
	
	public function setDefault(){
		$player = $this->getInstance();
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
	}
	
	
	public function getLife(){
		$player = $this->getInstance();
		if($player->getHealth() <= $player->getMaxHealth()){
			$life = TextFormat::GREEN . $player->getHealth();
		}elseif($player->getHealth() <= 15){
			$life = TextFormat::YELLOW . $player->getHealth();
		}elseif($player->getHealth() <= 7){
			$life = TextFormat::RED . $player->getHealth();
		}
		return $life;
	}
	
	/*public function setDevice(int $device){
		return $this->data["device"];
	}
	
	public function getDevice(): int {
		return $this->data["device"];
	}*/
	
	
	public function searchTeam(){
		$player = $this->getInstance();
		$arena = $this->getArena();
		$teams = ["RED", "BLUE"];
		$rray = $teams[array_rand($teams)];
		if($rray == "RED"){
			if(!$arena->getTeam("RED")->isFull()){
				$this->data["TeamName"] = "RED";
				$this->data["OtherTeam"] = "BLUE";
			}else{
				$this->searchTeam();
			}
		}elseif($rray == "BLUE"){
			if(!$arena->getTeam("BLUE")->isFull()){
				$this->data["TeamName"] = "BLUE";
				$this->data["OtherTeam"] = "RED";
			}else{
				$this->searchTeam();
			}
		}
	}
	
	
	
	public function setBattle(){
		$player = $this->getInstance();
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$blockc = $this->getTeam()->data["ArmorColor"];
		$helmet = Item::get(298, 0, 1);
		$chestplate = Item::get(299, 0, 1);
		$leggings = Item::get(300, 0, 1);
		$boots = Item::get(301, 0, 1);
		$swoord = Item::get(268, 0, 1);
		$picaxe = Item::get(278, 0, 1);
		$blocks = Item::get(159, $blockc[3], 64);
		$color = $this->getTeam()->getArmorColor();
		$helmet->setCustomColor($color);
		$chestplate->setCustomColor($color);
		$leggings->setCustomColor($color);
		$boots->setCustomColor($color);
		$player->getArmorInventory()->setHelmet($helmet);
		$player->getArmorInventory()->setChestplate($chestplate);
		$player->getArmorInventory()->setLeggings($leggings);
		$player->getArmorInventory()->setBoots($boots);
		$player->getInventory()->setHeldItemIndex(0);
		$player->getInventory()->setItem(0, $swoord);
		$player->getInventory()->setItem(1, $picaxe);
		$player->getInventory()->setItem(2, Item::get(322, 0, 5));
		$player->getInventory()->setItem(3, $blocks);
		$player->getInventory()->setItem(4, $blocks);
	}
	
	
	public function setKills(int $value){
		$this->data["kills"] = $value;
	}
	
	public function getKills(): int {
		return $this->data["kills"];
	}
	
	
	public function setRegister(bool $value){
		$this->data["register"] = $value;
	}
	
	public function getRegister(): bool {
		return $this->data["register"];
	}
}