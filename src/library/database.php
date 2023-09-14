<?php

class Database {

  private PDO $_db;

  private object $_debug;

  public function __construct(string $host, int $port, string $database, string $username, string $password) {

    $this->_db = new PDO('mysql:dbname=' . $database . ';host=' . $host . ';port=' . $port . ';charset=utf8', $username, $password, [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
    $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    $this->_db->setAttribute(PDO::ATTR_TIMEOUT, 600);

    $this->_debug = (object)
    [
      'query' => (object)
      [
        'select' => (object)
        [
          'total' => 0
        ],
        'insert' => (object)
        [
          'total' => 0
        ],
        'update' => (object)
        [
          'total' => 0
        ],
        'delete' => (object)
        [
          'total' => 0
        ],
      ]
    ];
  }

  // Tools
  public function beginTransaction() {

    $this->_db->beginTransaction();
  }

  public function commit() {

    $this->_db->commit();
  }

  public function rollBack() {

    $this->_db->rollBack();
  }

  public function getDebug() {

    return $this->_debug;
  }

  // Scheme
  public function addScheme(string $value) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `scheme` SET `value` = ?');

    $query->execute([$value]);

    return $this->_db->lastInsertId();
  }

  public function getScheme(int $schemeId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `scheme` WHERE `schemeId` = ?');

    $query->execute([$schemeId]);

    return $query->fetch();
  }

  public function findScheme(string $value) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `scheme` WHERE `value` = ?');

    $query->execute([$value]);

    return $query->fetch();
  }

  public function initSchemeId(string $value) : int {

    if ($result = $this->findScheme($value)) {

      return $result->schemeId;
    }

    return $this->addScheme($value);
  }

  // Host
  public function addHost(string $value) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `host` SET `value` = ?');

    $query->execute([$value]);

    return $this->_db->lastInsertId();
  }

  public function getHost(int $hostId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `host` WHERE `hostId` = ?');

    $query->execute([$hostId]);

    return $query->fetch();
  }

  public function findHost(string $value) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `host` WHERE `value` = ?');

    $query->execute([$value]);

    return $query->fetch();
  }

  public function initHostId(string $value) : int {

    if ($result = $this->findHost($value)) {

      return $result->hostId;
    }

    return $this->addHost($value);
  }

  // Port
  public function addPort(mixed $value) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `port` SET `value` = ?');

    $query->execute([$value]);

    return $this->_db->lastInsertId();
  }

  public function getPort(int $portId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `port` WHERE `portId` = ?');

    $query->execute([$portId]);

    return $query->fetch();
  }

  public function findPort(mixed $value) {

    $this->_debug->query->select->total++;

    if ($value) {

      $query = $this->_db->prepare('SELECT * FROM `port` WHERE `value` = ?');

      $query->execute([$value]);

    } else {

      $query = $this->_db->query('SELECT * FROM `port` WHERE `value` IS NULL');
    }

    return $query->fetch();
  }

  public function initPortId(mixed $value) : int {

    if ($result = $this->findPort($value)) {

      return $result->portId;
    }

    return $this->addPort($value);
  }

  // URI
  public function addUri(mixed $value) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `uri` SET `value` = ?');

    $query->execute([$value]);

    return $this->_db->lastInsertId();
  }

  public function getUri(int $uriId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `uri` WHERE `uriId` = ?');

    $query->execute([$uriId]);

    return $query->fetch();
  }

  public function findUri(mixed $value) {

    $this->_debug->query->select->total++;

    if ($value) {

      $query = $this->_db->prepare('SELECT * FROM `uri` WHERE `value` = ?');

      $query->execute([$value]);

    } else {

      $query = $this->_db->query('SELECT * FROM `uri` WHERE `value` IS NULL');
    }

    return $query->fetch();
  }

  public function initUriId(mixed $value) : int {

    if ($result = $this->findUri($value)) {

      return $result->uriId;
    }

    return $this->addUri($value);
  }

  // Info Hash
  public function addInfoHash(mixed $value, int $version) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `infoHash` SET `value` = ?, `version` = ?');

    $query->execute([$value, $version]);

    return $this->_db->lastInsertId();
  }

  public function getInfoHash(int $infoHashId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `infoHash` WHERE `infoHashId` = ?');

    $query->execute([$infoHashId]);

    return $query->fetch();
  }

  public function findInfoHash(string $value, int $version) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `infoHash` WHERE `value` = ? AND `version` = ?');

    $query->execute([$value, $version]);

    return $query->fetch();
  }

  public function initInfoHashId(mixed $value, int $version) : int {

    if ($result = $this->findInfoHash($value, $version)) {

      return $result->infoHashId;
    }

    return $this->addInfoHash($value, $version);
  }

  // Address Tracker
  public function addAddressTracker(int $schemeId, int $hostId, mixed $portId, mixed $uriId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `addressTracker` SET `schemeId` = ?, `hostId` = ?, `portId` = ?, `uriId` = ?');

    $query->execute([$schemeId, $hostId, $portId, $uriId]);

    return $this->_db->lastInsertId();
  }

  public function getAddressTracker(int $addressTrackerId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `addressTracker` WHERE `addressTrackerId` = ?');

    $query->execute([$addressTrackerId]);

    return $query->fetch();
  }

  public function findAddressTracker(int $schemeId, int $hostId, mixed $portId, mixed $uriId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `addressTracker` WHERE `schemeId` = ' . (int) $schemeId . '
                                                               AND   `hostId`   = ' . (int) $hostId . '
                                                               AND   `portId`     ' . ($portId ? ' = ' .  (int) $portId : ' IS NULL ') . '
                                                               AND   `uriId`      ' . ($uriId  ? ' = ' .  (int) $uriId  : ' IS NULL '));

    return $query->fetch();
  }

  public function initAddressTrackerId(int $schemeId, int $hostId, mixed $portId, mixed $uriId) : int {

    if ($result = $this->findAddressTracker($schemeId, $hostId, $portId, $uriId)) {

      return $result->addressTrackerId;
    }

    return $this->addAddressTracker($schemeId, $hostId, $portId, $uriId);
  }

  // Acceptable Source
  public function addAcceptableSource(int $schemeId, int $hostId, mixed $portId, mixed $uriId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `acceptableSource` SET `schemeId` = ?, `hostId` = ?, `portId` = ?, `uriId` = ?');

    $query->execute([$schemeId, $hostId, $portId, $uriId]);

    return $this->_db->lastInsertId();
  }

  public function getAcceptableSource(int $acceptableSourceId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `acceptableSource` WHERE `acceptableSourceId` = ?');

    $query->execute([$acceptableSourceId]);

    return $query->fetch();
  }

  public function findAcceptableSource(int $schemeId, int $hostId, mixed $portId, mixed $uriId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `acceptableSource` WHERE `schemeId` = ' . (int) $schemeId . '
                                                                 AND   `hostId`   = ' . (int) $hostId . '
                                                                 AND   `portId`     ' . ($portId ? ' = ' .  (int) $portId : ' IS NULL ') . '
                                                                 AND   `uriId`      ' . ($uriId  ? ' = ' .  (int) $uriId  : ' IS NULL '));

    return $query->fetch();
  }

  public function initAcceptableSourceId(int $schemeId, int $hostId, mixed $portId, mixed $uriId) : int {

    if ($result = $this->findAcceptableSource($schemeId, $hostId, $portId, $uriId)) {

      return $result->acceptableSourceId;
    }

    return $this->addAcceptableSource($schemeId, $hostId, $portId, $uriId);
  }

  // eXact Source
  public function addExactSource(int $schemeId, int $hostId, mixed $portId, mixed $uriId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `eXactSource` SET `schemeId` = ?, `hostId` = ?, `portId` = ?, `uriId` = ?');

    $query->execute([$schemeId, $hostId, $portId, $uriId]);

    return $this->_db->lastInsertId();
  }

  public function getExactSource(int $eXactSourceId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `eXactSource` WHERE `eXactSourceId` = ?');

    $query->execute([$eXactSourceId]);

    return $query->fetch();
  }

  public function findExactSource(int $schemeId, int $hostId, mixed $portId, mixed $uriId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `eXactSource` WHERE `schemeId` = ' . (int) $schemeId . '
                                                            AND   `hostId`   = ' . (int) $hostId . '
                                                            AND   `portId`     ' . ($portId ? ' = ' .  (int) $portId : ' IS NULL ') . '
                                                            AND   `uriId`      ' . ($uriId  ? ' = ' .  (int) $uriId  : ' IS NULL '));

    return $query->fetch();
  }

  public function initExactSourceId(int $schemeId, int $hostId, mixed $portId, mixed $uriId) : int {

    if ($result = $this->findExactSource($schemeId, $hostId, $portId, $uriId)) {

      return $result->eXactSourceId;
    }

    return $this->addExactSource($schemeId, $hostId, $portId, $uriId);
  }

  // Keyword Topic
  public function addKeywordTopic(string $value) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `keywordTopic` SET `value` = ?');

    $query->execute([$value]);

    return $this->_db->lastInsertId();
  }

  public function findKeywordTopic(string $value) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `keywordTopic` WHERE `value` = ?');

    $query->execute([$value]);

    return $query->fetch();
  }

  public function getKeywordTopic(int $keywordTopicId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `keywordTopic` WHERE `keywordTopicId` = ?');

    $query->execute([$keywordTopicId]);

    return $query->fetch();
  }

  public function initKeywordTopicId(string $value) : int {

    if ($result = $this->findKeywordTopic($value)) {

      return $result->keywordTopicId;
    }

    return $this->addKeywordTopic($value);
  }

  // User
  public function addUser(string $address, bool $approved, $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `user` SET `address` = ?, `approved` = ?, `timeAdded` = ?');

    $query->execute([$address, (int) $approved, $timeAdded]);

    return $this->_db->lastInsertId();
  }

  public function getUser(int $userId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `user` WHERE `userId` = ?');

    $query->execute([$userId]);

    return $query->fetch();
  }

  public function getUsers() {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `user`');

    return $query->fetchAll();
  }

  public function getUsersTotal() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `user`');

    $query->execute();

    return $query->fetch()->result;
  }

  public function getUsersTotalByPublic(mixed $public) : int {

    $this->_debug->query->select->total++;

    if (is_null($public))
    {
      $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `user` WHERE `public` IS NULL');
      $query->execute();
    }
    else
    {
      $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `user` WHERE `public` = ?');
      $query->execute([(int) $public]);
    }

    return $query->fetch()->result;
  }

  public function findUserByAddress(string $address) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `user` WHERE `address` = ?');

    $query->execute([$address]);

    return $query->fetch();
  }

  public function initUserId(string $address, bool $approved, int $timeAdded) : int {

    if ($result = $this->findUserByAddress($address)) {

      return $result->userId;
    }

    return $this->addUser($address, $approved, $timeAdded);
  }

  public function updateUserApproved(int $userId, mixed $approved, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `user` SET `approved` = ?, `timeUpdated` = ? WHERE `userId` = ?');

    $query->execute([(int) $approved, $timeUpdated, $userId]);

    return $query->rowCount();
  }

  public function updateUserPublic(int $userId, bool $public, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `user` SET `public` = ?, `timeUpdated` = ? WHERE `userId` = ?');

    $query->execute([(int) $public, $timeUpdated, $userId]);

    return $query->rowCount();
  }

  public function updateUserTimeUpdated(int $userId, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `user` SET `timeUpdated` = ? WHERE `userId` = ?');

    $query->execute([$timeUpdated, $userId]);

    return $query->rowCount();
  }

  // Magnet
  public function addMagnet(int $userId,
                            int $xl,
                            string $dn,
                            string $linkSource,
                            bool $public,
                            bool $comments,
                            bool $sensitive,
                            bool $approved,
                            int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnet` SET  `userId`      = ?,
                                                            `xl`          = ?,
                                                            `dn`          = ?,
                                                            `linkSource`  = ?,
                                                            `public`      = ?,
                                                            `comments`    = ?,
                                                            `sensitive`   = ?,
                                                            `approved`    = ?,
                                                            `timeAdded`   = ?');

    $query->execute(
      [
        $userId,
        $xl,
        $dn,
        $linkSource,
        $public ? 1 : 0,
        $comments ? 1 : 0,
        $sensitive ? 1 : 0,
        $approved ? 1 : 0,
        $timeAdded
      ]
    );

    return $this->_db->lastInsertId();
  }

  public function getMagnet(int $magnetId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnet` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetch();
  }

  public function getMagnets() {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnet`');

    $query->execute();

    return $query->fetchAll();
  }

  public function getMagnetsTotal() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnet`');

    $query->execute();

    return $query->fetch()->result;
  }

  public function findMagnet(int $userId, int $timeAdded) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnet` WHERE `userId` = ? AND `timeAdded` = ?');

    $query->execute([$userId, $timeAdded]);

    return $query->fetch();
  }

  public function findMagnetsByUserId(int $userId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnet` WHERE `userId` = ?');

    $query->execute([$userId]);

    return $query->fetchAll();
  }

  public function findMagnetsTotalByUserId(int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnet` WHERE `userId` = ?');

    $query->execute([$userId]);

    return $query->fetch()->result;
  }

  public function getMagnetsTotalByUsersPublic(bool $public) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM  `magnet`
                                                              JOIN  `user` ON (`user`.`userId` = `magnet`.`userId`)
                                                              WHERE `user`.`public` = ?');

    $query->execute([(int) $public]);

    return $query->fetch()->result;
  }

  public function updateMagnetDn(int $magnetId, string $dn, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `dn` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([$dn, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetTitle(int $magnetId, string $title, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `title` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([$title, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetPreview(int $magnetId, string $preview, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `preview` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([$preview, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetDescription(int $magnetId, string $description, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `description` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([$description, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetPublic(int $magnetId, bool $public, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `public` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([(int) $public, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetComments(int $magnetId, bool $comments, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `comments` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([(int) $comments, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetSensitive(int $magnetId, bool $sensitive, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `sensitive` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([(int) $sensitive, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetApproved(int $magnetId, bool $approved, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `approved` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([(int) $approved, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  // Magnet to Info Hash
  public function addMagnetToInfoHash(int $magnetId, int $infoHashId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetToInfoHash` SET `magnetId` = ?, `infoHashId` = ?');

    $query->execute([$magnetId, $infoHashId]);

    return $this->_db->lastInsertId();
  }

  public function findMagnetToInfoHashByMagnetId(int $magnetId)
  {
    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToInfoHash` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetchAll();
  }

  // Magnet to AddressTracker
  public function addMagnetToAddressTracker(int $magnetId, int $addressTrackerId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetToAddressTracker` SET `magnetId` = ?, `addressTrackerId` = ?');

    $query->execute([$magnetId, $addressTrackerId]);

    return $this->_db->lastInsertId();
  }

  public function updateMagnetToAddressTrackerSeeders(int $magnetToAddressTrackerId, int $seeders, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnetToAddressTracker` SET `seeders` = ?, `timeUpdated` = ? WHERE `magnetToAddressTrackerId` = ?');

    $query->execute([$seeders, $timeUpdated, $magnetToAddressTrackerId]);

    return $query->rowCount();
  }

  public function updateMagnetToAddressTrackerCompleted(int $magnetToAddressTrackerId, int $completed, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnetToAddressTracker` SET `completed` = ?, `timeUpdated` = ? WHERE `magnetToAddressTrackerId` = ?');

    $query->execute([$completed, $timeUpdated, $magnetToAddressTrackerId]);

    return $query->rowCount();
  }

  public function updateMagnetToAddressTrackerLeechers(int $magnetToAddressTrackerId, int $leechers, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnetToAddressTracker` SET `leechers` = ?, `timeUpdated` = ? WHERE `magnetToAddressTrackerId` = ?');

    $query->execute([$leechers, $timeUpdated, $magnetToAddressTrackerId]);

    return $query->rowCount();
  }

  public function updateMagnetToAddressTrackerTimeOffline(int $magnetToAddressTrackerId, mixed $timeOffline) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnetToAddressTracker` SET `timeOffline` = ? WHERE `magnetToAddressTrackerId` = ?');

    $query->execute([$timeOffline, $magnetToAddressTrackerId]);

    return $query->rowCount();
  }

  public function deleteMagnetToAddressTrackerByMagnetId(int $magnetId) : int {

    $this->_debug->query->delete->total++;

    $query = $this->_db->prepare('DELETE FROM `magnetToAddressTracker` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->rowCount();
  }

  public function findMagnetToAddressTracker(int $magnetId, int $addressTrackerId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToAddressTracker` WHERE `magnetId` = ? AND `addressTrackerId` = ?');

    $query->execute([$magnetId, $addressTrackerId]);

    return $query->fetch();
  }

  public function findAddressTrackerByMagnetId(int $magnetId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToAddressTracker` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetchAll();
  }

  public function getMagnetToAddressTrackerScrapeQueue(int $limit) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToAddressTracker`

                                           WHERE `timeOffline` IS NULL

                                           ORDER BY `timeUpdated` ASC, RAND()

                                           LIMIT ' . (int) $limit);

    $query->execute();

    return $query->fetchAll();
  }

  public function resetMagnetToAddressTrackerTimeOfflineByTimeout(int $timeOffline) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnetToAddressTracker` SET `timeOffline` = NULL WHERE `timeOffline` < ?');

    $query->execute(
      [
        time() - $timeOffline
      ]
    );

    return $query->rowCount();
  }

  public function initMagnetToAddressTrackerId(int $magnetId, int $addressTrackerId) : int {

    if ($result = $this->findMagnetToAddressTracker($magnetId, $addressTrackerId)) {

      return $result->magnetToAddressTrackerId;
    }

    return $this->addMagnetToAddressTracker($magnetId, $addressTrackerId);
  }

  public function getMagnetToAddressTrackerSeedersSumByMagnetId(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT SUM(`seeders`) AS `result` FROM `magnetToAddressTracker` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return (int) $query->fetch()->result;
  }

  public function getMagnetToAddressTrackerCompletedSumByMagnetId(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT SUM(`completed`) AS `result` FROM `magnetToAddressTracker` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return (int) $query->fetch()->result;
  }

  public function getMagnetToAddressTrackerLeechersSumByMagnetId(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT SUM(`leechers`) AS `result` FROM `magnetToAddressTracker` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return (int) $query->fetch()->result;
  }

  public function getMagnetToAcceptableSourceTotalByMagnetId(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetToAcceptableSource` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return (int) $query->fetch()->result;
  }

  public function getMagnetToAddressTrackerSeedersSum() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT SUM(`seeders`) AS `result` FROM `magnetToAddressTracker`');

    $query->execute();

    return (int) $query->fetch()->result;
  }

  public function getMagnetToAddressTrackerCompletedSum() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT SUM(`completed`) AS `result` FROM `magnetToAddressTracker`');

    $query->execute();

    return (int) $query->fetch()->result;
  }

  public function getMagnetToAddressTrackerLeechersSum() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT SUM(`leechers`) AS `result` FROM `magnetToAddressTracker`');

    $query->execute();

    return (int) $query->fetch()->result;
  }

  // Magnet to AcceptableSource
  public function addMagnetToAcceptableSource(int $magnetId, int $acceptableSourceId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetToAcceptableSource` SET `magnetId` = ?, `acceptableSourceId` = ?');

    $query->execute([$magnetId, $acceptableSourceId]);

    return $this->_db->lastInsertId();
  }

  public function deleteMagnetToAcceptableSourceByMagnetId(int $magnetId) : int {

    $this->_debug->query->delete->total++;

    $query = $this->_db->prepare('DELETE FROM `magnetToAcceptableSource` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->rowCount();
  }

  public function findMagnetToAcceptableSource(int $magnetId, int $acceptableSourceId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToAcceptableSource` WHERE `magnetId` = ? AND `acceptableSourceId` = ?');

    $query->execute([$magnetId, $acceptableSourceId]);

    return $query->fetch();
  }

  public function findAcceptableSourceByMagnetId(int $magnetId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToAcceptableSource` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetchAll();
  }

  public function initMagnetToAcceptableSourceId(int $magnetId, int $acceptableSourceId) : int {

    if ($result = $this->findMagnetToAcceptableSource($magnetId, $acceptableSourceId)) {

      return $result->magnetToAcceptableSourceId;
    }

    return $this->addMagnetToAcceptableSource($magnetId, $acceptableSourceId);
  }

  // Magnet to eXactSource
  public function addMagnetToExactSource(int $magnetId, int $eXactSourceId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetToExactSource` SET `magnetId` = ?, `eXactSourceId` = ?');

    $query->execute([$magnetId, $eXactSourceId]);

    return $this->_db->lastInsertId();
  }

  public function deleteMagnetToExactSourceByMagnetId(int $magnetId) : int {

    $this->_debug->query->delete->total++;

    $query = $this->_db->prepare('DELETE FROM `magnetToExactSource` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $this->_db->lastInsertId();
  }

  public function findMagnetToExactSource(int $magnetId, int $eXactSourceId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToExactSource` WHERE `magnetId` = ? AND `eXactSourceId` = ?');

    $query->execute([$magnetId, $eXactSourceId]);

    return $query->fetch();
  }

  public function findExactSourceByMagnetId(int $magnetId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToExactSource` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetchAll();
  }

  public function initMagnetToExactSourceId(int $magnetId, int $eXactSourceId) : int {

    if ($result = $this->findMagnetToEXactSource($magnetId, $eXactSourceId)) {

      return $result->magnetToExactSourceId;
    }

    return $this->addMagnetToEXactSource($magnetId, $eXactSourceId);
  }

  // Magnet to KeywordTopic
  public function addMagnetToKeywordTopic(int $magnetId, int $keywordTopicId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetToKeywordTopic` SET `magnetId` = ?, `keywordTopicId` = ?');

    $query->execute([$magnetId, $keywordTopicId]);

    return $this->_db->lastInsertId();
  }

  public function deleteMagnetToKeywordTopicByMagnetId(int $magnetId) : int {

    $this->_debug->query->delete->total++;

    $query = $this->_db->prepare('DELETE FROM `magnetToKeywordTopic` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->rowCount();
  }

  public function findMagnetToKeywordTopic(int $magnetId, int $keywordTopicId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToKeywordTopic` WHERE `magnetId` = ? AND `keywordTopicId` = ?');

    $query->execute([$magnetId, $keywordTopicId]);

    return $query->fetch();
  }

  public function findKeywordTopicByMagnetId(int $magnetId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetToKeywordTopic` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetchAll();
  }

  public function initMagnetToKeywordTopicId(int $magnetId, int $keywordTopicId) : int {

    if ($result = $this->findMagnetToKeywordTopic($magnetId, $keywordTopicId)) {

      return $result->magnetToKeywordTopicId;
    }

    return $this->addMagnetToKeywordTopic($magnetId, $keywordTopicId);
  }

  // Magnet lock
  public function addMagnetLock(int $magnetId, int $userId, int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetLock` SET `magnetId` = ?, `userId` = ?, `timeAdded` = ?');

    $query->execute([$magnetId, $userId, $timeAdded]);

    return $this->_db->lastInsertId();
  }

  public function flushMagnetLock(int $magnetId) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('DELETE FROM `magnetLock` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->rowCount();
  }

  public function findLastMagnetLock(int $magnetId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetLock` WHERE `magnetId` = ? ORDER BY `magnetLockId` DESC LIMIT 1');

    $query->execute([$magnetId]);

    return $query->fetch();
  }

  // Magnet comment
  public function addMagnetComment( int $magnetId,
                                    int $userId,
                                    mixed $magnetCommentIdParent,
                                    string $value,
                                    bool $approved,
                                    bool $public,
                                    int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetComment` SET `magnetId` = ?,
                                                                  `userId` = ?,
                                                                  `magnetCommentIdParent` = ?,
                                                                  `value` = ?,
                                                                  `approved` = ?,
                                                                  `public` = ?,
                                                                  `timeAdded` = ?');

    $query->execute(
      [
        $magnetId,
        $userId,
        $magnetCommentIdParent,
        $value,
        $approved,
        $public,
        $timeAdded
      ]
    );

    return $this->_db->lastInsertId();
  }

  public function updateMagnetCommentPublic(int $magnetCommentId, mixed $public) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnetComment` SET `public` = ? WHERE `magnetCommentId` = ?');

    $query->execute([(int) $public, $magnetCommentId]);

    return $query->rowCount();
  }

  public function updateMagnetCommentApproved(int $magnetCommentId, mixed $approved) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnetComment` SET `approved` = ? WHERE `magnetCommentId` = ?');

    $query->execute([(int) $approved, $magnetCommentId]);

    return $query->rowCount();
  }

  public function getMagnetCommentsTotal() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT COUNT(*) AS `result` FROM `magnetComment`');

    return $query->fetch()->result;
  }

  public function getMagnetComments() {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `magnetComment`');

    return $query->fetchAll();
  }

  public function findMagnetCommentsTotalByMagnetId(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(DISTINCT `userId`) AS `result` FROM `magnetComment` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetch()->result;
  }

  public function findMagnetCommentsTotalByUserId(int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(DISTINCT `magnetId`) AS `result` FROM `magnetComment` WHERE `userId` = ?');

    $query->execute([$userId]);

    return $query->fetch()->result;
  }

  public function findMagnetCommentsTotal(int $magnetId, int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetComment` WHERE `magnetId` = ? AND `userId` = ?');

    $query->execute([$magnetId, $userId]);

    return $query->fetch()->result;
  }

  public function findMagnetCommentsTotalByUsersPublic(bool $public) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM  `magnetComment`
                                                              JOIN  `user` ON (`user`.`userId` = `magnetComment`.`userId`)
                                                              WHERE `user`.`public` = ?');

    $query->execute([(int) $public]);

    return $query->fetch()->result;
  }

  public function findMagnetComments(int $magnetId, mixed $magnetCommentIdParent = null) {

    $this->_debug->query->select->total++;

    if ($magnetCommentIdParent)
    {
      $query = $this->_db->prepare('SELECT * FROM `magnetComment` WHERE `magnetId` = ? AND `magnetCommentIdParent` = ?');

      $query->execute([$magnetId, $magnetCommentIdParent]);
    }
    else
    {
      $query = $this->_db->prepare('SELECT * FROM `magnetComment` WHERE `magnetId` = ? AND `magnetCommentIdParent` IS NULL');

      $query->execute([$magnetId]);
    }

    return $query->fetchAll();
  }

  public function getMagnetComment(int $magnetCommentId) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetComment` WHERE `magnetCommentId` = ?');

    $query->execute([$magnetCommentId]);

    return $query->fetch();
  }

  // Magnet star
  public function addMagnetStar(int $magnetId, int $userId, bool $value, int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetStar` SET `magnetId` = ?, `userId` = ?, `value` = ?, `timeAdded` = ?');

    $query->execute([$magnetId, $userId, (int) $value, $timeAdded]);

    return $this->_db->lastInsertId();
  }

  public function getMagnetStars() {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `magnetStar`');

    return $query->fetchAll();
  }

  public function getMagnetStarsTotal() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT COUNT(*) AS `result` FROM `magnetStar`');

    return $query->fetch()->result;
  }

  public function findMagnetStarsTotalByMagnetId(int $magnetId, bool $value) : int {

    $this->_debug->query->select->total++;

    $total = 0;

    $query = $this->_db->prepare('SELECT COUNT(DISTINCT `userId`) AS `result` FROM `magnetStar` WHERE `magnetId` = ? AND `value` = ?');

    $query->execute([$magnetId, (int) $value]);

    return $query->fetch()->result;
  }

  public function findMagnetStarsTotalByUserId(int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(DISTINCT `magnetId`) AS `result` FROM `magnetStar` WHERE `userId` = ?');

    $query->execute([$userId]);

    return $query->fetch()->result;
  }

  public function findLastMagnetStarValue(int $magnetId, int $userId) : bool {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnetStar` WHERE `magnetId` = ? AND `userId` = ? ORDER BY `magnetStarId` DESC');

    $query->execute([$magnetId, $userId]);

    return $query->rowCount() ? (bool) $query->fetch()->value : false;
  }

  public function findMagnetStarsTotalByUsersPublic(bool $public) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM  `magnetStar`
                                                              JOIN  `user` ON (`user`.`userId` = `magnetStar`.`userId`)
                                                              WHERE `user`.`public` = ?');

    $query->execute([(int) $public]);

    return $query->fetch()->result;
  }

  // Magnet download
  public function addMagnetDownload(int $magnetId, int $userId, int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetDownload` SET `magnetId` = ?, `userId` = ?, `timeAdded` = ?');

    $query->execute([$magnetId, $userId, $timeAdded]);

    return $this->_db->lastInsertId();
  }

  public function getMagnetDownloads() {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `magnetDownload`');

    return $query->fetchAll();
  }

  public function getMagnetDownloadsTotal() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT COUNT(*) AS `result` FROM `magnetDownload`');

    return $query->fetch()->result;
  }

  public function findMagnetDownloadsTotal(int $magnetId, int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetDownload` WHERE `magnetId` = ? AND `userId` = ?');

    $query->execute([$magnetId, $userId]);

    return $query->fetch()->result;
  }

  public function findMagnetDownloadsTotalByMagnetId(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(DISTINCT `userId`) AS `result` FROM `magnetDownload` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetch()->result;
  }

  public function findMagnetDownloadsTotalByUserId(int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(DISTINCT `magnetId`) AS `result` FROM `magnetDownload` WHERE `userId` = ?');

    $query->execute([$userId]);

    return $query->fetch()->result;
  }

  public function findMagnetDownloadsTotalByUsersPublic(bool $public) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM  `magnetDownload`
                                                              JOIN  `user` ON (`user`.`userId` = `magnetDownload`.`userId`)
                                                              WHERE `user`.`public` = ?');

    $query->execute([(int) $public]);

    return $query->fetch()->result;
  }

  // Magnet view
  public function addMagnetView(int $magnetId, int $userId, int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetView` SET `magnetId` = ?, `userId` = ?, `timeAdded` = ?');

    $query->execute([$magnetId, $userId, $timeAdded]);

    return $this->_db->lastInsertId();
  }

  public function getMagnetViews() {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT * FROM `magnetView`');

    return $query->fetchAll();
  }

  public function getMagnetViewsTotal() : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->query('SELECT COUNT(*) AS `result` FROM `magnetView`');

    return $query->fetch()->result;
  }

  public function findMagnetViewsTotalByUserId(int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetView` WHERE `userId` = ?');

    $query->execute([$userId]);

    return $query->fetch()->result;
  }

  public function findMagnetViewsTotalByUsersPublic(bool $public) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM  `magnetView`
                                                              JOIN  `user` ON (`user`.`userId` = `magnetView`.`userId`)
                                                              WHERE `user`.`public` = ?');

    $query->execute([(int) $public]);

    return $query->fetch()->result;
  }
}