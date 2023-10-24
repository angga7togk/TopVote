<?php

namespace angga7togk\topvote;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class TopVote extends PluginBase
{
    use SingletonTrait;

    public Config $pos;
    private array $particle = [];
    public array $votes = [];

    public function onLoad(): void
    {
        self::setInstance($this);
    }

    public function onEnable(): void
    {
        $this->saveResource("pos.yml");
        $this->pos = new Config($this->getDataFolder() . "pos.yml", Config::YAML, array());
        if (empty($this->getConfig()->get("key"))) {
            $this->getServer()->getLogger()->warning("Key Api vote not found!");
            $this->getServer()->shutdown(); //Stop the server if api key is empty
            return;
        }
        if (!$this->pos->exists("position")) {
            $this->getServer()->getLogger()->info("Please Set Location");
            return;
        }
        $pos = $this->pos->get("position");
        $this->particle[] = new FloatingText(new Vector3($pos[0], $pos[1], $pos[2]));
        $this->getScheduler()->scheduleRepeatingTask(new UpdateTask($this), 20 * 5);
        $this->getServer()->getLogger()->info("Location Have Been Load");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Please use command in game!");
            return false;
        }
        if ($command->getName() == "settopvote") {
            $this->pos->setNested("position", [$sender->getPosition()->getX(), $sender->getPosition()->getY(), $sender->getPosition()->getZ()]);
            $this->pos->save();
            $sender->sendMessage("Please restart the server to update the leaderboard");
        }
        return true;
    }

    public function getLeaderBoard(): string
    {
        $data = $this->votes;
        $message = "";
        $top = $this->getConfig()->get("format")["title"];
        $footer = $this->getConfig()->get("format")["footer"];
        if (count($data) > 0) {
            arsort($data);
            $i = 1;
            foreach ($data as $name => $vote) {
                $message .= str_replace(["{top}", "{player}", "{vote}"], [$i, $name, $vote], $this->getConfig()->get("format")["message"]);
                if ($i > $this->getConfig()->get("top") - 1) {
                    break;
                }
                ++$i;
            }
        }
        return $top . $message . $footer;

    }


    public function getParticles(): array
    {

        return $this->particle;

    }
}