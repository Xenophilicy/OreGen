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
    private $oreList = [];

	public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->config->getAll();
        $this->getLogger()->info("OreGen has been enabled!");
        $version = $this->config->get("VERSION");
        if($version != "1.1.4"){
            $this->getLogger()->warning("You have updated OreGen but are using an old config! Please delete your outdated config for new features to be enabled!");
        }
        if($this->config->get("World-List") !== null && $this->config->get("World-List") !== []){
            if($this->config->get("List-Mode") == null || $this->config->get("List-Mode") == ""){
                $this->getLogger()->error("The list mode cannot be left null! Please choose either 'Blacklist' or 'Whitelist'! Disabling plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return false;
            }
        }
        $this->buildProbability();
	}

    public function buildProbability() : bool{
        $list = [];
        $cobbleProb = $this->config->get("Cobble-Probability");
        if(!is_numeric($cobbleProb)){
            $this->getLogger()->error("Cobblestone probability must be numerical! Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return false;
        }
        for($i=0;$i<$cobbleProb;$i++){
            $this->oreList = array_push($list,'Cobble');
        }
        $sum = $cobbleProb;
        $ores = ['Coal','Iron','Gold','Lapis','Redstone','Emerald','Diamond'];
        foreach($ores as $ore){
            $enabled = $this->config->getNested($ore.".Enabled");
            if($enabled === true){
                $chance = $this->config->getNested("$ore.Probability");
                if(is_numeric($chance)){
                    $sum = $sum + $chance;
                    for($i=0;$i<$chance;$i++){
                        $this->oreList = array_push($list,$ore);
                    }
                }
                else{
                    $this->getLogger()->warning("Ore '".$ore."' has an invalid value, it will be disabled!");
                }
            }
            elseif($enabled !== false){
                $this->getLogger()->warning("Ore '".$ore."' has an invalid value, it will be disabled!");
            }
        }
        $this->oreList = $list;
        if($sum != 100){
            $this->getLogger()->error("Ore probability has a sum of ".$sum);
            $this->getLogger()->error("Probability must have a sum equal to 100! Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return false;
        }
        return true;
    }

    public function onBlockSet(BlockUpdateEvent $event){
        if($this->config->get("World-List") !== false){
            if($this->config->get("List-Mode") == "Whitelist"){
                if(!in_array($event->getBlock()->getLevel()->getName(), $this->config->get("World-List"))){
                    return;
                }
            }
            elseif($this->config->get("List-Mode") == "Blacklist"){
                if(in_array($event->getBlock()->getLevel()->getName(), $this->config->get("World-List"))){
                    return;
                }
            }
        }
        $this->blockSet($event);
    }

    public function blockSet($event){
        $block = $event->getBlock();
        $waterPresent = false;
        $lavaPresent = false;
        if ($block == "Block[Cobblestone] (4:0)"){
            for ($target = 2; $target <= 5; $target++) {
                $blockSide = $block->getSide($target);
                if ($blockSide instanceof Water) {
                    $waterPresent = true;
                }
                elseif ($blockSide instanceof Lava) {
                    $lavaPresent = true;
                }
                if ($waterPresent && $lavaPresent) {
                    $pb = array_rand($this->oreList,1);
                    switch($this->oreList[$pb]){
                        case "Coal":
                            $placeBlock = Block::get(Block::COAL_ORE);
                            break;
                        case "Iron":
                            $placeBlock = Block::get(Block::IRON_ORE);
                            break;
                        case "Gold":
                            $placeBlock = Block::get(Block::GOLD_ORE);
                            break;
                        case "Lapis":
                            $placeBlock = Block::get(Block::LAPIS_ORE);
                            break;
                        case "Redstone":
                            $placeBlock = Block::get(Block::REDSTONE_ORE);
                            break;
                        case "Emerald":
                            $placeBlock = Block::get(Block::EMERALD_ORE);
                            break;
                        case "Diamond":
                            $placeBlock = Block::get(Block::DIAMOND_ORE);
                            break;
                        default:
                            $placeBlock = Block::get(Block::COBBLESTONE);
                    }
                    $block->getLevel()->setBlock($block, $placeBlock, false, false);
                    return true;
                }
            }
        }
    }
}
