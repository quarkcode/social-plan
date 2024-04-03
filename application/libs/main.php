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
						
			$this->full_title = $this->page_title . ( ( $this->page_title != "" && $settings->title != ""  ) ? " - " : "" ) . $settings->title;
			
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
		
		function pagination( $data, $tam_pagination = 6 ){
			
			$pagination = "";

			$pages_tam = $data->rows_size;
			$pages_ini = $this->pageIni ;
			$pages_max = $data->rows_total;
			
			if(!isset($pages_ini)) $pages_ini = 0;	
			else {
				if($pages_ini < 0) $pages_ini = 0;
				if($pages_ini >= $pages_max) $pages_ini = $pages_max - $pages_tam;
			}
		
			if($pages_ini < 0 ) $pages_ini = 0;
		
			$pages = $pages_max / $pages_tam;
			$pactual = $pages_ini / $pages_tam;
			$this_pag = $pages_ini;
		
			$pagination .= "\n<ul id='webPagination'>\n";
		
			if($pactual != 0 ){
				$pagination .= "<li class='previous'>&#x25C1;</li>\n";
				if($pactual > ($tam_pagination-1)) {
					$pagination .= "<li class='pag'>1</li>\n";
					if($pactual > ($tam_pagination))  $pagination .= "<li>...</li>\n";
				}
			}
		
			if(ceil($pages)>1) {
				$contp = 0;
				for($i = 0 ; $i < $pages ; $i++) {
					if($contp < ($tam_pagination+$pactual)){
						if($i > ($pactual-$tam_pagination)){
							if ($i == $pactual) {
								if($i==0) 
									$pagination .= "<li class='selected pag' id='pos".($i*$pages_tam)."' >".($i+1)."</li>\n";
								else
									$pagination .= "<li class='selected pag' id='pos".($i*$pages_tam)."' >".($i+1)."</li>\n";							
							
							} else {
								$pagination .= "<li class='pag'>".($i+1)."</li>\n";
							} 
						}
					}
					$contp++;
				}
			}
		
			if(($pactual+1) != ceil($pages) ) {
				if($pages_max > $pages_tam){
					if($pactual < ( ($pages) - ($tam_pagination)  ))  {
						if($pactual < ( ($pages) - ($tam_pagination+1)  ))  $pagination .= "<li>...</li>";
						$pagination .= "\n	<li class='pag'>".ceil($pages)."</li>\n";
					}
					$pagination .= "<li class='next'>&#x25B7;</li>\n";
				}
			}
		
			$pagination .= ($pagination == "\n<ul id='webPagination'>\n" ) ?  "<li></li>\n" : "";
		 	$pagination .= "</ul>";
				  
			print $pagination ;
						
		}
		
		function INPUT_checked($value){
			if($value)
				return ' checked="checked" ';
		}
		
		function INPUT_selected($value,$option){
			if($value==$option)
				return ' selected="selected" ';
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

