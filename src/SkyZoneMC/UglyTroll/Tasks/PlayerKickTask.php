<?php

namespace SkyZoneMC\UglyTroll\Tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class PlayerKickTask extends Task {

    protected Player $player;
    protected string $reason;

    public function __construct(Player $player, string $reason)
    {
        $this->player = $player;
        $this->reason = $reason;
    }

    public function onRun(): void
    {
        $this->player->kick($this->reason, false);
    }
}