<?php

namespace bridge;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\Config;
use pocketmine\Player;

use bridge\task\BridgeTask;
use bridge\utils\arena\Arena;
use bridge\utils\arena\ArenaManager;
use pocketmine\utils\TextFormat as T;

class Main extends PluginBase{
	
	public $arenas = [];
	
	public $prefix = T::GRAY."".T::YELLOW."§l§bThebridge".T::GRAY."";
	
	private $pos1 = [];
	
	private $pos2 = [];
	
	private $pos = [];
	
	private $spawn1 = [];
	
	private $spawn2= [];
	
	private $respawn1= [];
	
	private $respawn2= [];
	
	public function onEnable(){
		$this->initResources();
		$this->initArenas();
		
		$this->getScheduler()->scheduleRepeatingTask($this->scheduler = new BridgeTask($this), 20);
		$this->getServer()->getPluginManager()->registerEvents(new Arena($this), $this);
	}
	
	public function onDisable(){
		$this->close();
	}
	
	private function initResources(){
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "mapas/");
		@mkdir($this->getDataFolder() . "arenas/");
	}
	
	private function initArenas(){
		$src = $this->getDataFolder() . "arenas/";
		$count = 0;
		foreach(scandir($src) as $file){
			if($file !== ".." and $file !== "."){
				if(file_exists("$src" . $file)){
					$data = (new Config("$src" . $file, Config::YAML))->getAll();
					if(!isset($data["name"])){
						@unlink("$src" . $file);
						continue;
					}
					$this->arenas[strtolower($data["name"])] = new ArenaManager($this, $data);
					$count++;
				}
			}
		}
		return $count;
	}
	
	public function getPlayerArena(Player $p){
		$arenas = $this->arenas;
		if(count($arenas) <= 0){
			return null;
		}
		foreach($arenas as $arena){
			if($arena->isInArena($p)){
				return $arena;
			}
		}
		return null;
	}
	
	public function updateArenas($value = false){
		if(count($this->arenas) <= 0){
			return false;
		}
		foreach($this->arenas as $arena){
			$arena->onRun($value);
		}
	}
	
	private function close(){
		foreach($this->arenas as $name => $arena){
			$arena->close();
		}
	}
	
	public function join($player, $mode = "solo"){
		foreach($this->arenas as $name => $arena){
			if($arena->getData()["mode"] == $mode){
				if($arena->join($player)){
					return true;
				}
			}
		}
		return false;
	}
	
	public function createBridge($name, $p, $pos1, $pos2, $spawn1, $spawn2, $respawn1, $respawn2, $pos, $mode = "solo"){
		$src = $this->getDataFolder();
		if(file_exists($src . "arenas/" . strtolower($name) . ".yml")){
			$p->sendMessage($this->prefix. T::RED."Ya Existe Una Arena Con Ese Nombre");
			return false;
		}
		$config = new Config($src . "arenas/" . $name . ".yml", Config::YAML);
		
		$data = ["name" => $name, "mode" => $mode, "world" => $p->getLevel()->getName(), "local-de-espera" => $pos, "pos1" => $pos1, "pos2" => $pos2, "spawn1" => $spawn1, "spawn2" => $spawn2, "respawn1" => $respawn1, "respawn2" => $respawn2];
		
		$arena = new ArenaManager($this, $data);
		
		$this->arenas[strtolower($name)] = $arena;
				
		$config->setDefaults($data);
		$config->save();
		return true;
	}
	
	public function deleteBridge($name){
		if(file_exists($src . "arenas/" . strtolower($name) . ".yml")){
			if(unlink($src . "arenas/" . strtolower($name) . ".yml")){
				if(isset($this->arenas[strtolower($name)])){
					unset($this->arenas[strtolower($name)]);
				}
				return true;
			}
		}
		return false;
	}

	public function onCommand(CommandSender $sender, Command $cmd, String $label, array $args) : bool {
		if(strtolower($cmd->getName()) == "tb"){
			if(!$sender instanceof Player){
			    $sender->sendMessage("§cPlease use this command In-Game!");
				return true;
			}
			if(isset($args[0])){
				switch(strtolower($args[0])){
					case "pos1":
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§e§oT§6B§r§b /tb pos1 ");
							return true;
						}
						$x = $sender->getFloorX();
						$y = $sender->getFloorY();
						$z = $sender->getFloorZ();
						$this->pos1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
						$sender->sendMessage("§l§e§oT§6B§r §a1º Posicion De Porteria Marcada marcada en §bX:§c $x §bY:§c $y §bZ:§c $z");
						break;
						case "pos2":
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§e§oT§6B§r /tb help");
							return true;
						}
						$x = $sender->getFloorX();
						$y = $sender->getFloorY();
						$z = $sender->getFloorZ();
						$this->pos2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
						$sender->sendMessage("§l§e§oT§6B§r §a2º Posicion De Porteria Marcada marcada en §bX:§c $x §bY:§c $y §bZ:§c $z");
						break;
						case "spawn1":
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§e§oT§6B§r§b Use /tb help");
							return true;
						}
						$x = $sender->getFloorX();
						$y = $sender->getFloorY();
						$z = $sender->getFloorZ();
						$this->spawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
						$sender->sendMessage(" §a1º Spawn marcada en §bX:§c $x §bY:§c $y §bZ:§c $z");
						break;
						case "spawn2":
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§e§oT§6B§r§b Use /tb help");
							return true;
						}
						$x = $sender->getFloorX();
						$y = $sender->getFloorY();
						$z = $sender->getFloorZ();
						$this->spawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
						$sender->sendMessage("§l§e§oT§6B§r §a2º Spawn marcada en §bX:§c $x §bY:§c $y §bZ:§c $z");
						break;
						case "respawn1":
						
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§e§oT§6B§r §bUse /tb help");
							return true;
						}
						$x = $sender->getFloorX();
						$y = $sender->getFloorY();
						$z = $sender->getFloorZ();
						$this->respawn1[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
						$sender->sendMessage("§l§e§oT§6B§r §a1º Respawn marcada en §bX:§c $x §bY:§c $y §bZ:§c $z");
						break;
						case "respawn2":
	
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§e§oT§6B§§b Use /tb help");
							return true;
						}
						$x = $sender->getFloorX();
						$y = $sender->getFloorY();
						$z = $sender->getFloorZ();
						$this->respawn2[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z];
						$sender->sendMessage("§l§e§oT§6B§r §b2º Respawn marcada en §bX:§c $x §bY:§c $y §bZ:§c $z");
						break;
						case "spawn":
	
						if(!$sender->hasPermission("bridge.cmd")){
	
							$sender->sendMessage("§l§e§oT§6B§r §r§bUse /tb help");
							return true;
						}
						$x = $sender->getFloorX();
						$y = $sender->getFloorY();
						$z = $sender->getFloorZ();
						$this->pos[$sender->getName()] = ["x" => $x, "y" => $y, "z" => $z, "level" => $sender->getLevel()->getName()];
						$sender->sendMessage("§l§e§oT§6B§r §bLocal de Espera marcada en §bX:§c $x §bY:§c $y §bZ:§c $z");
						break;
						case "mode":
	
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§o§eT§6B§r§b Please type a vaild command");
							return true;
						}
						if(isset($args[1])){
							$name = $sender->getName();
							if(!isset($this->pos1[$name]) or !isset($this->pos2[$name])){
								$sender->sendMessage("§l§o§eT§6B§r§b You haven't setup the arena correctly");
								return true;
							}
							if(!isset($this->spawn1[$name]) or !isset($this->spawn2[$name])){
								$sender->sendMessage("§l§o§eT§6B§r§b You haven't setup the arena correctly");
								return true;
							}
							if(!isset($this->respawn1[$name]) or !isset($this->respawn2[$name])){
								$sender->sendMessage("§l§o§eT§6B§r§b You haven't setup the arena correctly");
								return true;
							}
							if(!isset($this->pos[$name])){
								$sender->sendMessage("§l§o§eT§6B§r§b You haven't setup the arena correctly");
								return true;
							}
							$level = $sender->getLevel();
							if(strlen($args[1]) > 15){
								$sender->sendMessage("§l§o§eT§6B§r§b You haven't setup the arena correctly");
								return true;
							}
							$mode = "solo";
							if(isset($args[2])){
								switch(strtolower($args[2])){
									case "solo":
									case "duos":
									case "squad":
									$mode = strtolower($args[2]);
									break;
									default:
									$sender->sendMessage("§l§eHELP!!\n§6you must use a available gamemode\n§bModes:\n§asolo\n§aduos\n§asquad");
									return true;
								}
							}
							if($this->createBridge($args[1], $sender, $this->pos1[$name], $this->pos2[$name], $this->spawn1[$name], $this->spawn2[$name], $this->respawn1[$name], $this->respawn2[$name], $this->pos[$name], $mode)){
								$sender->sendMessage("§l§o§eT§6B§r §bYour Arena has been created §a" . $args[1] . "§egood job setting it up");
							}
						} else {
							$sender->sendMessage("§l§o§eT§6B§r §bits /tb mode {map} {solo\duos\squad}");
							return true;
						}
						break;
						case "help":
							$sender->sendMessage(">§aTheBridge Commands: \n" .
							"§7/tb help : Displays list of TheBridge commands \n".
							"§7/tb <pos1|pos2> : Set the Goal Position <1|2> \n".
							"§7/tb <spawn1|spawn2> : Set the Spawn Position <1|2> \n".
							"§7/tb <respawn1|respawn2> : Set the Respawn position <1|2> \n".
							"§7/tb spawn : Set the Waiting Point \n".
							"§7/tb create: Create TheBridge arena \n" .
							"§7/tb delete : Delete TheBridge arena \n" .
							"§7/tb leaderboard : Spawn TheBridge Solos leaderboard \n" .
							"§7/tb npc : Spawn a NPC to join arena \n" .
							"§7/tb join : Connect player to the arena \n");
						break;
						case "delete":
						if(!$sender->hasPermission("bridge.cmd")){
							$sender->sendMessage("§l§e§oT§6B§r§b its /tb delete§e {map}");
							return true;
						}
						if(isset($args[1])){
							if($this->deleteBridge($args[1])){
								$sender->sendMessage("§l§e§oT§6B§r§b Your arena has been removed" . $args[1] . "§ethere is no backup for it!");
							} else {
								$sender->sendMessage("§l§e§oT§6B§r§b that map doesn't exist");
							}
						}
						break;
						case "join":
						$mode = "solo";
						if(isset($args[1])){
							switch(strtolower($args[1])){
								case "solo":
								case "duos":
								case "squad":
								$mode = strtolower($args[1]);
								break;
								default:
								$sender->sendMessage("§l§e§oT§6B§r That mode Doesn't exist!\n§l§eMODES§r:\n§asolo\n§aduos\n§asquad");
								return true;
							}
						}
						if($this->join($sender, $mode)){
							$sender->sendMessage("§l§e§oT§6B\n§l§ahow to play\n§bEach team starts off with a well! Don't let the other team score in your well! the first team to get 5 souls score in the well wins! Good luck\n\n§eplay.bladestorm.ml");
						} else {
							$sender->sendMessage("§l§cARENAS NOT FOUND\n§bWe couldn't find any arena!");
						}
						break;
						default:
						$sender->sendMessage("§l§e§oT§6B§r§b please type an able command");
						break;
					}
				} else {
					$sender->sendMessage("§l§e§oT§6B§r§bUse /tb help");
				}
			}
		return true;
	}
	}