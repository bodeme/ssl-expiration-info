<?php
/**
 * Created by PhpStorm.
 * User: mbo
 * Date: 30.10.16
 * Time: 08:55
 */

namespace bodeme\ssl;


class Host {

  /**
   * @var string
   */
  private $name;

  /**
   * @var int
   */
  private $port;

  /**
   * @param string $name
   * @param int $port
   */
  function __construct($name, $port = 443) {
    $this->name = $name;
    $this->port = $port;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return int
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * @param int $port
   */
  public function setPort($port) {
    $this->port = $port;
  }


}