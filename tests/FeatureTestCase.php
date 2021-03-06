<?php

namespace Laravel\Telescope\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Queue\Queue;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        // Prevent "Use of undefined constant LARAVEL_START" exception
        if(!defined('LARAVEL_START'))
            define('LARAVEL_START', microtime(true));

        TestResponse::macro('terminateTelescope', [$this, 'terminateTelescope']);

        Telescope::flushEntries();
    }

    protected function tearDown()
    {
        Telescope::flushEntries();

        Queue::createPayloadUsing(null);

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            TelescopeServiceProvider::class
        ];
    }

    protected function resolveApplicationCore($app)
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(function () {
            return 'self-testing';
        });
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('logging.default', 'errorlog');

        $config->set('database.default', 'testbench');

        $config->set('telescope.storage.database.connection', 'testbench');

        $config->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give('testbench');
    }

    protected function loadTelescopeEntries()
    {
        $this->terminateTelescope();

        return EntryModel::all();
    }

    public function terminateTelescope()
    {
        Telescope::store(app(EntriesRepository::class));
    }
}
