<?php

class Mailer
{
	public function __construct($host, $username, $password) { }

	public function send($to, $message) {
		echo "\033[35mMessage delivered to $to:\n\n\033[31m$message\n";
	}
}
