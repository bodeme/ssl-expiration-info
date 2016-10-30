<?php
/**
 * Created by PhpStorm.
 * User: mbo
 * Date: 30.10.16
 * Time: 09:14
 */

namespace bodeme\ssl;


class ExpirationInfo {

  /**
   * @var \DateTime
   */
  private $validFrom;

  /**
   * @var \DateTime
   */
  private $validTo;

  /**
   * @var int
   */
  private $threshold = 7;

  const STATE_VALID = 'valid';
  const STATE_EXPIRING = 'expiring';
  const STATE_EXPIRED = 'expired';

  /**
   * @param \DateTime $validFrom
   * @param \DateTime $validTo
   */
  function __construct($validFrom, $validTo) {
    $this->validFrom = $validFrom;
    $this->validTo = $validTo;
  }

  /**
   * @return bool
   */
  public function isValid() {
    $now = new \DateTime();

    return $this->getValidFrom() <= $now && $now < $this->getValidTo();
  }

  public function getInfo() {
    if($this->isValid()) {
      if($this->getDaysUntilExpiration() <= $this->getThreshold()) {
        return self::STATE_EXPIRING;
      } else {
        return self::STATE_VALID;
      }
    } else {
      return self::STATE_EXPIRED;
    }
  }

  /**
   * @return int
   */
  public function getDaysUntilExpiration() {
    $now = new \DateTime();

    $diff = $now->diff($this->getValidTo());

    return (int) $diff->format('%R%a');
  }

  /**
   * @return bool|\DateInterval
   */
  public function getExpirationInterval() {
    $now = new \DateTime();

    return $now->diff($this->getValidTo());
  }


  /**
   * @return \DateTime
   */
  public function getValidFrom() {
    return $this->validFrom;
  }

  /**
   * @param \DateTime $validFrom
   */
  public function setValidFrom($validFrom) {
    $this->validFrom = $validFrom;
  }

  /**
   * @return \DateTime
   */
  public function getValidTo() {
    return $this->validTo;
  }

  /**
   * @param \DateTime $validTo
   */
  public function setValidTo($validTo) {
    $this->validTo = $validTo;
  }

  /**
   * @return int
   */
  public function getThreshold() {
    return $this->threshold;
  }

  /**
   * @param int $threshold
   */
  public function setThreshold($threshold) {
    $this->threshold = $threshold;
  }


}