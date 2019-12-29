<?php

# Copyright SkyZoneMC 2019

namespace SkyZoneMC\UglyTroll;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\plugin\PluginBase;
use SkyZoneMC\UglyTroll\Tasks\RotatePlayerTask;

class Main extends PluginBase implements Listener {
    private $prefix = "§l§8[§aUgly§4Troll§8] §r§f";

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public $playerlaggers = [];

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if ($cmd->getName() == "troll") {
            if (!isset($args[0])) {
                return false;
            }
            if(!$sender->hasPermission("uglytroll.command.".$args[0])){
                $sender->sendMessage($this->prefix."No permissions!");
                return true;
            }
            if($args[0] == "serverlag"){
                if(!isset($args[1])){
                    $sender->sendMessage($this->prefix."Please specify a time in seconds! /troll serverlag <time>");
                }elseif(is_numeric($args[1])){
                    $sender->sendMessage($this->prefix."Lagging server!");
                    sleep((int) $args[1]);
                }else{
                    $sender->sendMessage($this->prefix."You didn't specify a valid number!");
                }
                return true;
            }
            if (!isset($args[1])) {
                $sender->sendMessage($this->prefix . "Please provide a player! /troll <function> <player> (<args..>)");
                return true;
            }
            if ($this->getServer()->getPlayer($args[1])) {
                $player = $this->getServer()->getPlayer($args[1]);
                if($player->hasPermission("uglytroll.except")){
                    $sender->sendMessage($this->prefix."This player can't be trolled!");
                    return true;
                }
            } else {
                $sender->sendMessage($this->prefix . "Player not found!");
                return true;
            }
            switch ($args[0]) {
                case "rotate":
                    if (isset($args[2])) {
                        if (is_numeric($args[2])) {
                            $rounds = (int)$args[2];
                        } else {
                            $sender->sendMessage($this->prefix . "You didn't specify a valid number!");
                            return true;
                        }
                    } else {
                        $rounds = 1;
                        $sender->sendMessage($this->prefix . "Did you know that you can specify the number of rounds? /troll rotate <player> <rounds>");
                    }
                    $sender->sendMessage($this->prefix . "Rotating " . $player->getName() . "!");
                    $player->setImmobile(true);
                    $this->getScheduler()->scheduleRepeatingTask(new RotatePlayerTask($player, $rounds), 1);
                    break;
                case "freeze":
                    if ($player->isImmobile()) {
                        $player->setImmobile(false);
                        $sender->sendMessage($this->prefix . "Unfreezed " . $player->getName() . "!");
                    } else {
                        $player->setImmobile(true);
                        $sender->sendMessage($this->prefix . "Freezed " . $player->getName() . "!");
                    }
                    break;
                case "randomtp":
                    if (!isset($args[2])) {
                        $sender->sendMessage($this->prefix . "Please specify a distance! /troll randomtp <player> <range>");
                        return true;
                    } elseif (!is_numeric($args[2])) {
                        $sender->sendMessage($this->prefix . "You didn't provide a valid range!");
                        return true;
                    }
                    $range = (int)$args[2];
                    $pos = [$player->getFloorX(), $player->getFloorZ()];
                    $op1 = rand(0, 1);
                    $op2 = rand(0, 1);
                    for ($i = 0; $i <= $range; $i++) {
                        if (rand(0, 1) == 0) {
                            if ($op1 == 0) {
                                $pos[0]++;
                            } else {
                                $pos[0]--;
                            }
                        } else {
                            if ($op2 == 0) {
                                $pos[1]++;
                            } else {
                                $pos[1]--;
                            }
                        }
                    }
                    $loc = new Vector3($pos[0], $player->getLevel()->getHighestBlockAt($pos[0], $pos[1]), $pos[1]);
                    $player->teleport($loc);
                    $sender->sendMessage($this->prefix . "Teleported " . $player->getName() . " to " . $loc->x . " " . $loc->y . " " . $loc->z);
                    break;
                case "burn":
                    if ($player->isOnFire()) {
                        $player->extinguish();
                        $sender->sendMessage($this->prefix . "Extinguished " . $player->getName() . "!");
                    } else {
                        $player->setOnFire(1000);
                        $sender->sendMessage($this->prefix . "Set " . $player->getName() . " on fire!");
                    }
                    break;
                case "playerlag":
                    if (!isset($args[2])) {
                        $sender->sendMessage($this->prefix . "Please provide the time in seconds you want to lag the player! /troll playerlag <player> <time>");
                        return true;
                    }
                    if (!is_numeric($args[2])) {
                        $sender->sendMessage($this->prefix . "Please provide a valid number of seconds!");
                        return true;
                    }
                    if (isset($this->playerlaggers[$player->getName()])) {
                        $sender->sendMessage($this->prefix . "Player already lagging.");
                        return true;
                    }
                    $this->playerlaggers[$player->getName()] = [time() + (int)$args[2], []];
                    $sender->sendMessage($this->prefix . " Lagging " . $player->getName() . "!");
                    break;
                case "fakeban":
                    $sender->sendMessage($this->prefix."Banned player ".$player->getName()."! Kappa");
                    $player->kick("Banned by admin.", false);
                    break;
                case "crash":
                    $chunk = $player->getLevel()->getChunkAtPosition($player);
                    $pk = LevelChunkPacket::withCache($chunk->getX(), $chunk->getZ(), 100000, [], "");
                    $player->sendDataPacket($pk);
                    $sender->sendMessage($this->prefix."Player should be crashed!");
                    break;
                default:
                    return false;
            }
        }
        return true;
    }

    public function onSendPacket(DataPacketSendEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if (isset($this->playerlaggers[$name])) {
            $until = $this->playerlaggers[$name][0];
            if ($until > time()) {
                $event->setCancelled(true);
                $this->playerlaggers[$player->getName()][1][] = $event->getPacket();
            } else {
                $data = $this->playerlaggers[$player->getName()];
                unset($this->playerlaggers[$player->getName()]);
                foreach ($data[1] as $pk) {
                    $pk->decode();
                    $player->sendDataPacket($pk);
                }
            }
        }
    }

    public function onReceivePacket(DataPacketReceiveEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if (isset($this->playerlaggers[$name])) {
            $until = $this->playerlaggers[$name][0];
            if ($until > time()) {
                $event->setCancelled(true);
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();

        if (isset($this->playerlaggers[$name])) {
            unset($this->playerlaggers[$name]);
        }
    }
}