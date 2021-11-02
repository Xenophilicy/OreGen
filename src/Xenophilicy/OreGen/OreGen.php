<?php
declare(strict_types=1);
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

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Cobblestone;
use pocketmine\block\BlockFactory;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\Listener;
use pocketmine\world\sound\FizzSound;
use pocketmine\plugin\PluginBase;
use pocketmine\world\WorldManager;

/**
 * Class OreGen
 * @package Xenophilicy\OreGen
 */
class OreGen extends PluginBase implements Listener {
    
    const CONFIG_VERSION = "1.2.0";
    
    /** @var array */
    private $probabilityList = [];
    /** @var array */
    private $blockList = [];
    /** @var array */
    private $levels = [];
    /** @var string */
    private $listMode;
    
    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $version = $this->getConfig()->get("VERSION");
        if($version < self::CONFIG_VERSION){
            $this->getLogger()->warning("You've updated OreGen, but have an outdated config. Please delete your old config to prevent issues.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $mode = strtolower($this->getConfig()->getNested("Worlds.Mode"));
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
                $this->getLogger()->error("Invalid world list mode! Valid modes are 'blacklist', 'whitelist', or false. Invalid mode: " . $mode . " is not supported!, disabling plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
        }
        if($this->listMode !== false){
            $worldList = $this->getConfig()->getNested("Worlds.List");
            foreach($worldList as $world){
                if(!$this->getServer()->getWorldManager()->isWorldGenerated($world)){
                    $this->getLogger()->critical("Invalid world name! Name: " . $world . " was not found, disabling plugin! Be sure you use the name of the world folder for the world name in the config!");
                    $this->getServer()->getPluginManager()->disablePlugin($this);
                    return;
                }else{
                    array_push($this->levels, $world);
                }
            }
        }
        $this->buildProbability();
    }
    
    private function buildProbability(): void{
        $cobbleProb = $this->getConfig()->get("Cobble-Probability");
        if(!is_numeric($cobbleProb)){
            $this->getLogger()->error("Cobblestone probability must be numerical, disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        for($i = 0; $i < $cobbleProb; $i++){
            array_push($this->probabilityList, (string)BlockLegacyIds::COBBLESTONE . ":0");
        }
        $probSum = $cobbleProb;
        $blocks = $this->getConfig()->get("Blocks");
        foreach($blocks as $block => $probability){
            $values = explode(":", $block);
            try{
                BlockFactory::getInstance()->get((int)$values[0], isset($values[1]) ? (int)$values[1] : 0);
            }catch(\InvalidArgumentException $e){
                $this->getLogger()->critical("Invalid block! Block " . $block . " was not found, it will be disabled!");
                continue;
            }
            if(is_numeric($probability)){
                $this->blockList[$block] = $probability;
                $probSum += $probability;
                for($i = 0; $i < $probability; $i++){
                    array_push($this->probabilityList, $block);
                }
            }else{
                $this->getLogger()->critical("Invalid block probablity! Block " . $block . " has an invalid probability, it will be disabled!");
            }
        }
        if($probSum != 100){
            $this->getLogger()->critical("Block probability has a sum of " . $probSum . ", it must have a sum of 100, disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
    }
    
    public function onCobblestoneForm(BlockFormEvent $event): void{
        $worldName = $event->getBlock()->getPosition()->getWorld()->getName();
        if(($this->listMode == "wl" && !in_array($WorldName, $this->levels)) || ($this->listMode == "bl" && in_array($WorldName, $this->levels))) return;
        $block = $event->getBlock();
        if(!$event->getNewState() instanceof Cobblestone) return;
        $index = array_rand($this->probabilityList, 1);
        $values = explode(":", $this->probabilityList[$index]);
        $choice = Block::get((int)$values[0], isset($values[1]) ? (int)$values[1] : 0);
        $event->setCancelled();
        $block->getWorld()->setBlock($block, $choice, true, true);
        $block->getWorld()->addSound(new FizzSound($block->add(0.5, 0.5, 0.5), 2.6 + (lcg_value() - lcg_value()) * 0.8));
    }
}
