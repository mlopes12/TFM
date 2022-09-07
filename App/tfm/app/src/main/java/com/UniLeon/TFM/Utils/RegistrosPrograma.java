package com.UniLeon.TFM.Utils;

import android.content.Context;
import android.content.SharedPreferences;

public class RegistrosPrograma {

	
	//Archivo de configuracion
	private final String SHARED_PREFS_FILE = "o2ePrefs";
	
	//Claves XML
	private final String KEY_SERVIDOR = "servidor";

	private final String KEY_DEVICE= "device";

	private final String KEY_SINCRO_PERIODO = "sincro_periodo";

	//CONTEXTO
	private Context mContext;
	
	
	public RegistrosPrograma(Context context){
		mContext = context;
	}
	
	
	private SharedPreferences getSettings(){
		return mContext.getSharedPreferences(SHARED_PREFS_FILE, 0);
	}
	
	
	public String getServidor(){
		return getSettings().getString(KEY_SERVIDOR, null);
	}
	
	public void setServidor(String servidor){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putString(KEY_SERVIDOR, servidor);
		editor.commit();
	}

	public String getDevice(){
		return getSettings().getString(KEY_DEVICE, null);
	}

	public void setDeviceNumber(String numberDevice){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putString(KEY_DEVICE, numberDevice);
		editor.commit();
	}

	public int getPeriodoSincronizacion(){
		return getSettings().getInt(KEY_SINCRO_PERIODO, 60);
	}

	public String getHoraServidor() {
		String a= getSettings().getString("HORA_SERVIDOR", "");
		return a;
	}

	public void setHoraServidor (String hora) {
		SharedPreferences.Editor editor = getSettings().edit();
		editor.putString("HORA_SERVIDOR", hora);
		editor.commit();
	}


	//user


	public int getIdUsuario(){
		return getSettings().getInt("idUsuario", -1);
	}

	public void setIdUsuario(int idUsuario){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putInt("idUsuario", idUsuario);
		editor.commit();
	}


	public String getNombre(){
		return getSettings().getString("nombre", null);
	}

	public void setNombre(String Nombre){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putString("nombre", Nombre);
		editor.commit();
	}


	public String getApellidos(){
		return getSettings().getString("apellidos", null);
	}

	public void setApellidos(String apellidos){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putString("apellidos", apellidos);
		editor.commit();
	}


	public String getEmail(){
		return getSettings().getString("email", null);
	}

	public void setEmail(String email){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putString("email", email);
		editor.commit();
	}


	public String getAutorizaciones(){
		return getSettings().getString("autorizaciones", null);
	}

	public void setAutorizaciones(String autorizaciones){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putString("autorizaciones", autorizaciones);
		editor.commit();
	}


	public Integer getIdAAutorizar(){
		return getSettings().getInt("id_autorizacion", 0);
	}

	public void setIdAAutorizar(Integer id_autorizacion){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putInt("id_autorizacion", id_autorizacion);
		editor.commit();
	}

	public String getPlazaAAutorizar(){
		return getSettings().getString("plaza", null);
	}

	public void setPlazaAAutorizar(String plaza){
		SharedPreferences.Editor editor=getSettings().edit();
		editor.putString("plaza", plaza);
		editor.commit();
	}

}
