<?php

//se comprueba si la llamada es correcta
if ( isset($_GET["signin"]) || isset($_GET["request"])){
    require 'data/sql_app.php';
    //se comprueban los datos enviados en la pantalla de login
    if(isset($_GET['signin'])){
        switch ($_GET["signin"])
        {
            case "1":
                post_login();
                break;	

            default:
                Error();
                break;	

        }
    }else if(isset($_GET['request'])){

        switch ($_GET["request"])
        {
            //en esta funcion se van solicitando las solicitudes de inicio de recarga
            case "1":
                request_autorizaciones_activas();
                break;
            //en esta funcion  llega el código recibido por email tras iniciar una recarga y se comprueba si esta asociado a alguna autorizacion
            case "2":
                post_codigo_autorizacion();
                break;

            default:
                Error();
                break;
        }
    }else{
        Error();
    }
}
else{
    Error();
}



/*************************************************************************/
/*******************		Requests            **********************/
/*************************************************************************/

//comprueba si los datos recibidos (login y contrasena) son correctos y devuelve un identificador de usuario, nombre, apellidos y email
function post_login(){

    if(isset($_POST['user'])){

        $sql = new sql_app();
        $pass = (isset($_POST['pass']))?$_POST['pass']:null;
        $result = $sql->comprueba_login($_POST['user'], $pass);

        if($result==true){

            $userData = $result->fetch();
            $linea['idUsuario'] = (int)$userData[0];
            $linea['nombre'] = $userData[1];
            if(!empty($userData[2]))
                $linea['apellidos'] = $userData[2];
            else
                $linea['apellidos'] = "";
            if(!empty($userData[3]))
                $linea['email'] = $userData[3];
            else
                $linea['email'] = "";
            echo jsonDecodeArray($linea);
        }
        else{
            if(isset($pass))
                ErrorLogin();
            else
                ErrorLoginNoPass();
        }

    }
    else{
        Error();
    }
        
}

//recibe el identificador del usuario, saca las autorizaciones pendientes asociadas a ese usuario y devuelve su identificador, su plaza, y la fecha
function request_autorizaciones_activas(){

    if( isset($_POST['id_usuario'])){

        $sql = new sql_app();
        $autorizaciones = $sql->get_authorizations($_POST['id_usuario']);
        $resultado['autorizacion'] = array();
        while ($row = $autorizaciones->fetch())
            $resultado['autorizacion'][]=array('id' => (int)$row[0], 'plaza'=> $row[1], 'fecha'=> $row[2]);
        
        if(count($resultado)>0)
            echo jsonDecodeArray( $resultado );
        else
            echo "{}";
    }
    else
        Error();
}

//recibe el identificador de autorizacion, el del individuo y el código introducido por el usuario y, si es correcto, la cierrta e inicia la recarga
function post_codigo_autorizacion(){

	if(isset($_POST['id_autorizacion']) && isset($_POST['id_individuo']) && isset($_POST['codigo'])){

		$sql = new sql_app();                
		$result = $sql->comprueba_autorizacion($_POST['id_autorizacion'], $_POST['id_individuo'], $_POST['codigo']);

		if($result[2]>0){

                    require_once("C:\inetpub\wwwroot\TFM\msr_ev/Servicio/16/remoteAgent.php");  
                    $ret=remoteStart($result[0], $result[1], 1);        
                    $sql->update_autorizacion($_POST['id_autorizacion']);
                    echo 1;
		}
		else{
                    Error();
                }

	}
	else{
		Error();
	}
        
}

/*************************************************************************/
/*******************		Auxiliares		******************/
/*************************************************************************/

function Error(){
	echo "-1";
}

function ErrorLogin(){
	echo "-2";
}

function ErrorLoginNoPass(){
	echo "-3";
}

function jsonDecodeArray( $array ){
    array_walk_recursive( $array, function(&$item) { 
        if(is_string($item))
                $item = utf8_decode( $item ); 
        else
                $item = $item;
    });
    return str_replace(':null', ':""', json_encode( $array, JSON_PARTIAL_OUTPUT_ON_ERROR ) );
}


?>