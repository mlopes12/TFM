package com.UniLeon.TFM.Asynctask;

import android.app.ProgressDialog;
import android.content.Context;
import android.content.Intent;
import android.os.AsyncTask;

import com.UniLeon.TFM.Utils.ACTION;
import com.UniLeon.TFM.Utils.RegistrosPrograma;
import com.UniLeon.TFM.activities.CodigoActivity;

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

import java.util.ArrayList;
import java.util.List;


public class RequestCodigo extends AsyncTask<String, Void , String> {

	private Intent broadcastIntent;

	Context ctx;
	RegistrosPrograma registroPrograma;
	Integer id_autorizacion;
	Integer id_individuo;
	String codigo;
	private final ProgressDialog dialog;


	public RequestCodigo(Context ctx, int id_autorizacion, int id_individuo, String codigo){
		this.ctx = ctx;
		registroPrograma = new RegistrosPrograma(ctx);
		this.id_autorizacion = id_autorizacion;
		this.id_individuo = id_individuo;
		this.codigo = codigo;
		dialog = new ProgressDialog(ctx);
	}

	@Override
	protected void onPreExecute(){
	}
	
	
	@Override
	protected void onPostExecute(String result) {
		this.dialog.dismiss();	

		CodigoActivity loginAct = (CodigoActivity) ctx;
		loginAct.recibido = result;
		if(result!=null){
			broadcastIntent = new Intent();
			broadcastIntent.setAction(ACTION.OK_LOGIN);
			broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
			ctx.sendBroadcast(broadcastIntent);
		}

	}
	
	@Override
	protected String doInBackground(String... params) {

		HttpParams httpParameters = new BasicHttpParams();
		HttpConnectionParams.setConnectionTimeout(httpParameters, 3000);
		HttpConnectionParams.setSoTimeout(httpParameters, 5000);

		HttpClient httpclient = new DefaultHttpClient(httpParameters);
		HttpPost httppost = new HttpPost(registroPrograma.getServidor()+"/app.php?request=2");

		String results = "";

	    try {
	    		    	
	        // Add your data
	        List<NameValuePair> nameValuePairs = new ArrayList<NameValuePair>(4);
	        nameValuePairs.add(new BasicNameValuePair("id_autorizacion", Integer.toString(id_autorizacion)));
			nameValuePairs.add(new BasicNameValuePair("id_individuo", Integer.toString(id_individuo)));
			nameValuePairs.add(new BasicNameValuePair("codigo", codigo));
	        nameValuePairs.add(new BasicNameValuePair("str", "p0Thpla23jkl5hvB"));
	        httppost.setEntity(new UrlEncodedFormEntity(nameValuePairs));
	        
	        // Execute HTTP Post Request
	        HttpResponse response = httpclient.execute(httppost);

		    response.getAllHeaders();
	        response.getEntity();
            
	        if(response.getStatusLine().getStatusCode() == HttpStatus.SC_OK)
	        {
	            results = EntityUtils.toString(response.getEntity());
	    		
	            if(!results.equals("-1")){

					broadcastIntent = new Intent();
					broadcastIntent.setAction(ACTION.OK_LOGIN);
					broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
					ctx.sendBroadcast(broadcastIntent);
					return "";

        			
	        	}
	            else if(results.equals("-1")){
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
