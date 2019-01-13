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
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\config;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\block\Water;
use pocketmine\block\Lava;

class OreGen extends PluginBase implements Listener{
    
    private $config;

    public function onLoad(){
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."settings.yml", Config::YAML);
        $this->config->getAll();
        $this->getLogger()->info("§eOreGen by §6Xenophilicy §eis loading...");
    }

	public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->Info("§6OreGen§a has been enabled!");
	}
	
    public function onDisable(){
        $this->getLogger()->info("§6OreGen§c has been disabled!");   
    }

    public function onBlockSet(BlockUpdateEvent $event) : bool{
        $block = $event->getBlock();
        $waterPresent = false;
        $lavaPresent = false;
        $defaultBlock = Block::get(Block::COBBLESTONE);
        $coal = $this->config->get("Coal");
        $iron = $this->config->get("Iron");
        $gold = $this->config->get("Gold");
        $lapis = $this->config->get("Lapis");
        $emerald = $this->config->get("Emerald");
        $diamond = $this->config->get("Diamond");
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
                    $pickBlock = mt_rand(1, $this->config->get("Probability"));
                    switch ($pickBlock) {
                        case 1:
                            if($coal){
                                $placeBlock = Block::get(Block::COAL_ORE);
                            }
                            else{
                                $placeBlock = $defaultBlock;
                            }
                            break;
                        case 2:
                            if($iron){
                            $placeBlock = Block::get(Block::IRON_ORE);
                            }
                            else{
                                $placeBlock = $defaultBlock;
                            }
                            break;
                        case 3:
                            if($gold){
                            $placeBlock = Block::get(Block::GOLD_ORE);
                            }
                            else{
                                $placeBlock = $defaultBlock;
                            }
                            break;
                        case 4:
                            if($lapis){
                            $placeBlock = Block::get(Block::LAPIS_ORE);
                            }
                            else{
                                $placeBlock = $defaultBlock;
                            }
                            break;
                        case 5:
                            if($emerald){
                            $placeBlock = Block::get(Block::EMERALD_ORE);
                            }
                            else{
                                $placeBlock = $defaultBlock;
                            }
                            break;
                        case 6:
                            if($diamond){
                            $placeBlock = Block::get(Block::DIAMOND_ORE);
                            }
                            else{
                                $placeBlock = $defaultBlock;
                            }
                            break;
                        default:
                            $placeBlock = $defaultBlock;
                            break;
                    }
                $block->getLevel()->setBlock($block, $placeBlock, false, false);
                return true;
                }
            }
        }
        return true;
    }
}
?>