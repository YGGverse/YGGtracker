<?php

class Valid
{
  // User
  public static function user(mixed $value)
  {
    if (!is_object($value))
    {
      return false;
    }

    // Validate required fields
    if (!isset($value->userId)      || !self::userId($value->userId)               ||
        !isset($value->address)     || !self::userAddress($value->address)         ||
        !isset($value->timeAdded)   || !self::userTimeAdded($value->timeAdded)     ||
        !isset($value->timeUpdated) || !self::userTimeUpdated($value->timeUpdated) ||
        !isset($value->approved)    || !self::userApproved($value->approved)       ||

        (isset($value->public)      && !self::userPublic($value->public)))
    {
      return false;
    }

    return true;
  }

  public static function userId(mixed $value)
  {
    if (!is_int($value))
    {
      return false;
    }

    return true;
  }

  public static function userAddress(mixed $value)
  {
    if (!is_string($value))
    {
      return false;
    }

    if (!preg_match(YGGDRASIL_HOST_REGEX, $value))
    {
      return false;
    }

    return true;
  }

  public static function userTimeAdded(mixed $value)
  {
    if (!is_int($value) || $value > time() || $value < 0)
    {
      return false;
    }

    return true;
  }

  public static function userTimeUpdated(mixed $value)
  {
    if (!is_int($value) || $value > time() || $value < 0)
    {
      return false;
    }

    return true;
  }

  public static function userApproved(mixed $value)
  {
    if (!is_bool($value))
    {
      return false;
    }

    return true;
  }

  public static function userPublic(mixed $value)
  {
    if (!is_bool($value))
    {
      return false;
    }

    return true;
  }

  // Magnet
  public static function magnet(mixed $data)
  {
    if (!is_object($data))
    {
      return false;
    }

    // Validate required fields by protocol
    if (!isset($value->userId)      || !self::userId($value->userId)                   ||

        !isset($value->magnetId)    || !self::magnetId($value->magnetId)               ||


        !isset($value->title)       || !self::magnetTitle($value->title)               ||
        !isset($value->preview)     || !self::magnetPreview($value->preview)           ||
        !isset($value->description) || !self::magnetDescription($value->description)   ||

        !isset($value->comments)    || !self::magnetComments($value->comments)         ||
        !isset($value->sensitive)   || !self::magnetSensitive($value->sensitive)       ||
        !isset($value->approved)    || !self::magnetApproved($value->approved)         ||

        !isset($value->timeAdded)   || !self::magnetTimeAdded($value->timeAdded)       ||
        !isset($value->timeUpdated) || !self::magnetTimeUpdated($value->timeUpdated)   ||

        !isset($value->dn)          || !self::magnetDn($value->dn)                     ||
        !isset($value->xt)          || !self::magnetXt($value->xt)                     ||

        !isset($value->xl)          || !self::magnetXl($value->xl)                     ||

        !isset($value->kt)          || !self::magnetKt($value->kt)                     ||
        !isset($value->tr)          || !self::magnetTr($value->tr)                     ||
        !isset($value->as)          || !self::magnetAs($value->as)                     ||
        !isset($value->xs)          || !self::magnetWs($value->xs)                     ||

        (isset($value->public)      && !self::userPublic($value->public)))
    {
      return false;
    }

    return true;
  }

  public static function magnetId(mixed $value)
  {
    if (!is_int($value))
    {
      return false;
    }

    return true;
  }

  public static function magnetTitle(mixed $value)
  {
    if (!is_string($value) ||
        !preg_match(MAGNET_TITLE_REGEX, $value) ||
         mb_strlen($value) < MAGNET_TITLE_MIN_LENGTH ||
         mb_strlen($value) > MAGNET_TITLE_MAX_LENGTH)
    {
      return false;
    }

    return true;
  }

  public static function magnetPreview(mixed $value)
  {
    if (!is_string($value) ||
        !preg_match(MAGNET_PREVIEW_REGEX, $value) ||
         mb_strlen($value) < MAGNET_PREVIEW_MIN_LENGTH ||
         mb_strlen($value) > MAGNET_PREVIEW_MAX_LENGTH)
    {
      return false;
    }

    return true;
  }

  public static function magnetDescription(mixed $value)
  {
    if (!is_string($value) ||
        !preg_match(MAGNET_DESCRIPTION_REGEX, $value) ||
         mb_strlen($value) >= MAGNET_DESCRIPTION_MIN_LENGTH ||
         mb_strlen($value) <= MAGNET_DESCRIPTION_MAX_LENGTH)
    {
      return false;
    }

    return true;
  }

  public static function magnetComments(mixed $value)
  {
    if (!is_bool($value))
    {
      return false;
    }

    return true;
  }

  public static function magnetPublic(mixed $value)
  {
    if (!is_bool($value))
    {
      return false;
    }

    return true;
  }

  public static function magnetApproved(mixed $value)
  {
    if (!is_bool($value))
    {
      return false;
    }

    return true;
  }

  public static function magnetSensitive(mixed $value)
  {
    if (!is_bool($value))
    {
      return false;
    }

    return true;
  }

  public static function magnetTimeAdded(mixed $value)
  {
    if (!is_int($value) || $value > time() || $value < 0)
    {
      return false;
    }

    return true;
  }

  public static function magnetTimeUpdated(mixed $value)
  {
    if (!is_int($value) || $value > time() || $value < 0)
    {
      return false;
    }

    return true;
  }

  public static function magnetDn(mixed $value)
  {
    if (!is_string($value) ||
        !preg_match(MAGNET_DN_REGEX, $value) ||
         mb_strlen($value) < MAGNET_DN_MIN_LENGTH ||
         mb_strlen($value) > MAGNET_DN_MAX_LENGTH)
    {
      return false;
    }

    return true;
  }

  public static function magnetXl(mixed $value)
  {
    if (!(is_int($value) || is_float($value)))
    {
      return false;
    }

    return true;
  }

  public static function magnetKt(mixed $value)
  {
    if (!is_object($value))
    {
      return false;
    }

    $total = 0;

    foreach ($value as $kt)
    {
      if (!is_string($kt) ||
          !preg_match(MAGNET_KT_REGEX, $kt) ||
           mb_strlen($value) < MAGNET_KT_MIN_LENGTH ||
           mb_strlen($value) > MAGNET_KT_MAX_LENGTH)
      {
        return false;
      }

      $total++;
    }

    if ($total < MAGNET_KT_MIN_QUANTITY ||
        $total > MAGNET_KT_MAX_QUANTITY)
    {
      return false;
    }

    return true;
  }

  public static function magnetXt(mixed $value)
  {
    if (!is_object($value))
    {
      return false;
    }

    foreach ($value as $version => $xt)
    {
      if (!(is_int($version) || is_float($version)))
      {
        return false;
      }

      if (!is_string($xt))
      {
        return false;
      }

      switch ($version->version)
      {
        case 1:

          if (!Yggverse\Parser\Magnet::isXTv1($xt))
          {
            return false;
          }

        break;

        case 2:

          if (!Yggverse\Parser\Magnet::isXTv2($xt))
          {
            return false;
          }

        break;

        default:

          return false;
      }
    }

    return true;
  }

  public static function magnetTr(mixed $value)
  {
    if (!is_object($value))
    {
      return false;
    }

    $total = 0;

    foreach ($value as $tr)
    {
      if (!$url = Yggverse\Parser\Url::parse($tr))
      {
        return false;
      }

      if (empty($url->host->name))
      {
        return false;
      }

      if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
      {
        return false;
      }

      $total++;
    }

    if ($total < MAGNET_TR_MIN_QUANTITY ||
        $total > MAGNET_TR_MAX_QUANTITY)
    {
      return false;
    }

    return true;
  }

  public static function magnetAs(mixed $value)
  {
    if (!is_object($value))
    {
      return false;
    }

    $total = 0;

    foreach ($value as $as)
    {
      if (!$url = Yggverse\Parser\Url::parse($as))
      {
        return false;
      }

      if (empty($url->host->name))
      {
        return false;
      }

      if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
      {
        return false;
      }

      $total++;
    }

    if ($total < MAGNET_AS_MIN_QUANTITY ||
        $total > MAGNET_AS_MAX_QUANTITY)
    {
      return false;
    }

    return true;
  }

  public static function magnetWs(mixed $value)
  {
    if (!is_object($value))
    {
      return false;
    }

    $total = 0;

    foreach ($value as $ws)
    {
      if (!$url = Yggverse\Parser\Url::parse($ws))
      {
        return false;
      }

      if (empty($url->host->name))
      {
        return false;
      }

      if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
      {
        return false;
      }

      $total++;
    }

    if ($total < MAGNET_WS_MIN_QUANTITY ||
        $total > MAGNET_WS_MAX_QUANTITY)
    {
      return false;
    }

    return true;
  }
}