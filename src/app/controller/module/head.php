<?php

class AppControllerModuleHead
{
  private $_title;
  private $_base;
  private $_links = [];

  public function __construct(string $base, string $title, array $links = [])
  {
    $this->setBase($base);
    $this->setTitle($title);

    foreach ($links as $link)
    {
      $this->addLink(
        $link['rel'],
        $link['type'],
        $link['href'],
      );
    }
  }

  public function setBase(string $base) : void
  {
    $this->_base = $base;
  }

  public function setTitle(string $title) : void
  {
    $this->_title = $title;
  }

  public function addLink(string $rel, string $type, string $href) : void
  {
    $this->_links[] = (object)
    [
      'rel'  => $rel,
      'type' => $type,
      'href' => $href,
    ];
  }

  public function render()
  {
    $base  = $this->_base;

    $links = $this->_links;

    $title = htmlentities($this->_title);

    include __DIR__ . '../../../view/theme/default/module/head.phtml';
  }
}