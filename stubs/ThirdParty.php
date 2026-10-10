<?php

declare(strict_types=1);

namespace DaPigGuy\libPiggyEconomy;

use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;

final class libPiggyEconomy {
	public static function init() : void {}

	public static function getProvider(mixed $config) : EconomyProvider {
		return new EconomyProvider();
	}
}

namespace DaPigGuy\libPiggyEconomy\exceptions;

class MissingProviderDependencyException extends \Exception {}
class UnknownProviderException extends \Exception {}

namespace DaPigGuy\libPiggyEconomy\providers;

use pocketmine\player\Player;

class EconomyProvider {
	public function giveMoney(Player $player, int $amount) : void {}

	public function takeMoney(Player $player, int $amount) : void {}

	public function getMoney(Player $player, \Closure $callback) : void {
		$callback(0);
	}
}

namespace dktapps\pmforms;

use pocketmine\form\Form;

final class CustomForm implements Form {
	public function __construct(string $title, array $elements, \Closure $handler) {}

	public function jsonSerialize() : array {
		return [];
	}
}

final class CustomFormResponse {
	/** @return array<string, mixed> */
	public function getAll() : array {
		return [];
	}
}

final class MenuForm implements Form {
	public function __construct(string $title, string $content, array $options, \Closure $handler) {}

	public function jsonSerialize() : array {
		return [];
	}
}

final class MenuOption {
	public function __construct(string $text) {}
}

namespace dktapps\pmforms\element;

final class Slider {
	public function __construct(string $name, string $text, int $min, int $max) {}
}

namespace thebigcrafter\Hydrogen;

use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;

final class Hydrogen {
	public static function checkForUpdates(Plugin $plugin) : void {}
}

final class HConfig {
	public static function verifyConfigVersion(Config $config, string $version) : void {}
}

namespace tobydev27\libMCUnicodeChars;

final class libMCUnicodeChars {
	public static function replace(string $text) : string {
		return $text;
	}
}
