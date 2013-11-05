<?php
class Former{
    private $_name;
    private $_status;
    private $_title;
    private $_elements;
    
    
    public function __construct($name, $status, $title, $elements){
        $this->_name = $name;
        $this->_status = $status;
        $this->_title = $title;
        $this->_elements = $elements;
    }
    
    public function build(){
        $this->_build_title();
        $this->_build_form();
        
        for($i=0; $i<count($this->_elements);$i++){
            $l = $this->_elements[$i];
            $el = new Elementer($l["type"], $l["title"], $l["name"], $l["value"], $l["sql"], $l["multi"]);
            $el->build();
        }
        
        $this->_build_end_form();
        $this->_build_end_title();
    }
    
    private function _build_title(){
        echo '<div class="wrap">';
        echo '<h2>'.$this->_title.'</h2>';
    }
    
    private function _build_end_title(){
        echo '</div';
    }
    
    private function _build_form(){
        echo '<form name="'.$this->_name.'" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
        echo '<input type="hidden" name="mpbx_hidden" value="'.$this->_status.'">';
    }
    
    private function _build_end_form(){
        echo '</form>';
    }
}


class Elementer {
    private $_type;
    private $_title;
    private $_name;
    private $_value;
    private $_sql;
    private $_multi;
    
    public function __construct($type, $title, $name, $value, $sql, $multi){
        $this->_type = $type;
        $this->_title = $title;
        $this->_name = $name;
        $this->_value = $value;
        $this->_sql = $sql;
        $this->_multi = $multi;
    }
    
    public function build(){
        switch ($this->_type){
            case "input":
                $this->build_input();
                break;
            case "textarea":
                $this->build_textarea();
                break;
            case "button":
                $this->build_button();
                break;
            case "select":
                $this->build_select();
                break;
            case "checkbox":
                $this->build_checkbox();
                break;
        }
    }
    
    private function build_input(){
        echo '<p>'._e($this->_title).'<input type="text" name="'.$this->_name.'" id="'.$this->_name.'" value="'.$this->_value.'" size="20"></p>';
    }
    
    private function build_checkbox(){
        $checked = "checked";
        if(!$this->_value){
            $checked = "";
        }
        echo '<p>'._e($this->_title).'<input type="checkbox" name="'.$this->_name.'" value="1" '.$checked.'></p>';
    }
    
    private function build_textarea(){
        echo '<p>'._e($this->_title).'<textarea name="'.$this->_name.'">'.$this->_value.'</textarea></p>';
    }
    
    private function build_button(){
        echo '<p class="submit">';
        echo '<input type="submit" name="Submit" value="'.$this->_value.'" />';
        echo '</p>';
    }
    
    private function build_select(){
        global $wpdb;
        
        echo '<p>'._e($this->_title);
        $results = $wpdb->get_results($this->_sql);
        $multi = "";
        $size = "";
        if($this->_multi){
            $multi = "multiple";
            $size = 'size="5"';
        }
        echo '<select name="'.$this->_name.'" '.$multi.' '.$size.'>';
        
        foreach($results as $res){
            $id = $res->id;
            $name = $res->name;
            if(is_array($this->_value)){
                foreach($this->_value as $val){
                    $sel = $this->is_selected($val->id, $id);
                    if($sel == 'selected'){
                        break;
                    }
                }
            }else{
                $sel = $this->is_selected($this->_value, $name);
            }
            
            echo '<option value="'.$id.'" '.$sel.'>'.$name.'</option>';
        }
        echo '</select>';
        echo '</p>';
    }
    
    private function is_selected($val, $id){
        if($val == $id){
            return 'selected';
        }else{
            return '';
        }
    }
}

?>