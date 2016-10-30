#!/usr/bin/env php
<?php

use bodeme\ssl\Check;
use bodeme\ssl\ExpirationInfo;
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

if(isset($yaml['mail']['notification']) && is_array($yaml['mail']['notification'])) {
  $mailRecipients =  $yaml['mail']['notification'];
}

if(is_integer($yaml['ssl']['threshold'])) {
  $thresholdWarning = $yaml['ssl']['threshold'];
}

foreach($yaml['hosts'] as $host) {
  $host = trim($host);
  if(preg_match('/^([^ ]+) ([0-9]+)$/', $host, $matches)) {
    $hosts[] = new Host($matches[1], $matches[2]);
  }
}

$rows = [];
$rowsWarning = [];

foreach($hosts as $host) {
  /** @var $host Host */
  try {
    $check = new Check($host);

    $info = $check->getExpirationInfo();
    $info->setThreshold($thresholdWarning);

    $state = $info->getInfo();

    $row = sprintf(
      '%-40s %s (%d)',
      $host->getName() . ':' . $host->getPort(),
      $state,
      $info->getDaysUntilExpiration()
    );

    $rows[] = $row;
    if(in_array($state, [ExpirationInfo::STATE_EXPIRING, ExpirationInfo::STATE_EXPIRED])) {
      $rowsWarning[] = $row;
    }

  } catch(\Exception $e) {
    $rows[] = sprintf(
      '%-40s %s',
      $host->getName() . ':' . $host->getPort(),
      $e->getMessage()
    );
  }
}

sort($rows);

if(sizeof($mailRecipients) == 0) {
  echo implode(PHP_EOL, $rows);
  echo PHP_EOL;
} else {
  foreach($mailRecipients as $mailRecipient) {
    $mailer = new PHPMailer();
    $mailer->setFrom($yaml['mail'][0]['from']);
    $mailer->addAddress($mailRecipient);
    $mailer->Subject = 'SSL Expiration Info';
    $mailer->Body = implode(PHP_EOL, $rows);
    $mailer->send();

    $mailer = new PHPMailer();
    $mailer->setFrom($yaml['mail'][0]['from']);
    $mailer->addAddress($mailRecipient);
    $mailer->Subject = 'SSL Expiration Info WARNING';
    $mailer->Body = implode(PHP_EOL, $rowsWarning);
    $mailer->send();
  }
}

die(0);