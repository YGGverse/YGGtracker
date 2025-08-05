# YGGtracker

> [!NOTE]
> Take a look at [βtracker](https://github.com/yggverse/btracker) - the modern aggregation alternative written in Rust!

A social-oriented BitTorrent catalog for the [Yggdrasil](https://github.com/yggdrasil-network) network, written in the Symfony framework.

YGGtracker is a manually operated catalog and social network that allows users to share their torrents in the local network. Engine uses IPv6 `0200::/7` addresses to identify users without registration.

#### [Showcase](https://github.com/YGGverse/YGGtracker/wiki/Showcase)

![Pasted image 1](https://github.com/YGGverse/YGGtracker/assets/108541346/962f7850-01e1-4add-9dbe-c11b80108a75)

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

[![Crowdin](https://badges.crowdin.net/yggtracker/localized.svg)](https://crowdin.com/project/yggtracker)

#### API

[Wiki reference](https://github.com/YGGverse/YGGtracker/wiki/API)

#### Contribution

Please make new branch for each PR

```
git checkout main
git checkout -b my-pr-branch-name
```

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
