<?php
namespace Multt\Translation;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
        Blade::directive('xtranslate',
            function ($expression) {
                return '<?php echo \MulttTranslator::translate(' . $expression . ') ?>';
            });
        Blade::directive('mtrans',
            function ($expression) {
                return '<?php echo \MulttTranslator::translate(' . $expression . ') ?>';
            });

        // goto multt/translation folder, execute 'php ../../../artisan vendor:publish --tag=multt_translation'
        $this->publishes(
            [
                __DIR__ . '/../config/multt_translation.php' => config_path('multt_translation.php')
            ], 'multt_translation');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('multt.translation.translator',
            function ($app) {
                return new Translator();
            });

        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('MulttTranslator', \Multt\Translation\Facades\MulttTranslator::class);

        $this->commands([
        	Console\Commands\CsvToExcel::class,
        	Console\Commands\PhpToExcel::class,
    		Console\Commands\ExcelToData::class,
        ]);
    }
}
