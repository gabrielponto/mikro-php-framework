<?php
abstract class GModel {
    /**
    @var $attributes Contém os atributos do objeto
    */
    public $attributes = array();
    public $validators;
    
    public $relations = array();
    
    protected $relationsValue = array();
    
    public $attributeNames = array();
    
    public $errors = array();
    
    
    public function __construct() {
        $this->attributeNames = $this->getAttributeNames();
        if (method_exists($this, 'relations')) {
            $this->relations = $this->relations();
        }
        if (method_exists($this, 'rules'))
            $this->validators = $this->rules();
        if (method_exists($this, 'init')) $this->init();
    }
    /**
    Retorna os nomes de atributos
    */
    abstract public function getAttributeNames();
    
    /**
    * Salva um model
    */
    abstract public function save($validate = TRUE, $attributes = null);
    
    /**
    * Retorna os validators
    */
    public function rules() {}
    
    
    public function __set($chave, $value) {
        if (in_array($chave, $this->attributeNames)) {
            $this->attributes[$chave] = $value;
        } else {
            if (array_key_exists($chave, $this->relations)) {
                $this->relationsValue[$chave] = $value;
            } else {
                throw new Exception('Atributo '.$chave.' não existe no objeto');
            }
        }
    }
    
    /**
    * Retorna o nome da relação do atributo. Se não houver relação, retorna FALSE
    */
    public function getRelation($attribute) {
        foreach ($this->relations as $name=>$relation) {
            if ($relation[0] == $attribute) return $relation;
        }
        return FALSE;
    }
    
    public function getRelationName($attribute) {
        foreach ($this->relations as $name=>$relation) {
            if ($relation[0] == $attribute) return $name;
        }
        return FALSE;
    }
    
    /**
    * Retorna NULL caso o conteúdo não esteja setado
    * Dispara uma exceção informando que o campo não existe no objeto caso ele não tenha sido retornado em getAttributeNames
    * Verifica também no array de relações se existe um campo com o nome solicitado. Nesse caso ele carrega o campo com os dados necessários.
    * Lembrando que se houver um atributo e uma relação com o mesmo nome, o valor retornado será do atributo.
    */
    public function __get($chave) {
        if (!in_array($chave, $this->attributeNames)) {
            // Verifica se o atributo está no array de relações
            if (array_key_exists($chave, $this->relations)) {
                return $this->relationsValue[$chave];
            }
            throw new Exception('Atributo '.$chave.' não existe no objeto');
        }
        if (!isset($this->attributes[$chave])) return null;
        return $this->attributes[$chave];
    }
    
    public function addError($msg, $attribute, $cod = 0) {
        if (!isset($this->errors[$attribute])) $this->errors[$attribute] = array();
        $this->errors[$attribute][] = array('msg'=>$msg, 'cod'=>0);
    }
    
    public function errors($attribute) {
        return $this->errors[$attribute];
    }
    
    protected static $_model;
    public static function model() {
        if (self::$_model !== null) return self::$_model;
        $class = get_called_class();
        self::$_model = new $class();
        return self::$_model;
    }
}