<?php

class Filter
{
  public static function magnetTitle(mixed $value) : string
  {
    $value = trim(
      strip_tags(
        html_entity_decode($value)
      )
    );

    return (string) $value;
  }

  public static function magnetPreview(mixed $value) : string
  {
    $value = trim(
      strip_tags(
        html_entity_decode($value)
      )
    );

    return (string) $value;
  }

  public static function magnetDescription(mixed $value) : string
  {
    $value = trim(
      strip_tags(
        html_entity_decode($value)
      )
    );

    return (string) $value;
  }

  public static function magnetDn(mixed $value) : string
  {
    $value = trim(
      strip_tags(
        html_entity_decode($value)
      )
    );

    return (string) $value;
  }
}