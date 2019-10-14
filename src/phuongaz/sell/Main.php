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
class Main extends PluginBase{

	public function onEnable(){
		$file = "sell.yml";
		
		if(!file_exists($this->getDataFolder() . $file)) {
				@mkdir($this->getDataFolder());
				file_put_contents($this->getDataFolder() . $file, $this->getResource($file));
		}
		$this->sell = new Config($this->getDataFolder() . "sell.yml", Config::YAML);
		$this->getLogger()->info(TF::BOLD. TF::GREEN."> Plugin by phuongaz");
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
}
