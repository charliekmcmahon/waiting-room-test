<?php

header('Content-Type: text/plain; charset=utf-8');

$queue = explode("\n", file_get_contents('status/queue')); //Läs in köfilen
$queue_pos = array_search($_COOKIE['queue'], $queue, true); //2=Aktuell spelare, 3=nästkommande spelare ..., false=Inte i kön
if($queue_pos != 2) { echo 'Ej först i kön!'; exit; } //Hantera inte kommandon från fel spelare!

$cmd = $_SERVER['PATH_INFO'];
$robot_ip = file_get_contents('server/robot_ip');


switch($cmd) {
	//case 'kommandonamn': kod som utför kommandot; echo svar; break;
	//case 'kommandonamn': kod som utför kommandot; echo svar; break;
	
	
	default: echo 'okänt_kommando ', $cmd, "\n";
}
	














