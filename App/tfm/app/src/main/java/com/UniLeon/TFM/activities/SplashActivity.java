package com.UniLeon.TFM.activities;

import android.content.Intent;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;

import com.UniLeon.TFM.Utils.RegistrosPrograma;
import com.UniLeon.TFM.R;

//Pantalla inicial (splash) de la app en la que se ve el logo de la Universidad de León y un logotipo de carga
//En esta pantalla se inicia la autorización y se declara el servidor al que se conectará.
//Finalmente abre la pantalla de acceso (LoginActivity)

public class SplashActivity extends AppCompatActivity {

    private final int SPLASH_SCREEN_DELAY = 2000;

    RegistrosPrograma registroPrograma;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getSupportActionBar().hide();
        setContentView(R.layout.activity_splash);

        registroPrograma = new RegistrosPrograma(this);

        //Inicializamos servidor:
        registroPrograma.setServidor("http://192.168.0.197/TFM/app.php");
        registroPrograma.setAutorizaciones("{\"autorizacion\":[]}");


        //Inicia la pantalla de acceso
            Thread timerTread = new Thread(){
                public void run(){
                    try{
                        sleep(SPLASH_SCREEN_DELAY);
                    }
                    catch (InterruptedException e){
                        e.printStackTrace();
                    } finally {
                        Intent intent = new Intent(SplashActivity.this, LoginActivity.class);
                        startActivity(intent);
                        finish();
                    }
                }
            };
            timerTread.start();

    }





}
