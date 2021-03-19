<?php

namespace TobyDev265\XPShop;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

use onebone\economyapi\EconomyAPI;

class Main extends PluginBase
{
    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
    }

    /**
     * @param CommandSender $sender
     * @param Command $cmd
     * @param string $label
     * @param array $args
     * @return bool
     */

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
    {
        if ($cmd->getName() == "xpshop") {
            if ($sender instanceof Player) {
                if ($sender->isOp() || $sender->hasPermission("xpshop.cmd")) {
                    $form = new SimpleForm(function (Player $player, int $data = null) {
                        $result = $data;
                        if ($result === null) {
                            return true;
                        }
                        switch ($result) {
                            case 0:
                                $this->sellForm($player);
                                break;
                            case 1:
                                $this->buyForm($player);
                                break;
                        }
                    });
                    $form->setTitle($this->cfg->get("title"));
                    $form->addButton($this->cfg->get("sell_button"));
                    $form->addButton($this->cfg->get("buy_button"));
                    $sender->sendForm($form);
                }
            }
        }
        return true;
    }
    public function sellForm($player)
    {
        $form = new CustomForm(function (Player $player, array $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            $money = $result[0] * $this->cfg->get("xpPriceWhenSell");
            $player->setXpLevel($player->getXpLevel() - $result[0]);
            EconomyAPI::getInstance()->addMoney($player, $money);
            $player->sendMessage($this->cfg->get("sellSuccess"));
        });
        $form->setTitle($this->cfg->get("sell_title"));
        $form->addSlider($this->cfg->get("slider_label"), 1, $player->getXpLevel());
        $player->sendForm($form);
        return true;
    }
    public function buyForm($player)
    {
        $form = new CustomForm(function (Player $player, array $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            if (!is_numeric($result[0])) {
                $player->sendMessage(C::RED . $this->cfg->get("isNotANumber"));
            } else {
                $money = $result[0] * $this->cfg->get("xpPriceWhenBuy");
                if (EconomyAPI::getInstance()->myMoney($player) >= $money) {
                    EconomyAPI::getInstance()->reduceMoney($player, $money);
                    $player->sendMessage($this->cfg->get("buySuccess"));
                    $player->setXpLevel($player->getXpLevel() + $result[0]);
                } else {
                    $player->sendMessage(C::RED . $this->cfg->get("doNotEnoughMoney"));
                }
            }
        });
        $form->setTitle($this->cfg->get("buy_title"));
        $form->addInput($this->cfg->get("input_label"));
        $player->sendForm($form);
        return true;
    }
}
