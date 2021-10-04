<?php

/*
 *  __   _______   _____ _
 *  \ \ / /  __ \ / ____| |
 *   \ V /| |__) | (___ | |__   ___  _ __
 *    > < |  ___/ \___ \| '_ \ / _ \| '_ \
 *   / . \| |     ____) | | | | (_) | |_) |
 *  /_/ \_\_|    |_____/|_| |_|\___/| .__/
 *                                 | |
 *                                 |_|
 *
 *  Copyright (C) 2021  MintoD
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 */

declare(strict_types=1);

namespace MintoD\XPShop;

use JackMD\UpdateNotifier\UpdateNotifier;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase
{

    private Config $cfg;

    public function onEnable(): void
    {
        UpdateNotifier::checkUpdate("XPShop", "1.1.0");
        $this->saveDefaultConfig();
        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() == "xpshop") {
            if ($sender->hasPermission("xpshop.cmd") || $sender->isOp()) {
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TextFormat::DARK_RED . "Please use this command in-game!");
                    return true;
                }

                $selectionForm = new SimpleForm(function (Player $player, int $data = null) {
                    if ($data === null) {
                        return;
                    }

                    switch ($data) {
                        case 0:
                            $this->sellForm($player);
                            break;
                        case 1:
                            $this->buyForm($player);
                            break;
                    }
                });
                $selectionForm->setTitle($this->cfg->get("title"));
                $selectionForm->addButton($this->cfg->get("sell_button"));
                $selectionForm->addButton($this->cfg->get("buy_button"));
                $sender->sendForm($selectionForm);
            }
        }
        return true;
    }

    private function sellForm(Player $player): void
    {
        $form = new CustomForm(function (Player $player, array $data = null) {
            if ($data === null) {
                return;
            }
            if ($player->getXpLevel() <= 0) {
                $player->sendMessage($this->cfg->get("xpToLow"));
            } else {
                $money = $data[0] * $this->cfg->get("xpPriceWhenSell");
                $player->setXpLevel((int)floor($player->getXpLevel() - $data[0]));
                EconomyAPI::getInstance()->addMoney($player, $money);
                $player->sendMessage($this->cfg->get("sellSuccess"));
            }
        });
        $form->setTitle($this->cfg->get("sell_title"));
        $form->addSlider($this->cfg->get("sell_slider_label"), 1, $player->getXpLevel());
        $player->sendForm($form);
    }

    private function buyForm(Player $player): void
    {
        $form = new CustomForm(function (Player $player, array $data = null) {
            if ($data === null) {
                return;
            }
            $money = $data[0] * $this->cfg->get("xpPriceWhenBuy");
            $player->setXpLevel((int)floor($player->getXpLevel() + $data[0]));
            EconomyAPI::getInstance()->reduceMoney($player, $money);
            $player->sendMessage($this->cfg->get("buySuccess"));
        });
        $form->setTitle($this->cfg->get("buy_title"));
        $form->addSlider($this->cfg->get("buy_slider_label"), 1, (int)floor(EconomyAPI::getInstance()->myMoney($player) / $this->cfg->get("xpPriceWhenBuy")) - 1);
        $player->sendForm($form);
    }
}
