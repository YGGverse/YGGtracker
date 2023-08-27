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

  // Magnet
  public function addMagnet(int $userId,
                            string $xt,
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
                                                            `xt`          = ?,
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
        $xt,
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

  public function findMagnet(int $userId, string $xt) {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT * FROM `magnet` WHERE `userId` = ? AND `xt` = ?');

    $query->execute([$userId, $xt]);

    return $query->fetch();
  }

  public function initMagnetId( int $userId,
                                string $xt,
                                int $xl,
                                string $dn,
                                string $linkSource,
                                bool $public,
                                bool $comments,
                                bool $sensitive,
                                bool $approved,
                                int $timeAdded) : int {

    if ($result = $this->findMagnet($userId, $xt)) {

      return $result->magnetId;
    }

    return $this->addMagnet($userId,
                            $xt,
                            $xl,
                            $dn,
                            $linkSource,
                            $public,
                            $comments,
                            $sensitive,
                            $approved,
                            $timeAdded);
  }

  public function updateMagnetDn(int $magnetId, string $dn, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `dn` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([$dn, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetMetaTitle(int $magnetId, string $metaTitle, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `metaTitle` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([$metaTitle, $timeUpdated, $magnetId]);

    return $query->rowCount();
  }

  public function updateMagnetMetaDescription(int $magnetId, string $metaDescription, int $timeUpdated) : int {

    $this->_debug->query->update->total++;

    $query = $this->_db->prepare('UPDATE `magnet` SET `metaDescription` = ?, `timeUpdated` = ? WHERE `magnetId` = ?');

    $query->execute([$metaDescription, $timeUpdated, $magnetId]);

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

  // Magnet to AddressTracker
  public function addMagnetToAddressTracker(int $magnetId, int $addressTrackerId) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetToAddressTracker` SET `magnetId` = ?, `addressTrackerId` = ?');

    $query->execute([$magnetId, $addressTrackerId]);

    return $this->_db->lastInsertId();
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

  public function initMagnetToAddressTrackerId(int $magnetId, int $addressTrackerId) : int {

    if ($result = $this->findMagnetToAddressTracker($magnetId, $addressTrackerId)) {

      return $result->magnetToAddressTrackerId;
    }

    return $this->addMagnetToAddressTracker($magnetId, $addressTrackerId);
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

  // Magnet comment
  public function getMagnetCommentsTotal(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetComment` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetch()->result;
  }

  public function findMagnetCommentsTotalByUserId(int $magnetId, int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetComment` WHERE `magnetId` = ? AND `userId` = ?');

    $query->execute([$magnetId, $userId]);

    return $query->fetch()->result;
  }

  // Magnet star
  public function addMagnetStar(int $magnetId, int $userId, int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetStar` SET `magnetId` = ?, `userId` = ?, `timeAdded` = ?');

    $query->execute([$magnetId, $userId, $timeAdded]);

    return $this->_db->lastInsertId();
  }

  public function deleteMagnetStarByUserId(int $magnetId, int $userId) : int {

    $this->_debug->query->delete->total++;

    $query = $this->_db->prepare('DELETE FROM `magnetStar` WHERE `magnetId` = ? AND `userId` = ?');

    $query->execute([$magnetId, $userId]);

    return $query->rowCount();
  }

  public function getMagnetStarsTotal(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetStar` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetch()->result;
  }

  public function findMagnetStarsTotalByUserId(int $magnetId, int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetStar` WHERE `magnetId` = ? AND `userId` = ?');

    $query->execute([$magnetId, $userId]);

    return $query->fetch()->result;
  }

  // Magnet download
  public function addMagnetDownload(int $magnetId, int $userId, int $timeAdded) : int {

    $this->_debug->query->insert->total++;

    $query = $this->_db->prepare('INSERT INTO `magnetDownload` SET `magnetId` = ?, `userId` = ?, `timeAdded` = ?');

    $query->execute([$magnetId, $userId, $timeAdded]);

    return $this->_db->lastInsertId();
  }

  public function getMagnetDownloadsTotal(int $magnetId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetDownload` WHERE `magnetId` = ?');

    $query->execute([$magnetId]);

    return $query->fetch()->result;
  }

  public function deleteMagnetDownloadByUserId(int $magnetId, int $userId) : int {

    $this->_debug->query->delete->total++;

    $query = $this->_db->prepare('DELETE FROM `magnetDownload` WHERE `magnetId` = ? AND `userId` = ?');

    $query->execute([$magnetId, $userId]);

    return $query->rowCount();
  }

  public function findMagnetDownloadsTotalByUserId(int $magnetId, int $userId) : int {

    $this->_debug->query->select->total++;

    $query = $this->_db->prepare('SELECT COUNT(*) AS `result` FROM `magnetDownload` WHERE `magnetId` = ? AND `userId` = ?');

    $query->execute([$magnetId, $userId]);

    return $query->fetch()->result;
  }
}