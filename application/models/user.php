<?php
	
	class user {
		
		var $db = NULL;
		var $web = NULL;
		var $image_size = 500;
		var $thumb_size = 150;
		var $rows_size = 15;
		var $rows_total = 0;
		var $tabla = "user";
		var $campos = array();
		var $error = array();
		var $error_fieldsJS = array();
		var $error_msg = "";
		var $searching = false;
		var $listOrder = "id";
		var $listDirection = "DESC";
		var $error_messages = array ( 
													0 => "There are errors" ,
													1 => "- Password lenght is among 6 and 18 characters." ,
													2 => "- User name can not be empty.",
													3 => "- E-mail format is invalid." 
													);
		var $error_fields = array ( 		
											0 => "" ,
											1 => "pass",
											2 => "user",
											3 => "email" 
											);
		
		public function __construct( $web )	{
			$this->web = $web;
			$this->db = $web->db;
			$this->tabla = mysql_prefix . $this->tabla;

			$querycols = $this->db->query("SHOW COLUMNS FROM ".$this->tabla);
			while ($data = $this->db->fetchNextObject($querycols)) {
				if($data->Field != "id") array_push($this->campos, $data->Field);
			}
		}
		
	    public function get($user) {

			foreach($user as $campo => $valor) {
				
		    	switch ($campo) {
		    		
					case 'date_registration':
						$user->$campo = date("d-m-Y",strtotime($valor));
						break;
						
					default:
						$user->$campo = @stripslashes($valor);
						break;
				}
				
			}
	    	
			return $user;
	    }
		
	    public function set($key,$value) {
	    	
	    	switch ($key) {
				
				case 'date_registration':
					$res = ($value=="") ? date("d-m-Y") : date("Y-m-d h:i:s",strtotime($value));
					break;
					
				case 'pass':
					if($value != ""){
						if( strlen($value) < 6 || strlen($value) > 18 ) array_push($this->error, 1);
						$res = encrypt(cleanString($value),$_SESSION['settings']->code);
					} elseif($this->web->POST->webAction == "register") {
						array_push($this->error, 1);
					} else {
						$res = false;
					}
					break;

				case 'user':
					if( strlen($value) == 0 && $this->web->POST->webAction != "register" ) array_push($this->error, 2);
					$res = $value;
					break;
					
				case 'email':
					if( !filter_var($value, FILTER_VALIDATE_EMAIL) ) array_push($this->error, 3);
					$res = $value;
					break;
					
				default:
					$res = $value;
					break;
			}
			
	    	return $res;
	    }
		
	    public function save($user) {

			$insert_query_campos =  "";
			$insert_query_values =  "";
			$i = 0;
			
			foreach($user as $campo => $valor) {

				if( in_array($campo,$this->campos) ){
					
					$resValue = $this->set($campo,$valor);
					
					if( $resValue !== false  ) {
						if($i > 0 ) {
							$insert_query_values .=  ", ";
							$insert_query_campos .=  ", ";
						}
						
						if( $campo != "id" ) {
							$insert_query_campos .=  $campo;
							$insert_query_values .= ( $resValue !== false ) ? "'" . $resValue . "'" : "";
							$i++;
						}
					}
					
				}
				
			}
			
			if( count($this->error) > 0 ) {
				
				$this->error_msg = $this->error_messages[0] . "<br/>";
				
				foreach ($this->error as $key => $value){
					$this->error_msg .= $this->error_messages[$value] . "<br/>";
					array_push($this->error_fieldsJS, $this->error_fields[$value]);
				}

			} else {
			
				$insert_query = "INSERT INTO  ".$this->tabla." ( ". $insert_query_campos . " ) VALUES ( " . $insert_query_values . " )  ";
				$result = $this->db->execute($insert_query);
				$id = $this->db->lastInsertedId();
				
			}
			
			return $id;
			
	    }
		
	    public function update($user) {
			$i = 0;
			$update_query = "";

			$aux_user = $this->db->queryUniqueObject("SELECT * FROM ".$this->tabla." WHERE id = ".$user->id);
			
			foreach($aux_user as $campo => $valor) {

				if( in_array($campo,$this->campos) ){
					$val = ( $user->$campo != $valor ) ? $user->$campo : $valor ;
		
					$resValue = $this->set($campo,$val);
					
					if( $resValue !== false ){
						
						$update_query .= ( $i > 0 ) ? " ," : ""; 
						$update_query .=  $campo . " = '" .  $resValue . "' ";		
						$i++;
						
					}
					
				}
				
			}

			if( count($this->error) > 0 ) {

				$this->error_msg = ($this->error_messages[0]). "<br/>";
				
				foreach ($this->error as $key => $value) {
					$this->error_msg .= ($this->error_messages[$value]) . "<br/>";
					array_push($this->error_fieldsJS, $this->error_fields[$value]);
				}

			} else {
				
				$update_query  = "UPDATE  ".$this->tabla." SET " . $update_query . " WHERE id = '" . $user->id . "' ";
				$result = $this->db->execute($update_query);
				
			}

	    }
		
	    public function delete($id) {

			$this->db->execute("DELETE FROM ".$this->tabla." WHERE id = ".$id);
	    	
	    }
	
	    public function query($iniRow=0,$search = null) {
	    	$res_data = array();
			
			$where = " WHERE 1 ";
			if(!is_null($search)){
				foreach($search as $campo => $valor) {
					if(in_array($campo,$this->campos) && $valor != ""){
						
						$this->searching = true;
						
				    	switch ($campo) {
							
							case 'active':
							case 'level':
								$where .=  " AND " . $campo . " = '" .  $valor. "' ";	
								break;
								
							default:
								$where .=  " AND " . $campo . " LIKE '%" .  $valor. "%' ";	
								break;
						}
						
					}
				}

				if($search->listOrder!=""){
					$order = (is_null($search)) ? "" :  " ORDER BY ".$search->listOrder." ".$search->listDirection;
				}	

			}else{

				$order = "";

			}
			
			$this->rows_total = $this->db->queryUniqueValue("SELECT count(*) FROM ".$this->tabla." ".$where."");

			$aux_query = $this->db->query("SELECT * FROM ".$this->tabla." ".$where." " . $order . "  LIMIT " . $iniRow . ", " .  $this->rows_size);

			while ($data = $this->db->fetchNextObject($aux_query)) {
				array_push($res_data, $this->get($data));
			}
			
			return $res_data;
	    }

	    public function byId($id) {
	    	
			$res_data = $this->db->queryUniqueObject("SELECT *  FROM ".$this->tabla." WHERE id = ".$id);
			
			return $this->get($res_data);
	    }
		
	}

	$this->user = new user($this);
	
?>