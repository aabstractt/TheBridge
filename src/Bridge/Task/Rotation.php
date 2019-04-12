<?php

namespace Bridge\Task;

use pocketmine\Player as pocketPlayer;;
use Bridge\Database\Entity\EntityJoin;
use Bridge\Main;
use pocketmine\math\Vector2;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;
use pocketmine\entity\Human;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class Rotation extends Task {

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	public function onRun(int $tick) {
		foreach($this->plugin->getServer()->getLevels() as $level){
			foreach($level->getEntities() as $entity){
				if($entity instanceof EntityJoin){
					$this->sendMovement($entity);
				}
			}
		}
	}
	
	
	public function sendMovement(Entity $entity) {
		foreach($entity->getLevel()->getNearbyEntities($entity->getBoundingBox()->expandedCopy(15, 15, 15), $entity) as $player) {
			if(!$player instanceof pocketPlayer){
				return true;
			}
			$xdiff = $player->x - $entity->x;
			$zdiff = $player->z - $entity->z;
			$angle = atan2($zdiff, $xdiff);
			$yaw = (($angle * 180) / M_PI) - 90;
			$ydiff = $player->y - $entity->y;
			$v = new Vector2($entity->x, $entity->z);
			$dist = $v->distance($player->x, $player->z);
			$angle = atan2($dist, $ydiff);
			$pitch = (($angle * 180) / M_PI) - 90;
			$pk = new MovePlayerPacket();
			$pk->entityRuntimeId = $entity->getId();
			$pk->position = $entity->asVector3()->add(0, $entity->getEyeHeight(), 0);
			$pk->yaw = $yaw;
			$pk->pitch = $pitch;
			$pk->headYaw = $yaw;
			$pk->onGround = $entity->onGround;
			$player->dataPacket($pk);
		}
	}
}