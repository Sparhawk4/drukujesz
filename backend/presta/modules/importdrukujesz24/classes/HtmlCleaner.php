<?php
class HtmlCleaner{
	static private $cleanFunction = null;		
	static public function cleanHtml($html){	
		if(!self::$cleanFunction){
			if(extension_loaded( 'tidy')){
				self::$cleanFunction = 'cleanTidy';							
			}else{	
				require_once(_PS_MODULE_DIR_.'importdrukujesz24/lib/htmLawed/htmLawed.php');
				self::$cleanFunction = 'cleanHtmLawed';			
			}			
		}
		if(self::$cleanFunction == 'cleanTidy')
			return self::cleanTidy($html);
		return self::cleanHtmLawed($html);	
		//$html = $this->{$this->cleanFunction}($html);
		
	}
	
	static private function cleanTidy($html){
		$config = array(
				   'indent'         => true,
				   'output-xhtml'   => true,
				   'wrap'           => 200);

		$tidy = new tidy;
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();	
		return tidy_get_output($tidy);
	}

	static private  function cleanHtmLawed($html){
		$config = array(//'valid_xhtml'=>1,
		//'unique_ids'=>0,
		//'cdata'=>1
		'balance'=>0
		);
		$x = strpos($html , '</head>');
		$head  = substr($html , 0 , $x).'</head>';
		$end  = strrpos($html , '</html>'); 
		$body  = substr($html , $x+7 ,$end - $x - 7);	
		return $head.htmLawed($body , $config).'</html>'; 		
	}	
	
}
