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
use pocketmine\command\{Command,CommandSender};
use pocketmine\utils\{Config,TextFormat as TF};
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;

class OreGen extends PluginBase implements Listener{

    private $probabilityList = [];
    private $blockList = [];
    private $levels = [];

	public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $configPath = $this->getDataFolder()."config.yml";
        if(!file_exists($configPath)){
            $this->getLogger()->critical("It appears that this is the first time you are using OreGen! This plugin does not function with the default config.yml, so please edit it to your preferred settings before attempting to use it.");
            $this->saveDefaultConfig();
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->config = new Config($configPath, Config::YAML);
        $this->config->getAll();
        $version = $this->config->get("VERSION");
        $this->pluginVersion = $this->getDescription()->getVersion();
        if($version < "1.2.0"){
            $this->getLogger()->warning("You have updated OreGen to v".$this->pluginVersion." but have a config from v$version! Please delete your old config for new features to be enabled and to prevent unwanted errors! Plugin will remain disabled...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        $levels = scandir($this->getServer()->getDataPath()."worlds/");
        foreach($levels as $level){
            if($level === "." || $level === ".."){
                continue;
            } else{
                $this->getServer()->loadLevel($level); 
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
            case "false":
            case false:
                $this->listMode = false;
                break;
            default:
                $this->getLogger()->error("Invalid world list mode! Valid modes are 'blacklist', 'whitelist', or false. Invalid mode: ".$mode." is not supported!, disabling plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
        }
        if($this->listMode !== false){
            $worldList = $this->config->getNested("Worlds.List");
            foreach($worldList as $world){
                $level = $this->getServer()->getLevelByName($world);
                if($level === null){
                    $this->getLogger()->critical("Invalid world name! Name: ".$world." was not found, disabling plugin! Be sure you use the name of the world folder for the world name in the config!");
                    $this->getServer()->getPluginManager()->disablePlugin($this);
                    return;
                } else{
                    array_push($this->levels,$world);
                }
            }
        }
        $this->buildProbability();
	}

    private function buildProbability(){
        $cobbleProb = $this->config->get("Cobble-Probability");
        if(!is_numeric($cobbleProb)){
            $this->getLogger()->error("Cobblestone probability must be numerical, disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        for($i=0;$i<$cobbleProb;$i++){
            array_push($this->probabilityList,Block::COBBLESTONE);
        }
        $probSum = $cobbleProb;
        $blocks = $this->config->get("Blocks");
        foreach($blocks as $block => $probability){
            $values = explode(":",$block);
            try{
                Block::get((int)$values[0],isset($values[1]) ?(int)$values[1]:0);
            } catch(\InvalidArgumentException $e){
                $this->getLogger()->warning("Invalid block! Block ".$block." was not found, it will be disabled!");
                continue;
            }
            if(is_numeric($probability)){
                $this->blockList[$block] = $probability;
                $probSum += $probability;
                for($i=0;$i<$probability;$i++){
                    array_push($this->probabilityList,$block);
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

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() == "oregen"){
            $sender->sendMessage(TF::GRAY."---".TF::GOLD." OreGen ".TF::GRAY."---");
            $sender->sendMessage(TF::YELLOW."Version: ".TF::AQUA.$this->pluginVersion);
            $sender->sendMessage(TF::YELLOW."Description: ".TF::AQUA."Generate ores inside a cobble generator");
            $sender->sendMessage(TF::GREEN."Blocks: ");
            foreach($this->blockList as $block => $probability){
                $values = explode(":",$block);
                $blockName = Block::get((int)$values[0],isset($values[1]) ?(int)$values[1]:0)->getName();
                $sender->sendMessage(TF::GOLD." - ".TF::BLUE.$blockName.TF::GOLD." | ".TF::LIGHT_PURPLE.$probability);
            }
            $sender->sendMessage(TF::GRAY."-------------------");
        }
        return true;
    }

    public function onBlockUpdate(BlockUpdateEvent $event){
        $levelName = $event->getBlock()->getLevel()->getName();
        if(($this->listMode == "wl" && !in_array($levelName,$this->levels)) ||($this->listMode == "bl" && in_array($levelName,$this->levels))){
            return;
        } else{
            $this->blockSet($event);
        }
    }

    private function blockSet($event){
        $block = $event->getBlock();
        $waterPresent = false;
        $lavaPresent = false;
        if($block->getId() === Block::COBBLESTONE){
            for($target = 2; $target <= 5; $target++){
                $blockSide = $block->getSide($target);
                if($blockSide instanceof Water){
                    $waterPresent = true;
                } elseif($blockSide instanceof Lava){
                    $lavaPresent = true;
                }
                if($waterPresent && $lavaPresent){
                    $event->setCancelled();
                    $pb = array_rand($this->probabilityList,1);
                    $values = explode(":",$this->probabilityList[$pb]);
                    $block->getLevel()->setBlock($block, Block::get($values[0],$values[1]), false, false);
                }
            }
        }
    }
}