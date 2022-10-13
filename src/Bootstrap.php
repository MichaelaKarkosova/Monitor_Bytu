<?php

namespace App;

use App\DI\Container;
use SixtyEightPublishers\Environment\Bootstrap\EnvBootstrap;

final class Bootstrap {

	public static function boot(): Container {
		EnvBootstrap::boot([]);

		//vytvoříme container a inicializujeme všechny služby v aplikaci.
		// Data k databázi přečteme z .env souboru (viz .env.dist)
		return new Container([
			'db.host' => env("DB_HOST"),
			'db.user' => env("DB_USER"),
			'db.dbname' => env("DB_NAME"),
			'db.password' => env("DB_PASSWORD"),
			'discordWebhookUrl' => env("DISCORD_WEBHOOK_URL", ""),

		]);

	}

}