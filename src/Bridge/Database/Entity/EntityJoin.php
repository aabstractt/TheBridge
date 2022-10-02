<?php

namespace Bridge\Database\Entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;

class EntityJoin extends Human {
	
	public function getName() : String{
		return "Name";
	}
}