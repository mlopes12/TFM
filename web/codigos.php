<?php
//se listan las funciones añadidas al CSMS base.
//se realizará una explicación de lo que hace cada función.
//en algunos puntos hay una sustitución. El código antiguo se notará entre /**/
//se indicará igualmente cuál es el nuevo y cuál el antiguo

	//añadido en estados plazas, desde donde se inician recargas en la web

	//cambio en la llamada al iniciar recarga desde la web
	
	//código antiguo
	//simplemente recoge individuo y cargador e inicia la recarga
	/*$id_individuo = $infousuario[0];
	$tagId = $infousuario[1]
	$ret=remoteStart($id_plaza, $tagId, $connectorNumero);
	*/
	
	
	//código nuevo
	//recoge la semilla
	//hace la cuenta con la fecha para convertirla en variable
	//recoge los 14 primeros dígitos (límite que acepta el punto de recarga)
	//esta tarjeta se asocia a la recarga como la tarjeta con la que se inició
	$id_individuo = $infousuario[0];
	$clave = $infousuario[1];
	$fecha_auth = date("Y-m-d H:i:s");
	$fecha = strtotime(date("YmdHis"));
	$tag_dec = hexdec($clave);
	$tagId = substr(dechex($fecha * $tag_dec), 0, 14);
	$tagId = str_pad($tagId, 14, "0", STR_PAD_RIGHT);
	//genera el código
	$codigo = rand(10000, 99999);
	//se genera otra tarjeta que se asocia a la carga para no poder pararla con la misma tarjeta que se inició (opción que contempla este protocolo)
	$tag_inicio_carga = str_pad(dechex(rand(0, 4294967295)), 8, "0", STR_PAD_LEFT);
	//añade la autorización
	$mysql->insert_authorization($codigo, $id_plaza, $tagId, $id_individuo, $fecha_auth, $tag_inicio_carga);
	$ret = 1;
	

//anadido en sql_estados_plazas
//estas interactúan entre la app y la parte de la web (Estado de plazas) que puede iniciar recargas         
	function insert_authorization($codigo, $id_plaza, $idTag, $id_individuo, $fecha_auth, $idTagCharge){
				
		
		$query = "
					INSERT INTO n_authorizations 
					(
						fecha
                                                ,validez
                                                ,id_plaza
                                                ,idTag
                                                ,codigo
                                                ,id_individuo
                                                ,idTagCharge
					)
					VALUES (
                                            '$fecha_auth'
                                            ,120                                        
                                            ,$id_plaza
                                            ,'".$idTag."'
                                            ,'".$codigo."'
                                            ,$id_individuo
                                            ,'$idTagCharge'
					)
				 ";

						
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute();
            
            
            
		$mail = new PHPMailer();

		$mail->IsSMTP();
		$mail->SMTPDebug = 1;
		$mail->Host = "smtp.strato.com";
		$mail->SMTPAuth = true;
		$mail->Host = "smtp.strato.com";
		$mail->Port = 587;
		$mail->Username = "noresponder@o2e.es";
		$mail->Password = "o2e2008";
		$mail->SMTPSecure = "tls";

		$mail->SetFrom('noreply@o2e.es', 'TFM');
		
		//Asunto
		$mail->Subject = "Verificación";
		
		$body = "Para verificar su identidad e iniciar la recarga en el punto ".$this->getplazaNombre($id_plaza)." introduzca el siguiente código: <b>$codigo</b><br/>"
                        . "En caso de que no haya iniciado una recarga por favor <b>ignore este correo</b>";
		
		$mail->MsgHTML($body);
		$mail->IsHTML(true);
		$mail->CharSet = 'UTF-8';
		
		$mail->AddAddress( $this->get_mail($id_individuo) ); 
		var_dump( $mail->Send() );  
            
            
	}
        
        
        
	function getplazaNombre($id_plaza){
		$query = "SELECT nombre FROM n_plazas where id_plaza=$id_plaza; 
                        ";
                $this->connectPDO();	
		$queryPDO=$this->link->prepare($query);//Prepara la consulta
		$queryPDO->execute(array());//Ejecuta la consulta y autoalmacena el queryResult	
		
		$nombre = -1;
		while ($row = $queryPDO->fetch()){
			$nombre = $row[0];
		}
		
 		return $nombre; 		
	}
        
        
	
	function get_mail($id_individuo){
		$query = "
					Select 		email as email
					From 		n_individuos 
					Where 		id_individuo = $id_individuo
				";	
                echo $query;
		$this->connectPDO();
                $queryPDO=$this->link->prepare($query);
                $queryPDO->execute();
                $email = '';
                while($row = $queryPDO->fetch())
                    $email = $row['email'];
		return $email;
		
	}
	
	
	
	
	
	//En la función en la cuál se trata la recepción de un Authorize (paso de tarjeta):
	
	//Antes:
		/*function Authorize($number, $json,$mac){

			//Paso 1: Extrae los datos del mensaje
			$mysql_server = new sql_server_base();
			
			$idTag = $json['idTag'];
			$chargeBoxIdentity = $mac;
			$login_org = $mysql_server->get_loginOrg($mac);
			
			//Paso 2: Procesar el mensaje (evaluar la autorizaci�n)
			$authorizeResponse = Authorize_base( $idTag, $chargeBoxIdentity, $login_org);
			
			//Paso 3: Componer y Devolver la respuesta
			$status=$authorizeResponse->idTagInfo->status;
			$expiryDate=$authorizeResponse->idTagInfo->expiryDate;
			$parentIdTag=$authorizeResponse->idTagInfo->parentIdTag;
			
			$json_response="[3, \"$number\", {\"idTagInfo\":{\"status\":\"$status\"";
				if($expiryDate!=null)
					$json_response .= ",\"expiryDate\":\"$expiryDate\"";
				if($parentIdTag!=null)
					$json_response .= ",\"parentIdTag\":\"$parentIdTag\"";
				$json_response .= "}}]";
			return $json_response;
		}
	*/
	//Ahora:
	
	
		function Authorize($number, $json,$mac){

			//Paso 1: Extrae los datos del mensaje
			$mysql_server = new sql_server_base();
			
			$idTag = $json['idTag'];
			$chargeBoxIdentity = $mac;
			$login_org = $mysql_server->get_loginOrg($mac);
			
			//recoge el estado del punto para comprobar si tiene que crear autorizacion o detener
			$status_charging = $mysql_server->get_status_charging($mac);
					
			//genera la etiqueta
			$fecha = strtotime(date("YmdHis"));
			$tag_dec = hexdec($idTag);    
			$tagId_carga = substr(dechex($fecha * $tag_dec), 0, 14);
			$tagId_carga = str_pad($tagId_carga, 14, "0", STR_PAD_RIGHT);
				
			//compone la respuesta
			$info_authorize = Authorize_base( $tagId_carga, $chargeBoxIdentity, $login_org);	
			$authorizeResponse = $info_authorize[0];
			$id_individuo = $info_authorize[1];
			$status=$authorizeResponse->idTagInfo->status;
			$expiryDate=$authorizeResponse->idTagInfo->expiryDate;
			$parentIdTag=$authorizeResponse->idTagInfo->parentIdTag;
				
				if($status == COM_AuthorizationStatus_Accepted && !$status_charging){
				//si no está cargando inserta la autorización como en estados_plazas.php
					$codigo = rand(10000, 99999);
					$id_plaza = $mysql_server->get_id_plaza($mac);
					$tag_inicio_carga = str_pad(dechex(rand(0, 4294967295)), 8, "0", STR_PAD_LEFT);
					$mysql_server->insert_authorization($codigo, $id_plaza, $tagId_carga, $id_individuo, $tag_inicio_carga);

				}else if($status_charging){
					//recoge los datos actuales de la recarga y la semilla del usuario al que está asociado la recarga que se está produciendo
					$datos_lastcharge = $mysql_server->get_datos_lastcharge($mac);
					
					$id_carga=$datos_lastcharge[0];
					$clave=$datos_lastcharge[1];
					$id_plaza=$datos_lastcharge[2];
					
					$fecha_auth = date("Y-m-d H:i:s");
					$tag_dec = hexdec($clave);            
					$tagId_BD = substr(dechex($fecha * $tag_dec), 0, 14);
					
					if($tagId_BD == $tagId_carga){   
						//si es el mismo, guarda la etiqueta en la recarga y la finaliza
						$mysql_server->update_tagidstop_charge($id_carga, $tagId_carga);
						require_once("C:\inetpub\wwwroot\TFM\msr_ev/Servicio/16/remoteAgent.php");                
						$ret=remoteStop($id_plaza, $id_carga);
					}else{
						//si es distinto, responde con una no autorización
						$json_response="[3, \"$number\", {\"idTagInfo\":{\"status\":\"Invalid\"}}]";
						return $json_response;
					}
				}else{
					//responde con no autorizada por defecto en caso de algún error por seguridad
					$json_response="[3, \"$number\", {\"idTagInfo\":{\"status\":\"$status\"";
					if($expiryDate!=null)
						$json_response .= ",\"expiryDate\":\"$expiryDate\"";
					if($parentIdTag!=null)
						$json_response .= ",\"parentIdTag\":\"$parentIdTag\"";
					$json_response .= "}}]";
					
					return $json_response;
				}
				
		}

		
			
			
	//Cambios en StartTransaction en la parte de autorizarla e iniciarla:
	
	//Antes:
	/*
	$id_individuo = $idTagInfo->calculaIdTagInfo( $idTag , $id_plaza, $login_org  );
	if($id_individuo == null){
		$id_individuo = $sql->get_individuo_id($idTag);
		$idTagInfo->status = COM_AuthorizationStatus_Accepted;
	}
	$nro_transaction = $sql->setStartTransaction( $id_plaza, $chargeBoxIdentity, $timestamp , $id_individuo , $meterStart , $connectorId, $idTag );
	*/
	
	//Ahora:
	//recoge los datos de la autorización para asociarlos a la recarga para mayor seguridad
	$info = $sql->get_authorization_ind($id_plaza, $idTag);
	$idTagInfo->status = COM_AuthorizationStatus_Accepted;
	$id_authorization = $info[0];
	$id_individuo = $info[1];
	$nro_transaction = $sql->setStartTransaction( $id_plaza, $chargeBoxIdentity, $timestamp , $id_individuo , $meterStart , $connectorId, $idTag, $id_authorization );
	
	
	
	//Cambios en funciones existentes que interactúan con estas funciones
	

	function setStartTransaction( $id_plaza, $estacion , $inicio , $id_individuo , $vatiosInicio , $connectorId, $tagId, $id_authorization  ){//OJO: tiene 1 parametro mas que la 1.6 y la 1.5 ( id_sensor no esta en  1.6 y 1.5)

	//inserta la carga añadiendo el identificador de la autorización
		$id_individuo = !empty($id_individuo) ? $id_individuo : 'Null';//OJO: en la query $id_individuo NO PUEDE ir entrecomillado. Por que 'Null' no es un valor aceptable para un int
		$timestamp = date("Y-m-d H:i:s", strtotime($inicio));
						
		$query = " 		INSERT INTO cargas( 	
							id_plaza,
							tagid,
							estacion,
							vatioscargados,
							inicio,
							id_individuo,
							vatiosInicio,
							connectorId,
							transaccion,
							id_authorization)
						VALUES(
							$id_plaza,
							'$tagId',
							'$estacion',
							0,
							'$timestamp',
							$id_individuo,
							$vatiosInicio,
							$connectorId,
							1,
							$id_authorization);";
									
		//$id_authorization es nueva
									
		$this->connectPDO();	
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute(array());
		$id_carga = $this->link->lastInsertId();
		//paquete que se envía al cargador con la recarga
		$query = " update ws_envio set mensaje = '[20, ".'"'."RemoteAgentRequest".'"'.", ".'"'."RemoteStopTransaction".'"'.",{".'"'."transactionId".'"'.":".'"'.$id_carga.'"'."},{".'"'."ClientIdentifier".'"'.":".'"'.$estacion.'"'."}]'
			WHERE mensaje like '%StopTransaction%' and mensaje like '%99999999%' and fecha_creacion > NOW()";
		
		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute(array());
		
		return $id_carga;		
	}
	
	

	function get_individuo_id($cardID){
		
		//Antes se sacaba el id_individuo con el cardID, ahora se saca el listado de individuos, se opera ese cardID, se compara con el que llega y si existe autoriza.

		$query = "SELECT id_individuo, cardID FROM n_individuos WHERE id_organizacion = 1";
                
		$this->connectPDO();//Conecta
		$queryPDO=$this->link->prepare($query);//Prepara la consulta
		$queryPDO->execute(array());
		$id = -1;
		while ($row = $queryPDO->fetch()){
			$fecha = strtotime(date("YmdHis"));
			$tag_dec = hexdec($row[1]);    
			$tagId_BD = substr(dechex($fecha * $tag_dec), 0, 14);
			$tagId_BD = str_pad($tagId_BD, 14, "0", STR_PAD_RIGHT);
			if($tagId_BD == $cardID){
				$id = $row[0];
				break;
			}
		}

		return $id;	
	}
	
	
	//Nuevas funciones creadas:
	        
	//recoge el id del cargador
	function get_id_plaza($mac){
			
		$query = " SELECT id_plaza from n_plazas where mac='$mac' and status != 'Charging' "; 
                   
                    $this->connectPDO();//Conecta
                    $queryPDO=$this->link->prepare($query);
                    $queryPDO->execute(array());
                    $id_plaza = -1;
                    while ($row = $queryPDO->fetch())
                        $id_plaza = $row[0];
                    return $id_plaza;
		}
	
        
	//comprueba si el cargador está cargando para ver si tiene que crear autorización o detener recarga
	function get_status_charging($mac){
		
		$query = " SELECT count(status) from n_plazas
			where mac='$mac' and status = 'Charging' "; 
			
		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute(array());
		$status_charging = false;
		while ($row = $queryPDO->fetch())
			$status_charging = ($row[0] > 0)?true:false;
                    
	        if(!$status_charging){
			$query = " SELECT count(id) from cargas
				where estacion='$mac' and fin is null and inicio > DATE_SUB(NOW(),INTERVAL 5 MINUTE)"; 
			echo $query;
			$this->connectPDO();//Conecta
			$queryPDO=$this->link->prepare($query);//Prepara la consulta
			$queryPDO->execute(array());//Ejecuta la consulta y autoalmacena el queryResult
			$status_charging = false;
			while ($row = $queryPDO->fetch())
				$status_charging = ($row[0] > 0)?true:false;
	        }
		
		return $status_charging;
	}
	
        

	//inserta la autorización y envía el correo
	function insert_authorization($codigo, $id_plaza, $idTag, $id_individuo, $idTagCharge){
				
		
		$query = "
					INSERT INTO n_authorizations 
					(
						fecha
						,validez
						,id_plaza
						,idTag
						,codigo
						,id_individuo
						,idTagCharge
					)
					VALUES (
						'".date("Y-m-d H:i:s")."'
						,120                                        
						,$id_plaza
						,'".$idTag."'
						,'".$codigo."'
						,$id_individuo
						,'$idTagCharge'
					)
				 ";


		$posibles_bonos = array();
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute();
            
            
            
		$mail = new PHPMailer();

		$mail->IsSMTP();
		$mail->SMTPDebug = 1;
		$mail->Host = "smtp.strato.com";
		$mail->SMTPAuth = true;
		$mail->Host = "smtp.strato.com";
		$mail->Port = 587;
		$mail->Username = "noresponder@o2e.es";
		$mail->Password = "o2e2008";
		$mail->SMTPSecure = "tls";

		$mail->SetFrom('noreply@o2e.es', 'TFM');
		
		//Asunto
		$mail->Subject = "Verificación";
		
		$body = "Para verificar su identidad e iniciar la recarga en el punto ".$this->getplazaNombre($id_plaza, 1)." introduzca el siguiente código: <b>$codigo</b><br/>"
                        . "En caso de que no haya iniciado una recarga por favor <b>ignore este correo</b>";
		
		$mail->MsgHTML($body);
		$mail->IsHTML(true);
		$mail->CharSet = 'UTF-8';
		
		$mail->AddAddress( $this->get_mail($id_individuo) ); 
		var_dump( $mail->Send() );  
                        
	}
        
	//recoge la información de la autorización
	function get_authorization_ind($id_plaza, $idTag){
			
		$query = " SELECT id_authorization, id_individuo from n_authorizations
			where id_plaza='$id_plaza' and idTagCharge = '$idTag' 
			order by id_authorization desc
                        LIMIT 1; "; 
						
		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute(array());
		$info = array(-1, -1);
		while ($row = $queryPDO->fetch())
			$info = $row;
		return $info;
		
	}
		
	//recoge los datos de la carga para poder cerrarla y actualizarla	
	function get_datos_lastcharge($mac){
			
		$query = " SELECT c.id, i.cardID, c.id_plaza
					from cargas c
					left outer join n_individuos i on i.id_individuo = c.id_individuo
					where c.estacion='$mac' and c.fin is null
					order by c.id desc
					LIMIT 1; ";
					
		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute(array());
		$return = array(-1, -1, -1);
		while ($row = $queryPDO->fetch())
			$return = $row;
		return $return;
		
	}
	
	//actualiza con la etiqueta de fin de la recarga
	function update_tagidstop_charge($id_carga, $tagIDStop){
			
		$query = " update cargas set tagIDStop = '$tagIDStop' where id = $id_carga "; 
		
		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);
		$queryPDO->execute(array());
	}
	
        
	
	?>
	
	
