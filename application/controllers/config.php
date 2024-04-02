<?php
	global $config;
	global $configs; 

	if(isset($this->POST->webAction) || $this->control == "config"){
		
		switch($this->POST->webAction){
			case 'save':
				$this->javascript = $this->JSform();
				$this->config->id = $this->config->save($this->POST);

				if( $this->config->error_msg != "" ) {
					foreach ($this->POST as $key => $value)  $this->POST->$key = stripslashes(stripslashes($value));
					$config = $this->POST;
					$this->webAction = "save";
					$this->javascript .= $this->JSshowMessage($this->config->error_msg, "danger", $this->config->error_fieldsJS );
					
				} else {
					$config = $this->config->byId($this->config->id);
					$this->webAction = "update";
					$this->javascript .= $this->JSshowMessage("saved");
					
				}
				
				break;
				
			case 'update':
				$this->webAction = "update";
				$this->javascript = $this->JSform();
				$this->config->update($this->POST);

				if( $this->error_msg != "" ) {
					foreach ($this->POST as $key => $value)  $this->POST->$key = stripslashes(stripslashes($value));
					$config = $this->POST;
					$this->javascript .= $this->JSshowMessage($this->error_msg, "danger",$this->config->error_fieldsJS);
					
				} else {
					$config = $this->config->byId($this->POST->id);
					$this->javascript .= $this->JSshowMessage("updated");
					
				}
				
				break;
				
			case 'delete':
				$this->config->delete($this->POST->id);
				break;
				
			case 'delete-image':
				unlink($this->POST->webObject);
				unlink(str_replace("/thumbs", "", $this->POST->webObject));
				$config = $this->config->byId($this->POST->id);
				$this->javascript = $this->JSform();
				$this->javascript .= $this->JSshowMessage("image deleted");
				$this->webAction = "update";
				break;
				
			case 'get':
				if($this->POST->id==0){
					$this->webAction = "save";
					
				}else{
					$config = $this->config->byId($this->POST->id);
					$this->webAction = "update";
					
				}
				$this->javascript = $this->JSform();
				break;
				
			case 'list':
			default:
				if($this->POST->webSearch){
					parse_str($this->POST->webSearch, $aux);
					$config = (object)$aux;
					$this->listOrder = ($config->webListOrder!="")?$config->webListOrder:"id";
					$this->listDirection  =  ($config->webListDirection!="")?$config->webListDirection:"asc";
				}

				$this->pageIni = $webPageIni =  ($this->POST->webPageIni=="") ? 0 : $this->POST->webPageIni;

				if( $this->config->db != NULL ){
					$configs = $this->config->query($webPageIni,$config);
				}

				$this->searching = $this->config->searching;
				$this->javascript = $this->JSwindow();
				break;

		}
	}
	
?>