package com.UniLeon.TFM.Asynctask;

import android.app.ProgressDialog;
import android.content.Context;
import android.content.Intent;
import android.os.AsyncTask;

import com.UniLeon.TFM.Objects.Usuario;
import com.UniLeon.TFM.Utils.ACTION;
import com.UniLeon.TFM.Utils.RegistrosPrograma;
import com.UniLeon.TFM.activities.LoginActivity;

import org.apache.http.HttpResponse;
import org.apache.http.HttpStatus;
import org.apache.http.NameValuePair;
import org.apache.http.client.HttpClient;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;
import org.apache.http.params.BasicHttpParams;
import org.apache.http.params.HttpConnectionParams;
import org.apache.http.params.HttpParams;
import org.apache.http.util.EntityUtils;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;


public class RequestLogin extends AsyncTask<String, Void , Usuario> {

	private Intent broadcastIntent;
	
	Context ctx;
	RegistrosPrograma registroPrograma;
	String user;
	String pass;
	boolean guardaUsuario;
	private final ProgressDialog dialog;
	
	
	public RequestLogin(Context ctx, String user, String pass, boolean guardaUsuario){
		this.ctx = ctx;
		registroPrograma = new RegistrosPrograma(ctx);
		this.user = user;
		this.pass = pass;
		this.guardaUsuario = guardaUsuario;
		dialog = new ProgressDialog(ctx);
	}

	@Override
	protected void onPreExecute(){
		this.dialog.setMessage("Conectando");
		this.dialog.show();
	}
	
	
	@Override
	protected void onPostExecute(Usuario result) {
		this.dialog.dismiss();	

		LoginActivity loginAct = (LoginActivity) ctx;
		loginAct.user = result;
		if(result!=null){
			broadcastIntent = new Intent();
			broadcastIntent.setAction(ACTION.OK_LOGIN);
			broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
			ctx.sendBroadcast(broadcastIntent);
		}

	}
	
	@Override
	protected Usuario doInBackground(String... params) {

		HttpParams httpParameters = new BasicHttpParams();
		HttpConnectionParams.setConnectionTimeout(httpParameters, 3000);
		HttpConnectionParams.setSoTimeout(httpParameters, 5000);

		HttpClient httpclient = new DefaultHttpClient(httpParameters);
		HttpPost httppost = new HttpPost(registroPrograma.getServidor()+"/app.php?signin=1");

		String results = "";

	    try {
	    		    	
	        // Add your data
	        List<NameValuePair> nameValuePairs = new ArrayList<NameValuePair>(4);
	        nameValuePairs.add(new BasicNameValuePair("user", user));
	        nameValuePairs.add(new BasicNameValuePair("pass", pass));
	        nameValuePairs.add(new BasicNameValuePair("str", "p0Thpla23jkl5hvB"));
	        httppost.setEntity(new UrlEncodedFormEntity(nameValuePairs));
	        
	        // Execute HTTP Post Request
	        HttpResponse response = httpclient.execute(httppost);

		    response.getAllHeaders();
	        response.getEntity();
            
	        if(response.getStatusLine().getStatusCode() == HttpStatus.SC_OK)
	        {
	            results = EntityUtils.toString(response.getEntity());
	    		
	            if(!results.equals("-1") && !results.equals("-2")){
	            	
	            	try{
	            		//Parseamos las respuesta
	            		JSONObject jsonChildNode =new JSONObject( results );


						Usuario usuario = new Usuario(jsonChildNode.optInt("idUsuario"),
														jsonChildNode.optString("nombre"),
														jsonChildNode.optString("apellidos"),
														jsonChildNode.optString("email"));


	            		return usuario;
	        		}
	        		catch (JSONException e){
	        			//Fallo en la sincronizacion
	        			broadcastIntent = new Intent();
	        			broadcastIntent.setAction(ACTION.FALLO_INTERNET);
	        			broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
	        			ctx.sendBroadcast(broadcastIntent);	
	        		}
	            	
        			
	        	}
	            else if(results.equals("-2")){
	            	//Password incorrecto
	            	broadcastIntent = new Intent();
        			broadcastIntent.setAction(ACTION.NOK_LOGIN);
        			broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
        			ctx.sendBroadcast(broadcastIntent);
    	            return null;
	            }
	        }
	        //Fallo en la sincronizacion
        	broadcastIntent = new Intent();
			broadcastIntent.setAction(ACTION.FALLO_INTERNET);
			broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
			ctx.sendBroadcast(broadcastIntent);
	    } 
	    catch( Exception e){
        	broadcastIntent = new Intent();
			broadcastIntent.setAction(ACTION.FALLO_INTERNET);
			broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
			ctx.sendBroadcast(broadcastIntent);
	    }   
		return null;
	}
	
	
	
}
