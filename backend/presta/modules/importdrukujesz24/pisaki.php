<?php
// require_once '../modules/'.$moduleName.'/lib/phpExcel/Classes/PHPExcel/IOFactory.php';
require_once './lib/phpExcel/Classes/PHPExcel/IOFactory.php';
$filename  = 'CENNIK 2015 FWI POLSKA DLA KLIENTÓW.xls';
	$objPHPExcel = PHPExcel_IOFactory::load($filename);
	$producer = null;
	$lines  = array();
	$linesIndex = 0;	
	foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
		$worksheetTitle     = $worksheet->getTitle();
		var_dump($worksheetTitle);
		$highestRow         = $worksheet->getHighestRow(); // e.g. 10		
		var_dump($highestRow);
		
		$highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
		var_dump($highestColumn);
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		var_dump($highestColumnIndex);
		

		$name = null;
		if($worksheetTitle == 'PARKER'){
//continue;			
			$state  =  0; 							
			for ($row = 2; $row <=  282; /*$highestRow;*/ ++ $row) {								
				
				$eanCell =  $worksheet->getCellByColumnAndRow(5, $row)->getValue();
				if( $state  ==  0 and  $eanCell  == 'EAN'){					
					$producer  = 'PARKER';
					 // ustalić producenta 	w nastepnej linii są dane 				 
					//$producer  = 	$worksheet->getCellByColumnAndRow(0, $row)->getValue();
					//$producer = preg_split('/[\s]+/' , $producer);
					//print_r($producer); //die;					 					 
					//$producer = $producer[0];
					$state = 1;								
					continue;											
				}				
				if($state == 1   and   preg_match( '/^(\d){13}/'  , $eanCell )  === 1 ){
					$line  = array();				
						$line['producer'] = $producer;
						$line['EAN'] = $eanCell;					
						$line['price'] = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
						$cat = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if(empty($cat)){
							$cat = $lines[$linesIndex-1]['category'] ; //  $worksheet->getCellByColumnAndRow(1, $row-1)->getValue();
						}
						$nrCell =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
						if(preg_match( '/^(\d)+/'  , $nrCell )  === 1  ){
						$name =$worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if($name  instanceof  PHPExcel_RichText ){
							$name = $name->getPlainText();							
						}
						}
						
						$line['category'] = $producer;
						$line['category'] = $cat;		
						$line['category'] = $producer;				
						$line['name'] = $name . ' ' .  $worksheet->getCellByColumnAndRow(2, $row)->getValue();
					/*for($column = 0 ; $column < 7 ; $column ++){
					
						$cell = $worksheet->getCellByColumnAndRow($column, $row);
						$r = $cell->getValue();
						$line[$column]  =  $r;
						//var_dump($cell);
					}	*/
					$lines[$linesIndex]  = $line;
					$linesIndex++;
					//echo print_r($line , true).'<br>';					
					
				}else{										
					if($state == 1) 
						$row--;
					$state  = 0;					
					continue;	
				}				 				 
			}			
			
			$name = null;
			$state  =  0; 	
			$category = null;						
			for ($row = 282; $row <=  317;  ++ $row) {		
//				continue;					
				$eanCell =  $worksheet->getCellByColumnAndRow(5, $row)->getValue();	
				//echo $eanCell. '<br>' ;			
				if( $state  ==  0 and  $eanCell  == 'EAN'){					
					$category =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
					$producer  = 'PARKER';
					$state = 1;								
					continue;											
				}				
				if($state == 1   and   preg_match( '/^(\d){13}/'  , $eanCell )  === 1 ){
					// linia towaru 
					$nrCell =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
					//echo $nrCell ; continue;
					if(preg_match( '/^(\d)+/'  , $nrCell )  === 1  ){
						$name =$worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if($name  instanceof  PHPExcel_RichText ){
							$name = $name->getPlainText();							
						}
					}
										
					$line  = array();				
						$line['producer'] = $producer;
						$line['EAN'] = $eanCell;					
						$line['price'] = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
						$cat = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if(empty($cat)){
							$cat = $lines[$linesIndex-1]['category'] ; //  $worksheet->getCellByColumnAndRow(1, $row-1)->getValue();
						}
						$line['category'] = $category;						
						$line['color'] = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
						$line['name'] = $name; // $worksheet->getCellByColumnAndRow(1, $row)->getValue();
					/*for($column = 0 ; $column < 7 ; $column ++){
					
						$cell = $worksheet->getCellByColumnAndRow($column, $row);
						$r = $cell->getValue();
						$line[$column]  =  $r;
						//var_dump($cell);
					}	*/
					$lines[$linesIndex]  = $line;
					$linesIndex++;
					//echo print_r($line , true).'<br>';					
					
				}else{					
					//$category = null;
					$state  = 0; 
					$row--;
					continue;	
				}				 				 				
				//echo $eanCell.'<br>';
			}
			
		}
		if($worksheetTitle == 'PARKER - pozostałe stalówki'){
//CONTINUE;			
			for ($row = 5; $row <=  85; /*$highestRow;*/ ++$row) {
			
				$eanCell =  $worksheet->getCellByColumnAndRow(2, $row)->getValue();
				$producer  = 'PARKER';
				if(preg_match( '/^(\d){13}/'  , $eanCell )  === 1 ){	
					$line  = array();						
					$line['producer'] = $producer;
					$line['EAN'] = $eanCell;					
					$line['price'] = $worksheet->getCellByColumnAndRow(5, $row)->getValue();						
					$line['category'] = $producer;								
					$line['name'] =  'WIETRZNE PIÓRO '. $worksheet->getCellByColumnAndRow(3, $row)->getValue();
					$lines[]  = $line;
					
				}
			
				
			}					
			//echo $worksheetTitle; die;
			
		}

		if($worksheetTitle == 'WATERMAN'){
//continue;							
			$state  =  0; 							
			for ($row = 7; $row <=  205; /*$highestRow;*/ ++ $row) {								
//continue;				
				$eanCell =  $worksheet->getCellByColumnAndRow(5, $row)->getValue();
				if( $state  ==  0 and  $eanCell  == 'EAN'){					
					$producer  = 'WATERMAN';
					 // ustalić producenta 	w nastepnej linii są dane 				 
					//$producer  = 	$worksheet->getCellByColumnAndRow(0, $row)->getValue();
					//$producer = preg_split('/[\s]+/' , $producer);
					//print_r($producer); //die;					 					 
					//$producer = $producer[0];
					$state = 1;								
					continue;											
				}				
				if($state == 1   and   preg_match( '/^(\d){13}/'  , $eanCell )  === 1 ){
					$line  = array();				
						$line['producer'] = $producer;
						$line['EAN'] = $eanCell;					
						$line['price'] = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
						$cat = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if(empty($cat)){
							$cat = $lines[$linesIndex-1]['category'] ; //  $worksheet->getCellByColumnAndRow(1, $row-1)->getValue();
						}
						$nrCell =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
						if(preg_match( '/^(\d)+/'  , $nrCell )  === 1  ){
						$name =$worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if($name  instanceof  PHPExcel_RichText ){
							$name = $name->getPlainText();							
						}
						}
						
						$line['category'] = $producer;
						$line['category'] = $cat;		
						$line['category'] = $producer;				
						$line['name'] = $name . ' ' .  $worksheet->getCellByColumnAndRow(2, $row)->getValue();
					/*for($column = 0 ; $column < 7 ; $column ++){
					
						$cell = $worksheet->getCellByColumnAndRow($column, $row);
						$r = $cell->getValue();
						$line[$column]  =  $r;
						//var_dump($cell);
					}	*/
					$lines[$linesIndex]  = $line;
					$linesIndex++;
					//echo print_r($line , true).'<br>';					
					
				}else{										
					if($state == 1) 
						$row--;
					$state  = 0;					
					continue;	
				}				 				 
			}			
			
			$name = null;
			$state  =  0; 	
			$category = null;						
			for ($row = 207; $row <=  238;  ++ $row) {		
//continue;					
				$eanCell =  $worksheet->getCellByColumnAndRow(5, $row)->getValue();	
				//echo $eanCell. '<br>' ;			
				if( $state  ==  0 and  $eanCell  == 'EAN'){					
					$category =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
					$producer  = 'WATERMAN';
					$state = 1;								
					continue;											
				}				
				if($state == 1   and   preg_match( '/^(\d){13}/'  , $eanCell )  === 1 ){
					// linia towaru 
					$nrCell =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
					//echo $nrCell ; continue;
					if(preg_match( '/^(\d)+/'  , $nrCell )  === 1  ){
						$name =$worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if($name  instanceof  PHPExcel_RichText ){
							$name = $name->getPlainText();							
						}
					}
										
					$line  = array();				
						$line['producer'] = $producer;
						$line['EAN'] = $eanCell;					
						$line['price'] = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
						$cat = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
						if(empty($cat)){
							$cat = $lines[$linesIndex-1]['category'] ; //  $worksheet->getCellByColumnAndRow(1, $row-1)->getValue();
						}
						$line['category'] = $category;						
						$line['color'] = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
						$line['name'] = $name; // $worksheet->getCellByColumnAndRow(1, $row)->getValue();
					/*for($column = 0 ; $column < 7 ; $column ++){
					
						$cell = $worksheet->getCellByColumnAndRow($column, $row);
						$r = $cell->getValue();
						$line[$column]  =  $r;
						//var_dump($cell);
					}	*/
					$lines[$linesIndex]  = $line;
					$linesIndex++;
					//echo print_r($line , true).'<br>';					
					
				}else{					
					//$category = null;					 
					if($state == 1)
						$row--;
					$state  = 0;
					continue;	
				}				 				 				
				//echo $eanCell.'<br>';
			}
			
		}

		
		if($worksheetTitle == 'WATERMAN - pozostałe stalówki'){
//CONTINUE;			
			for ($row = 5; $row <=  85; /*$highestRow;*/ ++$row) {
			
				$eanCell =  $worksheet->getCellByColumnAndRow(2, $row)->getValue();
				$producer  = 'WATERMAN';
				if(preg_match( '/^(\d){13}/'  , $eanCell )  === 1 ){	
					$line  = array();						
					$line['producer'] = $producer;
					$line['EAN'] = $eanCell;					
					$line['price'] = $worksheet->getCellByColumnAndRow(5, $row)->getValue();						
					$line['category'] = $producer;								
					$line['name'] =  'WIETRZNE PIÓRO '. $worksheet->getCellByColumnAndRow(3, $row)->getValue();
					$lines[]  = $line;
					
				}
			
				
			}					
			//echo $worksheetTitle; die;
			
		}
		
		if($worksheetTitle == 'Rotring techniczny'){
//CONTINUE;			
			for ($row = 5; $row <=  293; /*$highestRow;*/ ++$row) {
			
				$eanCell =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
				$producer  = 'ROTING';
				if(preg_match( '/^(\d){13}/'  , $eanCell )  === 1 ){	
					$line  = array();						
					$line['producer'] = $producer;
					$line['EAN'] = $eanCell;					
					$line['price'] = $worksheet->getCellByColumnAndRow(5, $row)->getValue();						
					$line['category'] = $producer;								
					$line['name'] =  $worksheet->getCellByColumnAndRow(2, $row)->getValue();
					if($line['name']  instanceof  PHPExcel_RichText ){
							$line['name'] = $line['name']->getPlainText();							
					}
										
					
					$pos  = strpos( $line['name'] ,   ' - JEDN');
					if( $pos !== false){
					$line['name']	= 	trim(substr($line['name'] , 0 ,  $pos));
					}
					$lines[]  = $line;
					
				}
			
				
			}					
			//echo $worksheetTitle; die;
			
		}
				

		
	}
	

	file_put_contents ( 'pisaki.json',  json_encode($lines) );
	$lines  = json_decode( file_get_contents('pisaki.json') , true);
	//var_dump($lines); die;
	
		foreach($lines as $l){
		echo print_r($l , true).'<br>';	
		}
		echo count($lines);		


?>
