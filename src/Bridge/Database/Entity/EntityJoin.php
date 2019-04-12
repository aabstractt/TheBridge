<?php

namespace Bridge\Database\Entity;

use pocketmine\entity\Human;
use pocketmine\entity\Entity;

use Bridge\Main;

class EntityJoin extends Human {
	
	public function getName() : String{
		return "Name";
	}
}