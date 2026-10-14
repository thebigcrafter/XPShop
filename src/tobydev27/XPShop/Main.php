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
 *  Copyright (C) 2021  tobydev27
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 */

declare(strict_types=1);

namespace tobydev27\XPShop;

use DaPigGuy\libPiggyEconomy\exceptions\MissingProviderDependencyException;
use DaPigGuy\libPiggyEconomy\exceptions\UnknownProviderException;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeFactory;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use thebigcrafter\Hydrogen\HConfig;
use thebigcrafter\Hydrogen\Hydrogen;
use tobydev27\libMCUnicodeChars\libMCUnicodeChars;

class Main extends PluginBase {

	/** @var EconomyProvider */
	private $economyProvider;

	/**
	 * @throws MissingProviderDependencyException
	 * @throws UnknownProviderException
	 */
	protected function onEnable(): void {
		Hydrogen::checkForUpdates($this);
		$this->saveDefaultConfig();
		HConfig::verifyConfigVersion($this->getConfig(), "1.0.0");
		libPiggyEconomy::init();
		$economyConfig = $this->getConfig()->get("economy", []);
		if (!is_array($economyConfig)) {
			throw new \UnexpectedValueException("The economy configuration must be a map.");
		}
		$this->economyProvider = libPiggyEconomy::getProvider($economyConfig);
	}

	public function getEconomyProvider(): EconomyProvider {
		return $this->economyProvider;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() == "xpshop") {
			if ($sender->hasPermission("xpshop.cmd") || $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
				if (!$sender instanceof Player) {
					$sender->sendMessage(TextFormat::colorize($this->replace($this->configString("messages_console"))));
					return true;
				}

				$selectionForm = new MenuForm(
					TextFormat::colorize($this->replace($this->configString("title"))),
					"",
					[
						new MenuOption(TextFormat::colorize($this->replace($this->configString("sell_button")))),
						new MenuOption(TextFormat::colorize($this->replace($this->configString("buy_button"))))
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

	private function sellForm(Player $player): void {
		$form = new CustomForm(
			TextFormat::colorize($this->replace($this->configString("sell_title"))),
			[
				new Slider("sell_slider_label", TextFormat::colorize($this->replace($this->configString("sell_slider_label"))), 0, $player->getXpManager()->getXpLevel())
			],
			function (Player $player, CustomFormResponse $response): void {
				if ($player->getXpManager()->getXpLevel() <= 0) {
					$player->sendMessage(TextFormat::colorize($this->replace($this->configString("xpTooLow"))));
				} else {
					$xp = $this->responseNumber($response, "sell_slider_label");
					$money = $xp * $this->configNumber("xpPriceWhenSell");
					$player->getXpManager()->subtractXpLevels((int) floor($xp));
					$this->getEconomyProvider()->giveMoney($player, (int) $money);
					$player->sendMessage(TextFormat::colorize($this->replace($this->configString("sellSuccess"))));
				}
			}
		);
		$player->sendForm($form);
	}

	private function buyForm(Player $player): void {
		$this->getEconomyProvider()->getMoney($player, function (float|int $balance) use ($player): void {
			$attribute = (int) AttributeFactory::getInstance()->mustGet(Attribute::EXPERIENCE_LEVEL)->getMaxValue();
			$affordableLevels = (int) floor($balance / $this->configNumber("xpPriceWhenBuy"));
			$remainingLevelCapacity = max(0, $attribute - $player->getXpManager()->getXpLevel());
			$max = min($affordableLevels, $remainingLevelCapacity);
			$form = new CustomForm(
				TextFormat::colorize($this->replace($this->configString("buy_title"))),
				[
					new Slider("buy_slider_label", TextFormat::colorize($this->replace($this->configString("buy_slider_label"))), 0, $max - $player->getXpManager()->getXpLevel())
				],
				function (Player $player, CustomFormResponse $response): void {
					$xp = $this->responseNumber($response, "buy_slider_label");
					if ($xp <= 0) {
						$player->sendMessage(TextFormat::colorize($this->replace($this->configString("moneyTooLow"))));
					} else {
						$money = $xp * $this->configNumber("xpPriceWhenBuy");
						$this->getEconomyProvider()->takeMoney($player, (int) $money);
						$player->getXpManager()->addXpLevels((int) floor($xp));
						$player->sendMessage(TextFormat::colorize($this->replace($this->configString("buySuccess"))));
					}
				}
			);
			$player->sendForm($form);
		});
	}

	private function configString(string $key): string {
		$value = $this->getConfig()->get($key);
		return is_string($value) ? $value : "";
	}

	private function configNumber(string $key): float {
		$value = $this->getConfig()->get($key);
		return is_numeric($value) ? (float) $value : 0.0;
	}

	private function responseNumber(CustomFormResponse $response, string $key): float {
		$value = $response->getAll()[$key] ?? null;
		return is_numeric($value) ? (float) $value : 0.0;
	}

	private function replace(string $str): string {
		return libMCUnicodeChars::replace($str);
	}
}
