<?php
# MADE BY:
#  __    __                                          __        __  __  __                     
# /  |  /  |                                        /  |      /  |/  |/  |                    
# $$ |  $$ |  ______   _______    ______    ______  $$ |____  $$/ $$ |$$/   _______  __    __ 
# $$  \/$$/  /      \ /       \  /      \  /      \ $$      \ /  |$$ |/  | /       |/  |  /  |
#  $$  $$<  /$$$$$$  |$$$$$$$  |/$$$$$$  |/$$$$$$  |$$$$$$$  |$$ |$$ |$$ |/$$$$$$$/ $$ |  $$ |
#   $$$$  \ $$    $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |$$ |$$ |      $$ |  $$ |
#  $$ /$$  |$$$$$$$$/ $$ |  $$ |$$ \__$$ |$$ |__$$ |$$ |  $$ |$$ |$$ |$$ |$$ \_____ $$ \__$$ |
# $$ |  $$ |$$       |$$ |  $$ |$$    $$/ $$    $$/ $$ |  $$ |$$ |$$ |$$ |$$       |$$    $$ |
# $$/   $$/  $$$$$$$/ $$/   $$/  $$$$$$/  $$$$$$$/  $$/   $$/ $$/ $$/ $$/  $$$$$$$/  $$$$$$$ |
#                                         $$ |                                      /  \__$$ |
#                                         $$ |                                      $$    $$/ 
#                                         $$/                                        $$$$$$/

namespace Xenophilicy\OreGen;

use pocketmine\plugin\PluginBase;
use pocketmine\block\{Block,Water,Lava};
use pocketmine\utils\config;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;

class OreGen extends PluginBase implements Listener{
    
    private $config;
    private $listMode;
    private $blockList = [];
    private $levels = [];

	public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->config->getAll();
        $version = $this->config->get("VERSION");
        if($version !== "1.2.0"){
            $this->getLogger()->warning("You have updated OreGen but are using an old config! Please delete your outdated config to continue using OreGen!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $levels = scandir($this->getServer()->getDataPath()."worlds/");
        foreach($levels as $level){
            if($level === "." || $level === ".."){
                continue;
            } else{
                $this->getServer()->loadLevel($level); 
            }
        }
        $worldList = $this->config->getNested("Worlds.List");
        $this->list = $this->config->get("List");
        foreach ($worldList as $world) {
            $level = $this->getServer()->getLevelByName($world);
            if($level === null){
                $this->getLogger()->critical("Invalid world name! Name: ".$world." was not found, disabling plugin! Be sure you use the name of the world folder for the world name in the config!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            } else{
                array_push($this->levels,$world);
            }
        }
        $mode = strtolower($this->config->getNested("Worlds.Mode"));
        switch($mode){
            case "whitelist":
                $this->listMode = "wl";
                break;
            case "blacklist":
                $this->listMode = "bl";
            break;
            case false:
                $this->listMode = false;
                break;
            default:
                $this->getLogger()->error("Invalid world list mode! Valid modes are 'blacklist', 'whitelist', or false. Invalid mode: ".$mode." is not supported!, disabling plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
        }
        $this->buildProbability();
	}

    public function buildProbability(){
        $cobbleProb = $this->config->get("Cobble-Probability");
        if(!is_numeric($cobbleProb)){
            $this->getLogger()->error("Cobblestone probability must be numerical, disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        for($i=0;$i<$cobbleProb;$i++){
            array_push($this->blockList,"4:0");
        }
        $probSum = $cobbleProb;
        $blocks = $this->config->get("Blocks");
        foreach($blocks as $block => $probability){
            $values = explode(":",$block);
            try{
                $test = Block::get((int)$values[0],isset($values[1]) ? (int)$values[1]:0);
            } catch(\InvalidArgumentException $e){
                $this->getLogger()->warning("Invalid block! Block ".$block." was not found, it will be disabled!");
                continue;
            }
            $chance = $this->config->getNested("Blocks.".$block);
            if(is_numeric($chance)){
                $probSum += $chance;
                for($i=0;$i<$chance;$i++){
                    array_push($this->blockList,$block);
                }
            } else{
                $this->getLogger()->warning("Invalid block probablity! Block ".$block." has an invalid probability, it will be disabled!");
            }
        }
        if($probSum != 100){
            $this->getLogger()->error("Block probability has a sum of ".$probSum.", it must have a sum of 100, disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
    }

    public function onBlockUpdate(BlockUpdateEvent $event){
        $levelName = $event->getBlock()->getLevel()->getName();
        if(($this->listMode == "wl" && !in_array($levelName,$this->levels)) || ($this->listMode == "bl" && in_array($levelName,$this->levels))){
            return;
        } else{
            $this->blockSet($event);
        }
    }

    public function blockSet($event){
        $block = $event->getBlock();
        $waterPresent = false;
        $lavaPresent = false;
        if ($block->getId() == 4 && $block->getDamage() == 0){
            for ($target = 2; $target <= 5; $target++) {
                $blockSide = $block->getSide($target);
                if ($blockSide instanceof Water) {
                    $waterPresent = true;
                } elseif ($blockSide instanceof Lava) {
                    $lavaPresent = true;
                }
                if ($waterPresent && $lavaPresent) {
                    $pb = array_rand($this->blockList,1);
                    $values = explode(":",$this->blockList[$pb]);
                    $event->setCancelled();
                    $block->getLevel()->setBlock($block, Block::get($values[0],$values[1]), false, false);
                }
            }
        }
    }
}
