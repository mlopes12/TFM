package com.UniLeon.TFM.Objects;

public class Autorizacion {

	int id;
	String plaza;
	String fecha;
	String email;

	public Autorizacion(){
		super();
	}

	public Autorizacion(int id, String plaza, String fecha, String email) {
		super();
		this.id = id;
		this.plaza = plaza;
		this.fecha = fecha;
		this.email = email;
	}
	
	public int getId() {
		return id;
	}

	public String getPlaza() {
		return plaza;
	}

	public String getFecha() {
		return fecha;
	}

	public String getEmail() {
		return email;
	}



}
