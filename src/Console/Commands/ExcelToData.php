<?php
namespace Multt\Translation\Console\Commands;

use PHPExcel;
use PHPExcel_IOFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExcelToData extends Command {
	protected $signature = 'excelToData {name} {--format=}';
	protected $description = "Excel's data to specific format.EX: {name} --format=csv/php";

	public function handle() {
		$name = $this->argument('name');
		$format = $this->option('format');
		$translation_path=base_path(config('multt_translation.files'));
		//$str=$translation_path . '\\' . $key . '\\'.$name.'_'. $key_result . '_XXX.'."{$format}";
		
		$reader = PHPExcel_IOFactory::createReader('Excel2007'); // 讀取2007 excel 檔案
		$PHPExcel = $reader->load($translation_path . '\\'.'allToExcel.xlsx'); // 檔案名稱 需已經上傳到主機上
		$objWorksheet = $PHPExcel->getSheet(0); // 讀取第一個工作表(編號從 0 開始)
		
		$folders = $categories_ori = $results = array();
		// 建立一個空 array get all folder name, rows' data, categories' name & key & number
		foreach ($objWorksheet->getRowIterator() as $row_key => $row) { // 開始做 row 迴圈
			$cellIterator = $row->getCellIterator(); // 抓取這一行的 cell 資訊
			if ($row_key === 1) {
				$cell_count = 1;
				$cellIterator->setIterateOnlyExistingCells(false); // 讀入整行的cells,如果為空就回傳 null
				foreach ($cellIterator as $cell_key => $cell) { // 做 cell 迴圈
					if ($cell->getValue() !== 'Categories' and $cell->getValue() !== 'key') {
						$folders[$cell_count] = trim($cell->getValue());
						$cell_count ++;
					}
				}
			} else{
				foreach ($cellIterator as $cell) {
					if ($cell->getColumn() === 'A' and $cell->getCalculatedValue() === null) {
						break;
					}
					$categories_ori[$row->getRowIndex()][$cell->getColumn()] = trim($cell->getCalculatedValue());
				}
			}
		}
		
		// softed $results folder/categories/key, ex:en_US/all/log out
		foreach ($categories_ori as $key => $categories_element) {
			$categories_flag = null;
			$key_flag = null;
			foreach ($categories_element as $key_data => $categories_data) {
				if ($key_data === 'A') {
					$categories_flag = trim($categories_data);
				}
				if ($key_data === 'B') {
					$key_flag = trim($categories_data);
				}
				for ($number_count = 1; $number_count <= count($folders); $number_count ++) {
					//if $categories_data==='' , not export data
					if (trim($categories_data)!=='' && chr(66 + $number_count) === $key_data) {
						$results[$folders[$number_count]][$categories_flag][$key_flag] = trim($categories_data);
					}
				}
			}
		}
		
		//write out result to csv file
		foreach ($results as $key => $result) {
			foreach ($result as $key_result => $result_element) {
				if (! is_dir($translation_path . '\\' . $key)) {
					mkdir($translation_path . '\\' . $key, 0777, true);
				} // if the folder is empty then create it
				$ExcelFile = fopen($translation_path . '\\' . $key . '\\'.$name.'_'. $key_result .'.'."{$format}", "w");
				if("{$format}"==='php'){
					fputs($ExcelFile, '<?php'. PHP_EOL.'return ['. PHP_EOL);
				}
				foreach ($result_element as $key_data => $result_data) {
					if("{$format}"==='csv' || (strpos($key_data,'\'')||strpos($result_data,'\''))){
						$sorted_array = array('"' . $key_data . '"','"' . $result_data . '"');
					}else{
						$sorted_array = array('\'' . $key_data . '\'','\'' . $result_data . '\'');
					}
					$csv_result = "{$format}"==='php'?"\x20\x20\x20\x20".implode('=>', $sorted_array).',' .PHP_EOL:
																			implode(',', $sorted_array) . PHP_EOL;
					fputs($ExcelFile, $csv_result);
				}
				if("{$format}"==='php'){
					fputs($ExcelFile, '];');
				}
				fclose($ExcelFile);
				echo 'Write Data '. $key . '\\' . $key_result.' To '.$format.' success.'."\n";
			}
		}
		
	}
}
