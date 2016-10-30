<?php
namespace bodeme\ssl;

class Check {

  /** @var Host */
  private $host;

  /**
   * @var array
   */
  private $sslCertificate;

  /**
   * @var array
   */
  private $sslCertificateInfo;

  /**
   * @var int
   */
  private $timeout = 5;

  function __construct($host) {
    $this->host = $host;
  }

  /**
   * @return ExpirationInfo
   * @throws \Exception
   */
  public function getExpirationInfo() {
    if($this->getSslCertificate() === null) {
      $this->initialize();
    }

    $info = $this->getSslCertificateInfo();

    if(!isset($info['validFrom_time_t'])) {
      throw new \Exception('Missing validFrom in ssl-certificate.');
    }

    if(!isset($info['validTo_time_t'])) {
      throw new \Exception('Missing validTo in ssl-certificate.');
    }

    $validFrom = new \DateTime();
    $validFrom->setTimestamp($info['validFrom_time_t']);

    $validTo = new \DateTime();
    $validTo->setTimestamp($info['validTo_time_t']);

    $expirationInfo = new ExpirationInfo($validFrom, $validTo);

    return $expirationInfo;
  }

  /**
   * Initialize SSL Certificate
   */
  private function initialize() {
    $options = stream_context_create([
        'ssl' => ['capture_peer_cert' => true],
      ]
    );

    $host = $this->getHost();
    $errorNumber = null;
    $errorString = null;


    $context = stream_socket_client(
      'ssl://' . $host->getName() . ':' . $host->getPort(),
      $errorNumber,
      $errorString,
      $this->getTimeout(),
      STREAM_CLIENT_CONNECT,
      $options
    );

    if($errorNumber > 0) {
      throw new \Exception(sprintf('Error while opening socket to "%s:%d": %s', $host->getName(), $host->getPort(), $errorString));
    }

    if(!is_resource($context)) {
      throw new \Exception(sprintf('Error while reading ssl certificate from "%s:%d".', $host->getName(), $host->getPort()));
    }

    $certificate = stream_context_get_params($context);
    $this->setSslCertificate($certificate);

    if(!isset($certificate['options']) || !isset($certificate['options']['ssl']) || !isset($certificate['options']['ssl']['peer_certificate'])) {
      throw new \Exception(sprintf('Error while parsing ssl certificate from "%s:%d".', $host->getName(), $host->getPort()));
    }

    $this->setSslCertificateInfo(openssl_x509_parse($certificate['options']['ssl']['peer_certificate']));
  }

  /**
   * @return Host
   */
  private function getHost() {
    return $this->host;
  }


  /**
   * @return array
   */
  private function getSslCertificate() {
    return $this->sslCertificate;
  }

  /**
   * @param array $sslCertificate
   */
  private function setSslCertificate($sslCertificate) {
    $this->sslCertificate = $sslCertificate;
  }

  /**
   * @return array
   */
  public function getSslCertificateInfo() {
    return $this->sslCertificateInfo;
  }

  /**
   * @param array $sslCertificateInfo
   */
  private function setSslCertificateInfo($sslCertificateInfo) {
    $this->sslCertificateInfo = $sslCertificateInfo;
  }

  /**
   * @return int
   */
  public function getTimeout() {
    return $this->timeout;
  }

  /**
   * @param int $timeout
   */
  public function setTimeout($timeout) {
    $this->timeout = $timeout;
  }

}