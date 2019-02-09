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
use pocketmine\Player;
use pocketmine\utils\config;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\Level;

class OreGen extends PluginBase implements Listener{
    
    private $config;
    private $oreList = [];

	public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->config->getAll();
        $this->getLogger()->info("OreGen has been enabled!");
        $this->buildProbability();
	}

    public function buildProbability() : bool{
        $list = [];
        $cobbleProb = $this->config->get("Cobble-Probability");
        if(!is_numeric($cobbleProb)){
            $this->getLogger()->critical("Cobblestone probability must be numerical! Disabling plugin...");
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
            $this->getLogger()->critical("Ore probability has a sum of ".$sum);
            $this->getLogger()->critical("Probability must have a sum equal to 100! Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return false;
        }
        return true;
    }

    public function onBlockSet(BlockUpdateEvent $event) : bool{
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
                    if($this->oreList[$pb] === "Coal"){
                        $placeBlock = Block::get(Block::COAL_ORE);
                    }
                    elseif($this->oreList[$pb] === "Iron"){
                        $placeBlock = Block::get(Block::IRON_ORE);
                    }
                    elseif($this->oreList[$pb] === "Gold"){
                        $placeBlock = Block::get(Block::GOLD_ORE);
                    }
                    elseif($this->oreList[$pb] === "Lapis"){
                        $placeBlock = Block::get(Block::LAPIS_ORE);
                    }
                    elseif($this->oreList[$pb] === "Redstone"){
                        $placeBlock = Block::get(Block::REDSTONE_ORE);
                    }
                    elseif($this->oreList[$pb] === "Emerald"){
                        $placeBlock = Block::get(Block::EMERALD_ORE);
                    }
                    elseif($this->oreList[$pb] === "Diamond"){
                        $placeBlock = Block::get(Block::DIAMOND_ORE);
                    }
                    else{
                        $placeBlock = Block::get(Block::COBBLESTONE);
                    }
                    $block->getLevel()->setBlock($block, $placeBlock, false, false);
                    return true;
                }
            }
        }
        return true;
    }
}
