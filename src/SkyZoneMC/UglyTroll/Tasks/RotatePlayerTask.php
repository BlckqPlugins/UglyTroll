<?php

namespace SkyZoneMC\UglyTroll\Tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class RotatePlayerTask extends Task {
    private $player;
    private $rounds;

    public function __construct(Player $player, int $rounds) {
        $this->player = $player;
        $this->rounds = $rounds;
    }

    private $point = 0;

    public function onRun(): void {
        if ($this->player->isOnline()) {
            if($this->rounds == 0){
                $this->player->setImmobile(false);
                $this->getHandler()->cancel();
                return;
            }

            if ($this->point == 40) {
                $this->point = 1;
                $this->rounds--;
            }

            $yaw = 360 / 40 * $this->point;
            $this->player->teleport($this->player->getPosition()->asVector3(), (float)$yaw, $this->player->getLocation()->getPitch());
            $this->point++;
        } else {
            $this->getHandler()->cancel();
        }
    }
}