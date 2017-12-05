<?php
namespace Multt\Translation\Console\Commands;

use PHPExcel;
use PHPExcel_IOFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CsvToExcel extends Command {
	protected $signature = 'csvToExcel';
	protected $description = 'Create All sites to excel file';
	/* 目的:從各語系資料夾收集各個CSV的key,value整合成一份xlsx
	 輸入: ex:xplova_all.csv裡面的'logout','logout',........
	 輸出: ex: in the xlsx catagory key en_US cn zh_TW .....
	 實作步驟:
	 line30 A部分:
	 xlsx init(頁籤，寬度)，再來寫入共通部分，最後再寫入各資料夾名
	 A1=Categories,B1=key,C1=en_US,.....

	 line47 B部分:
	 讀取lang下的所有csv檔案後，利用readCsvKeys function可得該category下所有KEY和重複KEY，
	 同時也利用array的map feature去過濾重複key，同時收集key以便寫入xlsx

	 line72 C部分:
	 利用各資料夾CSV的vlaue,透過__sort_array function,
	 先利用array_pad()產生特定大小該欄的空資料，比對上步驟蒐集的key和這次讀取的key,value,
	 若有同key則寫入value，沒有同KEY就跳過。

	 輸出: EX:$folders[0]=array('all'=>array('logout','null',.....),'foot'=>array(language,English,繁中,簡中),...)
	 $folders[1]=array('all'=>array('登出','送出',.....),'foot'=>array(語系,English,繁中,簡中),...)
	 *其中$folders[0]的0指folder順序。

	 line84 D部分:
	 計算excel 資料位置寫入剛蒐集的key,value array。 */
	public function handle() {
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getActiveSheet()->setTitle('Simple');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel_index=$objPHPExcel->setActiveSheetIndex(0);
		// set IO writer for excel
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

		// set directories control which column / all_file = Union all of events
		$translation_path=base_path(config('multt_translation.files'));
		$all_directories = File::directories($translation_path);
		$all_files = File::allFiles($translation_path);

		// $results=data , $collect_keys= all keys from each category
		$result_duplicated_key = $folders  = $csv_keys = array();
		// init $column_count=A , $row_count=1 for fill excel data
		$column_count = $objPHPExcel_index->getHighestColumn();
		$row_count =$objPHPExcel_index->getHighestRow();
		//A. fill up first row about Categories,key,'US'....,'0':Categories,key,de_DE;'1':dk;'2':en_US
		foreach ($all_directories as $key => $all_director) {
			$column = $objPHPExcel_index->getHighestColumn();
			$objPHPExcel_index->getColumnDimension(chr(65 + $key))->setWidth(20);
			$category=explode(".", basename($all_director))[0];
			if ($key === 0) {
				$objPHPExcel_index->setCellValue($column . '1', 'Categories');
				$objPHPExcel_index->setCellValue(++ $column . '1', 'key');
				$objPHPExcel_index->setCellValue(++ $column . '1',	$category);
			} else {
				$objPHPExcel_index->getColumnDimension(chr(65 + $key + 1))->setWidth(20);
				$objPHPExcel_index->getColumnDimension(chr(65 + $key + 2))->setWidth(20);
				$objPHPExcel_index->setCellValue(++ $column . '1',$category);
			}
		}
		$objWriter->save($translation_path . '\allToExcel.xlsx');

		//B. collect all datas' key from each category
		//output EX:$csv_keys[all]=array('logout','submit','search'),
		// 			$csv_keys[foot]=array(''Language','English','TraditionalChinese','SimplifiedChinese')
		foreach ($all_files as $result => $all_file) {
			if (explode(".", basename($all_file))[1] === 'csv') {
				$category_key = explode("_", explode(".", basename($all_file))[0])[1];
				// $keys[0]=collect datas' key;$keys[1]=collect duplicated keys
				$keys = $this->readCsvKeys($all_file, ',');
				//Ex:csv_keys[all]=array('log out')
				// check $csv_keys[all] is exist? and add $csv_keys[all] data first.
				if(!isset($csv_keys[$category_key]))
					$csv_keys[$category_key] = $keys[0];
					else{
						//append other data to same category
						foreach($keys[0] as $data){
							$csv_keys[$category_key] += array($data=>$data);
						}
					}
					$this->info('Collect key ' . $category_key . ' success.') . "\n";
					if (! empty($keys[1])) {
						$result_duplicated_key[explode(".", $all_file)[0]] = $keys[1];
					}
			}
		}

		//C. collect folder's number,categories,data. ex: $folders[0][all]= array(0=>'logout',1=>'',2=>'',3=>'',.....);
		foreach ($all_directories as $key_file => $all_directories_file) {
			$files = File::allFiles($all_directories_file);
			foreach ($files as $file) {
				if (explode(".", basename($file))[1] === 'csv') {
					$category = explode("_", explode(".", basename($file))[0])[1];
					$folders[$key_file][$category] = $this->__sort_array($this->readCsv($file, ','), $csv_keys,
						$category);
				}
			}
		}

		//D. main function
		foreach ($csv_keys as $key => $collect_key) { // $collect_keys[$category][$key]
			$row_count = $objPHPExcel_index->getHighestRow() + 1; // get row number
			$data_count = 0; // Distinguish folder
			$category = $key;
			foreach ($collect_key as $key_elemnt => $collect_key_element) {
				$column_count = chr(65); // init to first column 'A'
				$objPHPExcel_index->setCellValue($column_count ++ . $row_count, $key); // 'A1'
				$objPHPExcel_index->setCellValue($column_count ++ . $row_count, $key_elemnt); // 'B1'
				for ($folder_count = 0; $folder_count < count($folders); $folder_count ++) { // fill up data or null
					if (isset($folders[$folder_count][$category][$data_count])) { // 'C1','D1'....
						$objPHPExcel_index->setCellValue($column_count ++ . $row_count,
							$folders[$folder_count][$category][$data_count]);
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
		//display which file has duplicated key.
		if (! empty($result_duplicated_key)) {
			foreach ($result_duplicated_key as $key => $duplicated_key_element) {
				echo '**ERROR duplicated keys** File path-> ' . $key;
				foreach ($duplicated_key_element as $duplicated_key_data) {
					echo ' => ' . $duplicated_key_data;
				}
				echo "\n";
				echo "\n";
			}
		}
	}

	// it is routine data about category,key
	private function writeKeyToExcel($objPHPExcel, $categories, $results, $num_rows) {
		foreach ($results as $key => $result) {
			$row_number = 1;
			if ($categories === $key) {
				foreach ($result as $key_elemnt => $result_element) {
					$column = chr(65);
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue($column . ($row_number + $num_rows), $categories);
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue(++ $column . ($row_number + $num_rows),
						$result_element);
					$row_number ++;
				}
			}
		}
	}
	// get results to fill excel
	private function writeDataToExcel($objPHPExcel, $results, $row_count, $column_count, $num_rows) {
		foreach ($results as $key => $result) {
			$column = chr(65 + $column_count + 1);
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue(++ $column . ($key + 1 + $num_rows), $result);
		}
	}

	private function readCsvKeys($filename = '', $delimiter = ',') {
		if (! file_exists($filename) || ! is_readable($filename))
			return false;
			$datas = $result = $duplicated_key = array();
			if (($handle = fopen($filename, 'r')) !== false) {
				while (($row = $this->__fgetCsvKeys($handle, 1000, $delimiter)) !== false) {
					// collect $duplicated_key at first time,and check it at second time
					if (! isset($duplicated_key[$row])) {
						$duplicated_key[$row] = $row;
						$datas[$row] = $row;
					} else {
						if (in_array($row, $duplicated_key)) {
							$result[$row] = $duplicated_key[$row];
						}
					}
				}
				fclose($handle);
			}
			return array(
				$datas,
				$result
			);
	}
	//source:http://j796160836.pixnet.net/blog/post/28477041-%5Bphp%5Dutf-8%E7%9A%84fgetcsv%E5%87%BD%E6%95%B8
	private function __fgetCsvKeys(&$handle, $length = null, $d = ",", $e = '"') {
		$d = preg_quote($d);
		$e = preg_quote($e);
		$_line = "";
		$eof = false;
		while ($eof != true) {
			$_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
			$itemcnt = preg_match_all('/' . $e . '/', $_line, $dummy);
			if ($itemcnt % 2 == 0) {
				$eof = true;
			}
		}

		$_csv_line = preg_replace('/(?: |[ ])?$/', $d, trim($_line));
		$_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d .
		'/';
		preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
		$_csv_data = $_csv_matches[1];

		if (count($_csv_data) > 2) {
			for ($i = 2; $i < count($_csv_data); $i ++) {
				$_csv_data[1] = $_csv_data[1] . $_csv_data[$i];
			}
		}

		for ($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i ++) {
			$_csv_data[$_csv_i] = trim($_csv_data[$_csv_i]);
			$_csv_data[$_csv_i] = preg_replace("/^" . $e . "(.*)" . $e . "$/s", "$1", $_csv_data[$_csv_i]);
			$_csv_data[$_csv_i] = str_replace($e . $e, $e, $_csv_data[$_csv_i]);
		}

		$result = array(); // combine 2 -> 1
		return empty($_line) ? false : $_csv_data[0];
	}

	// open csv get data save to array
	private function readCsv($filename = '', $delimiter = ',') {
		if (! file_exists($filename) || ! is_readable($filename))
			return false;
			$datas = array();
			if (($handle = fopen($filename, 'r')) !== false) {
				while (($row = $this->__fgetcsv($handle, 1000, $delimiter)) !== false) {
					$datas[$row[0]] = $row; // use map feature to save lastest value
				}
				fclose($handle);
			}
			return $datas;
	}

	// overide fgetcsv because fgetcsv cant distinguish chinese.
	private function __fgetcsv(&$handle, $length = null, $d = ",", $e = '"') {
		$d = preg_quote($d);
		$e = preg_quote($e);
		$_line = "";
		$eof = false;
		while ($eof != true) {
			$_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
			$itemcnt = preg_match_all('/' . $e . '/', $_line, $dummy);
			if ($itemcnt % 2 == 0) {
				$eof = true;
			}
		}
		$_csv_line = preg_replace('/(?: |[ ])?$/', $d, trim($_line));
		$_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d .
		'/';
		preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
		$_csv_data = $_csv_matches[1];

		if (count($_csv_data) > 2) {
			for ($i = 2; $i < count($_csv_data); $i ++) {
				$_csv_data[1] = $_csv_data[1] . $_csv_data[$i];
			}
		}
		for ($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i ++) {
			$_csv_data[$_csv_i] = trim($_csv_data[$_csv_i]);
			$_csv_data[$_csv_i] = preg_replace("/^" . $e . "(.*)" . $e . "$/s", "$1", $_csv_data[$_csv_i]);
			$_csv_data[$_csv_i] = str_replace($e . $e, $e, $_csv_data[$_csv_i]);
		}
		// $result = array(); // combine 2 -> 1
		return empty($_line) ? false : $_csv_data;
	}

	// create a empty array and compare collect_keys, fill up data include null row
	//input:$datas=array('logout','logout'),
	//$collect_keys=array(all=>array('logout'=>'logout','submit'=>'submit','edit'=>'edit',..),foot=>array('language'=>'language',..)),
	//$category=all
	//output:$sorted_results=array(0=>'logout',1=>'submit',2=>edit...). final excel data;
	private function __sort_array($datas, $csv_keys, $category) {
		foreach ($csv_keys as $key => $collect_key) {
			//check if the $category== $key, create array fill up null and dimension=all folders number
			if ($category === $key) {
				$sorted_results = array_pad(array(), count($collect_key), null);
				$data_count = 0; // remember which data is missing
				if ($key === $category) {
					foreach ($collect_key as $key_element => $collect_element) {
						$data_count ++;
						//according to datas' content to fill up each column excel data
						foreach ($datas as $key_data => $data) {
							if ($key_element == $data[0]) {
								$sorted_results[$data_count - 1] = $data[1];
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
