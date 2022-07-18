<?php

namespace Bridge\Arena;

use Bridge\BossAnnounce\API;
use Bridge\Main;
use pocketmine\{Player as pocketPlayer, Server};
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class Arena {
	
	public $data;
	private $plugin;
	
	public function __construct(Main $plugin, array $data){
		$this->plugin = $plugin;
		$this->data = $data;
	}
	
	
	public function getName(): String {
		return $this->data["name"];
	}
	
	
	public function getMaxSlots(): int {
		return $this->data["maxSlots"];
	}
	
	public function getPlayers(): array {
		$players = [];
		foreach($this->plugin->getPlayers() as $player){
			if($player->getArenaName() == $this->getName()){
				$players[$player->getName()] = $player;
			}
		}
		return $players;
	}
	
	/*public function getSpectators(): array {
		$players = [];
		foreach($this->plugin->getSpectators() as $player){
			if($player->getArenaName() == $this->getName()){
				$players[$player->getName()] = $player;
			}
		}
		return $players;
	}*/
	
	
	public function getPocketPlayers(){
		$players = [];
		foreach($this->getPlayers() as $player){
			if($player->getInstance() instanceof pocketPlayer){
				$players[$player->getName()] = $player->getInstance();
			}
		}
		return $players;
	}
	
	
	public function getPlayersEveryone(): array {
		$players = [];
		foreach($this->getPlayers() as $player){
			$players[$player->getName()] = $player;
		}
		/*foreach($this->getSpectators() as $player){
			$players[$player->getName()] = $player;
		}*/
		return $players;
	}
	
	public function getPocketEveryone(): array {
		$players = [];
		foreach($this->getPocketPlayers() as $player){
			$players[$player->getName()] = $player;
		}
		/*foreach($this->getPocketSpectators() as $player){
			$players[$player->getName()] = $player;
		}*/
		return $players;
	}
	
	
	public function getTeam(String $team){
		$teamsleft = false;
		if(isset($this->data["Team"][$team])){
			$teamsleft = $this->data["Team"][$team];
		}
		return $teamsleft;
	}
	
	
	public function getStatus(): String {
		return $this->data["status"];
	}
	
	public function setStatus(String $value){
		$this->data["status"] = $value;
	}
	
	
	public function setLobbyTime(int $value){
		$this->data["LobbyTime"] = $value;
	}
	
	public function setGameTime(int $value){
		$this->data["GameTime"] = $value;
	}
	
	public function setEndTime(int $value){
		$this->data["EndTime"] = $value;
	}
	
	public function getLobbyTime(): int {
		return $this->data["LobbyTime"];
	}
	
	public function getGameTime(): int {
		return $this->data["GameTime"];
	}
	
	public function getEndTime(): int {
		return $this->data["EndTime"];
	}
	
	
	public function resetData(){
		$this->setLobbyTime(10);
		$this->setGameTime(1800);
		$this->setEndTime(60);
		$this->setStatus("Lobby");
	}
}