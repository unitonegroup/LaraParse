<?php

namespace UnitOneICT\LaraParse;

use Illuminate\Support\ServiceProvider;


class LaraParseServiceProvider extends ServiceProvider
{

	public function register()
    {
		
	}
	
	public function boot(){
		if (! $this->app->routesAreCached()) {
			require __DIR__.'/routes/routes.php';
		}
	}
}