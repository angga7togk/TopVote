<?php

namespace angga7togk\topvote;

use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class UpdateTask extends Task
{

    private TopVote $pl;
    public string $key;

    public function __construct(TopVote $pl)
    {
        $this->pl = $pl;
        $this->key = $pl->getConfig()->get("key");
    }

    public function onRun(): void
    {
        $this->pl->getServer()->getAsyncPool()->submitTask(new AsyncTaskTV($this->key));

        $lb = $this->pl->getLeaderBoard();
        $list = $this->pl->getParticles();
        foreach ($list as $particle) {
            $particle->setText($lb);
        }
    }
}

class AsyncTaskTV extends AsyncTask
{

    public string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function onRun(): void
    {
        $this->updateVotes();
    }

    public function updateVotes()
    {
        $key = $this->key;
        $url = "https://minecraftpocket-servers.com/api/?object=servers&element=voters&key=$key&month=current&format=json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3000); // 3 sec.
        curl_setopt($ch, CURLOPT_TIMEOUT, 10000); // 10 sec.
        $result = curl_exec($ch);
        curl_close($ch);
        $this->setResult($result);
    }

    public function onCompletion(): void
    {
        $result = $this->getResult();
        if ($result === "Error: server key not found") {
            Server::getInstance()->getLogger()->warning("api key doesnt connect");
        } else {
            $json = json_decode($result, true);
            if (isset($json["voters"])) {

                foreach ($json["voters"] as $voter) {

                    TopVote::getInstance()->votes[$voter["nickname"]] = $voter["votes"];

                }

            }

        }
    }
}