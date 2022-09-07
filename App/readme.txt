En esta carpeta se encuentra todo el código necesario para el funcionamiento de la parte de la autenticación de doble factor 2FA
La app está en la carpeta \TFM:
	·En \TFM\app\src\main\java\com\UniLeon\TFM\activities se encuentran los ficheros dónde se encuentra el código de las pantallas.
	·En \TFM\app\src\main\java\com\UniLeon\TFM\Asynktask las funciones a través de las que se interacúa con el Webservice.
	·En \TFM\app\src\main\java\com\UniLeon\TFM\Objects se encuentran los objetos que componen las Autorizaciones y los datos del Usuario.
	·En \TFM\app\src\main\java\com\UniLeon\TFM\Utils se encuentran los registros en los que se guarda información del servidor en el que se encuentra el webservice o del usuario.
	·En \TFM\app\src\main\res los recursos para construir el aspecto de las pantallas de la app.
El webservice en \webservice
	·En \webservice\app.php se encuentran las funciones que interactúan con la app y el resto del CSMS
	·En \webservice\data\sql_app.php se encuentran las funciones mediante las que app.php y, por tanto, la app y el resto del CSMS, interactúa con la BD.
	