package com.UniLeon.TFM.Utils;

public class ACTION {

	public static final String SINCRO_CONTINUA = "com.Unileon.TFM.SINCRONIZACION_CONTINUA";
	public static final String SINCRO_FINALIZADA = "com.Unileon.TFM.SINCRONIZACION_FINALIZADA";
	public static final String SINCRONIZACION_FALLIDA = "com.Unileon.TFM.SINCRONIZACION_FALLIDA";
	public static final String FALLO_INTERNET = "com.Unileon.TFM.FALLO_INTERNET";
	public static final String FALLO = "com.Unileon.TFM.FALLO";
	public static final String OK_LOGIN = "com.Unileon.TFM.OK_LOGIN";
	public static final String NOK_LOGIN = "com.Unileon.TFM.NOK_LOGIN";

	public static int PUNTO_SINCRO = 0;

	public static String GetEstadoSincro(){

		if(PUNTO_SINCRO==1)
			return "Individuos";
		else if(PUNTO_SINCRO==2)
			return "Máquinas";
		else if(PUNTO_SINCRO==3)
			return "Operaciones";
		else if(PUNTO_SINCRO==4)
			return "Materiales";
		else if(PUNTO_SINCRO==5)
			return "Inventarios";
		else if(PUNTO_SINCRO==6)
			return "Despieces";
		else return "";
	}
	
}

