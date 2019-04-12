<?php

namespace Bridge\Database;

use pocketmine\utils\Color;
use pocketmine\{Player, Server};

use Bridge\Main;

class Team {
	
	public $data;
	private $plugin;
	
	public function __construct(Main $plugin, array $data){
		$this->plugin = $plugin;
		$this->data = $data;
	}
	
	
	public function getArena(){
		return $this->plugin->getArena($this->getArenaName());
	}
	
	public function getArenaName(): String {
		return $this->data["arena"];
	}
	
	
	public function getTeamName(){
		return $this->data["TeamName"];
	}
	
	public function getColor(){
		return $this->data["TeamColor"];
	}
	
	public function getDisplayName(){
		return $this->getColor() . $this->getTeamName();
	}
	
	
	public function isFull(){
		return count($this->getPlayers()) >= $this->getMaxSlots();
	}
	
	public function getPlayers(){
		$players = [];
		foreach($this->getArena()->getPlayers() as $player){
			if($player->getTeamName() == $this->getTeamName()){
				$players[$player->getName()] = $player;
			}
		}
		return $players;
	}
	
	public function getMaxSlots(): int {
		return $this->data["maxSlots"];
	}
	
	
	public function inTeam(String $name){
		$inteam = false;
		foreach($this->getPlayers() as $teamPlayer){
			if($name == $teamPlayer){
				$inteam = true;
			}
		}
		return $inteam;
	}
	
	
	public function getBlockColor(): int {
		return $this->data["TeamBlock"];
	}
	
	public function getArmorColor(){
		$color = $this->data["ArmorColor"];
		return new Color($color[0], $color[1], $color[2]);
	}
	
	
	public function setSpawn($spawn){
		$this->data["spawn"] = $spawn;
	}
	
	public function getSpawn() {
		return $this->data["spawn"];
	}
	
	
	public function getPoints(): int {
		return $this->data["points"];
	}
	
	public function setPoints(int $value){
		$this->data["points"] = $value;
	}
}