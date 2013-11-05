<?php   
    /* 
    Plugin Name: Mapbox mapper 
    Plugin URI: http://www.pterzis.gr 
    Description: Plugin for publishing mapbox maps
    Author: P. Terzis
    Version: 0.1 
    Author URI: http://www.pterzis.gr 
    */
    
    //define("MY_PLUGIN_VERSION", 0.1);
    
    require_once("includes/database.php");
    
    register_activation_hook(__FILE__, 'mapper_data_tables_install');
    register_deactivation_hook(__FILE__, 'drop_tables');
    
    require_once("admin/mapper.php");
    init_admin();
    init_plugin();
    
    
    function init_plugin(){
        add_action('wp_enqueue_scripts', 'register_scripts');
        add_action('wp_enqueue_scripts', 'register_css');
        add_action('wp_ajax_mapbox_get_maps', 'mapbox_maps');
        add_action('wp_ajax_nopriv_mapbox_get_maps', 'mapbox_maps');
        add_shortcode("mapbox_mapper", "display_map");
    }
    
    function register_scripts(){
        //wp_enqueue_script('backbone');
        $scripts = array(
                         array("name" => "jquery", "file" => "js/jquery/jquery.js", "deregister" => true, "url"=>"include", "version"=> "1.2.1", "footer" => false),
                         array("name" => "jquery-migrate", "file" => "js/jquery/jquery-migrate.min.js", "deregister" => true, "url"=>"include", "version"=> "1.10.2", "footer" => false),
                         array("name" => "jquery-ui", "file" => "js/lib/jquery-ui-1.10.3.custom.min.js", "deregister" => false, "url"=>"plugin", "version"=> "1.10.3", "footer" => false),
                         array("name" => "json2", "file" => "js/json2.js", "deregister" => true, "url"=>"include", "version"=> "2011-02-23", "footer" => false),
                         array("name" => "underscore", "file" => "js/underscore.min.js", "deregister" => true, "url"=>"include", "version"=> "1.4.4", "footer" => false),
                         array("name" => "backbone", "file" => "js/backbone.min.js","deregister" => true, "url"=>"include", "version"=> "1.0.0", "footer" => false),
                         array("name" => "mapbox", "file" => "js/lib/mapbox.js", "deregister" => false, "url"=>"plugin", "version"=> "1.3.1", "footer" => false),
                         array("name" => "marionette", "file" => "js/lib/backbone.marionette.js", "deregister" => false, "url"=>"plugin", "version"=> "1.1.0", "footer" => false),
                         array("name" => "timeline", "file" => "js/lib/jquery.timelinr-0.9.53.js", "deregister" => false, "url"=>"plugin", "version"=> "0.9.53", "footer" => false),
                         array("name" => "app", "file" => "js/app.js", "deregister" => false, "url"=>"plugin", "version"=> "1.0", "footer" => false)
                         );
        add_scripts($scripts);
        wp_register_script('themes', plugins_url('js/themes.json', __FILE__), array(), '1.0', false);
    }
    
    function add_scripts($scripts){
        foreach($scripts as $script){
            if($script["deregister"]){
                wp_deregister_script($script["name"]);
            }
            if($script["url"] == "include"){
                wp_register_script($script["name"], includes_url($script["file"], __FILE__), array(), $script["version"], $script["footer"]);
            }else{
                wp_register_script($script["name"], plugins_url($script["file"], __FILE__), array(), $script["version"], $script["footer"]);
            }
            wp_enqueue_script($script["name"]);
        }
    }
    
    function register_css(){
        wp_register_style('mapboxcss', plugins_url('css/mapbox.css', __FILE__));
        wp_enqueue_style('mapboxcss');
        //wp_register_style('jquery-ui', plugins_url('css/jquery-ui-1.10.3.custom.min.css', __FILE__));
        //wp_enqueue_style('jquery-ui');
        wp_register_style('controls-ui', plugins_url('css/controls.css', __FILE__));
        wp_enqueue_style('controls-ui');
        
        global $is_IE;
        if ( $is_IE ) {
            echo '<!--[if lt IE 8]>';
            echo "<link href='".plugins_url('css/jquery-ui-1.10.3.custom.min.css', __FILE__)."' rel='stylesheet' >";
            echo '<![endif]-->';
        }
    }
    
    function display_map() {
        $width = (get_option('map_width') != '') ? get_option('map_width') : '100%';
        $height = (get_option('map_height') != '') ? get_option('map_height') : '400px';
        print '<!-----SLIDING MENU PANEL----->

                

        <!-----END SLIDING MENU PANEL----->

        <script type="text/template" id="subcategory-template">
            <a href="javascript:void(0);" class="theme"><%= name %></a>
        </script>
    
        <script type="text/template" id="accordion-group-template">
            <h3><%= name %></h3>
            <div class="subc">
                <p>
                    <ul></ul>
                </p>
            </div>
        </script>
        
        <script type="text/template" id="ul-list">
            <h4><%= name %></h4>
            <ul></ul>
        </script>
        
        <script type="text/template" id="dates-template">
            <a href="#"><%=date %></a>
        </script>
        
        <script type="text/template" id="issues-template">
            <li id="<%=date %>">
                <h4><%=name %></h4>
                <p><%=description %></p>
            </li>
        </script>
        

        <!-----DEMO ONLY----->
        <style>
            .site-main{position: static !important;}
            #map {position: relative; top:0; height: '.$height.'; width:'.$width.'; }
        </style>
            <div id="categories"></div>
            <div id="map"></div>
            <div id="controls"></div>
            <div id="timeline">
                <div id="datesdiv"></div>
                <div id="issuesdiv"></div>
                <div id="grad_left"></div>
                <div id="grad_right"></div>
                <a href="#" id="next">+</a>
                <a href="#" id="prev">-</a>
            </div>
            <div id="coords"></div>
        <script>
            get_categories();
            
        </script>

        <!-----END DEMO ONLY----->';

    }
    
    function mapbox_maps(){
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 4 Jul 2016 05:00:00 GMT');
        header('Content-type: application/json');
        
        global $wpdb;
        $sql = "select e.name as theme, d.name as subtheme, f.name as publish, a.id, a.name, a.description, c.id, c.name as layer, c.mapbox_id, c.description as layer_description, c.base, c.date
        from wp_maps a left join wp_map_rel_layer b ON (a.id=b.map_id)
        left JOIN wp_maplayers c ON (c.id = b.lay_id)
        inner join wp_mappublishing f ON (f.id = a.publ_id)
        inner join wp_mapsubthemes d ON (a.subtheme_id = d.id)
        inner join wp_mapthemes e ON (e.id = d.theme_id) ORDER BY theme, subtheme";
        
        $results = $wpdb->get_results($sql);
        $data = array();
        $cat = "";
        $subcat = "";
        $map = "";
        $i=0;
        $j=0;
        $k=0;
        $t=0;
        $baselayers = array();
        $layers = array();
        
        
        foreach($results as $res){
            
            if($cat != $res->theme){
                if(count($baselayers)>0){
                    usort($layers, "sortFunction");
                    $data[$i-1]["subcategories"][$j-1]["maps"][$k-1]["basemaps"] = $baselayers;
                    $data[$i-1]["subcategories"][$j-1]["maps"][$k-1]["layers"] = $layers;
                    $baselayers = array();
                    $layers = array();
                }
                $cat = $res->theme;
                $data[$i]["name"] = $cat;
                $j=0;
                $i++;
            }
            
            if($subcat != $res->subtheme){
                if(count($baselayers)>0){
                    usort($layers, "sortFunction");
                    $data[$i-1]["subcategories"][$j]["maps"][$k-2]["basemaps"] = $baselayers;
                    $data[$i-1]["subcategories"][$j]["maps"][$k-2]["layers"] = $layers;
                    $baselayers = array();
                    $layers = array();
                }
                
                $subcat = $res->subtheme;
                $data[$i-1]["subcategories"][$j]["name"] = $subcat;
                $k=0;
                $j++;
            }
            
            if($map != $res->name){
                if($k>0){
                    usort($layers, "sortFunction");
                    $data[$i-1]["subcategories"][$j-1]["maps"][$k-1]["basemaps"] = $baselayers;
                    $data[$i-1]["subcategories"][$j-1]["maps"][$k-1]["layers"] = $layers;
                }
                $map = $res->name;
                $maps = array("name"=> $map, "description" => $res->description, "publish"=>$res->publish);
                $data[$i-1]["subcategories"][$j-1]["maps"][$k]["name"] = $map;
                $data[$i-1]["subcategories"][$j-1]["maps"][$k]["description"] = $res->description;
                $data[$i-1]["subcategories"][$j-1]["maps"][$k]["publish"] = $res->publish;
                
                $baselayers = array();
                $layers = array();
                $k++;
            }
            if($res->base == "1"){
                    $baselayers[] = array("name"=>$res->layer, "mapbox_id"=>$res->mapbox_id, "date"=>$res->date);
                }else{
                    $layers[] = array("name"=>$res->layer, "description"=>$res->layer_description, "mapbox_id"=>$res->mapbox_id, "date"=>$res->date);
                }
            
            if($t == count($results)-1){
                usort($layers, "sortFunction");
                $data[$i-1]["subcategories"][$j-1]["maps"][$k-1]["basemaps"] = $baselayers;
                $data[$i-1]["subcategories"][$j-1]["maps"][$k-1]["layers"] = $layers;
            }
            
            $t++;
        }
        //print_r($data); 
        echo json_encode($data);
        
        die();
    }
    
    function sortFunction( $a, $b ) {
        return strtotime($a["date"]) - strtotime($b["date"]);
    }
    
    
?>
