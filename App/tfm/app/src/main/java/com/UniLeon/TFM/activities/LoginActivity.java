package com.UniLeon.TFM.activities;


import android.app.AlertDialog;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Bundle;
import android.support.v7.app.AppCompatActivity;
import android.view.MenuItem;
import android.view.View;
import android.widget.EditText;
import android.widget.Toast;

import com.UniLeon.TFM.Asynctask.RequestLogin;
import com.UniLeon.TFM.Objects.Usuario;
import com.UniLeon.TFM.R;
import com.UniLeon.TFM.Utils.ACTION;
import com.UniLeon.TFM.Utils.RegistrosPrograma;

import java.security.MessageDigest;

//En esta pantalla el usuario conecta con el WebService por primera vez para comprobar si la contraseña y email introducidos por el usuario están registrados en la plataforma

public class LoginActivity extends AppCompatActivity {

    Context _ctx;
    public Usuario user;
    EditText nombreEditText;
    EditText passEditText;
    ReceptorBroadcast receptorBroadcast;
    private IntentFilter filtro, filtro1, filtro2, filtro3;
    RegistrosPrograma registroPrograma;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        _ctx = this;
        registroPrograma = new RegistrosPrograma(_ctx);
        nombreEditText = (EditText) findViewById( R.id.editTextNombre );
        passEditText = (EditText) findViewById( R.id.editTextPassword );

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


    //Comprueba si hay email y contraseña introducidos al dar al boton "Iniciar"
    public void Iniciar(View view){

        //Los recoge y si hay valores correctos los envía al webservice para comprobarlos mediante un Asynctask llamado RequestLogin
        String nombre = nombreEditText.getText().toString();
        String pass = passEditText.getText().toString();
        if(nombre.equals("")){
            nombreEditText.setError("Campo obligatorio");
            nombreEditText.requestFocus();
            return;
        }else if(nombre.contains("'") || nombre.contains("\"") || nombre.contains("--")){
            nombreEditText.setError("Email inválido");
            nombreEditText.requestFocus();
            return;
        }
        else if(pass.equals("")){
            passEditText.setError("Campo obligatorio");
            passEditText.requestFocus();
            return;
        }
        else{

            try {
                pass = GetSHA(pass+"o2e");
            } catch (Exception e) { }
            //todo está correcto por lo que genera el asynctask y envía nombre y contraseña
            RequestLogin rl = new RequestLogin(_ctx, nombre, pass, true);
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
            //tras recibir la respuesta del webservice, si es correcto recoge los datos recibidos
            //los guarda en el registro para después iniciar la actividad con la lista de peticiones de inicio de recarga (MainActivity)
            //en caso contrario o da error si procede o dice que no son correctos los datos
            if( ACTION.OK_LOGIN.equals(intent.getAction()) ){
                registroPrograma = new RegistrosPrograma(ctx);
                registroPrograma.setIdUsuario(user.getIdUsuario());
                registroPrograma.setNombre(user.getNombre());
                registroPrograma.setApellidos(user.getApellidos());
                registroPrograma.setEmail(user.getEmail());
                LoginActivity.this.setResult(RESULT_OK);
                try {
                    unregisterReceiver(receptorBroadcast);
                } catch (Exception e) { }
                intent = new Intent(LoginActivity.this, MainActivity.class);
                startActivity(intent);
                finish();
            }
            else if( ACTION.NOK_LOGIN.equals(intent.getAction()) ){
                AlertDialog.Builder builder = new AlertDialog.Builder(_ctx);
                builder.setTitle("Atención!");
                builder.setMessage("Nombre de usuario o contraseña incorrectos!");
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

    //función para convertir la contraseña a SHA384, que es un algoritmo de hash
    //se utiliza para guardar de forma segura las contraseñas en la plataforma con la que interctúa esta app.
    public static String GetSHA(String cadena) throws Exception {

        MessageDigest md = MessageDigest.getInstance("SHA-384");
        byte[] b = md.digest(cadena.getBytes());

        int size = b.length;
        StringBuilder h = new StringBuilder(size);
        for (int i = 0; i < size; i++) {
            int u = b[i] & 255;
            if (u < 16)
                h.append("0").append(Integer.toHexString(u));
            else
                h.append(Integer.toHexString(u));
        }
        return h.toString();
    }




}
