<?php

class Valid
{
  private static $_error = [];

  // Common
  public static function getError()
  {
    return self::$_error;
  }

  public static function setError(array $value)
  {
    self::$_error = $value;
  }

  // User
  public static function user(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid user data type')
      );

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
      array_push(
        self::$_error,
        _('Invalid user data protocol')
      );

      return false;
    }

    return true;
  }

  public static function userId(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid userId data type')
      );

      return false;
    }

    return true;
  }

  public static function userAddress(mixed $value)
  {
    if (!is_string($value))
    {
      array_push(
        self::$_error,
        _('Invalid user address data type')
      );

      return false;
    }

    if (!preg_match(YGGDRASIL_HOST_REGEX, $value))
    {
      array_push(
        self::$_error,
        sprintf(
          _('User address format does not match condition "%s"'),
          YGGDRASIL_HOST_REGEX
        )
      );

      return false;
    }

    return true;
  }

  public static function userTimeAdded(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid user timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        self::$_error,
        _('User timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  public static function userTimeUpdated(mixed $value)
  {
    if (!(is_int($value) || is_bool($value)))
    {
      array_push(
        self::$_error,
        _('Invalid user timeUpdated data type')
      );

      return false;
    }

    if (is_int($value) && ($value > time() || $value < 0))
    {
      array_push(
        self::$_error,
        _('User timeUpdated out of range')
      );

      return false;
    }

    return true;
  }

  public static function userApproved(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid user approved data type')
      );

      return false;
    }

    return true;
  }

  public static function userPublic(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid user public data type')
      );

      return false;
    }

    return true;
  }

  // Magnet
  public static function magnet(mixed $data)
  {
    if (!is_object($data))
    {
      array_push(
        self::$_error,
        _('Invalid magnet data type')
      );

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

        (isset($value->public)      && !self::magnetPublic($value->public)))
    {
      array_push(
        self::$_error,
        _('Invalid magnet data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetId(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnetId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetTitle(mixed $value)
  {
    if (!is_string($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet title data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_TITLE_REGEX, $value))
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet title format does not match condition "%s"'),
          MAGNET_TITLE_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < MAGNET_TITLE_MIN_LENGTH ||
        mb_strlen($value) > MAGNET_TITLE_MAX_LENGTH)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet title out of %s-%s chars range'),
          MAGNET_TITLE_MIN_LENGTH,
          MAGNET_TITLE_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  public static function magnetPreview(mixed $value)
  {
    if (!is_string($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet preview data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_PREVIEW_REGEX, $value))
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet preview format does not match condition "%s"'),
          MAGNET_PREVIEW_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < MAGNET_PREVIEW_MIN_LENGTH ||
        mb_strlen($value) > MAGNET_PREVIEW_MAX_LENGTH)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet preview out of %s-%s chars range'),
          MAGNET_PREVIEW_MIN_LENGTH,
          MAGNET_PREVIEW_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  public static function magnetDescription(mixed $value)
  {
    if (!is_string($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet description data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_DESCRIPTION_REGEX, $value))
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet description format does not match condition "%s"'),
          MAGNET_DESCRIPTION_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < MAGNET_DESCRIPTION_MIN_LENGTH ||
        mb_strlen($value) > MAGNET_DESCRIPTION_MAX_LENGTH)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet description out of %s-%s chars range'),
          MAGNET_DESCRIPTION_MIN_LENGTH,
          MAGNET_DESCRIPTION_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  public static function magnetComments(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet comments data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetPublic(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet public data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetApproved(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet approved data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetSensitive(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet sensitive data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetTimeAdded(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        self::$_error,
        _('Magnet timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  public static function magnetTimeUpdated(mixed $value)
  {
    if (!(is_int($value) || is_bool($value)))
    {
      array_push(
        self::$_error,
        _('Invalid magnet timeUpdated data type')
      );

      return false;
    }

    if (is_int($value) && ($value > time() || $value < 0))
    {
      array_push(
        self::$_error,
        _('Magnet timeUpdated out of range')
      );

      return false;
    }

    return true;
  }

  public static function magnetDn(mixed $value)
  {
    if (!is_string($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet display name data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_DN_REGEX, $value))
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet display name format does not match condition "%s"'),
          MAGNET_DN_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < MAGNET_DN_MIN_LENGTH ||
        mb_strlen($value) > MAGNET_DN_MAX_LENGTH)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet display name out of %s-%s chars range'),
          MAGNET_DN_MIN_LENGTH,
          MAGNET_DN_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  public static function magnetXl(mixed $value)
  {
    if (!(is_int($value) || is_float($value)))
    {
      array_push(
        self::$_error,
        _('Invalid magnet exact length data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetKt(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet keyword data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $kt)
    {
      if (!is_string($value))
      {
        array_push(
          self::$_error,
          _('Invalid magnet keyword value data type')
        );

        return false;
      }

      if (!preg_match(MAGNET_KT_REGEX, $value))
      {
        array_push(
          self::$_error,
          sprintf(
            _('Magnet keyword format does not match condition "%s"'),
            MAGNET_KT_REGEX
          )
        );

        return false;
      }

      if (mb_strlen($value) < MAGNET_KT_MIN_LENGTH ||
          mb_strlen($value) > MAGNET_KT_MAX_LENGTH)
      {
        array_push(
          self::$_error,
          sprintf(
            _('Magnet keyword out of %s-%s chars range'),
            MAGNET_KT_MIN_LENGTH,
            MAGNET_KT_MAX_LENGTH
          )
        );

        return false;
      }

      $total++;
    }

    if ($total < MAGNET_KT_MIN_QUANTITY ||
        $total > MAGNET_KT_MAX_QUANTITY)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet keywords quantity out of %s-%s range'),
          MAGNET_KT_MIN_QUANTITY,
          MAGNET_KT_MAX_QUANTITY
        )
      );

      return false;
    }

    return true;
  }

  public static function magnetXt(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet info hash data type')
      );

      return false;
    }

    foreach ($value as $version => $xt)
    {
      if (!(is_int($version) || is_float($version)))
      {
        array_push(
          self::$_error,
          _('Invalid magnet info hash version data type')
        );

        return false;
      }

      if (!is_string($xt))
      {
        array_push(
          self::$_error,
          _('Invalid magnet info hash value data type')
        );

        return false;
      }

      switch ($version->version)
      {
        case 1:

          if (!Yggverse\Parser\Magnet::isXTv1($xt))
          {
            array_push(
              self::$_error,
              _('Invalid magnet info hash v1 value')
            );

            return false;
          }

        break;

        case 2:

          if (!Yggverse\Parser\Magnet::isXTv2($xt))
          {
            array_push(
              self::$_error,
              _('Invalid magnet info hash v2 value')
            );

            return false;
          }

        break;

        default:

          array_push(
            self::$_error,
            _('Magnet info hash version not supported')
          );

          return false;
      }
    }

    return true;
  }

  public static function magnetTr(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet address tracker data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $tr)
    {
      if (!$url = Yggverse\Parser\Url::parse($tr))
      {
        array_push(
          self::$_error,
          _('Invalid magnet address tracker URL')
        );

        return false;
      }

      if (empty($url->host->name))
      {
        array_push(
          self::$_error,
          _('Invalid magnet address tracker host name')
        );

        return false;
      }

      if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
      {
        array_push(
          self::$_error,
          sprintf(
            _('Magnet address tracker format does not match condition "%s"'),
            YGGDRASIL_HOST_REGEX
          )
        );

        return false;
      }

      $total++;
    }

    if ($total < MAGNET_TR_MIN_QUANTITY ||
        $total > MAGNET_TR_MAX_QUANTITY)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet address trackers quantity out of %s-%s range'),
          MAGNET_TR_MIN_QUANTITY,
          MAGNET_TR_MAX_QUANTITY
        )
      );

      return false;
    }

    return true;
  }

  public static function magnetAs(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet acceptable source data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $as)
    {
      if (!$url = Yggverse\Parser\Url::parse($as))
      {
        array_push(
          self::$_error,
          _('Invalid magnet acceptable source URL')
        );

        return false;
      }

      if (empty($url->host->name))
      {
        array_push(
          self::$_error,
          _('Invalid magnet acceptable source host name')
        );

        return false;
      }

      if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
      {
        array_push(
          self::$_error,
          sprintf(
            _('Magnet acceptable source format does not match condition "%s"'),
            YGGDRASIL_HOST_REGEX
          )
        );

        return false;
      }

      $total++;
    }

    if ($total < MAGNET_AS_MIN_QUANTITY ||
        $total > MAGNET_AS_MAX_QUANTITY)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet acceptable sources quantity out of %s-%s range'),
          MAGNET_AS_MIN_QUANTITY,
          MAGNET_AS_MAX_QUANTITY
        )
      );

      return false;
    }

    return true;
  }

  public static function magnetWs(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet web seed data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $ws)
    {
      if (!$url = Yggverse\Parser\Url::parse($ws))
      {
        array_push(
          self::$_error,
          _('Invalid magnet web seed URL')
        );

        return false;
      }

      if (empty($url->host->name))
      {
        array_push(
          self::$_error,
          _('Invalid magnet web seed host name')
        );

        return false;
      }

      if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
      {
        array_push(
          self::$_error,
          sprintf(
            _('Magnet web seed format does not match condition "%s"'),
            YGGDRASIL_HOST_REGEX
          )
        );

        return false;
      }

      $total++;
    }

    if ($total < MAGNET_WS_MIN_QUANTITY ||
        $total > MAGNET_WS_MAX_QUANTITY)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet web seeds quantity out of %s-%s range'),
          MAGNET_WS_MIN_QUANTITY,
          MAGNET_WS_MAX_QUANTITY
        )
      );

      return false;
    }

    return true;
  }

  // Magnet comment
  public static function magnetComment(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet comment data type')
      );

      return false;
    }

    if (!isset($value->magnetCommentId)       || !self::magnetCommentId($value->magnetCommentId)   ||
        !isset($value->magnetCommentIdParent) || !self::magnetCommentIdParent($value->value)       ||
        !isset($value->magnetId)              || !self::magnetId($value->magnetId)                 ||
        !isset($value->userId)                || !self::userId($value->userId)                     ||
        !isset($value->timeAdded)             || !self::magnetCommentTimeAdded($value->timeAdded)  ||
        !isset($value->approved)              || !self::magnetCommentApproved($value->approved)    ||
        !isset($value->value)                 || !self::magnetCommentValue($value->value)          ||

        (isset($value->public)                && !self::magnetCommentPublic($value->public)))
    {
      array_push(
        self::$_error,
        _('Invalid magnet comment data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentId(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnetCommentId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentIdParent(mixed $value)
  {
    if (!(is_bool($value) || is_int($value)))
    {
      array_push(
        self::$_error,
        _('Invalid magnet magnetCommentIdParent data type')
      );

      return false;
    }

    if (!self::magnetCommentId($value))
    {
      return false;
    }

    return true;
  }

  public static function magnetCommentTimeAdded(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet comment timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        self::$_error,
        _('Magnet comment timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentApproved(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet comment approved data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentPublic(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet comment public data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentValue(mixed $value)
  {
    if (!is_string($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet comment value data type')
      );

      return false;
    }

    if (mb_strlen($value) < MAGNET_COMMENT_MIN_LENGTH ||
        mb_strlen($value) > MAGNET_COMMENT_MAX_LENGTH)
    {
      array_push(
        self::$_error,
        sprintf(
          _('Magnet comment value out of %s-%s chars range'),
          MAGNET_COMMENT_MIN_LENGTH,
          MAGNET_COMMENT_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  // Magnet download
  public static function magnetDownload(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet download data type')
      );

      return false;
    }

    if (!isset($value->magnetDownloadId) || !self::magnetDownloadId($value->magnetDownloadId) ||
        !isset($value->magnetId)         || !self::magnetId($value->magnetId) ||
        !isset($value->userId)           || !self::userId($value->userId) ||
        !isset($value->timeAdded)        || !self::magnetDownloadTimeAdded($value->timeAdded)
      )
    {
      array_push(
        self::$_error,
        _('Invalid magnet download data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetDownloadId(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnetDownloadId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetDownloadTimeAdded(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet download timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        self::$_error,
        _('Magnet download timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  // Magnet star
  public static function magnetStar(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet download data type')
      );

      return false;
    }

    if (!isset($value->magnetStarId) || !self::magnetViewId($value->magnetStarId)     ||
        !isset($value->magnetId)     || !self::magnetId($value->magnetId)             ||
        !isset($value->userId)       || !self::userId($value->userId)                 ||
        !isset($value->timeAdded)    || !self::magnetStarTimeAdded($value->timeAdded) ||
        !isset($value->value)        || !self::magnetStarValue($value->value)
      )
    {
      array_push(
        self::$_error,
        _('Invalid magnet star data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetStarId(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnetStarId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetStarValue(mixed $value)
  {
    if (!is_bool($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet star value data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetStarTimeAdded(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet star timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        self::$_error,
        _('Magnet star timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  // Magnet view
  public static function magnetView(mixed $value)
  {
    if (!is_object($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet download data type')
      );

      return false;
    }

    if (!isset($value->magnetViewId) || !self::magnetViewId($value->magnetViewId)     ||
        !isset($value->magnetId)     || !self::magnetId($value->magnetId)             ||
        !isset($value->userId)       || !self::userId($value->userId)                 ||
        !isset($value->timeAdded)    || !self::magnetViewTimeAdded($value->timeAdded)
      )
    {
      array_push(
        self::$_error,
        _('Invalid magnet view data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetViewId(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnetViewId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetViewTimeAdded(mixed $value)
  {
    if (!is_int($value))
    {
      array_push(
        self::$_error,
        _('Invalid magnet view timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        self::$_error,
        _('Magnet view timeAdded out of range')
      );

      return false;
    }

    return true;
  }
}