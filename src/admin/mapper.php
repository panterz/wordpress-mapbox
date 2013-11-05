<?php
    
    function init_admin(){
        
        add_action('admin_menu', 'mapper_admin_actions');
    
        function mapper_admin_actions() {
            add_options_page("Mapbox publisher", "Mapbox publisher", 1, "Mapbox_publisher", "mapper_settings");
            //add_management_page("Mapbox publisher", "Mapbox publisher", 1, "Mapbox_publisher", );
            add_menu_page("Mapbox mapper", "Maps", "manage_options", "mapboxoptions", "map_page", "", 6);
            add_submenu_page("mapboxoptions", "Mapbox map", "Add map", "manage_options", "add-maps-submenu-page", "maps_addsubmenu_page_callback");
            
            add_submenu_page("mapboxoptions", "Mapbox category", "Categories", "manage_options", "categories-submenu-page", "categories_submenu_page_callback");
            add_submenu_page("mapboxoptions", "Mapbox category", "Add Category", "manage_options", "add-categories-submenu-page", "categories_addsubmenu_page_callback");
            
            add_submenu_page("mapboxoptions", "Mapbox subcategory", "Subcategories", "manage_options", "subcategories-submenu-page", "subcategories_submenu_page_callback");
            add_submenu_page("mapboxoptions", "Mapbox subcategory", "Add Subcategory", "manage_options", "add-subcategories-submenu-page", "subcategories_addsubmenu_page_callback");
            
            add_submenu_page("mapboxoptions", "Mapbox layer", "Layers", "manage_options", "layers-submenu-page", "layers_submenu_page_callback");
            add_submenu_page("mapboxoptions", "Mapbox layer", "Add Layer", "manage_options", "add-layers-submenu-page", "layers_addsubmenu_page_callback");
            register_admin_scripts();
        }
        
        function register_admin_scripts(){
            wp_register_style('jquery-ui', plugins_url('../css/jquery-ui-1.10.3.custom.min.css', __FILE__));
            wp_enqueue_style('jquery-ui');
            global $is_IE;
            if ( $is_IE ) {
                echo '<!--[if lt IE 8]>';
                echo "<link href='".plugins_url('../css/jquery-ui-1.10.3.custom.min.css', __FILE__)."' rel='stylesheet' >";
                echo '<![endif]-->';
            }
            wp_register_script("jquery-ui", plugins_url( "../js/lib/jquery-ui-1.10.3.custom.min.js", __FILE__), array(), "1.10.3", false);
            wp_enqueue_script("jquery-ui");
        }
    
    }
    
    /*******************SETTINGS******************************/
    
    function mapper_settings() {
        
        if($_POST['mpbx_hidden'] == 'Y') {
            //Form data sent
            $width = $_POST['map_width'];
            update_option('map_width', $width);
            
            $height = $_POST['map_height'];
            update_option('map_height', $height);

            echo '<div class="updated"><p><strong>'. _e('Options saved.' ).'</strong></p></div>';
        }else{
            $width = (get_option('map_width') != '') ? get_option('map_width') : '100%';
            $height = (get_option('map_height') != '') ? get_option('map_height') : '400px';
            $html = '</pre>
            <div class="wrap">
                <form action="'. str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'" method="post" name="options">
                    <h2>Select Your Settings</h2>' . wp_nonce_field('update-options') . '
                    <table class="form-table" width="100%" cellpadding="10">
                        <tbody>
                            <tr>
                                <td scope="row" align="left">
                                    <label>Map Width</label>
                                    <input type="text" name="map_width" value="' . $width . '" />
                                </td>
                            </tr>
                            <tr>
                                <td scope="row" align="left">
                                    <label>Map Height</label>
                                    <input type="text" name="map_height" value="' . $height . '" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <input type="hidden" name="mpbx_hidden" value="Y">  
                    <input type="submit" name="Submit" value="Update" />
                </form>
            </div><pre>';
            echo $html;
        }
    }

    
    /****************MAPS SECTION*****************************/
    
    function map_page(){
        global $wpdb;
        if($_GET['mode'] == 'delete'){
            $id = escape_var($_GET['cat_id']);
            $wpdb->delete('wp_maps', array('id'=>$id));
        }
        
        if($_POST['mpbx_hidden'] == 'U') {
            $id = escape_var($_GET['cat_id']);
            $wpdb->update('wp_maps', array('name' => escape_var($_POST["map_name"]),
                                           'description' => escape_var($_POST["description"]),
                                           'subtheme_id' => escape_var($_POST["subtheme_id"]),
                                           'publ_id' => escape_var($_POST["publ_id"])), array('id'=>$id));
            $results = $wpdb->get_results("Select a.lay_id, b.base from wp_map_rel_layer a, wp_maplayers b where a.map_id=$id and a.lay_id = b.id");
            update_map($results, $_POST['layer_id'], $_POST['blayer_id'], $id);
            create_maps_table();
        }else if($_GET['mode'] == 'edit'){
            $id = escape_var($_GET['cat_id']);
            $results = $wpdb->get_results("SELECT a.name, a.description, a.subtheme_id, b.name as subtheme, c.name as publ FROM wp_maps a, wp_mapsubthemes b, wp_mappublishing c
                                          WHERE a.id=$id and a.subtheme_id=b.id and a.publ_id=c.id");
            foreach($results as $res){
                $name = $res->name;
                $description = $res->description;
                $subtheme = $res->subtheme;
                $publ = $res->publ;
            }
            
            $results = $wpdb->get_results("SELECT a.id, a.name, a.base FROM wp_maplayers a, wp_map_rel_layer b
                                          WHERE b.map_id=$id and b.lay_id=a.id");
            map_form("U", "Edit map", array($name, $description, $subtheme, $publ, $results), 'Update');
        }else{
            create_maps_table();
        }
    }
    
    function update_map($results, $layers, $blayers, $id){
        if(count($results) < (count($layers)+count($blayers))){
            add_layers($results, $layers, $blayers, $id);
        }else if(count($results) > (count($layers)+count($blayers))){
            remove_layers($results, $layers, $blayers);
        }else{
            add_layers($results, $layers, $blayers, $id);
            remove_layers($results, $layers, $blayers);
        }
    }
    
    function add_layers($results, $layers, $blayers, $id){
        add_layer($results, $layers, 0, $id);
        add_layer($results, $blayers, 1, $id);
    }
    
    function add_layer($results, $layers, $state, $id){
        global $wpdb;
        $action = true;
        foreach($layers as $l){
            $action = doCheck($l, $results, $state);
            if($action){
                $wpdb->insert('wp_map_rel_layer', array('map_id'=>$id, 'lay_id' => $l));
            }
        }
        
    }
    
    function remove_layers($results, $layers, $blayers){
        global $wpdb;
        $action = true;
        foreach($results as $res){
            $action = doCheck($layers, $res, 0);
            if($action){
                $action = doCheck($blayers, $res, 1);
            }
            if($action){
                $wpdb->delete('wp_map_rel_layer', array('lay_id' => $res->lay_id));
            }
        }
    }
    
    function doCheck($layers, $results, $is_base){
        $action = true;
        if(is_array($layers)){
            foreach($layers as $l){
                if($results->base==$is_base && $l == $results->lay_id){
                    return false;
                }
            }
        }else{
            foreach($results as $res){
                if($layers == $res->lay_id && $res->base == $is_base){
                    return false;
                }
            }
        }
        return true;
    }
    
    function maps_addsubmenu_page_callback(){
        global $wpdb;
        if($_POST['mpbx_hidden'] == 'I') {
            $name = escape_var($_POST['map_name']);
            $layers = $_POST['layer_id'];
            $blayers = $_POST['blayer_id'];
            $description = escape_var($_POST['description']);
            if($name != "" && (count($layers) > 0 || count($blayers) > 0)){
                $wpdb->insert('wp_maps', array('name' => $name, 'description' => $description, 'subtheme_id'=> mysql_real_escape_string(trim($_POST['subtheme_id'])), 'publ_id' => mysql_real_escape_string(trim($_POST['publ_id']))));
                $map_id = $wpdb->insert_id;
                foreach($layers as $l){
                    $wpdb->insert('wp_map_rel_layer', array('map_id'=>$map_id, 'lay_id' => $l));
                }
                foreach($blayers as $l){
                    $wpdb->insert('wp_map_rel_layer', array('map_id'=>$map_id, 'lay_id' => $l));
                }
                create_maps_table();
            }else{
                echo "You need to insert a name";
            }
        }else{
            map_form("I", "Add new map", array(), "Add");
        }
    }
    
    function map_form($status, $title, $values, $btn_val){
        $elements = array(array("type" => "input", "title" => "Layer Name", "name" => "map_name", "value" => $values[0]),
                          array("type" => "textarea", "title" => "Description", "name" => "description", "value" => $values[1]),
                          array("type" => "select", "title" => "Subtheme", "name" => "subtheme_id", "value" => $values[2], "sql" => "SELECT * FROM wp_mapsubthemes", "multi" => false),
                          array("type" => "select", "title" => "Publishing way", "name" => "publ_id", "value" => $values[3], "sql" => "SELECT * FROM wp_mappublishing", "multi" => false),
                          array("type" => "select", "title" => "Layer", "name" => "layer_id[]", "value" => $values[4], "sql" => "SELECT id, name FROM wp_maplayers WHERE base=0", "multi" => true),
                          array("type" => "select", "title" => "Base Layer", "name" => "blayer_id[]", "value" => $values[4], "sql" => "SELECT id, name FROM wp_maplayers WHERE base=1", "multi" => true),
                          array("type" => "button", "value"=> $btn_val));
        create_form("mpbx_map_form", $status, $title, $elements);
    }
    
    function create_maps_table(){
        $sqls = array("SELECT a.id, a.name, a.description, a.subtheme_id, b.name as subtheme, c.name as publish FROM wp_maps a, wp_mapsubthemes b, wp_mappublishing c
                      where a.subtheme_id=b.id and a.publ_id =c.id",
                      "SELECT b.name, b.base FROM wp_map_rel_layer a, wp_maplayers b WHERE a.lay_id=b.id and a.map_id=");
        create_table("mapboxoptions", "Maps", $sqls, array("id", "name", "description", "subtheme_id", "subtheme", "publish", "layers"));
    }
    
    
    /*********CATEGORIES SECTION*****/
    function categories_addsubmenu_page_callback(){
        global $wpdb;
        if($_POST['mpbx_hidden'] == 'I') {
            $name = escape_var($_POST["cat_name"]);
            if($name != ""){
                $wpdb->insert('wp_mapthemes', array('name' => $name));
                create_categories_table();
            }else{
                echo "You need to fill in a name";
            }
        }else{
            category_form("I", "Add new category", "", "Add");
        }
    }
    
    function categories_submenu_page_callback() {
        global $wpdb;
        if($_GET['mode'] == 'delete'){
            $id = escape_var($_GET['cat_id']);
            $wpdb->delete('wp_mapthemes', array('id'=>$id));
            create_categories_table();
        }else if($_GET['mode'] == 'edit'){
            if($_POST['mpbx_hidden'] == 'U') {
                $id = escape_var($_GET['cat_id']);
                $name = escape_var($_POST["cat_name"]);
                if($name != ""){
                    $wpdb->update('wp_mapthemes', array('name' => $name), array('id'=>$id));
                    create_categories_table();
                }else{
                    print "The name should not be blank";
                }
            }else{
                $id = escape_var($_GET['cat_id']);
                $results = $wpdb->get_results("SELECT * FROM wp_mapthemes WHERE id=$id");
                foreach($results as $res){
                    $name = $res->name;
                }
                category_form("U", "Edit category", $name, 'Update');
            }
        }else{
            create_categories_table();
        }
    }
    
    function create_categories_table(){
        create_table("categories-submenu-page", "Categories", array("SELECT * FROM wp_mapthemes"), array("id", "Name"));
    }
    
    function category_form($status, $title, $value, $btn_val){
        $elements = array(array("type" => "input", "title" => "Category Name", "name" => "cat_name", "value" => $value),
                          array("type" => "button", "value"=> $btn_val));
        create_form("mpbx_cat_form", $status, $title, $elements);
    }
    
    /**************SUBCATEGORIES SECTION *************/
    function subcategories_addsubmenu_page_callback(){
        global $wpdb;
        if($_POST['mpbx_hidden'] == 'I') {
            $wpdb->insert('wp_mapsubthemes', array('name' => escape_var($_POST['subcat_name']), 'theme_id' =>escape_var($_POST['theme_id'])));
            create_subcategories_table();
        }else{
            subcategory_form("I", "Add new category", array(), "Add", "");
        }
    }
    
    function subcategories_submenu_page_callback() {
        global $wpdb;
        if($_GET['mode'] == 'delete'){
            $id = escape_var($_GET['cat_id']);
            if($id != ""){
                $wpdb->delete('wp_mapsubthemes', array('id'=>$id));
            }
            create_subcategories_table();
        }else if($_GET['mode'] == 'edit'){
            if(escape_var($_POST['mpbx_hidden']) == 'U') {
                $id = escape_var($_GET['cat_id']);
                $name = escape_var($_POST["subcat_name"]);
                if($name != ""){
                    $wpdb->update('wp_mapsubthemes', array('name' => $name,
                                                       'theme_id' => escape_var($_POST["theme_id"])), array('id'=>$id));
                    create_subcategories_table();
                }else{
                    echo "You need to insert a name";
                }
            }else{
                $id = escape_var($_GET['cat_id']);
                $results = $wpdb->get_results("SELECT * FROM wp_mapsubthemes WHERE id=$id");
                foreach($results as $res){
                    $name = $res->name;
                    $theme = $res->theme_id;
                }
                subcategory_form("U", "Edit subcategory", array($name, $theme), 'Update');
        }
        }else{
            create_subcategories_table();
        }
    }
    
    function create_subcategories_table(){
        create_table("subcategories-submenu-page", "Subcategories", array("SELECT a.id, a.name, a.theme_id, b.name as theme FROM wp_mapsubthemes a, wp_mapthemes b where a.theme_id = b.id"), array("id", "Name", "theme_id", "Theme"));
    }
    
    function subcategory_form($status, $title, $values, $btn_val){
        $elements = array(array("type" => "input", "title" => "Subategory Name", "name" => "subcat_name", "value" => $values[0]),
                          array("type" => "select", "title" => "Category Name", "name" => "theme_id", "value" => $values[1], "sql" => "SELECT * FROM wp_mapthemes", "multi" => false),
                          array("type" => "button", "value"=> $btn_val));
        create_form("mpbx_subcat_form", $status, $title, $elements);
    }
    
    /*****LAYERS SECTION*****/
    function layers_addsubmenu_page_callback(){
        global $wpdb;
        if($_POST['mpbx_hidden'] == 'I') {
            if(isset($_POST['base']) && $_POST['base']==1){
                $base = true;
            }else{
                $base = false;
            }
            $name = escape_var($_POST['lay_name']);
            $mapbox_id = escape_var($_POST["mpbx_id"]);
            $date = escape_var($_POST["date"]);
            if($name != "" && $mapbox_id != ""){
                $wpdb->insert("wp_maplayers", array('name' => $name,
                                                'description' => escape_var($_POST["description"]),
                                                'mapbox_id' => $mapbox_id,
                                                'base'=>$base,
                                                'date'=>$date));
                create_layers_table();
            }else{
                if($name == ""){
                    echo "You need to insert a name";
                }else{
                    echo "You need to insert a valid id";
                }
            }
        }else{
            layer_form("I", "Add new layer", array(), "Add", true);
        }
    }
    
    function layers_submenu_page_callback() {
        global $wpdb;
        if($_GET['mode'] == 'delete'){
            $id = escape_var($_GET['cat_id']);
            $wpdb->delete("wp_maplayers", array('id'=>$id));
        }
        
        if($_POST['mpbx_hidden'] == 'U') {
            $id = escape_var($_GET['cat_id']);
            $name = escape_var($_POST["lay_name"]);
            if($name != ""){
                $wpdb->update("wp_maplayers", array('name' => $name,
                                                'description' => escape_var($_POST["description"]),
                                                'mapbox_id' => escape_var($_POST["mpbx_id"]),
                                                'base'=> escape_var($_POST['base']),
                                                'date'=> escape_var($_POST['date'])), array('id'=>$id));
                create_layers_table();
            }else{
                print "You need to insert a name";
            }
        }else if($_GET['mode'] == 'edit'){
            $id = $_GET['cat_id'];
            $results = $wpdb->get_results("SELECT * FROM wp_maplayers WHERE id=$id");
            foreach($results as $res){
                $name = $res->name;
                $description = $res->description;
                $mapbox = $res->mapbox_id;
                $base = $res->base;
                $date = $res->date;
            }
            layer_form("U", "Edit layer", array($name, $description, $mapbox, $base, $date), 'Update', true);
        }else{
            create_layers_table();
        }
        
    }
    
    function create_layers_table(){
        create_table("layers-submenu-page", "Layers", array("SELECT * FROM wp_maplayers"), array("id", "Name", "Description", "Mapbox_id", "Base map", "Date"));
    }
    
    function layer_form($status, $title, $values, $btn_val, $date){
        $elements = array(array("type" => "input", "title" => "Layer Name", "name" => "lay_name", "value" => $values[0]),
                          array("type" => "textarea", "title" => "Description", "name" => "description", "value" => $values[1]),
                          array("type" => "input", "title" => "Mapbox id", "name" => "mpbx_id", "value" => $values[2]),
                          array("type" => "checkbox", "title" => "Base layer", "name" => "base", "value" => $values[3]),
                          array("type" => "input", "title" => "Date", "name" => "date", "value" => $values[4]),
                          array("type" => "button", "value"=> $btn_val));
        create_form("mpbx_lay_form", $status, $title, $elements, $date);
    }
    
    
    /******general functions******/
    
    function create_form($name, $status, $title, $elements, $date){
        require_once("former.class.php");
        echo '<style>#wpfooter{position: relative !important;}</style>';
        if($date){
            wp_register_script("admin", plugins_url( "../js/admin.js", __FILE__), array(), "1", false);
            wp_enqueue_script("admin");
        }
        $f = new Former($name, $status, $title, $elements);
        $f->build();
        
    }
    
    function create_table($page, $title, $sqls, $cols){
        require_once("tabler.class.php");
        $t = new Tabler($sqls, $title, $cols);
        $t->initPagination($page);
    }
    
    function escape_var($var){
        return mysql_real_escape_string(trim($var));
    }
    
    
?>