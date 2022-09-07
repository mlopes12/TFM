package com.UniLeon.TFM.Asynctask;

import android.content.Context;
import android.content.Intent;
import android.os.AsyncTask;

import com.UniLeon.TFM.Utils.ACTION;
import com.UniLeon.TFM.Utils.RegistrosPrograma;
import com.UniLeon.TFM.activities.MainActivity;

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


public class GetAuthorizations extends AsyncTask<String, Void , String> {

	private Intent broadcastIntent;

	Context ctx;
	RegistrosPrograma registroPrograma;


	public GetAuthorizations(Context ctx){
		this.ctx = ctx;
		registroPrograma = new RegistrosPrograma(ctx);
	}

	@Override
	protected void onPreExecute(){
	}


	@Override
	protected void onPostExecute(String result) {

		MainActivity mainAct = (MainActivity) ctx;
		mainAct.auths = result;
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
		HttpPost httppost = new HttpPost(registroPrograma.getServidor()+"/app.php?request=1");

		String results = "";

		try {

			// Add your data
			List<NameValuePair> nameValuePairs = new ArrayList<NameValuePair>(4);
			nameValuePairs.add(new BasicNameValuePair("id_usuario", Integer.toString(registroPrograma.getIdUsuario())));
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

					//try{
						//Parseamos las respuesta
						//JSONObject jsonChildNode =new JSONObject( results );


						broadcastIntent = new Intent();
						broadcastIntent.setAction(ACTION.SINCRO_FINALIZADA);
						broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
						ctx.sendBroadcast(broadcastIntent);
						return results;
					/*}
					catch (JSONException e){
						//Fallo en la sincronizacion
						broadcastIntent = new Intent();
						broadcastIntent.setAction(ACTION.FALLO_INTERNET);
						broadcastIntent.addCategory(Intent.CATEGORY_DEFAULT);
						ctx.sendBroadcast(broadcastIntent);
					}*/


				}
				else{
					//Password incorrecto
					broadcastIntent = new Intent();
					broadcastIntent.setAction(ACTION.SINCRONIZACION_FALLIDA);
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
