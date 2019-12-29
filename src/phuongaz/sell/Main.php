<?php

/*
*   @author: phuongaz
*   @api: 3.0.0
*/

namespace phuongaz\sell;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

use onebone\economyapi\EconomyAPI;
use jojoe77777\FormAPI\
{
	SimpleForm,
	CustomForm
};
use pocketmine\event\{Listener, block\BlockBreakEvent};

class Main extends PluginBase implements Listener{

	public $can;
	public $prefix = "§7[§a Auto Sell §7]";


	public function onEnable(){
		$file = 'sell.yml';

			if(!file_exists($this->getDataFolder() . $file)) {
				@mkdir($this->getDataFolder());
				file_put_contents($this->getDataFolder() . $file, $this->getResource($file));
			}
			$this->sell = new Config($this->getDataFolder() . "sell.yml", Config::YAML);
				
	
		$this->getLogger()->info(TF::BOLD. TF::GREEN."> Plugin by phuongaz");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

	}


	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
		if(strtolower($cmd->getName()) === 'sell'){
			if($sender instanceof Player){
				/*  @var SimpleForm   */
				$form = new SimpleForm(function(Player $player, $data){
					if(is_null($data)) return;
					switch ($data) {
						case 0:
							$this->sellHand($player);
							break;
						case 1:
						    $this->sellAll($player);
						    break;
						default:
							# Nothing todo....
							break;
					}
				});
				$form->setTitle("§l§6> SELL MENU");
				$form->setContent("§l§1>§6 Yên của bạn: ".EconomyAPI::getInstance()->myMoney($sender));
				$form->addButton("§l§e> §bBán vật phẩm trên tay§l§e >");
				$form->addButton("§l§e> §bBán tất cả vật phẩm§l§e >");
				$form->sendToPlayer($sender);
			}
		}elseif($cmd->getName() == "autosell"){
				if(!$sender->hasPermission('autosell')){
					return true;
				}
		       if(!isset($args[0])){
			      $sender->sendMessage($this->prefix." /autosell on | off");
			       return true;
		      }
		      if($args[0] == "on"){
			     $this->can[$sender->getName()] = true;
			   $sender->sendMessage($this->prefix."§a bạn đã bật chức năng tự động bán");
			}
			if($args[0] == "off"){
				if(isset($this->can[$sender->getName()])){
					unset($this->can[$sender->getName()]);
					$sender->sendMessage($this->prefix."§c Bạn đã tắt chức năng tự động bán");
					return true;
				}
				$sender->sendMessage($this->prefix."§c Bạn chưa bật chức năng tự động bán");
			}
	    }
		return true;
	}

	public function sellAll($player)
	{
	    $allmoney = 0;	
	    /*  @var CustomForm   */
		$form = new CustomForm(function(Player $player, $data){
			if(is_null($data)) return;
		});
		$form->setTitle("§l§6> SELL ALL");
		$form->addLabel("> Các vật phẩm đã bán:");
		$form->addLabel("§l§e>§a Vật phẩm§d |§a Số Lượng§d |§a Số tiền nhận §d|");
		$items = $player->getInventory()->getContents();
		foreach($items as $item){
			if($this->sell->get($item->getId()) !== null && $this->sell->get($item->getId()) > 0){
				
				$price = $this->sell->get($item->getId()) * $item->getCount();
				EconomyAPI::getInstance()->addMoney($player, $price);
				$money = $this->sell->get($item->getId());
				$count = $item->getCount();
				$iname = $item->getName();
				$form->addLabel("§l§e>§a $iname §d|§a $count §d|§a $price §d|");
				$allmoney = $allmoney + $price;
				$player->getInventory()->remove($item);
			}
		}
		$form->addLabel("§l§f>§6 Tổng số tiền nhận được:§b $allmoney");
		$form->sendToPlayer($player);
	}

	public function sellHand($player)
	{
		$item = $player->getInventory()->getItemInHand();
		$itemId = $item->getId();
		if($item->getId() === 0){
			$player->sendMessage("§l§1>§f Trên tay bạn hiện không có gì cả");
            return false;
		}
		if($this->sell->get($itemId) == null){
			$player->sendMessage("§l§1>§f Bạn không thể bán vật phẩm này");
			return false;
		}
		EconomyAPI::getInstance()->addMoney($player, $this->sell->get($itemId) * $item->getCount());
		$player->getInventory()->removeItem($item);
		$price = $this->sell->get($item->getId()) * $item->getCount();
		/*  @var CustomForm   */
		$form = new CustomForm(function(Player $player, $data){
			if(is_null($data)) return;
		});
		$iname = $item->getName();
		$count = $item->getCount();
		$form->setTitle("§l§6> SELL HAND");
		$form->addLabel("> Bạn đã bán vật phẩm:");
		$form->addLabel("§l§e>§a Vật phẩm§d |§a Số Lượng§d |§a Số tiền nhận §d|");
		$form->addLabel("§l§e>§a $iname §d|§a $count §d|§a $price");
		$form->sendToPlayer($player);		
	}

	public function AutoSell($player) :bool
	{
		if(isset($this->can[$player->getName()])){
			return true;
		}
		return false;
	}

	public function onBreak(BlockBreakEvent $event) {
		
		$player = $event->getPlayer();
		if($this->AutoSell($player) == false) return true;
		foreach($event->getDrops() as $drop) {
			if(!$player->getInventory()->canAddItem($drop)) {
				$event->getPlayer()->addTitle("§l§a✠§6 FULL INVENTORY §a✠", "§l§cTự động bán!");
                $this->sellAll($player);

            }
				break; 
			}
	}


}
