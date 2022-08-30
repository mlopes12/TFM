<?php

require_once 'sql_base_mysql.php';


class sql_app extends sql_base_mysql {


	public function comprueba_login($login, $pass) {
		
		$conditions = [];
		$parameters = [];	
		
		$conditions[] = "l.login = ?";
		$parameters[] = $this->correo_tras_gmail($login);
		$conditions[] = "i.verificado = ?";
		$parameters[] = 1;
		$conditions[] = "i.validado = ?";
		$parameters[] = 1;
		$conditions[] = "l.baja = ?";
		$parameters[] = 0;		
		if(!empty($pass)){
			$conditions[] = "l.password = ?";
			$parameters[] = $pass;
		}/*else{
			$conditions[] = "l.password is NULL";
		}*/

		$query = "SELECT i.id_individuo, i.nombre, i.apellidos, i.dni, i.direccion, i.vehiculo, i.marca, i.modelo, i.color, i.num_matricula, i.cardID,  i.telefono, i.movil, i.email, i.metodo_pago, i.token, i.num_tarjeta, l.id_organizacion, i.portal, i.piso, i.letra, i.codigo_postal, i.poblacion,
                    case when i.txnid is not null and i.txnid!='' then i.txnid else '999999999999999' end, i.facturacion_mensual
					FROM n_logins l
						INNER JOIN n_individuos i ON l.id_individuo = i.id_individuo
					WHERE ";
						
		$query.=implode(" AND ",$conditions);
		//echo $query;
		$this->connectPDO();
        $queryPDO=$this->link->prepare($query);
        $queryPDO->execute($parameters);
		if ($queryPDO->rowCount() != 0) {
			return $queryPDO;
		}
		return false;
	}
        
        public function save_token_movil($token, $id_movil){
            
            $this->connectPDO();//Conecta

            $query = "SELECT token "
                    . "FROM tokens_movil "
                    . "WHERE token = '".$token."'";
            
            
            $queryPDO=$this->link->prepare($query);
            $queryPDO->execute();
            $row = $queryPDO->rowCount();
                         
            if ($row == 0){
               //la cuenta de tokens es 0 y no existe, se crea
               $query = "INSERT INTO tokens_movil(id_individuo, token) "
                        . "VALUES($id_movil, '$token')";

                        $fichero = fopen("E:\logs\hnotificaciones_firebase.csv", 'a');
                        fwrite($fichero, date("Y-m-d H:i:s") .";query2: ".$query."\r\n");
                        fclose($fichero);

               $this->connectPDO();//Conecta
               $queryPDO=$this->link->prepare($query);
               $queryPDO->execute();
            }else{
                $query = "  UPDATE tokens_movil
                            SET id_individuo = $id_movil
                            WHERE token = '".$token."'";
                
                $fichero = fopen("E:\logs\hnotificaciones_firebase.csv", 'a');
                fwrite($fichero, date("Y-m-d H:i:s") .";query2: ".$query."\r\n");
                fclose($fichero);
                
                $this->connectPDO();//Conecta
                $queryPDO=$this->link->prepare($query);
                $queryPDO->execute();
            }             
        }
        
        function delete_token_movil($token, $id_usuario){
            $query = "DELETE FROM tokens_movil WHERE token = '".$token."' and id_individuo = ".$id_usuario;
            
            $fichero = fopen("E:\logs\delete_token.csv", 'a');
            fwrite($fichero, date("Y-m-d H:i:s") .";querydelete: ".$query."\r\n");
            fclose($fichero);
            
            $this->connectPDO();//Conecta
            $queryPDO=$this->link->prepare($query);
            $queryPDO->execute();
        }

	
	public function comprueba_clave_login($idIndividuo, $clave) {


			$conditions = [];
			$parameters = [];
			$conditions[]='id_individuo= ? ';
			$parameters[]=$idIndividuo;
			$conditions[]='clave= ?';
			$parameters[]=$clave;

            $query='SELECT id_individuo
					FROM ac_individuos';


			$query.=" WHERE ".implode(" AND ",$conditions);
			
			$this->connectPDO();
			$queryPDO=$this->link->prepare($query);
            $queryPDO->execute($parameters);

			return  $queryPDO;

	}

	public function comprueba_mail($mail, $org) {

		$conditions = [];
		$parameters = [];
		$conditions[]='(l.login= ? OR i.email= ? )';
		$parameters[]=$this->correo_tras_gmail($mail);
		$parameters[]=$mail;
		$conditions[]='o.organizacion= ?';
		$parameters[]=$org;

            $query='SELECT i.id_individuo, i.nombre, i.apellidos, i.email
					FROM ac_login l
						INNER JOIN ac_organizaciones o ON o.id_organizacion = l.id_organizacion
						INNER JOIN ac_individuos i ON l.id_individuo = i.id_individuo
					WHERE i.fecha_baja is null AND i.verificado=1 AND i.validado=1';


			$query.=" AND ".implode(" AND ",$conditions);
			$queryPDO=$this->link->prepare($query);
            $queryPDO->execute($parameters);
			return  $queryPDO;

	}
        

	//INSERTS

	public function set_clave_pass($idIndividuo, $clave) {

		$claveSet = NULL;
		if(!empty($clave))
			$claveSet = $clave;

			$conditions = [];
			$parameters = [];
			$conditions[]='clave= ? ';
			$parameters[]=$claveSet;
			$conditions[]='id_individuo= ?';
			$parameters[]=$idIndividuo;

            $query='update ac_individuos';


			$query.=" SET ".implode(" WHERE ",$conditions);
			
			$this->connectPDO();
			$queryPDO=$this->link->prepare($query);
            $queryPDO->execute($parameters);

	}

	public function set_pass_usuario($idIndividuo, $pass) {

			$conditions = [];
			$parameters = [];
			$conditions[]='password= ? ';
			$parameters[]=$pass;
			$conditions[]='id_individuo= ?';
			$parameters[]=$idIndividuo;

            $query='update ac_login';


			$query.=" SET ".implode(" WHERE ",$conditions);

			$this->connectPDO();
			$queryPDO=$this->link->prepare($query);
            $queryPDO->execute($parameters);
	}
  
        function get_token_movil($id_individuo){
            $query = "SELECT token FROM tokens_movil WHERE id_individuo = :id_individuo";
            
            
            $this->connectPDO();
            $queryPDO = $this->link->prepare($query);
            $queryPDO->execute(array('id_individuo' => $id_individuo));
            $token = array();
            while ($row = $queryPDO->fetch()){
			$token[] = $row;
		}
            return $token;
            
        }
                
        
        public function correo_tras_gmail($email){
            $email = strrev($email);
            if(stripos($email, 'moc.liamg@')===0){
                $email = str_ireplace('moc.liamg@', '', $email);
                $email = 'moc.liamg@'.str_ireplace('.', '', $email);
            }
            return strrev($email);
        }
        
             
        public function get_authorizations($id_individuo){
		$query = "Select 
                    a.id_authorization, p.nombre, a.fecha, i.email
                    FROM n_authorizations a
                    left outer join n_plazas p
                        on a.mac = p.mac
                    left outer join n_individuos i
                        on a.id_individuo = i.id_individuo
		WHERE a.id_individuo = :id_individuo and NOW() <= date_add(a.fecha, interval a.validez second)
                    and (a.autorizada = 0 or a.autorizada is null)";

		//echo $query;

		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);//Prepara la consulta
		$queryPDO->execute(array('id_individuo' => $id_individuo));//Ejecuta la consulta y autoalmacena el queryResult
		//La relación NO es 1-1, necesitamos un bucle while que nos recorra todos los ids posibles y almacenarlos en arrays

		return $queryPDO;
        }
        
        public function comprueba_autorizacion($id_authorization, $id_individuo, $codigo){
		$query = "Select 
                    p.id_plaza, a.idTag, a.id_individuo
                    FROM n_authorizations a
                    left outer join n_plazas p
                        on a.mac = p.mac
		WHERE a.id_authorization = :id_authorization and a.id_individuo = :id_individuo 
                    and a.codigo = :codigo and NOW() <= date_add(a.fecha, interval a.validez second)
                    and (a.autorizada = 0 or a.autorizada is null)";

		//echo $query;

		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);//Prepara la consulta
		$queryPDO->execute(array('id_authorization' => $id_authorization, 'id_individuo' => $id_individuo, 'codigo' => $codigo));//Ejecuta la consulta y autoalmacena el queryResult
		                
                $ret = array('','',-1);
                while($row = $queryPDO->fetch())
                    $ret = array($row[0], $row[1], $row[2]);
		return $ret;
        }
        
        public function update_autorizacion($id_authorization){
		$query = "update n_authorizations set autorizada = 1
		WHERE id_authorization = :id_authorization
                    and (autorizada = 0 or autorizada is null)";

		//echo $query;

		$this->connectPDO();
		$queryPDO=$this->link->prepare($query);//Prepara la consulta
		$queryPDO->execute(array('id_authorization' => $id_authorization));//Ejecuta la consulta y autoalmacena el queryResult
		
        }
        
   
}
