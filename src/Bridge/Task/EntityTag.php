<?php
namespace Bridge\Task;

use Bridge\Database\Entity\EntityJoin;
use Bridge\Main;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Player as pocketPlayer;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class EntityTag extends Task{
	
	public function __construct(Main $eid){
		$this->plugin = $eid;
	}
	
	public function onRun(int $currentTick){
		foreach($this->plugin->getServer()->getLevels() as $level) {
			foreach($level->getEntities() as $entity) {
				if($entity instanceof EntityJoin){
					$entity->setNameTag(TextFormat::YELLOW . TextFormat::BOLD . count($this->plugin->getPlayers()) . " Playing\n" . TextFormat::AQUA . "The Bridge" . TextFormat::GRAY . " [Solo]" . TextFormat::RESET . "\n" . TextFormat::YELLOW . TextFormat::BOLD . "CLICK TO PLAY");
					$entity->getInventory()->setItem(0, Item::get(159, 11, 1));
					$entity->getInventory()->setHeldItemIndex(0);
					$entity->setNameTagAlwaysVisible(true);
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