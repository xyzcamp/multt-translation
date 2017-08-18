<?php
namespace Multt\Translation;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class TranslationServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router $router)
    {
        // Blade標籤 for i18n
        Blade::directive('xtranslate', function ($expression) {
            return '<?php echo \MulttTranslator::translate' . $expression . ' ?>';
        });
        Blade::directive('mtrans', function ($expression) {
            return '<?php echo \MulttTranslator::translate' . $expression . ' ?>';
        });
        
        // goto multt/translation folder, execute 'php ../../../artisan vendor:publish --tag=translation'
        $this->publishes([
            __DIR__ . '/../config/translation.php' => config_path('translation.php')
        ], 'translation');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('multt.translation.translator', function ($app) {
            return new Translator();
        });
        
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('MulttTranslator', \Multt\Translation\Facades\MulttTranslator::class);
    }
}
