<?php
class sql_base_mysql {
	
	
	
	public function connectPDO() {
		try{
			
			$this->link=new PDO('mysql:host=localhost;port=3306;dbname=TFM_charge', 'root', 'TFM-MLS-UniLeon');
			$this->link->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
		
		}
		catch(PDOException $e){
			echo "ERROR: " . $e->getMessage();
		} 
	}
	
		
}
?>