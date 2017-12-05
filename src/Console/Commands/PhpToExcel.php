<?php
namespace Multt\Translation\Console\Commands;

use PHPExcel;
use PHPExcel_IOFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PhpToExcel extends Command {
	protected $signature = 'phpToExcel';
	protected $description = 'Collect lang php to one excel file';

	public function handle() {
		$translation_path=base_path(config('multt_translation.files'));
		
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getActiveSheet()->setTitle('Simple');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel_index = $objPHPExcel->setActiveSheetIndex(0);
		// set IO writer for excel
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		
		// set directories control which column / all_file = Union all of events
		$all_directories = File::directories($translation_path);
		$all_files = File::allFiles($translation_path);
		// $results=data , $collect_keys= all keys from each category
		$folders = array();
		// init $column_count=A , $row_count=1 for fill excel data
		$column_count = $objPHPExcel_index->getHighestColumn();
		$row_count = $objPHPExcel_index->getHighestRow();
		// A. fill up first row about Categories,key,'US'....,'0':Categories,key,de_DE;'1':dk;'2':en_US
		foreach ($all_directories as $key => $all_director) {
			$column = $objPHPExcel_index->getHighestColumn();
			$objPHPExcel_index->getColumnDimension(chr(65 + $key))->setWidth(30);
			$category = explode(".", basename($all_director))[0];
			if ($key === 0) {
				$objPHPExcel_index->setCellValue($column . '1', 'Categories');
				$objPHPExcel_index->setCellValue(++ $column . '1', 'key');
				$objPHPExcel_index->setCellValue(++ $column . '1', $category);
			} else {
				$objPHPExcel_index->getColumnDimension(chr(65 + $key + 1))->setWidth(30);
				$objPHPExcel_index->getColumnDimension(chr(65 + $key + 2))->setWidth(30);
				$objPHPExcel_index->setCellValue(++ $column . '1', $category);
			}
		}
		$objWriter->save($translation_path . '\allToExcel.xlsx');
		
		// B. collect all datas' key from each category
		// output EX:$csv_keys[all]=array('logout','submit','search'),
		// $csv_keys[foot]=array(''Language','English','TraditionalChinese','SimplifiedChinese')
		foreach ($all_files as $result => $all_file) {
			if (explode(".", basename($all_file))[1] === 'php') {
			$category_key = explode("_", explode(".", basename($all_file))[0])[1];
				// $keys[0]=collect datas' key;$keys[1]=collect duplicated keys
				$keys[$category_key] = $this->_readPhpKeys(basename($all_file), $all_directories);
				$this->info('Collect key ' . $category_key . ' success.') . "\n";
			}
		}
		
		// C. collect folder's number,categories,data. ex: $folders[0][all]= array(0=>'logout',1=>'',2=>'',3=>'',.....);
		foreach ($all_directories as $key_file => $all_directories_file) {
			$files = File::allFiles($all_directories_file);
			foreach ($files as $file) {
				if (explode(".", basename($file))[1] === 'php') {
				$category =  explode("_", explode(".", basename($file))[0])[1];
					$folders[$key_file][$category] = $this->__sort_array(require($file->getPathname()), $keys, $category);
				}
			}
		}
		
 		// D. main function
		foreach ($keys as $key => $collect_key) { // $collect_keys[$category][$key]
			$row_count = $objPHPExcel_index->getHighestRow() + 1; // get row number
			$data_count = 0; // Distinguish folder
			$category = $key;
			foreach ($collect_key as $key_elemnt => $collect_key_element) {
				$column_count = chr(65); // init to first column 'A'
				$objPHPExcel_index->setCellValue($column_count ++ . $row_count, $key); // 'A1'
				$objPHPExcel_index->setCellValue($column_count ++ . $row_count, $key_elemnt); // 'B1'
				for ($folder_count = 0; $folder_count < count($folders); $folder_count ++) { // fill up data or null
					if (isset($folders[$folder_count][$category][$data_count])) { // 'C1','D1'....
						$objPHPExcel_index->setCellValue($column_count ++ . $row_count, $folders[$folder_count][$category][$data_count]);
					} else {
						$objPHPExcel_index->setCellValue($column_count ++ . $row_count, '');
					}
				}
				$row_count ++;
				$data_count ++;
				echo 'Collect Data Categories:' . $key . ',Key:' . $key_elemnt . ' To Excel success.' . "\n";
			}
			// write to excel
			$objWriter->save($translation_path . '\allToExcel.xlsx');
		}
	}

	private function _readPhpKeys($category, $all_directories) {
		foreach ($all_directories as $all_directory) {
		if(file_exists($all_directory.'/'.$category)){
			$datas=require($all_directory.'/'.$category);
				if (! isset($result)) {
					$result = $datas;
				} else {
					$result = $result + $datas;
				}
			}
		}
		return $result;
	}
	
	// create a empty array and compare collect_keys, fill up data include null row
	// input:$datas=array('logout','logout'),
	// $collect_keys=array(all=>array('logout'=>'logout','submit'=>'submit','edit'=>'edit',..),foot=>array('language'=>'language',..)),
	// $category=all
	// output:$sorted_results=array(0=>'logout',1=>'submit',2=>edit...). final excel data;
	private function __sort_array($file, $keys, $category) {
		foreach ($keys as $key => $collect_key) {
			// check if the $category== $key, create array fill up null and dimension=all folders number
			if ($category === $key) {
				$sorted_results = array_pad(array(), count($collect_key), null);
				$data_count = 0; // remember which data is missing
				if ($key === $category) {
					foreach ($collect_key as $key_element => $collect_element) {
						$data_count ++;
						// according to datas' content to fill up each column excel data
						foreach ($file as $key_data => $data) {
							if ($key_element === $key_data) {
								$sorted_results[$data_count - 1] = $data;
								break;
							}
						}
					}
				}
				return $sorted_results;
			}
		}
	}
}
