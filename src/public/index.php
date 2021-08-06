<?php
include_once __DIR__.'/../public/Tickets.php';
$res = new Tickets();
$res->getData();
var_dump($res);

