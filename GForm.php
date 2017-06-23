<?php
class GForm {
    public function __construct($type = '') {
        if ($type) $this->type = $type;
    }
    public $type;
    public $model;
    
    public $options = array();
    
    public $wrapper = false; // Indica se os labels e inputs devem ser colocados dentro de colunas. Com th e td respectivamente
    
    public function open($action = '', $method='post', $attributes = array()) {
        $html = '<form action="'.$action.'" method="'.$method.'" '.$this->_renderAttributes($attributes).'>';
        if ($this->type) $html .= $this->_formType($this->type);
        return $html;
    }
    
    public function close() {
        return '</form>';
    }
    // Cria um formulário baseado no model
    public function form($type = '', $attributes = '*') {
        if ($attributes == '*')
            $attributes = $this->model->attributeNames;
        $html = $this->open();
        // Se o parâmetro groups estiver setado retorna o html dividido
        if (isset($this->options['groups'])) {
            $this->wrapper = true;
            $html .= '<table class="gformtable">';
            $numCols = 1;
            foreach ($this->options['groups'] as $group) {
                $html .= '<tr>';
                if (count($group) > $numCols) $numCols = count($group);
                foreach ($group as $attr) {
                    $html .= '<td>';
                    if ($relation = $this->model->getRelation($attr)) {
                        $model = new $relation[1];
                        $html .= $this->select($attr, $this->model->$attr, GView::listData($model->findAll(), $model->pk, $model->name));
                    } else {
                        $html .= $this->text($attr, $this->model->$attr);
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            //Submit
            $numCols = $numCols * 2; // Cada atributo ocupa 2 colunas com label e valor
            $html .= '<tr><td colspan="'.$numCols.'">'.$this->submit().'</td></tr>';
            $html .= '</table>';
        } else {
            foreach ($attributes as $attribute) {
                if ($relation = $this->model->getRelation($attribute)) {
                    $model = new $relation[1];
                    $html .= $this->select($attribute, $this->model->$attribute, GView::listData($model->findAll(), $model->pk, $model->name));
                } else {
                    $html .= $this->text($attribute, $this->model->$attribute);
                }
            }
            $html .= $this->submit();
        }
        // Adiciona o tipo de formulário
        $html .= $this->_formType($type);
        $html .= $this->close();
        return $html;
    }
    
    protected function _getProtectedName() {
        return md5(get_class($this->model));
    }
    
    /* Retorna campos que definem o tipo de envio. Se for vazio o método irá verifica se model possui o campo id, se possuir é uma edição 'edit'. Se não possuir adição 'add'.
    O parâmetro $type só deve ser informado se o campo for do tipo 'search'
    */
    protected function _formType($type = '') {
        $pk = $this->model->pk;
        if ($type != 'search') {
            $type = $this->model->$pk ? 'edit' : 'add';
        }
        $result = array(
            'type'=>$type,
            'id'=>$this->model->$pk,
        );
        $result = base64_encode(serialize($result));
        return '<input type="hidden" name="'.$this->_getProtectedName().'" value="'.$result.'" />';
    }
    
    public function hidden($attribute, $value, $attributes = array()) {
        return '<input type="hidden" name="'.$this->_elementName($attribute).'" id="'.$this->_elementId($attribute).'" value="'.$value.'" '.$this->_renderAttributes($attributes).' />';
    }
    
    protected function _elementId($attribute) {
        return get_class($this->model) . '_' . $attribute;
    }
    
    protected function _elementName($attribute) {
        return get_class($this->model).'['.$attribute.']';
    }
    
    protected function _label($attribute) {
        $labels = $this->model->attributeLabels();
        return $labels[$attribute];
    }
    
    protected function _labelElement($attribute) {
        $html = '<label for="'.$this->_elementId($attribute).'">'.$this->_label($attribute).'</label>';
        if ($this->wrapper) $html = '<th>' . $html . '</th>';
        return $html;
    }
    
    protected function _renderAttributes($attributes) {
        $result = array();
        foreach ($attributes as $key=>$value) {
            $result[] = "$key=\"$value\"";
        }
        return implode(' ', $result);
    }
    
    protected function _renderSelectOptions($options) {
        $return = '<option value="">Selecione</option>';
        foreach ($options as $key=>$value) {
            $return .= '<option value="'.$key.'">'.$value.'</option>';
        }
        return $return;
    }
    
    public function text($attribute, $value = '', $attributes =  array()) {
        $td = '';
        $tdClose = '';
        if ($this->wrapper) {
            $td = '<td>';
            $tdClose = '</td>';
        }
        return $this->_labelElement($attribute) . $td . '<input type="text" name="'.$this->_elementName($attribute).'" id="'.$this->_elementId($attribute).'" value="'.$value.'" '.$this->_renderAttributes($attributes).' />' . $tdClose;
    }
    
    public function select($attribute, $value = '', $options = array(), $attributes = array()) {
        $td = '';
        $tdClose = '';
        if ($this->wrapper) {
            $td = '<td>';
            $tdClose = '</td>';
        }
        return $this->_labelElement($attribute) . $td . '<select name="'.$this->_elementName($attribute).'" id="'.$this->_elementId($attribute).'" '.$this->_renderAttributes($attributes).'>'.$this->_renderSelectOptions($options).'</select>' . $tdClose;
    }
    
    public function submit($label = 'Salvar', $attributes = array()) {
        return '<input type="submit" value="'.$label.'" '.$this->_renderAttributes($attributes).' />';
    }
}