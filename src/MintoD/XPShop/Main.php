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
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\CustomFormResponse;
use MintoD\libMCUnicodeChars\libMCUnicodeChars;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeFactory;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use DaPigGuy\libPiggyEconomy\exceptions\MissingProviderDependencyException;
use DaPigGuy\libPiggyEconomy\exceptions\UnknownProviderException;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;

class Main extends PluginBase
{

	private Config $cfg;

	/** @var EconomyProvider */
	private $economyProvider;

	/**
	 * @throws MissingProviderDependencyException
	 * @throws UnknownProviderException
	 */
	protected function onEnable(): void
	{
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		$this->saveDefaultConfig();
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		libPiggyEconomy::init();
		$this->economyProvider = libPiggyEconomy::getProvider($this->getConfig()->get("economy"));
	}

	public function getEconomyProvider(): EconomyProvider
	{
		return $this->economyProvider;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		if ($command->getName() == "xpshop") {
			if ($sender->hasPermission("xpshop.cmd") || $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
				if (!$sender instanceof Player) {
					$sender->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("messages_console"))));
					return true;
				}

				$selectionForm = new MenuForm(
					TextFormat::colorize($this->replace($this->cfg->get("title"))),
					"",
					[
						new MenuOption(TextFormat::colorize($this->replace($this->cfg->get("sell_button")))),
						new MenuOption(TextFormat::colorize($this->replace($this->cfg->get("buy_button"))))
					],
					function (Player $sender, int $selected): void {
						switch ($selected) {
							case 0:
								$this->sellForm($sender);
								break;
							case 1:
								$this->buyForm($sender);
								break;
							default:
								return;
						}
					}
				);
				$sender->sendForm($selectionForm);
			}
			return true;
		}
		return false;
	}

	private function sellForm(Player $player): void
	{
		$form = new CustomForm(
			TextFormat::colorize($this->replace($this->cfg->get("sell_title"))),
			[
				new Slider("sell_slider_label", TextFormat::colorize($this->replace($this->cfg->get("sell_slider_label"))), 0, $player->getXpManager()->getXpLevel())
			],
			function (Player $player, CustomFormResponse $response): void {
				if ($player->getXpManager()->getXpLevel() <= 0) {
					$player->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("xpTooLow"))));
				} else {
					$money = $response->getAll()["sell_slider_label"] * $this->cfg->get("xpPriceWhenSell");
					$player->getXpManager()->subtractXpLevels((int) floor($response->getAll()["sell_slider_label"]));
					$this->getEconomyProvider()->giveMoney($player, (int) $money);
					$player->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("sellSuccess"))));
				}
			}
		);
		$player->sendForm($form);
	}

	private function buyForm(Player $player): void
	{
		$this->getEconomyProvider()->getMoney($player, function (float|int $balance) use ($player): void {
			$attribute = (int) AttributeFactory::getInstance()->mustGet(Attribute::EXPERIENCE_LEVEL)->getMaxValue();
			$result = (int) floor($balance / $this->cfg->get("xpPriceWhenBuy"));
			$max = ($result > $attribute) ? $attribute : $result;
			$form = new CustomForm(
				TextFormat::colorize($this->replace($this->cfg->get("buy_title"))),
				[
					new Slider("buy_slider_label", TextFormat::colorize($this->replace($this->cfg->get("buy_slider_label"))), 0, $max - $player->getXpManager()->getXpLevel())
				],
				function (Player $player, CustomFormResponse $response): void {
					if ($response->getAll()["buy_slider_label"] <= 0) {
						$player->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("moneyTooLow"))));
					} else {
						$money = $response->getAll()["buy_slider_label"] * $this->cfg->get("xpPriceWhenBuy");
						$this->getEconomyProvider()->takeMoney($player, (int) $money);
						$player->getXpManager()->addXpLevels((int) floor($response->getAll()["buy_slider_label"]));
						$player->sendMessage(TextFormat::colorize($this->replace($this->cfg->get("buySuccess"))));
					}
				}
			);
			$player->sendForm($form);
		});
	}

	private function replace(string $str): string
	{
		return libMCUnicodeChars::replace($str);
	}
}
