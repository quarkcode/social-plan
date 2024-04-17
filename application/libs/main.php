<?php
	global $db;

	include APPPATH.'config.php';
	include APPPATH.'libs/functions.php';
	include APPPATH.'libs/db.php';
		
	class web {
		
		var $title = "";
		var $meta_keywords = "";
		var $meta_description = "";
		var $page_title = "Social Plan";
		var $full_title = "";
		var $theme = "default";
		var $application_title = "default";
		var $level =  100;
		var $domain = "";
		var $charset = "utf-8";
		var $logged = false;
		var $listOrder = "id";
		var $listDirection = "asc";
		var $language = "es";
		var $defaultLanguage = "es";
		var $head = null;
		var $content = null;
		var $page = null;
		var $db_table = null;
		var $db = null;
		var $POST = null;
		var $message = null;
		var $message_class = null;
		var $settings = null;
		var $javascript = null;
		var $pageIni = 0;
		var $searching = false;
		var $webAction = "";
		var $value1 = "";
		var $value2 = "";
		var $value3 = "";
		var $model = "";
		var $control = "";
	
		public function __construct( $settings )	{
			
			foreach($settings as $key => $value) $this->$key = $value;

				
			$this->db = new DB(	mysql_db, mysql_host, mysql_username, mysql_password);		
			
			$resTLB = $this->db->query("SHOW TABLES LIKE '".mysql_prefix ."config' ");	
			$aux = $this->db->fetchNextObject($resTLB);	
			if(!is_null($aux)){
				$this->title = $this->config("web_name");
				$this->meta_keywords = $this->config("meta_keywords");
				$this->meta_description = $this->config("meta_description");
			}
			
			global $db;
			$db = $this->db;

	        #WITH PAGE LEVEL
	        if( $this->level > 0 && isset($_SESSION['user']) ) {

				if( $_SESSION['user']->id > 0 )	{
										
					if($this->level > $_SESSION['user']->level ) {
						header("Location: /");
						exit(303);
					}
					
				} else {
					header("Location: /");
					exit(303);
				}
		   }
		   
			#GET THE GET URL VALUES
			foreach($_GET as $nombre_campo => $valor) $this->$nombre_campo = $valor;
			
			#CLEAN AND GET THE POST
			if($_POST){
				$this->POST = new stdClass();
				foreach($_POST as $nombre_campo => $valor) $this->POST->$nombre_campo = cleanString($valor,$db); 
			} else {
				$this->POST = new stdClass();
				$this->POST->webAction = null;
				$this->POST->webSearch = null;
				$this->POST->webPageIni = null;
			}

			#COJO EL MODELO DE OBJETO
			if(file_exists(APPPATH."models/".$this->model.".php") && $this->model != "" ) 
				include APPPATH."models/".$this->model.".php";
			
			#CARGO EL CONTROLADOR
			if(file_exists(APPPATH."controllers/".$this->control.".php") && $this->control != "") 
				include APPPATH."controllers/".$this->control.".php";
			
			#CAMBIO DE IDIOMA
			if($_SESSION['language']!=$this->language)
				$this->language = $_SESSION['language'];
						
			$this->full_title = ( ( $this->page_title != "" && $settings->title != ""  ) ? $settings->title . " - " : "" )  . $this->page_title;
			
			#GUARDO EL HEAD
			$this->head = ob_get_contents();
			ob_end_clean();
			ob_start();
			
		}
	
		public function launch()	{
			
			#GUARDO EN CONTENT
			$this->content=ob_get_contents();
			ob_end_clean();
			
			switch ($this->theme) {
					
				case 'blank':
					header('Content-Type: text/html; charset='.$this->charset);
					print $this->content;
					break;
				
				default:
					include RELPATH.'themes/'.$this->theme."/index.phtml";
					break;
			}
				
			
		}

		public function config($value)	{
			if($value!="" ){
				$res = $this->db->queryUniqueValue("SELECT value FROM ".mysql_prefix ."config WHERE name = '".$value."'");
				return $res;		
			} else {
				return false;
			}
		}

		public function layout($name,$theme = false)	{
			
			if(!$theme) $theme = $this->theme;
			include RELPATH."themes/".$theme."/".$name.".phtml";
			
		}
				
		#TRANSLATE TEXT FUNCTION
		function t( $IDtexto ) {
			$string = "";
			
			if(file_exists("../languages/".$this->language."/".$this->page.".csv")){
				$languageLoad = $this->language;
			} else {
				$languageLoad = $this->defaultLanguage;
			}
			
			#CARGO LOS TEXTOS DE LA PAGINA
			$fila = 1;
			if (($gestor = @fopen("../languages/".$languageLoad."/".$this->page.".csv", "r")) !== FALSE) {
			    while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
			        $numero = count($datos);
			        $fila++;
			        for ($c=0; $c < $numero; $c++) {
			        	if($datos[0]==$IDtexto) $string = $datos[1];
			        }
			    }
			    fclose($gestor);
			}
			
			#SI NO LO ENCUENTRO EN EL IDIOMA NORMAL, BUSCO EN EL DEFAULT
			if($string == "" && $languageLoad != $this->defaultLanguage){
				
				$fila = 1;
				if (($gestor = @fopen("../languages/".$this->defaultLanguage."/".$this->page.".csv", "r")) !== FALSE) {
				    while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
				        $numero = count($datos);
				        $fila++;
				        for ($c=0; $c < $numero; $c++) {
				        	if($datos[0]==$IDtexto) $string = $datos[1];
				        }
				    }
				    fclose($gestor);
				}
			}
			
			#SI SIGO SIN ENCONTRARLO MIRO EN EL GENERAL DE IDIOMA...
			if($string==""){
				
				$fila = 1;
				if (($gestor = @fopen("../languages/".$this->language."/general.csv", "r")) !== FALSE) {
				    while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
				        $numero = count($datos);
				        $fila++;
				        for ($c=0; $c < $numero; $c++) {
				        	if($datos[0]==$IDtexto) $string = $datos[1];
				        }
				    }
				    fclose($gestor);
				}
			}
			
			$string = ($string=="") ? $IDtexto : $string;
			
			return $string;
			
		}
		
		function INPUT_checked($value){
			if($value)
				return ' checked="checked" ';
		}
		
		function INPUT_selected($value,$option){
			if($value==$option)
				return ' selected="selected" ';
		}
		function pagination($data, $tam_pagination = 3) {
			$pages_tam = $data->rows_size;
			$pages_ini = $this->pageIni;
			$pages_max = $data->rows_total;
		
			if (!isset($pages_ini)) $pages_ini = 0;
			else {
				if ($pages_ini < 0) $pages_ini = 0;
				if ($pages_ini >= $pages_max) $pages_ini = $pages_max - $pages_tam;
			}
		
			$pages = ceil($pages_max / $pages_tam);
			$pactual = floor($pages_ini / $pages_tam);
		
			$pagination = '<div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">';
			$pagination .= '    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">';
			$pagination .= '        <div>';
			$pagination .= '            <p class="text-sm text-gray-700">';
			$pagination .= '                Showing <span class="font-medium">' . ($pages_ini + 1) . '</span> to <span class="font-medium">' . min($pages_ini + $pages_tam, $pages_max) . '</span> of <span class="font-medium">' . $pages_max . '</span> results';
			$pagination .= '            </p>';
			$pagination .= '        </div>';
			$pagination .= '        <div>';
			$pagination .= '            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">';
		
			if ($pactual > 0) {
				$pagination .= '            <a href="/' . $data->web->page .'/'.($pages_ini - $pages_tam) . '" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">';
				$pagination .= '                <span class="sr-only">Previous</span>';
				$pagination .= '                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"/></svg>';
				$pagination .= '            </a>';
			}
		
			$start = max($pactual - $tam_pagination, 0);
			$end = min($pactual + $tam_pagination + 1, $pages);
		
			if ($start > 0) {
				$pagination .= '            <a href="/' . $data->web->page .'/0" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-50 ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0">1</a>';
				if ($start > 1) {
					$pagination .= '            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">...</span>';
				}
			}
		
			for ($i = $start; $i < $end; $i++) {
				$class = ($i == $pactual) ? 'bg-indigo-600 text-white' : 'text-gray-900 hover:bg-gray-50';
				$pagination .= '            <a href="/' . $data->web->page .'/'.($i * $pages_tam) . '" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ' . $class . ' ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0">' . ($i + 1) . '</a>';
			}
		
			if ($end < $pages) {
				if ($end < $pages - 1) {
					$pagination .= '            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">...</span>';
				}
				$pagination .= '            <a href="/' . $data->web->page .'/'.(($pages - 1) * $pages_tam) . '" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-50 ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0">' . $pages . '</a>';
			}
		
			if (($pactual + 1) < $pages) {
				$pagination .= '            <a href="/' . $data->web->page .'/'.($pages_ini + $pages_tam) . '" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">';
				$pagination .= '                <span class="sr-only">Next</span>';
				$pagination .= '                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"/></svg>';
				$pagination .= '            </a>';
			}
		
			$pagination .= '            </nav>';
			$pagination .= '        </div>';
			$pagination .= '    </div>';
			$pagination .= '</div>';
		
			print $pagination;
		}
		
		
		/* 
		 * JAVASCRIPT FUNCTIONS
		 */
		
		function showMessage($texto, $tipo = "success", $error_fields=null){				
		}
		 
		function JSform(){			
		}

		function JSwindow(){
		}
		
		function JSshowMessage(){
		}
		
	}

	ob_start();
	
?>

