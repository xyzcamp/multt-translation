<?php

namespace Multt\Translation\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
       	Commands\CsvToExcel::class,
    	Commands\PhpToExcel::class,
    	Commands\ExcelToData::class,
    ];
}
