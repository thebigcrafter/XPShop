[![State](https://poggit.pmmp.io/shield.state/XPShop)](https://poggit.pmmp.io/p/XPShop)
[![API](https://poggit.pmmp.io/shield.api/XPShop)](https://poggit.pmmp.io/p/XPShop)
[![Downloads Total](https://poggit.pmmp.io/shield.dl.total/XPShop)](https://poggit.pmmp.io/p/XPShop)

<div align="center">
  <a href="https://github.com/thebigcrafter/XPShop">
    <img src="https://raw.githubusercontent.com/thebigcrafter/XPShop/main/icon.png" alt="Logo" width="100" height="100">
  </a>

<h3 align="center">XPShop</h3>

  <p align="center">
    Buy and sell XP
    <br />
    <a href="https://github.com/thebigcrafter/XPShop/releases">Releases</a>
    ·
    <a href="https://github.com/thebigcrafter/XPShop/issues">Report Bug</a>
  </p>
</div>

# Installation

XPShop targets PocketMine-MP API 5 and bundles the virions declared by its
locked Composer dependencies: [pmforms](https://github.com/thebigcrafter/pmforms),
[Hydrogen](https://github.com/thebigcrafter/Hydrogen),
[libMCUnicodeChars](https://github.com/thebigcrafter/libMCUnicodeChars) and
[libPiggyEconomy](https://github.com/thebigcrafter/libPiggyEconomy). The
thebigcrafter forks contain the standalone-packaging fixes used by this
repository. Choose and
install a supported economy provider before using the shop. The default
configuration selects BedrockEconomy; EconomyAPI and the built-in XP provider
are also documented in `resources/config.yml`.

## Download from GitHub Releases

  - Download the latest verified `XPShops.phar` from the [GitHub Releases](https://github.com/thebigcrafter/XPShop/releases) page.
  - Move it to PocketMine plugin folder.
  - Restart the server.
## Using oh-my-pmmp
```shell
install XPShop latest
```

# Usage and configuration

Run `/xpshop` in game to open the buy/sell form. The `xpshop.cmd` permission
is available to all players by default; server administrators can restrict it
through the permission system when needed. Console senders receive the
configured console message instead of a form. After the first start, edit
`plugin_data/XPShop/config.yml` to set prices, form labels, messages and the
`economy.provider` value. The `config-version` field is managed by the plugin
and should not be changed manually.

# Development

The repository keeps a locked Composer setup for its runtime virions and
PHPStan. Run:

```shell
composer install
composer run analyse
```

The repository keeps a Poggit manifest for compatibility with its historical
build service. The maintained release path uses DevTools to assemble and
validate the PHAR; `devtools.yml` disables duplicate Poggit library imports so
the reviewed Composer lockfile remains authoritative.

# License
Copyright (c) tobydev27. All rights reserved.

Licensed under the [GPL-3.0](https://github.com/thebigcrafter/XPShop#GPL-3.0-1) license.
