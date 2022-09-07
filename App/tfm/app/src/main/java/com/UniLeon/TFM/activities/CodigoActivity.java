package com.UniLeon.TFM.activities;


import android.app.AlertDialog;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Bundle;
import android.support.v7.app.AppCompatActivity;
import android.view.KeyEvent;
import android.view.MenuItem;
import android.view.View;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import com.UniLeon.TFM.Asynctask.RequestCodigo;
import com.UniLeon.TFM.R;
import com.UniLeon.TFM.Utils.ACTION;
import com.UniLeon.TFM.Utils.RegistrosPrograma;

public class CodigoActivity extends AppCompatActivity {

    Context _ctx;
    EditText nombreEditText;
    TextView passEditText;
    ReceptorBroadcast receptorBroadcast;
    private IntentFilter filtro, filtro1, filtro2, filtro3;
    RegistrosPrograma registroPrograma;
    public String recibido;
    public boolean finished = false;


    //se crea la pantalla en la cuál sale un texto con la información del email del usuario que trata de iniciar
    //y también de la plaza donde lo intenta
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_codigo);

        _ctx = this;
        registroPrograma = new RegistrosPrograma(_ctx);
        nombreEditText = (EditText) findViewById( R.id.codigo );
        passEditText = (TextView) findViewById( R.id.texto_informativo );
        passEditText.setText("Para iniciar la recarga en "+registroPrograma.getPlazaAAutorizar() +
                " introduzca el código que ha llegado a su correo electrónico " + registroPrograma.getEmail() );


        //Ponemos a la escucha los filtros: Se ponen en onCreate ya que deben funcionar aunque no esta activa
        filtro = new IntentFilter(ACTION.OK_LOGIN);
        filtro.addCategory(Intent.CATEGORY_DEFAULT);

        filtro1 =new IntentFilter(ACTION.NOK_LOGIN);
        filtro1.addCategory(Intent.CATEGORY_DEFAULT);

        filtro2 =new IntentFilter(ACTION.FALLO_INTERNET);
        filtro2.addCategory(Intent.CATEGORY_DEFAULT);

        filtro3 =new IntentFilter(ACTION.FALLO_INTERNET);
        filtro3.addCategory(Intent.CATEGORY_DEFAULT);

        receptorBroadcast = new ReceptorBroadcast();

        registerReceiver(receptorBroadcast, filtro);
        registerReceiver(receptorBroadcast, filtro1);
        registerReceiver(receptorBroadcast, filtro2);
        registerReceiver(receptorBroadcast, filtro3);

    }


    @Override
    public void onDestroy(){
        super.onDestroy();
        try {
            this.unregisterReceiver(receptorBroadcast);
        } catch (Exception e) { }
    }


    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        switch (item.getItemId()) {
            case android.R.id.home:
                finish();
                return true;
            default:
                return super.onOptionsItemSelected(item);
        }
    }

    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        // TODO Auto-generated method stub
        if (keyCode == event.KEYCODE_BACK) {
            Intent intent = new Intent(CodigoActivity.this, MainActivity.class);
            startActivity(intent);
            finish();
        }
        return super.onKeyDown(keyCode, event);
    }

    //si se introducen los cinco caracteres se consulta si son correctos mediante un Asyntask
    public void Iniciar(View view){

        String codigo = nombreEditText.getText().toString();
        if(codigo.length() != 5){
            nombreEditText.setError("Introduzca los cinco caracteres");
            nombreEditText.requestFocus();
            return;
        }
        else{

            RequestCodigo rl = new RequestCodigo(_ctx, registroPrograma.getIdAAutorizar(), registroPrograma.getIdUsuario(), codigo);
            rl.execute("");
        }

    }


    public void Menu(View v){
        finish();
    }



    /*******************************************************/
    /********   	 Receptor de broadcast  		********/
    /*******************************************************/

    private class ReceptorBroadcast extends BroadcastReceiver {

        @Override
        public void onReceive(Context ctx, Intent intent){
            //si es correcto, el webservice envía la orden de iniciar la recarga y responde a la app
            //en ese caso, la app cierra la pantalla y vuelve al listado de autorizaciones
            //si es incorrecto se informa de ello
            if( ACTION.OK_LOGIN.equals(intent.getAction()) ){
                if(!finished) {
                    intent = new Intent(CodigoActivity.this, MainActivity.class);
                    startActivity(intent);
                    finished = true;
                    finish();
                }
            }
            else if( ACTION.NOK_LOGIN.equals(intent.getAction()) ){
                AlertDialog.Builder builder = new AlertDialog.Builder(_ctx);
                builder.setTitle("Atención!");
                builder.setMessage("Código incorrecto!");
                builder.setPositiveButton("OK",null);
                builder.create();
                builder.show();
            }
            else if( ACTION.FALLO.equals(intent.getAction()) ){
                Toast.makeText(getApplicationContext(), "Error", Toast.LENGTH_LONG).show();
            }
            else if( ACTION.FALLO_INTERNET.equals(intent.getAction()) ) {
                Toast.makeText(getApplicationContext(), "Error de conexión a Internet", Toast.LENGTH_LONG).show();
            }

        }
    }




}
