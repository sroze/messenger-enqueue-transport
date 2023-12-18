#!/usr/bin/env php
<?php

if (empty($argv[1])) {
    throw new \LogicException('Provide a Symfony version in Composer requirement format (e.g. "^7.0")');
}

$newVersion = $argv[1];

$composer = file_get_contents(__DIR__.'/../composer.json');

$updatedComposer = preg_replace('/"symfony\/(.*)": ".*"/', '"symfony/$1": "'.$newVersion.'"', $composer).PHP_EOL;
echo $updatedComposer.PHP_EOL;

file_put_contents(__DIR__.'/../composer.json', $updatedComposer);
