<?php
namespace Multt\Translation\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Multt\Translation\Facades\MulttTranslator;

trait TranslationTrait
{

    protected $parameter_locale = 'locale';

    function __construct()
    {
        $this->parameter_locale = Config::get('multt_translation.parameter.locale', $this->parameter_locale);
    }

    public function switchLocaleAction(Request $request)
    {
        $langCode = $request->input($this->parameter_locale);
        if (! empty($langCode)) {
            MulttTranslator::setLocale($langCode, true);
        }

        return response()->json($this->apiResponse());
    }
}
