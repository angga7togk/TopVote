<?php

namespace angga7togk\topvote;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

class TopVote extends PluginBase {

	public Config $cfg;
	public Config $pos;
	private $particle = [];
	public array $votes = [];

	public static $instance;
	
	public function onLoad(): void {
		self::$instance = $this;
	}

	public function onEnable():void{
		$this->saveResource("config.yml");
		$this->saveResource("pos.yml");
		$this->cfg = new Config($this->getDataFolder(). "config.yml", Config::YAML, array());
		$this->pos = new Config($this->getDataFolder(). "pos.yml", Config::YAML, array());
		if(empty($this->cfg->get("key"))){
			$this->getServer()->getLogger()->Info("Key Api vote not found!");
			return;
		}
		if(!$this->pos->exists("position")){
			$this->getServer()->getLogger()->Info("Please Set Location");
			return;
		}
		$pos = $this->pos->get("position");
		$this->particle[] = new FloatingText(new Vector3($pos[0], $pos[1], $pos[2]));
		$this->getScheduler()->scheduleRepeatingTask(new UpdateTask($this), 20*5);
    	$this->getServer()->getLogger()->Info("Location Have Been Load");
	}

	public function onCommand(CommandSender $sender, Command $command, String $label, array $args):bool{
		if(!$sender instanceof Player){
			$sender->sendMessage("pake command di server jir :V");
			return false;
		}
		if($command->getName() == "settopvote"){
			$this->pos->setNested("position", [$sender->getPosition()->getX(), $sender->getPosition()->getY(), $sender->getPosition()->getZ()]);
			$this->pos->save();
			$sender->sendMessage("Silakan restart server ya tod biar update");
		}
		return true;
	}

	public function getLeaderBoard(): string{
		$data = $this->votes;
		$message = "";
		$top = $this->cfg->get("format")["title"];
		$footer = $this->cfg->get("format")["footer"];
		if(count($data) > 0){
    		arsort($data);
    		$i = 1;
			foreach ($data as $name => $vote) {
				$message .= str_replace(["{top}", "{player}", "{vote}"], [$i, $name, $vote], $this->cfg->get("format")["message"]);
				if($i > $this->cfg->get("top") - 1){
				break;
				}
				++$i;
			}
		}
    	return (string) $top.$message.$footer;

	}

	
	public function getParticles(): array{

		return $this->particle;

	}
	
	public static function getInstance():self{
		return self::$instance;
	}
}