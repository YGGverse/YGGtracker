<?php

class Valid
{
  // Common
  public static function host(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid host data type')
      );

      return false;
    }

    if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $value)))
    {
      array_push(
        $error,
        sprintf(
          _('Host "%s" not match condition "%s"'),
          $value,
          YGGDRASIL_HOST_REGEX
        )
      );

      return false;
    }

    return true;
  }

  public static function url(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid URL data type')
      );

      return false;
    }

    if (!$url = Yggverse\Parser\Url::parse($value))
    {
      array_push(
        $error,
        sprintf(
          _('URL "%s" invalid'),
          $value
        )
      );

      return false;
    }

    if (empty($url->host->name))
    {
      array_push(
        $error,
        sprintf(
          _('Could not init host name for URL "%s"'),
          $value
        )
      );

      return false;
    }

    if (!self::host($url->host->name, $error))
    {
      array_push(
        $error,
        sprintf(
          _('URL host "%s" not supported'),
          $value,
          $url->host->name
        )
      );

      return false;
    }

    return true;
  }

  // User
  public static function user(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid user data type')
      );

      return false;
    }

    // Validate required fields
    if (!isset($value->userId)      || !self::userId($value->userId, $error)               ||
        !isset($value->address)     || !self::userAddress($value->address, $error)         ||
        !isset($value->timeAdded)   || !self::userTimeAdded($value->timeAdded, $error)     ||
        !isset($value->timeUpdated) || !self::userTimeUpdated($value->timeUpdated, $error) ||
        !isset($value->approved)    || !self::userApproved($value->approved, $error)       ||

        (isset($value->public)      && !self::userPublic($value->public, $error)))
    {
      array_push(
        $error,
        _('Invalid user data protocol')
      );

      return false;
    }

    return true;
  }

  public static function userId(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid userId data type')
      );

      return false;
    }

    return true;
  }

  public static function userAddress(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid user address data type')
      );

      return false;
    }

    if (!self::host($value, $error))
    {
      array_push(
        $error,
        sprintf(
          _('User address "%s" not supported'),
          $value
        )
      );

      return false;
    }

    return true;
  }

  public static function userTimeAdded(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid user timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        $error,
        _('User timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  public static function userTimeUpdated(mixed $value, array &$error = []) : bool
  {
    if (!(is_int($value) || is_bool($value)))
    {
      array_push(
        $error,
        _('Invalid user timeUpdated data type')
      );

      return false;
    }

    if (is_int($value) && ($value > time() || $value < 0))
    {
      array_push(
        $error,
        _('User timeUpdated out of range')
      );

      return false;
    }

    return true;
  }

  public static function userApproved(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid user approved data type')
      );

      return false;
    }

    return true;
  }

  public static function userPublic(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid user public data type')
      );

      return false;
    }

    return true;
  }

  // Magnet
  public static function magnet(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet data type')
      );

      return false;
    }

    // Validate required fields by protocol
    if (!isset($value->userId)      || !self::userId($value->userId, $error)                   ||

        !isset($value->magnetId)    || !self::magnetId($value->magnetId, $error)               ||


        !isset($value->title)       || !self::magnetTitle($value->title, $error)               ||
        !isset($value->preview)     || !self::magnetPreview($value->preview, $error)           ||
        !isset($value->description) || !self::magnetDescription($value->description, $error)   ||

        !isset($value->comments)    || !self::magnetComments($value->comments, $error)         ||
        !isset($value->sensitive)   || !self::magnetSensitive($value->sensitive, $error)       ||
        !isset($value->approved)    || !self::magnetApproved($value->approved, $error)         ||

        !isset($value->timeAdded)   || !self::magnetTimeAdded($value->timeAdded, $error)       ||
        !isset($value->timeUpdated) || !self::magnetTimeUpdated($value->timeUpdated, $error)   ||

        !isset($value->dn)          || !self::magnetDn($value->dn, $error)                     ||
        !isset($value->xt)          || !self::magnetXt($value->xt, $error)                     ||

        !isset($value->xl)          || !self::magnetXl($value->xl, $error)                     ||

        !isset($value->kt)          || !self::magnetKt($value->kt, $error)                     ||
        !isset($value->tr)          || !self::magnetTr($value->tr, $error)                     ||
        !isset($value->as)          || !self::magnetAs($value->as, $error)                     ||
        !isset($value->xs)          || !self::magnetWs($value->xs, $error)                     ||

        (isset($value->public)      && !self::magnetPublic($value->public, $error)))
    {
      array_push(
        $error,
        _('Invalid magnet data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetId(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnetId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetTitle(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid magnet title data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_TITLE_REGEX, $value))
    {
      array_push(
        $error,
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
        $error,
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

  public static function magnetPreview(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid magnet preview data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_PREVIEW_REGEX, $value))
    {
      array_push(
        $error,
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
        $error,
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

  public static function magnetDescription(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid magnet description data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_DESCRIPTION_REGEX, $value))
    {
      array_push(
        $error,
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
        $error,
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

  public static function magnetComments(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid magnet comments data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetPublic(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid magnet public data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetApproved(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid magnet approved data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetSensitive(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid magnet sensitive data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetTimeAdded(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnet timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        $error,
        _('Magnet timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  public static function magnetTimeUpdated(mixed $value, array &$error = []) : bool
  {
    if (!(is_int($value) || is_bool($value)))
    {
      array_push(
        $error,
        _('Invalid magnet timeUpdated data type')
      );

      return false;
    }

    if (is_int($value) && ($value > time() || $value < 0))
    {
      array_push(
        $error,
        _('Magnet timeUpdated out of range')
      );

      return false;
    }

    return true;
  }

  public static function magnetDn(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid magnet display name data type')
      );

      return false;
    }

    if (!preg_match(MAGNET_DN_REGEX, $value))
    {
      array_push(
        $error,
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
        $error,
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

  public static function magnetXl(mixed $value, array &$error = []) : bool
  {
    if (!(is_int($value) || is_float($value)))
    {
      array_push(
        $error,
        _('Invalid magnet exact length data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetKt(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
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
          $error,
          _('Invalid magnet keyword value data type')
        );

        return false;
      }

      if (!preg_match(MAGNET_KT_REGEX, $value))
      {
        array_push(
          $error,
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
          $error,
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
        $error,
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

  public static function magnetXt(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet info hash data type')
      );

      return false;
    }

    foreach ($value as $xt)
    {
      if (empty($xt->version))
      {
        array_push(
          $error,
          _('Magnet info hash version required')
        );

        return false;
      }

      if (!(is_int($xt->version) || is_float($xt->version)))
      {
        array_push(
          $error,
          _('Invalid magnet info hash version data type')
        );

        return false;
      }

      if (empty($xt->value))
      {
        array_push(
          $error,
          _('Magnet info hash value required')
        );

        return false;
      }

      if (!is_string($xt->value))
      {
        array_push(
          $error,
          _('Invalid magnet info hash value data type')
        );

        return false;
      }

      switch ($xt->version)
      {
        case 1:

          if (!preg_match('/^([A-z0-9]{40})$/i', $xt->value))
          {
            array_push(
              $error,
              _('Invalid magnet info hash v1 value')
            );

            return false;
          }

        break;

        case 2:

          if (!preg_match('/^([A-z0-9]{64})$/i', $xt->value))
          {
            array_push(
              $error,
              _('Invalid magnet info hash v2 value')
            );

            return false;
          }

        break;

        default:

          array_push(
            $error,
            _('Magnet info hash version not supported')
          );

          return false;
      }
    }

    return true;
  }

  public static function magnetTr(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet address tracker data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $tr)
    {
      if (!self::url($tr, $error))
      {
        array_push(
          $error,
          sprintf(
            _('Invalid magnet address tracker URL "%s"'),
            $tr
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
        $error,
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

  public static function magnetAs(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet acceptable source data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $as)
    {
      if (!self::url($as, $error))
      {
        array_push(
          $error,
          sprintf(
            _('Invalid magnet acceptable source URL "%s"'),
            $as
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
        $error,
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

  public static function magnetWs(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet web seed data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $ws)
    {
      if (!self::url($ws, $error))
      {
        array_push(
          $error,
          sprintf(
            _('Invalid magnet web seed URL "%s"'),
            $ws
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
        $error,
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
  public static function magnetComment(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet comment data type')
      );

      return false;
    }

    if (!isset($value->magnetCommentId)       || !self::magnetCommentId($value->magnetCommentId, $error)   ||
        !isset($value->magnetCommentIdParent) || !self::magnetCommentIdParent($value->value, $error)       ||
        !isset($value->magnetId)              || !self::magnetId($value->magnetId, $error)                 ||
        !isset($value->userId)                || !self::userId($value->userId, $error)                     ||
        !isset($value->timeAdded)             || !self::magnetCommentTimeAdded($value->timeAdded, $error)  ||
        !isset($value->approved)              || !self::magnetCommentApproved($value->approved, $error)    ||
        !isset($value->value)                 || !self::magnetCommentValue($value->value, $error)          ||

        (isset($value->public)                && !self::magnetCommentPublic($value->public, $error)))
    {
      array_push(
        $error,
        _('Invalid magnet comment data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentId(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnetCommentId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentIdParent(mixed $value, array &$error = []) : bool
  {
    if (!(is_bool($value) || is_int($value)))
    {
      array_push(
        $error,
        _('Invalid magnet magnetCommentIdParent data type')
      );

      return false;
    }

    if (!self::magnetCommentId($value, $error))
    {
      return false;
    }

    return true;
  }

  public static function magnetCommentTimeAdded(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnet comment timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        $error,
        _('Magnet comment timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentApproved(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid magnet comment approved data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentPublic(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid magnet comment public data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetCommentValue(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid magnet comment value data type')
      );

      return false;
    }

    if (mb_strlen($value) < MAGNET_COMMENT_MIN_LENGTH ||
        mb_strlen($value) > MAGNET_COMMENT_MAX_LENGTH)
    {
      array_push(
        $error,
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
  public static function magnetDownload(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet download data type')
      );

      return false;
    }

    if (!isset($value->magnetDownloadId) || !self::magnetDownloadId($value->magnetDownloadId, $error) ||
        !isset($value->magnetId)         || !self::magnetId($value->magnetId, $error) ||
        !isset($value->userId)           || !self::userId($value->userId, $error) ||
        !isset($value->timeAdded)        || !self::magnetDownloadTimeAdded($value->timeAdded, $error)
      )
    {
      array_push(
        $error,
        _('Invalid magnet download data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetDownloadId(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnetDownloadId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetDownloadTimeAdded(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnet download timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        $error,
        _('Magnet download timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  // Magnet star
  public static function magnetStar(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet download data type')
      );

      return false;
    }

    if (!isset($value->magnetStarId) || !self::magnetViewId($value->magnetStarId, $error)     ||
        !isset($value->magnetId)     || !self::magnetId($value->magnetId, $error)             ||
        !isset($value->userId)       || !self::userId($value->userId, $error)                 ||
        !isset($value->timeAdded)    || !self::magnetStarTimeAdded($value->timeAdded, $error) ||
        !isset($value->value)        || !self::magnetStarValue($value->value, $error)
      )
    {
      array_push(
        $error,
        _('Invalid magnet star data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetStarId(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnetStarId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetStarValue(mixed $value, array &$error = []) : bool
  {
    if (!is_bool($value))
    {
      array_push(
        $error,
        _('Invalid magnet star value data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetStarTimeAdded(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnet star timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        $error,
        _('Magnet star timeAdded out of range')
      );

      return false;
    }

    return true;
  }

  // Magnet view
  public static function magnetView(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet download data type')
      );

      return false;
    }

    if (!isset($value->magnetViewId) || !self::magnetViewId($value->magnetViewId, $error)     ||
        !isset($value->magnetId)     || !self::magnetId($value->magnetId, $error)             ||
        !isset($value->userId)       || !self::userId($value->userId, $error)                 ||
        !isset($value->timeAdded)    || !self::magnetViewTimeAdded($value->timeAdded, $error)
      )
    {
      array_push(
        $error,
        _('Invalid magnet view data protocol')
      );

      return false;
    }

    return true;
  }

  public static function magnetViewId(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnetViewId data type')
      );

      return false;
    }

    return true;
  }

  public static function magnetViewTimeAdded(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid magnet view timeAdded data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        $error,
        _('Magnet view timeAdded out of range')
      );

      return false;
    }

    return true;
  }
}