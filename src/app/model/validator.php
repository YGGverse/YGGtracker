<?php

class AppModelValidator
{
  private $_config;

  public function __construct(object $config)
  {
    // @TODO validate config

    $this->_config = $config;
  }

  // Page

  /// Page title
  public function getPageTitleLengthMin() : int
  {
    return $this->_config->page->title->length->min;
  }

  public function getPageTitleLengthMax() : int
  {
    return $this->_config->page->title->length->max;
  }

  public function getPageTitleRegex() : string
  {
    return $this->_config->page->title->regex;
  }

  /// Page description
  public function getPageDescriptionLengthMin() : int
  {
    return $this->_config->page->description->length->min;
  }

  public function getPageDescriptionLengthMax() : int
  {
    return $this->_config->page->description->length->max;
  }

  public function getPageDescriptionRegex() : string
  {
    return $this->_config->page->description->regex;
  }

  /// Page keywords
  public function getPageKeywordsLengthMin() : int
  {
    return $this->_config->page->keywords->length->min;
  }

  public function getPageKeywordsLengthMax() : int
  {
    return $this->_config->page->keywords->length->max;
  }

  public function getPageKeywordsQuantityMin() : int
  {
    return $this->_config->page->keywords->quantity->min;
  }

  public function getPageKeywordsQuantityMax() : int
  {
    return $this->_config->page->keywords->quantity->max;
  }

  public function getPageKeywordsRegex() : string
  {
    return $this->_config->page->keywords->regex;
  }

  // Common
  public function host(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid host data type')
      );

      return false;
    }

    if (!filter_var(str_replace(['[',']'], false, $value), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
    {
      array_push(
        $error,
        sprintf(
          _('Host of "%s" not supported'),
          $value
        )
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

  public function url(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid URL data type')
      );

      return false;
    }

    if (!filter_var($value, FILTER_VALIDATE_URL))
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

    if (!$host = parse_url($value, PHP_URL_HOST))
    {
      array_push(
        $error,
        sprintf(
          _('Could not init host for URL "%s"'),
          $value
        )
      );

      return false;
    }

    if (!self::host($host, $error))
    {
      array_push(
        $error,
        sprintf(
          _('URL "%s" has not supported host "%s"'),
          $value,
          $host,
        )
      );

      return false;
    }

    return true;
  }

  // User
  public function user(mixed $value, array &$error = []) : bool
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

  public function userId(mixed $value, array &$error = []) : bool
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

  public function userAddress(mixed $value, array &$error = []) : bool
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

  public function userTimeAdded(mixed $value, array &$error = []) : bool
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

  public function userTimeUpdated(mixed $value, array &$error = []) : bool
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

  public function userApproved(mixed $value, array &$error = []) : bool
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

  public function userPublic(mixed $value, array &$error = []) : bool
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
  public function magnet(mixed $value, array &$error = []) : bool
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

  public function magnetId(mixed $value, array &$error = []) : bool
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

  public function magnetTitle(mixed $value, array &$error = []) : bool
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

  public function magnetPreview(mixed $value, array &$error = []) : bool
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

  public function magnetDescription(mixed $value, array &$error = []) : bool
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

  public function magnetComments(mixed $value, array &$error = []) : bool
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

  public function magnetPublic(mixed $value, array &$error = []) : bool
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

  public function magnetApproved(mixed $value, array &$error = []) : bool
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

  public function magnetSensitive(mixed $value, array &$error = []) : bool
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

  public function magnetTimeAdded(mixed $value, array &$error = []) : bool
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

  public function magnetTimeUpdated(mixed $value, array &$error = []) : bool
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

  public function magnetDn(mixed $value, array &$error = []) : bool
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

  public function magnetXl(mixed $value, array &$error = []) : bool
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

  public function magnetKt(mixed $value, array &$error = []) : bool
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
      if (!is_string($kt))
      {
        array_push(
          $error,
          _('Invalid magnet keyword value data type')
        );

        return false;
      }

      if (!preg_match(MAGNET_KT_REGEX, $kt))
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

      if (mb_strlen($kt) < MAGNET_KT_MIN_LENGTH ||
          mb_strlen($kt) > MAGNET_KT_MAX_LENGTH)
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

  public function magnetXt(mixed $value, array &$error = []) : bool
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

  public function magnetTr(mixed $value, array &$error = []) : bool
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

  public function magnetAs(mixed $value, array &$error = []) : bool
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

  public function magnetWs(mixed $value, array &$error = []) : bool
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
  public function magnetComment(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet comment data type')
      );

      return false;
    }

    if (!isset($value->magnetCommentId)       || !self::magnetCommentId($value->magnetCommentId, $error)                   ||
        !isset($value->magnetId)              || !self::magnetId($value->magnetId, $error)                                 ||
        !isset($value->userId)                || !self::userId($value->userId, $error)                                     ||
        !isset($value->timeAdded)             || !self::magnetCommentTimeAdded($value->timeAdded, $error)                  ||
        !isset($value->approved)              || !self::magnetCommentApproved($value->approved, $error)                    ||
        !isset($value->value)                 || !self::magnetCommentValue($value->value, $error)                          ||

        (isset($value->magnetCommentIdParent) && !self::magnetCommentIdParent($value->magnetCommentIdParent, $error))      ||

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

  public function magnetCommentId(mixed $value, array &$error = []) : bool
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

  public function magnetCommentIdParent(mixed $value, array &$error = []) : bool
  {
    if (!(is_null($value) || is_int($value)))
    {
      array_push(
        $error,
        _('Invalid magnet magnetCommentIdParent data type')
      );

      return false;
    }

    if (is_int($value) && !self::magnetCommentId($value, $error))
    {
      return false;
    }

    return true;
  }

  public function magnetCommentTimeAdded(mixed $value, array &$error = []) : bool
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

  public function magnetCommentApproved(mixed $value, array &$error = []) : bool
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

  public function magnetCommentPublic(mixed $value, array &$error = []) : bool
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

  public function magnetCommentValue(mixed $value, array &$error = []) : bool
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
  public function magnetDownload(mixed $value, array &$error = []) : bool
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

  public function magnetDownloadId(mixed $value, array &$error = []) : bool
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

  public function magnetDownloadTimeAdded(mixed $value, array &$error = []) : bool
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
  public function magnetStar(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet star data type')
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

  public function magnetStarId(mixed $value, array &$error = []) : bool
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

  public function magnetStarValue(mixed $value, array &$error = []) : bool
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

  public function magnetStarTimeAdded(mixed $value, array &$error = []) : bool
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
  public function magnetView(mixed $value, array &$error = []) : bool
  {
    if (!is_object($value))
    {
      array_push(
        $error,
        _('Invalid magnet view data type')
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

  public function magnetViewId(mixed $value, array &$error = []) : bool
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

  public function magnetViewTimeAdded(mixed $value, array &$error = []) : bool
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

  // Torrent
  public function torrentAnnounce(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid torrent announce data type')
      );

      return false;
    }

    if (!self::url($tr, $error))
    {
      array_push(
        $error,
        sprintf(
          _('Invalid torrent announce URL "%s"'),
          $tr
        )
      );

      return false;
    }

    return true;
  }

  public function torrentAnnounceList(mixed $value, array &$error = []) : bool
  {
    if (!is_array($value))
    {
      array_push(
        $error,
        _('Invalid torrent announce data type')
      );

      return false;
    }

    $total = 0;

    foreach ($value as $list)
    {
      if (!is_array($list))
      {
        array_push(
          $error,
          _('Invalid torrent announce list')
        );

        return false;
      }

      foreach ($list as $announce)
      {
        if (!self::torrentAnnounce($announce, $error))
        {
          array_push(
            $error,
            sprintf(
              _('Invalid torrent announce list URL "%s"'),
              $announce
            )
          );

          return false;
        }

        $total++;
      }
    }

    if ($total < TORRENT_ANNOUNCE_MIN_QUANTITY ||
        $total > TORRENT_ANNOUNCE_MAX_QUANTITY)
    {
      array_push(
        $error,
        sprintf(
          _('Torrent announces quantity out of %s-%s range'),
          TORRENT_ANNOUNCE_MIN_QUANTITY,
          TORRENT_ANNOUNCE_MAX_QUANTITY
        )
      );

      return false;
    }

    return true;
  }

  public function torrentComment(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid torrent comment data type')
      );

      return false;
    }

    if (!preg_match(TORRENT_COMMENT_REGEX, $value))
    {
      array_push(
        $error,
        sprintf(
          _('Torrent comment format does not match condition "%s"'),
          TORRENT_COMMENT_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < TORRENT_COMMENT_MIN_LENGTH ||
        mb_strlen($value) > TORRENT_COMMENT_MAX_LENGTH)
    {
      array_push(
        $error,
        sprintf(
          _('Torrent comment out of %s-%s chars range'),
          TORRENT_COMMENT_MIN_LENGTH,
          TORRENT_COMMENT_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  public function torrentCreatedBy(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid torrent created by data type')
      );

      return false;
    }

    if (!preg_match(TORRENT_CREATED_BY_REGEX, $value))
    {
      array_push(
        $error,
        sprintf(
          _('Torrent created by format does not match condition "%s"'),
          TORRENT_CREATED_BY_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < TORRENT_CREATED_BY_MIN_LENGTH ||
        mb_strlen($value) > TORRENT_CREATED_BY_MAX_LENGTH)
    {
      array_push(
        $error,
        sprintf(
          _('Torrent created by out of %s-%s chars range'),
          TORRENT_CREATED_BY_MIN_LENGTH,
          TORRENT_CREATED_BY_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  public function torrentCreationDate(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid torrent creation date data type')
      );

      return false;
    }

    if ($value > time() || $value < 0)
    {
      array_push(
        $error,
        _('Torrent creation date out of range')
      );

      return false;
    }

    return true;
  }

  public function torrentInfo(mixed $value, array &$error = []) : bool
  {
    if (!is_array($value))
    {
      array_push(
        $error,
        _('Invalid torrent info data type')
      );

      return false;
    }

    if (empty($value))
    {
      array_push(
        $error,
        _('Torrent info has no keys')
      );

      return false;
    }

    foreach ($value as $info)
    {
      if (!is_array($info))
      {
        array_push(
          $error,
          _('Invalid torrent info protocol')
        );

        return false;
      }

      if (empty($info))
      {
        array_push(
          $error,
          _('Torrent info has no values')
        );

        return false;
      }

      foreach ($info as $key => $data)
      {
        switch ($key)
        {
          case 'file-duration':

            if (!self::torrentInfoFileDuration($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info file-duration')
              );

              return false;
            }

          break;
          case 'file-media':

            if (!self::torrentInfoFileMedia($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info file-media')
              );

              return false;
            }

          break;
          case 'files':

            if (!self::torrentInfoFiles($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info files')
              );

              return false;
            }

          break;
          case 'name':

            if (!self::torrentInfoName($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info name')
              );

              return false;
            }

          break;
          case 'piece length':

            if (!self::torrentInfoPieceLength($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info piece length')
              );

              return false;
            }

          break;
          case 'pieces':

            if (!self::torrentInfoPieces($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info pieces')
              );

              return false;
            }

          break;
          case 'private':

            if (!self::torrentInfoPrivate($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info private')
              );

              return false;
            }

          break;
          case 'profiles':

            if (!self::torrentInfoProfiles($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info profiles')
              );

              return false;
            }

          break;
          case 'source':

            if (!self::torrentInfoSource($data, $error))
            {
              array_push(
                $error,
                _('Invalid torrent info source')
              );

              return false;
            }

          break;
          default:
            array_push(
              $error,
              _('Not supported torrent info key')
            );
        }
      }
    }

    return true;
  }

  public function torrentInfoName(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid torrent info name data type')
      );

      return false;
    }

    if (!preg_match(TORRENT_INFO_NAME_REGEX, $value))
    {
      array_push(
        $error,
        sprintf(
          _('Torrent info name format does not match condition "%s"'),
          TORRENT_INFO_NAME_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < TORRENT_INFO_NAME_MIN_LENGTH ||
        mb_strlen($value) > TORRENT_INFO_NAME_MAX_LENGTH)
    {
      array_push(
        $error,
        sprintf(
          _('Torrent info name out of %s-%s chars range'),
          TORRENT_INFO_NAME_MIN_LENGTH,
          TORRENT_INFO_NAME_MAX_LENGTH
        )
      );

      return false;
    }

    return true;
  }

  public function torrentInfoSource(mixed $value, array &$error = []) : bool
  {
    if (!is_string($value))
    {
      array_push(
        $error,
        _('Invalid torrent info source data type')
      );

      return false;
    }

    if (!preg_match(TORRENT_INFO_SOURCE_REGEX, $value))
    {
      array_push(
        $error,
        sprintf(
          _('Torrent info source format does not match condition "%s"'),
          TORRENT_INFO_SOURCE_REGEX
        )
      );

      return false;
    }

    if (mb_strlen($value) < TORRENT_INFO_SOURCE_MIN_LENGTH ||
        mb_strlen($value) > TORRENT_INFO_SOURCE_MAX_LENGTH)
    {
      array_push(
        $error,
        sprintf(
          _('Torrent info source out of %s-%s chars range'),
          TORRENT_INFO_SOURCE_MIN_LENGTH,
          TORRENT_INFO_SOURCE_MAX_LENGTH
        )
      );

      return false;
    }

    return true;

    return true;
  }

  public function torrentInfoFileDuration(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid torrent file-duration data type')
      );

      return false;
    }

    if ($value < 0)
    {
      array_push(
        $error,
        _('Torrent file-duration out of range')
      );

      return false;
    }

    return true;
  }

  public function torrentInfoPieceLength(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid torrent info piece length data type')
      );

      return false;
    }

    if ($value < 0)
    {
      array_push(
        $error,
        _('Torrent torrent info piece length out of range')
      );

      return false;
    }

    return true;
  }

  public function torrentInfoPieces(mixed $value, array &$error = []) : bool
  {
    // @TODO

    return true;
  }

  public function torrentInfoPrivate(mixed $value, array &$error = []) : bool
  {
    if (!is_int($value))
    {
      array_push(
        $error,
        _('Invalid torrent info private data type')
      );

      return false;
    }

    if (!in_array($value, [0, 1]))
    {
      array_push(
        $error,
        _('Invalid torrent info private value')
      );

      return false;
    }

    return true;
  }

  public function torrentInfoProfiles(mixed $value, array &$error = []) : bool
  {
    // @TODO

    return true;
  }

  public function torrentInfoFileMedia(mixed $value, array &$error = []) : bool
  {
    // @TODO

    return true;
  }

  public function torrentInfoFiles(mixed $value, array &$error = []) : bool
  {
    // @TODO

    return true;
  }
}