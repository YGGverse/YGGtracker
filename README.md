# YGGtracker

[![Crowdin](https://badges.crowdin.net/yggtracker/localized.svg)](https://crowdin.com/project/yggtracker)

BitTorrent Network for Yggdrasil

YGGtracker is catalog, open tracker and social network with many features that allow to convert, filter and download any torrent in [Yggdrasil network](https://github.com/yggdrasil-network) by community.

Engine uses IPv6 `0200::/7` addresses to identify users without registration.

#### [Showcase](https://github.com/YGGverse/YGGtracker/wiki/Showcase)

![Pasted image 1](https://github.com/YGGverse/YGGtracker/assets/108541346/962f7850-01e1-4add-9dbe-c11b80108a75)

#### Instances

* `http://[201:23b4:991a:634d:8359:4521:5576:15b7]/yggtracker/`
  + `ftp://[201:23b4:991a:634d:8359:4521:5576:15b7]:21/yggtracker/`

#### Installation

```
symfony check:requirements
```

##### Production

Install stable release

```
composer create-project yggverse/yggtracker
```

##### Development

Latest codebase available in repository

```
git clone https://github.com/YGGverse/YGGtracker.git
cd YGGtracker
composer update
symfony server:start
```

##### Database

New installation

```
php bin/console doctrine:schema:update --force
```

Existing DB upgrade

```
php bin/console doctrine:migrations:migrate
```

##### Crontab

* `* * * * * /crontab/torrent/scrape/{%app.key%}` - update seeding stats

##### FTP

Setup anonymous read-only access to `/var/ftp` catalog ([read more](https://github.com/YGGverse/YGGtracker/wiki/Features#the-wanted))

##### App settings

Custom settings could be provided in the `/.env.local` file by overwriting default `/.env` values

#### Localization

Join community translations by [Crowdin](https://crowdin.com/project/yggtracker)

#### API

[Wiki reference](https://github.com/YGGverse/YGGtracker/wiki/API)

#### Contribution

Please make new branch for each PR

```
git checkout main
git checkout -b my-pr-branch-name
```

#### Donate to contributors

* @d47081:

  + ![wakatime](https://wakatime.com/badge/user/0b7fe6c1-b091-4c98-b930-75cfee17c7a5/project/059ec567-2c65-4c65-a48e-51dcc366f1a0.svg)
  + [BTC](https://www.blockchain.com/explorer/addresses/btc/bc1qngdf2kwty6djjqpk0ynkpq9wmlrmtm7e0c534y) | [LTC](https://live.blockcypher.com/ltc/address/LUSiqzKsfB1vBLvpu515DZktG9ioKqLyj7) | [XMR](835gSR1Uvka19gnWPkU2pyRozZugRZSPHDuFL6YajaAqjEtMwSPr4jafM8idRuBWo7AWD3pwFQSYRMRW9XezqrK4BEXBgXE) | [ZEPH](ZEPHsADHXqnhfWhXrRcXnyBQMucE3NM7Ng5ZVB99XwA38PTnbjLKpCwcQVgoie8EJuWozKgBiTmDFW4iY7fNEgSEWyAy4dotqtX)
  + Support our server by order [Linux VPS](https://www.yourserver.se/portal/aff.php?aff=610)
  + Inspiration by [SomaFM Deep Space One](https://somafm.com/deepspaceone/)

#### License

* Engine sources [MIT License](https://github.com/YGGverse/YGGtracker/blob/main/LICENSE)

#### Versioning

[Semantic Versioning 2.0.0](https://semver.org/#semantic-versioning-200)

#### Components

* [Symfony Framework](https://symfony.com)
* [SVG icons](https://icons.getbootstrap.com)
* [Scrapper](https://github.com/medariox/scrapeer) / [Composer Edition](https://github.com/YGGverse/scrapeer)
* [Bencode](https://github.com/Rhilip/Bencode)
* [Transliteration](https://github.com/ashtokalo/php-translit)
* [Identicons](https://github.com/dmester/jdenticon-php)

#### Support

* [Issues](https://github.com/YGGverse/YGGtracker/issues)
* [Documentation](https://github.com/YGGverse/YGGtracker/wiki)
* [HowTo Yggdrasil](https://ygg.work.gd/yggdrasil:bittorrent:yggtracker)

#### Blog

* [Mastodon](https://mastodon.social/@YGGverse)

#### Integrations

* [YGGtracker Search Plugin for qBittorrent](https://github.com/YGGverse/qbittorrent-yggtracker-search-plugin)
* [Crontab script that allows to receive wanted torrents from multiple YGGtracker nodes](https://github.com/YGGverse/yggtracker-wanted-torrents-receiver)

#### See also

* [YGGo - YGGo! Distributed Web Search Engine ](https://github.com/YGGverse/YGGo)
* [YGGwave ~ The Radio Catalog](https://github.com/YGGverse/YGGwave)
* [YGGstate - Yggdrasil Network Explorer](https://github.com/YGGverse/YGGstate)
