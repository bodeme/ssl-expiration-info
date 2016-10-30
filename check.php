#!/usr/bin/env php
<?php

use bodeme\ssl\Check;
use bodeme\ssl\Host;

define('BASE_PATH', dirname(__FILE__));
include(BASE_PATH . '/vendor/autoload.php');

$yamlFile = 'config.yml';
$thresholdWarning = 14;
$mailRecipients = [];
$hosts = [];

if(!is_readable($yamlFile)) {
  die('Cannot read yaml file "'.$yamlFile.'".' . PHP_EOL);
}

$yaml = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($yamlFile));

if(!is_array($yaml['hosts'])) {
  throw new \Exception('Missing hosts in yaml file.');
}

if(isset($yaml['mail'][0]['notification']) && is_array($yaml['mail'][0]['notification'])) {
  $mailRecipients =  $yaml['mail'][0]['notification'];
}

if(is_integer($yaml['ssl'][0]['threshold'])) {
  $thresholdWarning = $yaml['ssl'][0]['threshold'];
}

foreach($yaml['hosts'] as $host) {
  $host = trim($host);
  if(preg_match('/^([^ ]+) ([0-9]+)$/', $host, $matches)) {
    $hosts[] = new Host($matches[1], $matches[2]);
  }
}

$rows = [];
foreach($hosts as $host) {
  /** @var $host Host */
  try {
    $check = new Check($host);

    $info = $check->getExpirationInfo();
    $info->setThreshold($thresholdWarning);

    $state = $info->getInfo();

    $rows[] = sprintf(
      '%-40s %s (%d)',
      $host->getName() . ':' . $host->getPort(),
      $state,
      $info->getDaysUntilExpiration()
    );

  } catch(\Exception $e) {
    $rows[] = sprintf(
      '%-40s %s',
      $host->getName() . ':' . $host->getPort(),
      $e->getMessage()
    );
  }
}

sort($rows);
$message = implode(PHP_EOL, $rows);

if(sizeof($mailRecipients) == 0) {
  echo $message;
  echo PHP_EOL;
} else {
  foreach($mailRecipients as $mailRecipient) {
    $mailer = new PHPMailer();
    $mailer->setFrom($yaml['mail'][0]['from']);
    $mailer->addAddress($mailRecipient);
    $mailer->Subject = 'SSL Expiration Info';
    $mailer->Body = $message;
    $mailer->send();
  }
}

die(0);