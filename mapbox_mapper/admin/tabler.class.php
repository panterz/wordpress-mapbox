<?php
class Tabler{
    private $_sql;
    private $_attrs;
    private $_title;
    
    public function __construct($sql, $title, $attrs){
        $this->_sql = $sql;
        $this->_title = $title;
        $this->_attrs = $attrs;
    }
    
    public function initPagination($page){
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>'.$this->_title.'</h2>';
        echo '<div class="tablenav">';
        echo '<div class="tablenav-pages">';
        $items = $this->checkResults();
        if($items > 0){
            require_once("pagination.class.php");
            $p = new pagination;
            $p->items($items);
            $p->limit(10); // Limit entries per page
            $p->target("admin.php?page=$page"); 
            $p->currentPage($_GET[$p->paging]); // Gets and validates the current page
            $p->calculate(); // Calculates what to show
            $p->parameterName('paging');
            $p->adjacents(1); //No. of page away from the current page
            
            if(!isset($_GET['paging'])) {
                $p->page = 1;
            } else {
                $p->page = $_GET['paging'];
            }
            
            //Query for limit paging
            $limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
            
            echo $p->show();  // Echo out the list of paging. 
        }else{
            echo "No Record Found";
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        if($limit){
            $this->printTable($page, $limit);
        }
    }
    
    public function printTable($page, $limit){
        global $wpdb;
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        foreach($this->_attrs as $attr){
            echo '<th>'.$attr.'</th>';
        }
        echo '<th>Edit|Delete</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $sql = $this->_sql[0]." ".$limit;
        //$result = mysql_query($sql) or die ('Error, query failed');
        $results = $wpdb->get_results($sql, ARRAY_N);
        
        if (count($results) > 0){
            foreach($results as $res){
                echo '<tr>';
                $i=0;
                foreach($res as $r){
                    if($i==0){
                        $id = $r;
                    }
                    echo '<td>'.$r.'</td>';
                    $i++;
                }
                if(count($this->_sql) > 1){
                    //echo $this->_sql[1].$id;
                    $result = mysql_query($this->_sql[1].$id) or die ('Error, query failed');
                    echo '<td>';
                    while($row1 = mysql_fetch_assoc($result)){
                        $base = "Layer";
                        if($row1['base'] == 1){
                            $base = "Base layer";
                        }
                        echo $base.": ".$row1['name']."<br>";
                    }
                    echo '</td>';
                }
                echo '<td><a href="admin.php?page='.$page.'&mode=edit&cat_id='.$id.'">Edit</a> | <a href="admin.php?page='.$page.'&mode=delete&cat_id='.$id.'">Delete</a></td>';
                echo '</tr>';
            } 
        } else {
            echo '<tr>';
            echo 'No record found!';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '<a href="admin.php?page=add-'.strtolower($this->_title).'-submenu-page">Add '.$this->_title.'</a>';
        echo '</div>';
    }
    
    private function checkResults(){
        return mysql_num_rows(mysql_query($this->_sql[0]));
    }
}
?>