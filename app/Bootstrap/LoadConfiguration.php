<?php

namespace App\Bootstrap;

use Illuminate\Foundation\Bootstrap\LoadConfiguration as BaseLoadConfiguration;
use Symfony\Component\Finder\Finder;

/**
 * Laravel merges vendor/laravel/framework/config/*.php as base defaults before
 * app config files. The framework database.php still reads PDO::MYSQL_ATTR_SSL_CA,
 * which is deprecated on PHP 8.5+. This app publishes a complete config/database.php,
 * so skip requiring the framework copy and keep merging the other defaults.
 */
class LoadConfiguration extends BaseLoadConfiguration
{
    /**
     * Get the base configuration files.
     *
     * @return array
     */
    protected function getBaseConfiguration()
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(base_path('vendor/laravel/framework/config')) as $file) {
            $name = basename($file->getRealPath(), '.php');

            if ($name === 'database') {
                continue;
            }

            $config[$name] = require $file->getRealPath();
        }

        return $config;
    }
}
