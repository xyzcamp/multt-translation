<?php
namespace Multt\Translation;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class Translator
{

    // 當下的locale, 用以決定採用哪個語言的翻譯包
    private $_locale;

    // 特定語系的全部tag+key+value
    // $_data[$locale][$tag][$key] == 翻譯檔的一行(key=value)
    private $_data = array();

    // 紀錄已使用的翻譯字
    private $_translation = array();

    // 是否透過session保留locale
    private $locale_session_enable = false;

    // 保留locale的session key
    private $locale_session_key = 'multt.translation.locale';

    public function __construct()
    {
        $locale_session_enable = config('multt_translation.locale_session.enable');
        $locale_session_key = config('multt_translation.locale_session.key');

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
    public function setlocale($locale, $forceOverride = false)
    {
        if (! $forceOverride) {
            // 若啟用session, 則自session取得. 忽略傳入的locale
            if ($this->locale_session_enable) {
                $session_locale = Session::get($this->locale_session_key, false);
                if ($session_locale !== false) {
                    $this->_locale = $session_locale;
                    return;
                }
            }
        }

        $this->_locale = $locale;

        // 若啟用session, 則儲存之
        if ($this->locale_session_enable) {
            Session::put($this->locale_session_key, $locale);
        }
    }

    public function getlocale()
    {
        return $this->_locale;
    }

    /**
     * 載入語言包
     *
     * @param string $_locale
     *            en_US, zh_TW ...
     */
    public function load()
    {
        $_locale = $this->getlocale();

        // 讀取翻譯包 lang/$locale/*.csv ($locale=zh_TW, en_US...)
        // @TODO 考量做cache
        $langDir = base_path(config('multt_translation.files')) . DIRECTORY_SEPARATOR . $_locale . DIRECTORY_SEPARATOR;
        $handle = opendir($langDir);
        while (false !== ($file = readdir($handle))) {
            if (self::endsWith($file, 'csv')) {

                // *.csv檔名必須為xxx_yyy.csv, yyy為locale tag, xxx為locale module(目前無用)
                $_module = explode('.', $file)[0];
                $_tag = explode('_', $_module)[1];

                $row = 1;
                if (($csv_handle = fopen($langDir . $file, "r")) !== FALSE) {
                    while (($word = fgetcsv($csv_handle, 1000, ",")) !== FALSE) {
                        $_key = $word[0];
                        $this->_data[$_locale][$_tag][$_key] = $word;
                    }
                    fclose($csv_handle);
                }
            }
        }

        closedir($handle);
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

        // 值預設為Key
        $value = $key;

        if (isset($this->_data[$locale][$tag][$key]) && $this->_data[$locale][$tag][$key][1]) {
            $value = $this->_data[$locale][$tag][$key][1];
        } elseif (isset($this->_data[$locale]['all'][$key]) && $this->_data[$locale]['all'][$key][1]) {
            // 於指定tag找不到時, 找'all' tag內的key
            $value = $this->_data[$locale]['all'][$key][1];
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
