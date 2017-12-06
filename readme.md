# Translation for Laravel 5.5

## Features
* 以CSV格式的語言包來進行翻譯

## Installation

透過composer require取得此套件.
```sh
$ composer require multt/translation
```

增加TranslationServiceProvider到config/app.php的providers陣列.
```php
Multt\Translation\TranslationServiceProvider::class,
```

## Configuration
設定檔為config/multt_translation.php, 複製到你專案的config目錄, 並修改之.

```php
return [

    /*
     * |--------------------------------------------------------------------------
     * | Language File Location
     * |--------------------------------------------------------------------------
     * |
     * |
     */
    'files' => 'packages/xplova/webadmin/resources/admin/lang',

    /*
     * |--------------------------------------------------------------------------
     * | Locale value stored in Session
     * |--------------------------------------------------------------------------
     * |
     * |
     */
    'locale_session' => [
        'enable' => true,
        'key' => 'multt.translation.locale'
    ]
];
```

* `files` : 語言包的目錄. 在此目錄內, 須有各語系對應到每一個子目錄. 每一語系子目錄內可有多個語言檔, 檔名為$module$_$tag$.csv, 如xyzcamp_home.csv.
* `locale_session`: 是否儲存locale於session.

## Usage
### 語系載入
```php
// $locale == en_US, zh_TW...
\Multt\Translation\Facades\MulttTranslator::load($locale);
```

### 執行翻譯(in Blade)
```php
@mtrans("home", "Join Xplova Now");
```

### 執行 Command: csvToExcel
* 該 command 會把蒐集放在files路徑下的各國的csv檔案至一份Excel表格中。
```php
php artisan csvToExcel
```

### 執行 Command: phpToExcel
* 該 command 會把蒐集放在files路徑下的各國的php檔案至一份Excel表格中。
```php
php artisan phpToExcel
```

### 執行 Command: excelToData {name} {--format=}'
* 該 command 會把files路徑下的Excel匯出指定的檔名和格式至files資料夾。
* `{name}` : 匯出的檔名、 `{--format=}` : 匯出的格式(目前有csv/php)。
```php
php artisan excelToData xyzcamp --format=csv
```
* 上例:匯出xyzcamp_XXX.csv 到 files 資料夾下。