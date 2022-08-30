<?php


require_once('../msr_ev/mail/class.phpmailer.php');
require_once('com_includes_mail.php');

$GLOBALS['subgrupo'] = '86';
$GLOBALS['version_ios'] = '4.3.1';
$GLOBALS['version_android'] = '4.3.1';
$GLOBALS['preautorizacion_eur'] = 5000;
$GLOBALS['preautorizacion_kwh'] = 90;

//TODO: cambiar por version definitiva $link

if(isset($_GET["type"])){

	//Accesos desde navegador

	require 'data/sql_app.php';

	switch ($_GET["type"])
	{
		case "p":
			pag_verificar_pass();
			logConnection("Cambio contraseña");
			break;
		case "p2":
			pag_verificar_pass_db();
			break;

		case "at":
			envio_validacion();
			logConnection("Cambio contrasena y aceptacion terminos legales");
			break;


		default:
			Error();
	}
}
else if ( /* isset($_POST['str']) &&*/ ( isset($_GET["signin"]) || isset($_GET["request"]) || isset($_GET["signout"]) ) )
{
	require 'data/sql_app.php';
	if(isset($_GET['signin'])){

		switch ($_GET["signin"])
		{
				case "1":
					post_login();
					logConnection("Consulta Login");
					break;		
				
                                case "2":
                                        restart_pass();
                                        logConnection("Restaura pass");
                                        break;	

		}
	}elseif(isset($_GET['signout'])){
            switch ($_GET["signout"])
		{
                    case "1":
                        post_logout();
                        //logConnection("Consulta Login");
                        break;
		}
        }
	else if(isset($_GET['request'])){

		switch ($_GET["request"])
		{
			case "1":
				request_autorizaciones_activas();
				logConnection("Consulta puntos de recarga");
				break;
			case "2":
				post_codigo_autorizacion();
				logConnection("Consulta puntos de recarga");
				break;
                            
			default:
				Error();
				break;
		}
	}
	else{
		Error();
	}
}
else{
	Error();
}



/*************************************************************************/
/*******************			Request				**********************/
/*************************************************************************/

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

			if(!empty($userData[13]))
				$linea['email'] = $userData[13];
			else
				$linea['email'] = "";
			
			$fichero = fopen("e:/logs/login_con_token.csv", 'a');
			fwrite($fichero, date("Y-m-d H:i:s") .";"."usuario ".$linea['idUsuario']."\r\n");
			fclose($fichero);
                        
                        //si existe variable post tokenMessaging y es distinta de la guardada en bbdd se actualiza
                        /*if(isset($_POST['tokenmessaging'])){
                            
                            $token = $_POST['tokenmessaging'];
                            
                            $id_movil = $linea['idUsuario'];
                            $sql->save_token_movil($token, $id_movil);
                        }*/
                        
			echo jsonDecodeArray($linea);
		}
		else{
                    if(isset($pass))
			echo ErrorLogin();
		else
			echo ErrorLoginNoPass();
                }

	}
	else{
		Error();
	}
        
}

function post_logout(){
    $sql = new sql_app();
    
    if(isset($_POST['user']) && isset($_POST['tokenmessaging'])){ 
        $id_usuario = $_POST['user'];
        $token = $_POST['tokenmessaging'];
        $sql->delete_token_movil($token, $id_usuario);
        echo "1";
    }else{
        echo Error();
    }
}

function request_autorizaciones_activas(){

	if( isset($_POST['id_usuario'])){

            $sql = new sql_app();
            
            $autorizaciones = $sql->get_authorizations($_POST['id_usuario']);
            
            $resultado['autorizacion'] = array();
            while ($row = $autorizaciones->fetch()){
                $resultado['autorizacion'][]=array('id' => (int)$row[0], 'plaza'=> $row[1], 'fecha'=> $row[2], 'email'=> $row[3]);
            }

            if(count($resultado)>0)
                    echo jsonDecodeArray( $resultado );
            else
                    echo "{}";
	}
	else
		echo Error();
}

function post_codigo_autorizacion(){

	if(isset($_POST['id_autorizacion']) && isset($_POST['id_individuo']) && isset($_POST['codigo'])){

		$sql = new sql_app();
                
		$result = $sql->comprueba_autorizacion($_POST['id_autorizacion'], $_POST['id_individuo'], $_POST['codigo']);

		if($result[2]>0){

                    require_once("C:\inetpub\wwwroot\TFM\msr_ev/Servicio/16/remoteAgent.php");  
        
                    $id_plaza = $result[0];
                    //if($result[1]==="99999999"){
                        //$id_individuo=$result[0];
                        //$mysql->start_transaction_empty_id_tag($id_individuo, $id_plaza);
                    //}
                    $ret=remoteStart($id_plaza, $result[1], 1);
                    
                
                    $sql->update_autorizacion($_POST['id_autorizacion']);

                    echo 1;
		}
		else{
                    echo Error();
                }

	}
	else{
		Error();
	}
        
}

function restart_pass(){

	if(isset($_POST['user'])){
		echo RestartPass($_POST['user'], $_POST['org']);
	}
	else
		Error();
}


/**** AUX****/
function RestartPass($user, $organizacion){

	$sqlPDO = new sql_app();

	$sqlPDO->connectPDO();
	$result = $sqlPDO->comprueba_mail($user, $organizacion);
	if($result->rowCount() !== 0){

		$userData = $result->fetch();
		$idIndividuo = $userData[0];
		$nombre = $userData[1];
		$apellidos = $userData[2];
		$email = $userData[3];
		$clave = GetClave();
		//Actualizamos el individuo con la clave:
		$existeLogin = $sqlPDO->set_clave_pass($idIndividuo, $clave);

		//Enviamos el email
		//EmailContrasena($idIndividuo, $nombre, $apellidos, 'jose.sanchez@o2e.es', $clave);
		EmailContrasena($idIndividuo, $nombre, $apellidos, $email, $clave);


		return "1";
	}
	else{

		return ErrorLogin();
	}
}



/*************************************************************************/
/*****			Paginas web para entrar con el navegador		*********/
/*************************************************************************/

//Pagina para cambio de contraseña
function pag_verificar_pass(){

	if(isset($_GET['id']) && isset($_GET['k'])){

		$sql = new sql_app();

		$result = $sql->comprueba_clave_login($_GET['id'], $_GET['k']);
		if($result->rowCount()!=0){

			require 'com_includes.php';

			Cabecera("Recuperaci?n contrase?a", '');
?>
			<h1>Restaurar contrase&ntilde;a</h1>
			<form class="filter" action="<?php $_SERVER['PHP_SELF'] ?>?type=p2" method="post">

				<div class="row">
					<label class="descripcion">Contrase&ntilde;a</label>
					<input type="password" name="pass1" maxlength="16" title="Contrase?a 1" />
				</div>

				<div class="row">
					<label class="descripcion">Repita contrase&ntilde;a</label>
					<input type="password" name="pass2" maxlength="16" title="Contrase?a 2" />
				</div>

				<div class="row">
					<label class="descripcion">&nbsp;</label>
					<input type="submit" value="Grabar" name="filter" class="bluebutton" />
				</div>

				<input type='hidden' name='id' value='<?php echo $_GET['id']; ?>' />
				<input type='hidden' name='k' value='<?php echo $_GET['k']; ?>' />
			</form>
<?php
			Pie();
		}
		else{
			PaginaInfo("Error");
		}



	}
	else{
		Error();
	}
}


function pag_verificar_pass_db(){

	if(isset($_POST['id']) && isset($_POST['pass1']) && isset($_POST['pass2'])){
		$sql = new sql_app();

		$result = $sql->comprueba_clave_login($_POST['id'], $_POST['k']);

		if($result->rowCount()!=0){
			$sql->set_pass_usuario($_POST['id'], hash('sha384',$_POST['pass1']."o2e"));

			//Reseteamos la clave
			$sql->set_clave_pass($_POST['id'], '');

			PaginaInfo("Contrase&ntilde;a modificada");

		}else{
			PaginaInfo("Error");
		}

	}
	else{
		Error();
	}

}


/*************************************************************************/
/*******************			Emails					******************/
/*************************************************************************/


function EmailContrasena($idIndividuo, $nombre, $apellidos, $dir, $clave){

	require_once 'com_includes_mail.php';

	require_once('mail/class.phpmailer.php');

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

	$mail->SetFrom('noreply@edpmoveon.com', 'EDP movilidad sostenible');

	//Asunto
	$mail->Subject = "Restauración contraseña";

	$nombre = utf8_decode(utf8_decode($nombre));
	$body = "";//$body =CabeceraCorreo("Restauración contraseña");//Se ha anulado la cabecera porque ya no se precisa imagen antes del texto
	$body .= '
			<div style="width:80% !important; margin:auto !important">
				<p>Hola, '.$nombre.'.</p>
				<p>Hemos recibido tu solitud. Pulsa el siguiente enlace para <i><a href="http://edpmoveon.com/moveox/post.php?type=p&id='.$idIndividuo.'&k='.$clave.'"> restaurar tu contraseña</a></i>.</p>
				<br/>
				<p>Un saludo.</p>
				<img src="'.__DIR__.'\images\EDP_string_logo.jpg"  width="64px" height="50px"><br/>
				Plaza del Fresno, 2
				<br/>
				33007 &#45; Oviedo
				<br/>
				<a href="www.edpmoveon.es" target="_top">www.edpmoveon.es</a>
				<br/>
				<p style="font-size:smaller;">Si no has solicitado el alta en este servicio, ignora este correo electrónico.</p>
				<br/><br/>


				<p>Hi '.$nombre.',</p>
				<p>We have received your request. Please click the following link to <i><a href="http://edpmoveon.com/moveox/post.php?type=p&id='.$idIndividuo.'&k='.$clave.'"> restore your password</a></i>.</p>
				<br/>
				<p>Best regards,</p>
				<img src="'.__DIR__.'\images\EDP_string_logo.jpg"  width="64px" height="50px"><br/>
				Plaza del Fresno, 2
				<br/>
				33007 &#45; Oviedo
				<br/>
				<a href="www.edpmoveon.es" target="_top">www.edpmoveon.es</a>
				<br/>
				<p style="font-size:smaller;">If you have not requested to register for this service, ignore this email.</p>
			</div>
			';

	$body .= PieCorreo();

	$mail->MsgHTML($body);

	$mail->AddAddress($dir);


	if($mail->Send())
		return true;
	else
		return false;

}



/*************************************************************************/
/*******************			Auxiliares				******************/
/*************************************************************************/

function tienePermiso($ids_cargador, $ids_permisos){//Se la invoca en request_charge_points y es IDENTICA a la definida en msr/api_mapa.php la cual se invoca en mapa_cargadores
	//NOTA: Cualquier cambio en esta función debería reflejarse en su homónima en post


	//Comprobamos en el array de ids_permisos del individuo si el cargador (definido por sus ids) tiene permiso.
	//NOTA: Si se obtiene una respuesta afirmativa no es necesario seguir recorriendo el array

	$permiso = false;

	if(empty($ids_permisos)){
		return false;
	}

	for ($i=0; $i<sizeof($ids_permisos); $i++){//recorro todos los permisos hasta localizar uno que cumpla
		if(
				(  ($ids_cargador['id_sensor'] 		 	== $ids_permisos[$i]['id_sensor']			) || empty($ids_permisos[$i]['id_sensor'])			)
			&&  (  ($ids_cargador['id_ubicacion'] 	 	== $ids_permisos[$i]['id_ubicacion']		) || empty($ids_permisos[$i]['id_ubicacion'])		)
			&&  (  ($ids_cargador['id_superubicacion'] 	== $ids_permisos[$i]['id_superubicacion']	) || empty($ids_permisos[$i]['id_superubicacion'])	)
			&&  (  ($ids_cargador['id_hiperubicacion'] 	== $ids_permisos[$i]['id_hiperubicacion']	) || empty($ids_permisos[$i]['id_hiperubicacion'])	)
			&&  (  ($ids_cargador['id_grupocargadores']	== $ids_permisos[$i]['id_grupocargadores']	) || empty($ids_permisos[$i]['id_grupocargadores']) )
			&&  (  ($ids_cargador['id_superCPO']            == $ids_permisos[$i]['id_superCPO']             ) || empty($ids_permisos[$i]['id_superCPO']) )
		){
			$permiso = true;
			break;
		}
	}

	return !empty($permiso) ? true : false;
}

function logConnection($request){

	$fp = fopen('log.txt', 'a');
	$string = date('Y-m-d H:i:s').' - '.$request.PHP_EOL;
	fwrite($fp, $string );
	fclose($fp);

}


function logIncidencia($usuario, $cargador, $incidencia, $comentario){

	$fp = fopen('logIncidencias.txt', 'a');
	$string = date('Y-m-d H:i:s').' - '.$cargador.PHP_EOL;
	$string .='"Usuario: - '.$usuario.PHP_EOL;
	$string .='"Incidencia: - '.$incidencia.PHP_EOL;
	$string .='"Comentario: - '.$comentario.PHP_EOL;
	$string .= PHP_EOL;
	fwrite($fp, $string );
	fclose($fp);

}

function Error(){
	echo "-1";
}

function Error_version(){
    return 'Error Version';
}

function ErrorLogin(){
	return "-2";
}

function ErrorLoginNoPass(){
	return "-3";
}

function ErrorReserva(){
	echo "-2";
}

function Error400(){
	header("HTTP/1.0 400");
	echo "-1";
}

function ErrorPagoBono(){
	return "-4";
}

function GetClave() {

	$todos="12345678901234567890ABCDEFGHIJQLMNOPQRSTUVWXYZ";
	$aleatorio=$todos[rand(0,45)].$todos[rand(0,45)].$todos[rand(0,45)].$todos[rand(0,45)].$todos[rand(0,45)].$todos[rand(0,45)].$todos[rand(0,45)].$todos[rand(0,45)];

	return $aleatorio;
}



function PaginaInfo($texto){

	require_once 'com_includes.php';

	Cabecera("Titulo", '');

	echo "<p>".$texto."</p>";

	Pie();
}


function dia_de_semana_post($dias){
    $dias_text="";
    if(strpos($dias, "1"))
        $dias_text .= "L, ";
    if(strpos($dias, "2"))
        $dias_text .= "M, ";
    if(strpos($dias, "3"))
        $dias_text .= "X, ";
    if(strpos($dias, "4"))
        $dias_text .= "J, ";
    if(strpos($dias, "5"))
        $dias_text .= "V, ";
    if(strpos($dias, "6"))
        $dias_text .= "S, ";
    if(strpos($dias, "0"))
        $dias_text .= "D, ";
    if($dias_text=="L, M, X, J, V, S, D, ")
        return "";
    else
        return substr($dias_text, 0, -2);
}

function utf8_check_and_encode($str) {
	//Si no es un texto utf8 valido entonces codifica el texto a utf8
	$utf8_str = !mb_detect_encoding($str, 'UTF-8', true) ? utf8_encode($str) : $str;
	return $utf8_str;
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