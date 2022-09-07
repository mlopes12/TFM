package com.UniLeon.TFM.Objects;

public class Usuario {

	int id_usuario;
	String nombre;
	String apellidos;
	String email;

	public Usuario(){
		super();
	}

	public Usuario(int id_usuario, String nombre, String apellidos, String email) {
		super();
		this.id_usuario = id_usuario;
		this.nombre = nombre;
		this.apellidos = apellidos;
		this.email = email;
	}
	
	public int getIdUsuario() {
		return id_usuario;
	}
		
	public String getNombre() {
		return nombre;
	}

	public String getApellidos() {
		return apellidos;
	}

	public String getEmail() {
		return email;
	}



}
