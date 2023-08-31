# YGGtracker

BitTorrent Catalog for Yggdrasil ecosystem

YGGtracker uses [Yggdrasil](https://github.com/yggdrasil-network/yggdrasil-go) IPv6 addresses to identify users without registration.

#### Online instances

  * [http://[201:23b4:991a:634d:8359:4521:5576:15b7]/yggtracker](http://[201:23b4:991a:634d:8359:4521:5576:15b7]/yggtracker/)

#### Trackers

Initial trackers defined in [trackers.json](#).

* Application appends trackers to all download links and magnet forms
* Trackers not in list will be cropped by the application filter
* Feel free to PR new yggdrasil tracker instance!

#### Requirements

```
php8^
php-pdo
php-mysql
sphinxsearch
```
#### Installation

* `git clone https://github.com/YGGverse/YGGtracker.git`
* `cd YGGtracker`
* `composer update`

#### Setup
* Server configuration `/example/environment`
* The web root dir is `/src/public`
* Deploy the database using [MySQL Workbench](https://www.mysql.com/products/workbench) project presented in the `/database` folder
* Install [Sphinx Search Server](https://sphinxsearch.com)
* Configuration examples presented at `/config` folder
* Set up the `/src/crontab` by following [example](https://github.com/YGGverse/YGGtracker/blob/main/example/environment/crontab)

#### Contribute

Please make new branch for each PR

```
git checkout main
git checkout -b my-pr-branch-name
```

#### Roadmap

* [ ] Magnet
  + [ ] Protocol
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
  + [x] Options
    + [x] Public
    + [x] Sensitive
    + [x] Comments
  + [ ] Features
    + [x] Scrape trackers
      + [x] Peers
      + [x] Completed
      + [x] Leechers
    + [x] Stars
    + [x] Downloads
    + [x] Threading comments
    + [x] Info page
    + [ ] Views
    + [ ] Forks
    + [ ] Wanted

+ [ ] Profile
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

* [x] Other
  + [x] Sitemap
  + [x] RSS
  + [x] Moderation
  + [ ] Federative API

#### Donate to contributors

* @d47081: [BTC](https://www.blockchain.com/explorer/addresses/btc/bc1qngdf2kwty6djjqpk0ynkpq9wmlrmtm7e0c534y) | [LTC](https://live.blockcypher.com/ltc/address/LUSiqzKsfB1vBLvpu515DZktG9ioKqLyj7) | [XMR](835gSR1Uvka19gnWPkU2pyRozZugRZSPHDuFL6YajaAqjEtMwSPr4jafM8idRuBWo7AWD3pwFQSYRMRW9XezqrK4BEXBgXE) | [ZEPH](ZEPHsADHXqnhfWhXrRcXnyBQMucE3NM7Ng5ZVB99XwA38PTnbjLKpCwcQVgoie8EJuWozKgBiTmDFW4iY7fNEgSEWyAy4dotqtX) | [DOGE](https://dogechain.info/address/D5Sez493ibLqTpyB3xwQUspZvJ1cxEdRNQ) | Support our server by order [Linux VPS](https://www.yourserver.se/portal/aff.php?aff=610)

#### License

* Engine sources [MIT License](https://github.com/YGGverse/YGGtracker/blob/main/LICENSE)

#### Components

* [SVG icons](https://icons.getbootstrap.com)
* [PHP Scrapper](https://github.com/medariox/scrapeer)
* [Identicon](https://github.com/dmester/jdenticon-php)

#### Feedback

[https://github.com/YGGverse/YGGtracker/issues](https://github.com/YGGverse/YGGtracker/issues)

#### Community

* [Mastodon](https://mastodon.social/@YGGverse)
* [[matrix]](https://matrix.to/#/#YGGtracker:matrix.org)

#### See also

* [YGGo - YGGo! Distributed Web Search Engine ](https://github.com/YGGverse/YGGo)
* [YGGwave ~ The Radio Catalog](https://github.com/YGGverse/YGGwave)
* [YGGstate - Yggdrasil Network Explorer](https://github.com/YGGverse/YGGstate)