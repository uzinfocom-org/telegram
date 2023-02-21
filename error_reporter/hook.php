<?php

include 'config.php';
include 'functions.php';

$last_error_check = "";
$last_ping_send = "";
$last_ping_send_time = time();

while (true) {
	run();
	sleep(1);
}