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

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use JackMD\UpdateNotifier\UpdateNotifier;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use MintoD\libMCUnicodeChars\libMCUnicodeChars;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase
{

	private Config $cfg;

	protected function onEnable(): void
	{
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		$this->saveDefaultConfig();
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		if ($command->getName() == "xpshop") {
			if ($sender->hasPermission("xpshop.cmd") || $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
				if (!$sender instanceof Player) {
					$sender->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("messages_console"))));
					return true;
				}

				$selectionForm = new SimpleForm(function (Player $player, ?int $data = null) {
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
				$selectionForm->setTitle(TextFormat::colorize($this->replace($this->cfg->get("title"))));
				$selectionForm->addButton(TextFormat::colorize($this->replace($this->cfg->get("sell_button"))));
				$selectionForm->addButton(TextFormat::colorize($this->replace($this->cfg->get("buy_button"))));
				$sender->sendForm($selectionForm);
			}
			return true;
		}
		return false;
	}

	private function sellForm(Player $player): void
	{
		$form = new CustomForm(function (Player $player, ?array $data = null) {
			if ($data === null) {
				return;
			}
			if ($player->getXpManager()->getXpLevel() <= 0) {
				$player->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("xpTooLow"))));
			} else {
				$money = $data[0] * $this->cfg->get("xpPriceWhenSell");
				$player->getXpManager()->subtractXpLevels((int) floor($data[0]));
				BedrockEconomyAPI::getInstance()->addToPlayerBalance($player->getName(), (int) $money);
				$player->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("sellSuccess"))));
			}
		});
		$form->setTitle(TextFormat::colorize($this->replace($this->cfg->get("sell_title"))));
		$form->addSlider(TextFormat::colorize($this->replace($this->cfg->get("sell_slider_label"))), 1, $player->getXpManager()->getXpLevel());
		$player->sendForm($form);
	}

	private function buyForm(Player $player): void
	{
		BedrockEconomyAPI::legacy()->getPlayerBalance(
			$player->getName(),
			ClosureContext::create(
				function (?int $balance) use ($player): void {
					$form = new CustomForm(function (Player $player, ?array $data = null) {
						if ($data === null) {
							return;
						}
						$money = $data[0] * $this->cfg->get("xpPriceWhenBuy");
						BedrockEconomyAPI::legacy()->subtractFromPlayerBalance($player->getName(), (int) $money);
						$player->getXpManager()->addXpLevels((int) floor($data[0]));
						$player->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("buySuccess"))));
					});
					$form->setTitle(TextFormat::colorize($this->replace($this->cfg->get("buy_title"))));
					$form->addSlider(TextFormat::colorize($this->replace($this->cfg->get("buy_slider_label"))), 1, (int) floor($balance / $this->cfg->get("xpPriceWhenBuy")) - 1);
					$player->sendForm($form);
				},
			)
		);
	}

	private function replace(string $str): string
	{
		return libMCUnicodeChars::replace($str);
	}
}
