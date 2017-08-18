<?php
namespace Multt\Translation;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class Translator
{

    private $_data = array();

    private $_is_load = false;

    private $_locale;

    private $_translation = array();

    private $locale_session_enable = false;

    private $locale_session_key = 'multt.translation.locale';

    public function __construct()
    {
        $locale_session_enable = config('translation.locale_session.enable');
        $locale_session_key = config('translation.locale_session.key');
        
        if ($locale_session_enable == true && $locale_session_key !== null)
            $this->locale_session_enable = true;
        else
            $this->locale_session_enable = false;
    }

    /**
     *
     * @param string $locale
     *            en_US, zh_TW ...
     */
    public function setlocale($locale)
    {
        $this->_locale = $locale;
        
        if ($this->locale_session_enable) {
            Session::put($this->locale_session_key, $locale);
        }
    }

    public function getlocale()
    {
        $locale = $this->_locale;
        return $locale;
    }

    /**
     * 載入語言包
     *
     * @param string $_locale
     *            en_US, zh_TW ...
     */
    public function load($locale)
    {
        if ($this->locale_session_enable) {
            $session_locale = Session::get($this->locale_session_key, false);
            if ($session_locale !== false) {
                $locale = $session_locale;
            }
        }
        $this->setlocale($locale);
        
        if (! $this->_is_load) {
            // 讀取 lang/$locale/*.csv ($locale=zh_TW, en_US...)
            $langDir = base_path(config('translation.files')) . DIRECTORY_SEPARATOR . $this->getlocale() . DIRECTORY_SEPARATOR;
            $handle = opendir($langDir);
            while (false !== ($file = readdir($handle))) {
                if (self::endsWith($file, 'csv')) {
                    
                    // *.csv檔名必須為xxx_yyy.csv, yyy為locale category, xxx為locale module(目前無用)
                    $_a = explode('.', $file)[0];
                    $_b = explode('_', $_a)[1];
                    $row = 1;
                    if (($csv_handle = fopen($langDir . $file, "r")) !== FALSE) {
                        while (($data = fgetcsv($csv_handle, 1000, ",")) !== FALSE) {
                            $this->_data[$this->getlocale()][$_b][$data[0]] = $data;
                        }
                        fclose($csv_handle);
                    }
                }
            }
            
            closedir($handle);
            $this->_is_load = true;
        }
    }

    /**
     * 翻譯
     * $translation->translate("home", "Join Xplova Now")
     */
    function translate($tag = null, $key, $locale = null)
    {
        if (! isset($tag) || $tag == '') {
            return $tag;
        }
        
        if ($locale == null) {
            $locale = $this->getlocale();
        }
        
        $value = $key;
        
        if (isset($this->_data[$locale]) && isset($this->_data[$locale][$tag]) && isset($this->_data[$locale][$tag][$key]) && $this->_data[$locale][$tag][$key][1]) {
            $value = $this->_data[$locale][$tag][$key][1];
        } else {
            // find all
            if (isset($this->_data[$locale]) && isset($this->_data[$locale]['all']) && isset($this->_data[$locale]['all'][$key]) && $this->_data[$locale]['all'][$key][1]) {
                $value = $this->_data[$locale]['all'][$key][1];
            }
        }
        
        $this->_translation[$locale][$tag][$key] = $value;
        
        return $value;
    }

    /**
     * 將一語系的內容, 全部轉成JSON, 可作為javascript翻譯包
     *
     * @param array $locales
     *            ['en_US', 'zh_TW', ...]
     */
    public function exportToJson($locales = [])
    {
        if (sizeof($locales) == 0) {
            echo json_encode($this->_data);
            return;
        }
        
        $rtn = [];
        foreach ($locales as $key => $locale) {
            if (isset($this->_data[$locale])) {
                $rtn[$locale] = $this->_data[$locale];
            }
        }
        echo json_encode($rtn);
        return;
    }

    /**
     * for debug
     */
    public function dumpTranslation($locale)
    {
        $dump = '';
        foreach ($this->_translation as $locale => $tag_keys) {
            $valus = '';
            foreach ($tag_keys as $tag => $keys) {
                foreach ($keys as $key => $val) {
                    if ($key) {
                        $valus .= '<li>
									<ul>
										<li>[' . $tag . '] - [' . $key . ']</li>
										<li>[' . $tag . '] - [' . $val . ']</li>
									</ul>
								</li>';
                    }
                }
            }
            if ($valus) {
                $valus = '<ul>' . $valus . '</ul>';
            }
            $dump .= '<div class="dump_trans"></p><ul>
							<li>' . $locale . '</li>
							<li>' . $valus . '</li>
						</ul></div>';
        }
        echo $dump;
    }

    private function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        
        return (substr($haystack, - $length) === $needle);
    }
}
