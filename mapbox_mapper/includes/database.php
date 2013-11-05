<?php
    
    function mapper_data_tables_install(){
        $table_version = MY_PLUGIN_VERSION;
        
        $t = "mapthemes";
        
        $sql = "id int NOT NULL AUTO_INCREMENT,
        name VARCHAR(55) DEFAULT ''  NOT NULL,
        UNIQUE KEY id (id)";
        
        create_sql($t, $sql);
        
        $t = "mapsubthemes";
        
        $sql = "id int NOT NULL AUTO_INCREMENT,
        name VARCHAR(55) DEFAULT ''  NOT NULL,
        theme_id int NOT NULL,
        UNIQUE KEY id (id)";
        
        create_sql($t, $sql);
        
        $t = "maps";
        
        $sql = "id int NOT NULL AUTO_INCREMENT,
        name VARCHAR(55) DEFAULT ''  NOT NULL,
        description TEXT DEFAULT '',
        subtheme_id int NOT NULL,
        publ_id int NOT NULL,
        UNIQUE KEY id (id)";
        
        create_sql($t, $sql);
        
        $t = "maplayers";
        
        $sql = "id int NOT NULL AUTO_INCREMENT,
        name VARCHAR(55) DEFAULT ''  NOT NULL,
        description TEXT DEFAULT '',
        mapbox_id VARCHAR(55) DEFAULT '' NOT NULL,
        base BOOLEAN NOT NULL DEFAULT 0,
        date DATE,
        UNIQUE KEY id (id)";
        
        create_sql($t, $sql);
        
        $t = "mappublishing";
        
        $sql = "id int NOT NULL AUTO_INCREMENT,
        name VARCHAR(55) DEFAULT ''  NOT NULL,
        UNIQUE KEY id (id)";
        
        create_sql($t, $sql);
        
        $t = "map_rel_layer";
        
        $sql = "map_id int NOT NULL,
        lay_id int NOT NULL,
        PRIMARY KEY (map_id, lay_id)";
        
        create_sql($t, $sql);
        insert_data();
        
    }
    
    function create_sql($t, $sql){
        global $wpdb;
        
        $table_name = $wpdb->prefix . $t;
        $sql_create = "CREATE TABLE IF NOT EXISTS " .$table_name ." ( ".$sql." );";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_create );
    }
    
    function drop_tables(){
        
        global $wpdb;
        
        $tables = array("mapthemes", "mapsubthemes", "maps", "maplayers", "mappublishing", "map_rel_layer");
        foreach($tables as $t){
            $table_name = $wpdb->prefix . $t;
            $sql = "DROP TABLE IF EXISTS " .$table_name.";";
            $wpdb->query($sql);
        }
        
    }
    
    function insert_data(){
        global $wpdb;
        $wpdb->insert("wp_mappublishing", array("name" => "simple"));
        $wpdb->insert("wp_mappublishing", array("name" => "opacity"));
        $wpdb->insert("wp_mappublishing", array("name" => "timeslider"));
        $wpdb->insert("wp_maplayers", array("name" => "openstreetmap", "mapbox_id"=>"openstreetmap", "base"=>1));
    }

?>