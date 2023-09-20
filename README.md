# YGGtracker

Distributed BitTorrent Registry for Yggdrasil

YGGtracker uses [Yggdrasil](https://github.com/yggdrasil-network/yggdrasil-go) IPv6 addresses to identify users without registration.

#### Nodes online

YGGtracker is distributed index engine, default nodes list defined in [nodes.json](https://github.com/YGGverse/YGGtracker/blob/main/src/config/nodes.json)

If you have launched new one, feel free to participate by PR.

#### Trackers

Open trackers defined in [trackers.json](https://github.com/YGGverse/YGGtracker/blob/main/src/config/trackers.json)

* Application appends initial trackers to all download links and magnet forms
* Trackers not in list will be cropped by the application filter
* Feel free to PR new yggdrasil tracker!

#### Public peers

Traffic-oriented public peers for Yggdrasil defined in [peers.json](https://github.com/YGGverse/YGGtracker/blob/main/src/config/peers.json)

#### Requirements

```
php8^
php-pdo
php-mysql
php-curl
php-memcached
sphinxsearch
memcached
```
#### Installation

##### Production

* `composer create-project yggverse/yggtracker`

##### Development

* `git clone https://github.com/YGGverse/YGGtracker.git`
* `cd YGGtracker`
* `composer update`

#### Setup
* Server configuration `/example/environment`
* The web root dir is `/src/public`
* Deploy the database using [MySQL Workbench](https://www.mysql.com/products/workbench) project presented in the `/database` folder
* Install [Sphinx Search Server](https://sphinxsearch.com)
* Configuration examples presented at `/example/environment` folder. On first app launch, configuration file will be auto-generated in `/src/config`
* Make sure `/src/api` folder writable

#### Contribute

Please make new branch for each PR

```
git checkout main
git checkout -b my-pr-branch-name
```

#### Roadmap

* [ ] BitTorrent protocol
  + [ ] Protocol
    + [ ] announce
    + [ ] announce-list
    + [ ] comment
    + [ ] created by
    + [ ] creation date
    + [ ] info
      + [ ] file-duration
      + [ ] file-media
      + [ ] files
      + [ ] name
      + [ ] piece length
      + [ ] pieces
      + [ ] private
      + [ ] profiles

* [ ] Magnet protocol
  + [x] Exact Topic / xt
  + [x] Display Name / dn
  + [x] eXact Length / xl
  + [x] Address Tracker / rt
  + [x] Web Seed / ws
  + [x] Acceptable Source / as
  + [x] eXact Source / xs
  + [x] Keyword Topic / kt
  + [ ] Manifest Topic / mt
  + [ ] Select Only / so
  + [ ] PEer / x.pe

* [ ] Catalog
    + [x] Public levels
    + [x] Sensitive filter
    + [x] Comments
    + [x] Scrape trackers
      + [x] Peers
      + [x] Completed
      + [x] Leechers
    + [x] Stars
    + [x] Views
    + [x] Downloads
    + [x] Wanted
    + [x] Threading comments
    + [ ] Forks

* [ ] Profile
  + [ ] Listing
    + [ ] Uploads
    + [ ] Downloads
    + [ ] Stars
    + [ ] Following
    + [ ] Followers
    + [ ] Comments
  + [ ] Settings
    + [ ] Public name
    + [ ] Downloads customization
      + [ ] Address Tracker
      + [ ] Web Seed
      + [ ] Acceptable Source
      + [ ] eXact Source
    + [ ] Content filters

* [x] API
  + [x] Active (push)
    + [x] Magnet
      + [x] Edit
      + [x] Download
      + [x] Comment
      + [x] Star
      + [x] View
  + [x] Passive (feed)
    + [x] Manifest
    + [x] Users
    + [x] Magnets
    + [x] Downloads
    + [x] Comments
    + [x] Stars
    + [x] Views

* [x] Export
  + [x] Sitemap
  + [x] RSS
    + [x] Magnets
    + [x] Comments

* [x] Other
  + [x] Moderation
    + [x] UI
    + [ ] CLI
  + [ ] Installation tools


#### Donate to contributors

* @d47081:

  + ![wakatime](https://wakatime.com/badge/user/0b7fe6c1-b091-4c98-b930-75cfee17c7a5/project/059ec567-2c65-4c65-a48e-51dcc366f1a0.svg)
  + [BTC](https://www.blockchain.com/explorer/addresses/btc/bc1qngdf2kwty6djjqpk0ynkpq9wmlrmtm7e0c534y) | [LTC](https://live.blockcypher.com/ltc/address/LUSiqzKsfB1vBLvpu515DZktG9ioKqLyj7) | [XMR](835gSR1Uvka19gnWPkU2pyRozZugRZSPHDuFL6YajaAqjEtMwSPr4jafM8idRuBWo7AWD3pwFQSYRMRW9XezqrK4BEXBgXE) | [ZEPH](ZEPHsADHXqnhfWhXrRcXnyBQMucE3NM7Ng5ZVB99XwA38PTnbjLKpCwcQVgoie8EJuWozKgBiTmDFW4iY7fNEgSEWyAy4dotqtX)
  + Support our server by order [Linux VPS](https://www.yourserver.se/portal/aff.php?aff=610)
  + Inspiration by [SomaFM Deep Space One](https://somafm.com/deepspaceone/)

#### License

* Engine sources [MIT License](https://github.com/YGGverse/YGGtracker/blob/main/LICENSE)

#### Components

* [SVG icons](https://icons.getbootstrap.com)
* [PHP Scrapper](https://github.com/medariox/scrapeer)
* [Identicons](https://github.com/dmester/jdenticon-php)

#### Feedback

[https://github.com/YGGverse/YGGtracker/issues](https://github.com/YGGverse/YGGtracker/issues)

#### Community

* [Mastodon](https://mastodon.social/@YGGverse)

#### See also

* [YGGo - YGGo! Distributed Web Search Engine ](https://github.com/YGGverse/YGGo)
* [YGGwave ~ The Radio Catalog](https://github.com/YGGverse/YGGwave)
* [YGGstate - Yggdrasil Network Explorer](https://github.com/YGGverse/YGGstate)