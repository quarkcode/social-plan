<?php
	global $user;
	global $users; 
	
	if(isset($this->POST->webAction) || $this->control == "user"){
		

		switch($this->POST->webAction){
			
			case 'login':
				

				if($this->POST) {
				
					$Luser = cleanString($_POST['user']);
					$Lpass = encrypt(cleanString($_POST['pass']),code);
					
					$user = $this->db->queryUniqueObject("SELECT * FROM  ".mysql_prefix."user WHERE user = '".$Luser."'  "); 
			
					if($user){
						
						if($user->active) {
							
							if($user->pass == $Lpass ) {
								
								$remoteIP = $_SERVER['REMOTE_ADDR'];
								$diferenciaDias = ((((mktime(0, 0, 0, date("m")  , date("d"), date("Y")) - strtotime($user->date_last))/60)/60)/24);
				
								$this->db->execute("UPDATE  ".mysql_prefix."user SET login_ip = '".$remoteIP."', login_attempts=0 WHERE id=".$user->id);
								
								$_SESSION[code] = true;
								$_SESSION['user'] = $user;
								unset($_SESSION['user']->pass);
									
							} else {
			
								$this->javascript .= $this->showMessage("Error: Username or password.", "danger");
													
								$nIntentos = ($user->login_attempts + 1);
								if($nIntentos > $this->config('login_attempts')) {
									$this->db->execute("UPDATE  ".mysql_prefix."user SET login_attempts=".$nIntentos.", active='N' WHERE id=".$user->id);
								} else {
									$this->db->execute("UPDATE  ".mysql_prefix."user SET  login_attempts=".$nIntentos." WHERE id=".$user->id);
								}
								
							}
							
						} else {
							
							$this->javascript .= $this->showMessage("Error: User disabled, please contact with your admin.", "danger");
							
						}
						
					} else {
						
						$this->javascript .= $this->showMessage("Error", "danger" );
						
					}
					
				} 
				
				break;
				
			case 'register':
				$this->javascript = $this->JSform();
				$this->POST->user = $this->POST->email;
				$this->POST->active = 1;
				$this->user->id = $this->user->save($this->POST);
				if($this->POST->pass != $this->POST->rpass ) {
					array_push($this->user->error_fieldsJS,"rpass");
					$this->user->error_msg .= "- Rewrite the password.<br/>";
				}
				if( $this->user->error_msg != "" ) {
					foreach ($this->POST as $key => $value)  $this->POST->$key = stripslashes(stripslashes($value));
					$user = $this->POST;
					$this->webAction = "save";
					$this->javascript .= $this->showMessage($this->user->error_msg, "danger", $this->user->error_fieldsJS );
					
				} else {
					$this->webAction = "saved";
					$this->message = "<h2>Thank you for register on ".$this->config("web_name"). '</h2> <div class="small default btn"><a href="/login">Sign in</a></div>';
					
				}
				break;
				
			case 'save':
				$this->javascript = $this->JSform();
				$this->user->id = $this->user->save($this->POST);

				if( $this->user->error_msg != "" ) {
					foreach ($this->POST as $key => $value)  $this->POST->$key = stripslashes(stripslashes($value));
					$user = $this->POST;
					$this->webAction = "save";
					$this->javascript .= $this->showMessage($this->user->error_msg, "danger", $this->user->error_fieldsJS );
					
				} else {
					$user = $this->user->byId($this->user->id);
					$this->webAction = "update";
					$this->javascript .= $this->showMessage("saved");
					
				}
				
				break;
				
			case 'update':
				$this->webAction = "update";
				$this->javascript = $this->JSform();
				$this->user->update($this->POST);

				if( $this->user->error_msg != "" ) {
					foreach ($this->POST as $key => $value)  $this->POST->$key = stripslashes(stripslashes($value));
					$user = $this->POST;
					$this->javascript .= $this->showMessage($this->user->error_msg, "danger",$this->user->error_fieldsJS);
					
				} else {
					$user = $this->user->byId($this->POST->id);
					$this->javascript .= $this->showMessage("updated");
					
				}
				
				break;
				
			case 'delete':
				$this->user->delete($this->POST->id);
				break;
				
			case 'lostpassword':
				if( filter_var($this->POST->email, FILTER_VALIDATE_EMAIL) ) {
					$user = $this->db->queryUniqueObject("SELECT * FROM ".$this->user->tabla." WHERE email = '" . $this->POST->email . "'");
					if($user->id > 0){
						$resetPass = randomPassword();
						$user->pass = $resetPass;
						$this->user->update($user);
						
						$data = new stdClass();
						$data->emailto = $user->email;
						$data->titulo = "Forgoten password";
						$data->contenido = "Forgoten password: ". $resetPass;
						$this->mailTo($data);
						$this->message = "<h2>Password reset! It has sent a new password to your email</h2>";
					}
				} else {
					$this->javascript .= $this->showMessage("E-mail", "danger",array("email"));
					
				}
				break;
				
			case 'delete-file':
				if(file_exists($this->POST->webObject)) 
					unlink($this->POST->webObject);
				if(file_exists(str_replace("/thumbs", "", $this->POST->webObject))) 
					unlink(str_replace("/thumbs", "", $this->POST->webObject));
				$user = $this->user->byId($this->POST->id);
				$this->javascript = $this->JSform();
				$this->javascript .= $this->showMessage("file deleted");
				$this->webAction = "update";
				break;
				
			case 'get':
				if($this->POST->id==0){
					$this->webAction = "save";
					
				}else{
					$user = $this->user->byId($this->POST->id);
					$this->webAction = "update";
					
				}
				$this->javascript = $this->JSform();
				break;
				
			default:
			case 'list':
				if($this->POST->webSearch){
					parse_str($this->POST->webSearch, $aux);
					$user = (object)$aux;
					$this->listOrder = ($user->webListOrder!="")?$user->webListOrder:"id";
					$this->listDirection  =  ($user->webListDirection!="")?$user->webListDirection:"asc";
				}

				$this->pageIni = $webPageIni =  ($this->POST->webPageIni=="") ? 0 : $this->POST->webPageIni;
			
				if( $this->user->db != NULL ){
					$users = $this->user->query($webPageIni,$user);
				}
				$this->searching = $this->user->searching;
				$this->javascript = $this->JSwindow();
				break;

		}

	}
	
?>