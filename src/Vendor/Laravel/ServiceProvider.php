<?php namespace PragmaRX\Tracker\Vendor\Laravel;
 
use PragmaRX\Tracker\Tracker;

use PragmaRX\Tracker\Services\Authentication;

use PragmaRX\Tracker\Support\Config;
use PragmaRX\Tracker\Support\MobileDetect;
use PragmaRX\Tracker\Support\UserAgentParser;
use PragmaRX\Tracker\Support\FileSystem;

use PragmaRX\Tracker\Data\Repositories\Session;
use PragmaRX\Tracker\Data\Repositories\Access;
use PragmaRX\Tracker\Data\Repositories\Agent;
use PragmaRX\Tracker\Data\Repositories\Device;
use PragmaRX\Tracker\Data\Repositories\Cookie;

use PragmaRX\Tracker\Data\RepositoryManager;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Foundation\AliasLoader as IlluminateAliasLoader;

class ServiceProvider extends IlluminateServiceProvider {

    const PACKAGE_NAMESPACE = 'pragmarx/tracker';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package(self::PACKAGE_NAMESPACE, self::PACKAGE_NAMESPACE, __DIR__.'/../..');

        if( $this->app['config']->get(self::PACKAGE_NAMESPACE.'::create_tracker_alias') )
        {
            IlluminateAliasLoader::getInstance()->alias(
                                                            $this->getConfig('tracker_alias'),
                                                            'PragmaRX\Tracker\Vendor\Laravel\Facade'
                                                        );
        }

        $this->wakeUp();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {   
        // Unfortunately, we are stuck with PHP session, because
        // Laravel's Session ID changes every time user logs in.
        session_start(); 

        new UserAgentParser($this->app->make('path.base'));

        $this->registerConfig();

        $this->registerAuthentication();

        $this->registerRepositories();

        $this->registerTracker();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('tracker');
    }

    /**
     * Takes all the components of Tracker and glues them
     * together to create Tracker.
     *
     * @return void
     */
    private function registerTracker()
    {
        $this->app['tracker'] = $this->app->share(function($app)
        {
            $app['tracker.loaded'] = true;

            return new Tracker(
                                    $app['tracker.config'],
                                    $app['tracker.repositories'],
                                    $app['request']
                                );
        });
    }

    public function registerRepositories()
    {
        $this->app['tracker.repositories'] = $this->app->share(function($app)
        {
            $sessionModel = $this->getConfig('session_model');
            $accessModel = $this->getConfig('access_model');
            $agentModel = $this->getConfig('agent_model');
            $deviceModel = $this->getConfig('device_model');
            $cookieModel = $this->getConfig('cookie_model');

            return new RepositoryManager(
                                        new Session(new $sessionModel, 
                                                    $app['tracker.config'], 
                                                    $app['session.store']),

                                        new Access(new $accessModel),

                                        new Agent(new $agentModel),

                                        new Device(new $deviceModel),

                                        new Cookie(new $cookieModel,
                                                    $app['tracker.config'],
                                                    $app['request'],
                                                    $app['cookie']),

                                        new MobileDetect,

                                        new UserAgentParser($app->make('path.base')),

                                        $app['tracker.authentication'],

                                        $app['session.store'],

                                        $app['tracker.config']
                                    );
        });
    }

    public function registerAuthentication()
    {
        $this->app['tracker.authentication'] = $this->app->share(function($app)
        {
            return new Authentication($app['tracker.config'], $app);
        });
    }

    public function registerConfig()
    {
        $this->app['tracker.config'] = $this->app->share(function($app)
        {
            return new Config($app['config'], self::PACKAGE_NAMESPACE);
        });
    }

    private function wakeUp()
    {
        $this->app['tracker']->boot();
    }

    private function getConfig($key)
    {
        return $this->app['config']->get(self::PACKAGE_NAMESPACE.'::'.$key);
    }

}
