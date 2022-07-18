<?php

namespace Bridge;

use Bridge\Database\Entity\EntityJoin;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player as pocketPlayer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as T;

class EventListener implements Listener
{

    private $plugin;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }


    public function onRecive(DataPacketReceiveEvent $ev)
    {
        $player = $ev->getPlayer();
        $arena = $this->plugin->getArena($player->getLevel()->getFolderName());
        if ($ev->getPacket() instanceof ModalFormResponsePacket) {
            if ($ev->getPacket()->formId === 5412) {
                $datos = json_decode($ev->getPacket()->formData, true);
                if ($datos !== null) {
                    switch ($datos) {
                        case TextFormat::DARK_RED . TextFormat::BOLD . "Salir de la arena":
                            $sender = $this->plugin->getPlayer($player->getName());
                            $playersCount = count($sender->getArena()->getPlayers()) - 1;
                            foreach ($sender->getArena()->getPocketPlayers() as $p) {
                                $p->sendMessage(TextFormat::GRAY . $player->getName() . TextFormat::YELLOW . " ha salido del juego. " . TextFormat::GRAY . "(" . $playersCount . "/" . $sender->getArena()->getMaxSlots() . ")");
                            }
                            $this->plugin->removePlayer($sender->getName());
                            unset($this->plugin->search[$sender->getName()]);
                            $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                            $player->sendMessage(TextFormat::YELLOW . "Has salido de la partida.");
                            break;
                    }
                }
            }
        }
    }


    public function onBreak(BlockBreakEvent $ev)
    {
        $sender = $ev->getPlayer();
        $spawns = $this->plugin->getConfiguration("spawns");
        $levelname = $sender->getLevel()->getFolderName();
        $block = $ev->getBlock();
        $player = $this->plugin->getPlayer($sender->getName());
        $arenas = $this->plugin->getArenas();
        if (isset($this->plugin->players[strtolower($sender->getName())])) {
            if ($player->getArenaName() == $levelname) {
                if ($player->getRegister()) {
                    $rray = [$block->getX(), $block->getY(), $block->getZ()];
                    $spawns->set($levelname . $player->getTeam()->getTeamName(), $rray);
                    $spawns->save();
                    $player->getTeam()->data["spawn"] = $spawns->get($levelname . $player->getTeamName());
                    $sender->sendMessage("El spawn " . $player->getTeam()->getColor() . $player->getTeam()->getTeamName() . TextFormat::RESET . " ha sido registrado");
                    $player->setRegister(false);
                    $this->plugin->removePlayer($sender->getName());
                    $ev->setCancelled(true);
                }
                if ($player->getArena()->getStatus() == "Lobby") {
                    $ev->setCancelled();
                }
            }
        }
    }


    public function onMove(PlayerMoveEvent $ev)
    {
        $sender = $ev->getPlayer();
        $player = $this->plugin->getPlayer($sender->getName());
        $levelname = $sender->getLevel()->getFolderName();
        $arena = $this->plugin->getArena($levelname);
        $block = $sender->getLevel()->getBlock($sender->floor()->subtract(0, 2));
        $block2 = $sender->getLevel()->getBlock($sender->floor()->subtract(0, 1));
        if (in_array($levelname, $this->plugin->arenas)) {
            $sender->setFood(20);
            if ($block->getId() == 35 and $block->getDamage() == 14) {
                if (isset($this->plugin->players[strtolower($sender->getName())])) {
                    if ($player->getArena()->getStatus() == "InGame") {
                        $points = $player->getTeam()->getPoints() + 1;
                        if ($player->getTeam()->getTeamName() == "BLUE") {
                            $player->getTeam()->setPoints($points);
                            $this->plugin->addStrike($sender);
                            foreach ($arena->getPlayers() as $p) {
                                $p->teleportToSlot();
                            }
                            foreach ($arena->getPocketPlayers() as $p) {
                                $this->plugin->respawnTask($p);
                                $p->sendMessage(TextFormat::GOLD . "--------------------" . TextFormat::RESET . "\n" . $player->getDisplayName() . TextFormat::GRAY . " (" . $player->getLife() . TextFormat::GRAY . ") " . TextFormat::YELLOW . "anoto!" . TextFormat::RESET . "\n" . TextFormat::BOLD . $player->getTeam()->getColor() . $player->getTeam()->getPoints() . TextFormat::GRAY . " - " . $arena->getTeam("RED")->getColor() . $arena->getTeam("RED")->getPoints() . TextFormat::RESET . "\n" . TextFormat::GOLD . "--------------------");
                            }
                        } else {
                            $player->teleportToSlot();
                            $sender->sendMessage(TextFormat::RED . "No puedes anotar punto en tu propia base.");
                        }
                    }
                }
            } elseif ($block->getId() == 35 and $block->getDamage() == 11) {
                if (isset($this->plugin->players[strtolower($sender->getName())])) {
                    if ($player->getArena()->getStatus() == "InGame") {
                        $points = $player->getTeam()->getPoints() + 1;
                        if ($player->getTeam()->getTeamName() == "RED") {
                            $player->getTeam()->setPoints($points);
                            $this->plugin->addStrike($sender);
                            foreach ($arena->getPlayers() as $p) {
                                $p->teleportToSlot();
                            }
                            foreach ($arena->getPocketPlayers() as $p) {
                                $this->plugin->respawnTask($p);
                                $p->sendMessage(TextFormat::GOLD . "--------------------" . TextFormat::RESET . "\n" . $player->getDisplayName() . TextFormat::GRAY . " (" . $player->getLife() . TextFormat::GRAY . ") " . TextFormat::YELLOW . "anoto!" . TextFormat::RESET . "\n" . TextFormat::BOLD . $player->getTeam()->getColor() . $player->getTeam()->getPoints() . TextFormat::GRAY . " - " . $arena->getTeam("BLUE")->getColor() . $arena->getTeam("BLUE")->getPoints() . TextFormat::RESET . "\n" . TextFormat::GOLD . "--------------------");
                            }
                        } else {
                            $player->teleportToSlot();
                            $sender->sendMessage(TextFormat::RED . "No puedes anotar punto en tu propia base.");
                        }
                    }
                }
            }
        }
    }


    public function onHit(EntityDamageEvent $ev)
    {
        if ($ev->getEntity() instanceof pocketPlayer) {
            $entity = $ev->getEntity();
            $arena = $this->plugin->getArena($entity->getLevel()->getFolderName());
            if (in_array($entity->getLevel()->getFolderName(), $this->plugin->arenas)) {
                if ($arena->getStatus() == "Lobby") {
                    $ev->setCancelled(true);
                }
            }
            if ($ev instanceof EntityDamageByEntityEvent) {
                if ($ev->getEntity() instanceof pocketPlayer && $ev->getDamager() instanceof pocketPlayer) {
                    $victim = $ev->getEntity();
                    $status = "-";
                    $damager = $ev->getDamager();
                    if (in_array($entity->getLevel()->getFolderName(), $this->plugin->arenas)) {
                        if ($arena->getStatus() != "InGame") {
                            $ev->setCancelled(true);
                        } else {
                            $this->plugin->data["lasthit"][$victim->getName()] = $damager->getName();
                        }
                    }
                }
            }
        }
    }

    public function onBlockPlaceYT(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->plugin->arenas)) {
            if ($block->getId() == 159) {
                if ($block->getSide(Vector3::SIDE_DOWN)->getId() == 159 or $block->getSide(Vector3::SIDE_NORTH)->getId() == 159 or $block->getSide(Vector3::SIDE_WEST)->getId() == 159 or $block->getSide(Vector3::SIDE_EAST)->getId() == 159) {
                } else {
                    $event->setCancelled(true);
                    $player->sendTip(T::RED . "You can't place this block");
                }
            } else {
                $event->setCancelled(true);
                $player->sendTip(T::RED . "You can't place this block");
            }
        }
    }

    public function onBlockBreajk(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $level = $player->getLevel()->getFolderName();
        if (in_array($level, $this->plugin->arenas)) {
            if ($block->getId() == 159) {
            } else {
                $event->setCancelled(true);
                $player->sendMessage(T::RED . "You can't break this block");
            }
        }
    }

    public function onMuerte(EntityDamageEvent $ev)
    {
        $player = $ev->getEntity();
        if ($player instanceof pocketPlayer) {
            $level = $player->getLevel()->getFolderName();
            $arena = $this->plugin->getArena($level);
            $damage = $ev->getFinalDamage() >= $player->getHealth();
            if (in_array($level, $this->plugin->arenas)) {
                switch ($ev->getCause()) {
                    case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                        if ($damage) {
                            $killer = $ev->getDamager();
                            if ($killer instanceof pocketPlayer) {
                                $ev->setCancelled(true);
                                $asesino = $this->plugin->getPlayer($killer->getName());
                                $jug = $this->plugin->getPlayer($player->getName());
                                $jug->teleportToSlot();
                                $jug->setBattle();
                                $player->setGamemode(0);
                                $player->getInventory()->setHeldItemIndex(0);
                                $player->setHealth(20);
                                $player->setHealth($player->getMaxHealth());
                                $kills = $asesino->getKills() + 1;
                                $asesino->setKills($kills);
                                $message = TextFormat::GRAY . $jug->getDisplayName() . TextFormat::YELLOW . " was killed by " . TextFormat::GOLD . $asesino->getDisplayName() . TextFormat::YELLOW . ".";
                                foreach ($player->getLevel()->getPlayers() as $p) {
                                    $p->level->broadcastLevelSoundEvent($p, LevelSoundEventPacket::SOUND_LEVELUP);
                                    $p->sendMessage($message);
                                }
                            }
                        }
                        break;
                    case EntityDamageEvent::CAUSE_VOID:
                        if ($ev->getFinalDamage() >= $player->getHealth()) {
                            if ($player->getGamemode() == 3) {
                                $ev->setCancelled(true);
                                $player->teleport($this->plugin->getServer()->getLevelByName($arena->getName())->getSafeSpawn());
                            }
                            if ($player->getGamemode() == 0) {
                                $ev->setCancelled(true);
                                $jug = $this->plugin->getPlayer($player->getName());
                                $jug->teleportToSlot();
                                $jug->setBattle();
                                $player->setGamemode(0);
                                $player->getInventory()->setHeldItemIndex(0);
                                $player->setHealth($player->getMaxHealth());
                                $message = $jug->getDisplayName() . TextFormat::YELLOW . " fell into the void.";
                                $pn2 = null;
                                foreach ($player->getLevel()->getPlayers() as $p) {
                                    if ($this->plugin->data["lasthit"][$player->getName()] == $p->getName()) {
                                        $pn2 = $p->getName();
                                    }
                                    if ($pn2 != null) {
                                        $asesino = $this->plugin->getPlayer($pn2);
                                        $kills = $asesino->getKills() + 1;
                                        $asesino->setKills($kills);
                                        $player->setHealth(20);
                                        $p->level->broadcastLevelSoundEvent($p, LevelSoundEventPacket::SOUND_LEVELUP);
                                        $p->sendMessage($jug->getDisplayName() . TextFormat::YELLOW . " fell to the void with the help of " . $asesino->getDisplayName());
                                    } else {
                                        $p->sendMessage($message);
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
    }


    public function onInteract(PlayerInteractEvent $ev)
    {
        $sender = $ev->getPlayer();
        $player = $this->plugin->getPlayer($sender->getName());
        if ($ev->getItem()->getCustomName() == TextFormat::RED . TextFormat::BOLD . "Leave " . TextFormat::RESET . TextFormat::GRAY . "[Click]") {
            $this->selectOption($sender);
        } elseif ($ev->getItem()->getCustomName() == TextFormat::AQUA . TextFormat::BOLD . "Play Again " . TextFormat::RESET . TextFormat::GRAY . "[Click]") {
            if (isset($this->plugin->players[$sender->getName()])) {
                $this->plugin->removePlayer($sender->getName());
            }
            if (!isset($this->plugin->search[$sender->getName()])) {
                $this->plugin->search[$sender->getName()] = "yes";
                $this->plugin->getTask($sender);
            } else {
                $sender->sendMessage(TextFormat::RED . "Ya estas buscando una arena, por favor espera.");
            }
        }
    }


    public function onDamageEntity(EntityDamageEvent $ev)
    {
        $entity = $ev->getEntity();
        if ($ev instanceof EntityDamageByEntityEvent) {
            $damager = $ev->getDamager();
            if ($entity instanceof EntityJoin && $damager instanceof pocketPlayer) {
                $ev->setCancelled(true);
                if (!isset($this->plugin->search[$damager->getName()])) {
                    $this->plugin->search[$damager->getName()] = "yes";
                    $this->plugin->getTask($damager);
                    $damager->sendMessage($this->plugin->getPrefix() . TextFormat::GOLD . " Buscando arena...");
                } else {
                    $damager->sendMessage(TextFormat::RED . "Ya estas buscando una arena, por favor espera.");
                }
            }
            if ($entity instanceof pocketPlayer && $damager instanceof pocketPlayer) {
                $sender = $this->plugin->getPlayer($damager->getName());
                $player = $this->plugin->getPlayer($entity->getName());
                if (isset($this->plugin->players[strtolower($entity->getName())]) && isset($this->plugin->players[strtolower($damager->getName())])) {
                    if ($sender->getTeamName() == $player->getTeamName()) {
                        $ev->setCancelled(true);
                        $damager->sendMessage(TextFormat::RED . "No puedes atacar a alguien de tu equipo.");
                    }
                }
            }
        }
    }
}