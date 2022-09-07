package com.UniLeon.TFM.activities;
import android.app.Activity;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Bundle;
import android.support.v7.app.AppCompatActivity;
import android.view.LayoutInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import com.UniLeon.TFM.Asynctask.GetAuthorizations;
import com.UniLeon.TFM.Objects.Autorizacion;
import com.UniLeon.TFM.R;
import com.UniLeon.TFM.Utils.ACTION;
import com.UniLeon.TFM.Utils.RegistrosPrograma;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.Timer;
import java.util.TimerTask;

//en esta pantalla se listan las peticiones de inicios de recarga para poder acceder a ellas a introducir el código de autorización 2FA
public class MainActivity extends AppCompatActivity{

    Context ctx;
    RegistrosPrograma registroPrograma;
    public String auths;


    static ListView listView;
    ImageView enter;
    static ListViewAdapter adapter;
    static ArrayList<Integer> id_auths;
    static ArrayList<String> items;
    static ArrayList<String> plazas;
    static ArrayList<String> emails;
    static Context context;


    private IntentFilter filtro, filtro1, filtro2, filtro3;

    //SINCRO
    ReceptorBroadcast receptorBroadcast;

    //Temporizador
    Timer myTimer;
    boolean _sincronizando=false;
    int _tiempoSincronizacion = 15000;




    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);


        ctx = this;
        registroPrograma = new RegistrosPrograma(ctx);

        //protección por si de alguna forma la app llega aquí sin autorización
        if(registroPrograma.getIdUsuario() == -1){
            Intent intent = new Intent(MainActivity.this, LoginActivity.class);
            startActivity(intent);
            finish();
        }

        setContentView(R.layout.activity_main);
        getSupportActionBar().setDisplayShowHomeEnabled(true);

        //genera el temporizador para el Asynktask que realiza peticiones al webservice para pedir las peticiones de recarga
        myTimer = new Timer();
        myTimer.schedule(new TimerTask() {
            @Override
            public void run() {
                TimerMethod();
            }

        }, 0, _tiempoSincronizacion);

        //Ponemos a la escucha los filtros: Se ponen en onCreate ya que deben funcionar aunque no está activa
        filtro = new IntentFilter(ACTION.SINCRO_CONTINUA);
        filtro.addCategory(Intent.CATEGORY_DEFAULT);

        filtro1 = new IntentFilter(ACTION.SINCRONIZACION_FALLIDA);
        filtro1.addCategory(Intent.CATEGORY_DEFAULT);

        filtro2 = new IntentFilter(ACTION.FALLO_INTERNET);
        filtro2.addCategory(Intent.CATEGORY_DEFAULT);

        filtro3 = new IntentFilter(ACTION.SINCRO_FINALIZADA);
        filtro3.addCategory(Intent.CATEGORY_DEFAULT);

        receptorBroadcast = new ReceptorBroadcast();

        registerReceiver(receptorBroadcast, filtro);
        registerReceiver(receptorBroadcast, filtro1);
        registerReceiver(receptorBroadcast, filtro2);
        registerReceiver(receptorBroadcast, filtro3);


        //se crea lo necesario para mostrar la pantalla
        setContentView(R.layout.activity_main);
        listView = findViewById(R.id.list);
        enter = findViewById(R.id.add);
        context = getApplicationContext();

        items = new ArrayList<>();
        id_auths = new ArrayList<>();
        plazas = new ArrayList<>();
        emails = new ArrayList<>();

        //crea la lista en la que se cargarán de forma dinámica las peticiones
        listView.setLongClickable(true);
        adapter = new ListViewAdapter(this, items);
        listView.setAdapter(adapter);
        listView.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            //en caso de que se acceda a una petición se carga la pantalla de introducción del código
            public void onItemClick(AdapterView<?> parent, View view, int position, long id) {
                registroPrograma.setIdAAutorizar(id_auths.get(position));
                registroPrograma.setPlazaAAutorizar(plazas.get(position));
                registroPrograma.setEmail(emails.get(position));
                Intent intent = new Intent(MainActivity.this, CodigoActivity.class);
                startActivity(intent);
                finish();
            }
        });


    }


    @Override
    protected void onStop() {
        super.onStop();
    }


    @Override
    protected void onRestart() {
        super.onRestart();

    }

    //en caso de cerrar la app se cancelan las consultas por más autorizaciones de carga
    @Override
    protected void onDestroy() {
        super.onDestroy();
        try {
            this.unregisterReceiver(receptorBroadcast);
        }catch (Exception e){}
        if(myTimer!=null)
            myTimer.cancel();
    }

    //elimina un item de la lista cuando ya no viene del webservice
    public static void removeItem(int i) {
        int posicion = id_auths.indexOf(i);
        if(posicion != -1) {
            id_auths.remove(posicion);
            items.remove(posicion);
            plazas.remove(posicion);
            emails.remove(posicion);
            listView.setAdapter(adapter);
        }
    }

    //añade un item a la lista cuando no estaba en la lista y viene del webservice
    public static void addItem(String item, int id_auth, String plaza, String email) {
        id_auths.add(id_auth);
        items.add(item);
        plazas.add(plaza);
        emails.add(email);
        listView.setAdapter(adapter);
    }


    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        if(item.getItemId()==android.R.id.home)
            onBackPressed();
        return true;
    }



    //Receptor de las autorizaciones del webservice desde el Asynktask
    public class ReceptorBroadcast extends BroadcastReceiver {

        @Override
        public void onReceive(Context arg0, Intent intent){

            if(ACTION.SINCRO_CONTINUA.equals(intent.getAction()) ){

            }
            else{

                if( ACTION.SINCRO_FINALIZADA.equals(intent.getAction()) ){
                    ArrayList<Autorizacion> autorizaciones_existentes = new ArrayList<Autorizacion>();
                    ArrayList<Autorizacion> autorizaciones_nuevas = new ArrayList<Autorizacion>();
                    try {
                        //realiza todo el proceso de comprobación de si la autorización ya estaba en la lista
                        //elimina o guarda en la lista como procesa
                        String autorizaciones = registroPrograma.getAutorizaciones();
                        JSONObject jsonChildNode = null;
                        jsonChildNode = new JSONObject( autorizaciones );


                        JSONArray lista_autorizaciones = new JSONArray();
                        lista_autorizaciones = (JSONArray) jsonChildNode.get("autorizacion");

                        for (int i = 0; i < lista_autorizaciones.length(); i++) {
                            JSONObject autorizacion_simple =new JSONObject(  );
                            autorizacion_simple = (JSONObject) lista_autorizaciones.get(i);


                            Autorizacion autorizacion = new Autorizacion(autorizacion_simple.optInt("id"),
                                    autorizacion_simple.optString("plaza"),
                                    autorizacion_simple.optString("fecha"),
                                    autorizacion_simple.optString("email"));
                            autorizaciones_existentes.add(autorizacion);
                        }

                        autorizaciones = auths;
                        if(autorizaciones != null) {
                            jsonChildNode = new JSONObject(autorizaciones);


                            lista_autorizaciones = (JSONArray) jsonChildNode.get("autorizacion");

                            for (int i = 0; i < lista_autorizaciones.length(); i++) {
                                JSONObject autorizacion_simple = new JSONObject();
                                autorizacion_simple = (JSONObject) lista_autorizaciones.get(i);


                                Autorizacion autorizacion = new Autorizacion(autorizacion_simple.optInt("id"),
                                        autorizacion_simple.optString("plaza"),
                                        autorizacion_simple.optString("fecha"),
                                        autorizacion_simple.optString("email"));
                                autorizaciones_nuevas.add(autorizacion);
                            }
                            registroPrograma.setAutorizaciones(autorizaciones);

                            ArrayList<Autorizacion> autorizaciones_existentes_ = autorizaciones_existentes;
                            ArrayList<Autorizacion> autorizaciones_nuevas_ = autorizaciones_nuevas;

                            autorizaciones_existentes_.removeAll(autorizaciones_nuevas);
                            autorizaciones_nuevas_.removeAll(autorizaciones_existentes);


                            for (int i = 0; i < autorizaciones_nuevas_.size(); i++) {
                                addItem(autorizaciones_nuevas_.get(i).getPlaza() + " a fecha " + autorizaciones_nuevas_.get(i).getFecha(), autorizaciones_nuevas_.get(i).getId(), autorizaciones_nuevas_.get(i).getPlaza(), autorizaciones_nuevas_.get(i).getEmail());
                            }
                            for (int i = 0; i < autorizaciones_existentes_.size(); i++) {
                                removeItem(autorizaciones_existentes_.get(i).getId());

                            }
                        }




                    } catch (JSONException e) {
                        e.printStackTrace();
                    }

                }
                else if( ACTION.SINCRONIZACION_FALLIDA.equals(intent.getAction()) ){
                    Toast.makeText(getApplicationContext(), "Error de sincronización", Toast.LENGTH_LONG).show();
                }
                else if( ACTION.FALLO_INTERNET.equals(intent.getAction()) ) {
                    Toast.makeText(getApplicationContext(), "Error de conexión a Internet", Toast.LENGTH_LONG).show();
                }

                _sincronizando = false;
            }
        }
    }



    //Crea el Asynktask
    private void TimerMethod()
    {
        this.runOnUiThread(Timer_Tick);
    }

    private Runnable Timer_Tick = new Runnable() {
        public void run() {
            Sincronizar();
        }
    };

    public void Sincronizar(){
        _sincronizando = false;
        if(!_sincronizando){
            _sincronizando = true;

            GetAuthorizations dhr= new GetAuthorizations( ctx);
            dhr.execute("");
        }
    }



}




class ListViewAdapter extends ArrayAdapter<String> {
    ArrayList<String> list;
    Context context;

    // The ListViewAdapter Constructor
    // @param context: the Context from the MainActivity
    // @param items: The list of items in our Grocery List
    public ListViewAdapter(Context context, ArrayList<String> items) {
        super(context, R.layout.list_row, items);
        this.context = context;
        list = items;
    }

    // The method we override to provide our own layout for each View (row) in the ListView
    @Override
    public View getView(int position, View convertView, ViewGroup parent) {
        if (convertView == null) {
            LayoutInflater mInflater = (LayoutInflater) context.getSystemService(Activity.LAYOUT_INFLATER_SERVICE);
            convertView = mInflater.inflate(R.layout.list_row, null);
            TextView name = convertView.findViewById(R.id.name);

            name.setText(list.get(position));

        }
        return convertView;
    }

}

