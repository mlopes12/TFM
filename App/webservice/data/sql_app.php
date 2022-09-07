<?php

require_once 'sql_base_mysql.php';

//La interacción con la BD se realiza mediante PDO para mayor seguridad
class sql_app extends sql_base_mysql {

	//función que consulta a la BD si el login enviado por la app es correcto
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
            $conditions[] = "l.password = ?";
            $parameters[] = $pass;


            $query = "SELECT i.id_individuo, i.nombre, i.apellidos, i.email
                        FROM n_logins l
                            INNER JOIN n_individuos i ON l.id_individuo = i.id_individuo
                        WHERE ";

            $query.=implode(" AND ",$conditions);

            $this->connectPDO();
            $queryPDO=$this->link->prepare($query);
            $queryPDO->execute($parameters);
            if ($queryPDO->rowCount() != 0) {
                    return $queryPDO;
            }
            return false;
	}      
        
        
        public function correo_tras_gmail($email){
            $email = strrev($email);
            if(stripos($email, 'moc.liamg@')===0){
                $email = str_ireplace('moc.liamg@', '', $email);
                $email = 'moc.liamg@'.str_ireplace('.', '', $email);
            }
            return strrev($email);
        }     
        
		//función que lista las autorizaciones y las envía a la app
        public function get_authorizations($id_individuo){
            $query = "Select a.id_authorization, p.nombre, a.fecha
                        FROM n_authorizations a
                            left outer join n_plazas p on a.id_plaza = p.id_plaza
                        WHERE a.id_individuo = :id_individuo and NOW() <= date_add(a.fecha, interval a.validez second)
                            and (a.autorizada = 0 or a.autorizada is null)";

            $this->connectPDO();
            $queryPDO=$this->link->prepare($query);
            $queryPDO->execute(array('id_individuo' => $id_individuo));

            return $queryPDO;
        }
        
		//comprueba que el código recibido sea correcto
        public function comprueba_autorizacion($id_authorization, $id_individuo, $codigo){
            $query = "Select id_plaza, idTagCharge, id_individuo
                        FROM n_authorizations
                        WHERE id_authorization = :id_authorization and id_individuo = :id_individuo 
                            and codigo = :codigo and NOW() <= date_add(fecha, interval validez second)
                            and (autorizada = 0 or autorizada is null)";

            $this->connectPDO();
            $queryPDO=$this->link->prepare($query);
            $queryPDO->execute(array('id_authorization' => $id_authorization, 'id_individuo' => $id_individuo, 'codigo' => $codigo));
            
            $ret = array(-1, -1, -1);
            while($row = $queryPDO->fetch())
                $ret = array($row[0], $row[1], $row[2]);
            
            return $ret;
        }
        
        //marca la autorización como completada por seguridad
        public function update_autorizacion($id_authorization){
            $query = "update n_authorizations set autorizada = 1
                        WHERE id_authorization = :id_authorization and (autorizada = 0 or autorizada is null)";

            $this->connectPDO();
            $queryPDO=$this->link->prepare($query);
            $queryPDO->execute(array('id_authorization' => $id_authorization));
		
        }        
   
}