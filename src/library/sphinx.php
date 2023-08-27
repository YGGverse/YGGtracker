<?php

class Sphinx {

  private $_sphinx;

  public function __construct(string $host, int $port)
  {
    $this->_sphinx = new PDO('mysql:host=' . $host . ';port=' . $port . ';charset=utf8', false, false, [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
    $this->_sphinx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->_sphinx->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
  }

  public function searchMagnetsTotal(string $keyword) : int
  {
    $query = $this->_sphinx->prepare('SELECT COUNT(*) AS `total` FROM `magnet` WHERE MATCH(?)');

    $query->execute(
      [
        self::_match($keyword)
      ]
    );

    return $query->fetch()->total;
  }

  public function searchMagnets(string $keyword, int $start, int $limit, int $maxMatches)
  {
    $query = $this->_sphinx->prepare("SELECT *

                                      FROM `magnet`

                                      WHERE MATCH(?)

                                      ORDER BY `magnetId` DESC, WEIGHT() DESC

                                      LIMIT " . (int) ($start >= $maxMatches ? ($maxMatches > 0 ? $maxMatches - 1 : 0) : $start) . "," . (int) $limit . "

                                      OPTION `max_matches`=" . (int) ($maxMatches >= 1 ? $maxMatches : 1));

    $query->execute(
      [
        self::_match($keyword)
      ]
    );

    return $query->fetchAll();
  }

  private static function _match(string $keyword) : string
  {
    $keyword = trim($keyword);

    if (empty($keyword))
    {
      return $keyword;
    }

    $keyword = str_replace(['"'], ' ', $keyword);
    $keyword = preg_replace('/[\W]/ui', ' ', $keyword);
    $keyword = preg_replace('/[\s]+/ui', ' ', $keyword);
    $keyword = trim($keyword);

    $result = [];

    foreach ((array) explode(' ', $keyword) as $value)
    {
      $result[] = sprintf('@"*%s*"', $value);
    }

    return implode(' | ', $result);
  }
}
