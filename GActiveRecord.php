<?php
class GActiveRecord extends GModel {
    protected $_db;
    public $pk = 'id';
    public $name = 'nome';
    
    public $scenario = 'insert';
    
    public $formOptions = array();
    
    public $isNewRecord;
    
    const REL_BELONGS_TO = 'belongs_to';
    const REL_MANY_MANY = 'many_many';
    
    public function __construct() {
        $this->_db = GPDO::instance();
        parent::__construct();
        if (method_exists($this, 'formOptions')) $this->formOptions = $this->formOptions();
    }
    
    public function getAttributeNames() {
        return $this->_db->columns($this->tableName());
    }
    
    public function form($type = '') {
        $form = new GForm();
        $form->model = $this;
        $form->options = $this->formOptions;
        return $form->form($type, $this->getAttributesForm());
    }
    
    public function validate() {
        return true;
    }
    
    public function getAttributesForm() {
        return '*';
    }
    /*
    Retorna o id em caso de insert e true em caso de update
    */
    public function save($validate = TRUE, $attributes = null) {
        if ($validate && !$this->validate()) return false;
        if ($this->id) {
            $sql = "UPDATE {$this->tableName()} SET {$this->_sqlFields('update')} WHERE {$this->pk} = {$this->id}";
            if ($this->_db->exec($sql)) return true;
            return false;
        } else {
            $sql = "INSERT INTO {$this->tableName()} {$this->_sqlFields('insert')}";
            if ($this->_db->exec($sql)) {
                $this->id = $this->_db->id();
                return $this->id;
            }
            return false;
        }
    }

    protected function _sqlFields($type = 'insert') {
        if ($type == 'insert') {
            $fields = array();
            $values = array();
            foreach ($this->attributeNames as $name) {
                $fields[] = $name;
                $values[] = $this->$name;
            }
            return '(' . implode(',', $fields) . ") VALUES ('" . implode("','", $values) . "')";
        } else {
            foreach ($this->attributeNames as $name) {
                $fields[] = $name . "='" . $this->$name . "'";
            }
            return implode(',', $fields);
        }
    }
    
    protected function _sqlGetFields($attributes = array()) {
        if (!$attributes) return '*';
        return implode(',', $attributes);
    }
    
    protected function _sqlAttributes($attributes) {
        $result = array();
        $this->_log(var_export($attributes, true));
        foreach ($attributes as $field=>$value) {
            // Não monta com atributos não existentes
            if (!in_array($field, $this->getAttributeNames())) continue;
            $result[] = $field . "='" . $value . "'";
        }
        $this->_log(var_export($result, true));
        return implode(' AND ', $result);
    }
    
    protected function _log($message) {
        // desativado
        return;
        $content = file_get_contents('gmanagerlog');
        $content .= "\n" . date('H:i:s d/m/Y') . ' - ' . $message;
        file_put_contents('gmanagerlog', $content);
    }
    protected function _find($attributes = null, $condition = '', $params = array(), $type = '', $sqlForce = null) {
        $sql = "SELECT * FROM {$this->tableName()}";
        if (is_numeric($condition)) $sql .= " WHERE {$this->pk} = $condition";
        elseif ($attributes) $sql .= " WHERE " . $this->_sqlAttributes($attributes);
        elseif ($condition) $sql .= " WHERE $condition";
        if ($sqlForce) $sql = $sqlForce;
        $class = get_class($this);
        $this->_log("SQL $class: $sql");
        $result = $this->_db->query($sql);
        if ($result) {
            $list = array();
            foreach ($result as $item) {
                $obj = new $class();
                $obj->attributes = $item;
                // Preenche as relações
                foreach ($this->relations as $attribute=>$relation) {
                    // Por enquanto suporte somente para REL_BELONGS_TO. TO_DO: Implementar REL_MANY_MANY
                    $rObjName = $relation[1];
                    $rObj = new $rObjName;
                    $attr = $relation[0];
                    $rObj = $rObj->find($obj->$attr);
                    if ($rObj != null)
                        $obj->$attribute = $rObj;
                }
                if (!$type) return $obj;
                $list[] = $obj;
            }
            if (!$list) return null;
            return $list;
        }
        return null;
    }
    
    public function find($condition = '', $params = array()) {
        return $this->_find(null, $condition, $params, '');
    }
    
    public function findAll($condition = '', $params = array(), $sqlForce = null) {
        return $this->_find(null, $condition, $params, 'all', $sqlForce);
    }
    
    public function findByAttributes($attributes, $condition = '', $params = array()) {
        return $this->_find($attributes, $condition, $params, '');
    }
    
    public function findAllByAttributes($attributes, $condition = '', $params = array()) {
        return $this->_find($attributes, $condition, $params, 'all');
    }
}