<?php
class GView {
    protected $_static_path;
    public $model;
    public function __construct(GModel $model = null) {
        if ($model) $this->model = $model;
        $this->_static_path = dirname(__FILE__) . '/assets/';
    }
    public function grid($data = null, GModel $model = null, $attributes = '*') {
        if ($model) $this->model = $model;
        if (!$data) $data = $this->model->findAll();
        $html = '<table width="100%">';
        $labels = $this->model->getAttributeNames();
        if ($attributes == '*') $attributes = $this->model->attributeNames;
        $thead = '<thead><tr>';
        $tbody = '<tbody>';
        foreach ($attributes as $attribute) {
            $thead .= '<th>'.$labels[$attribute].'</th>';
        }
        $thead .= '<th></th>';
        foreach ($data as $item) {
            $tbody .= '<tr>';
            foreach ($attributes as $attribute) {
                $tbody .= '<td>'.$item->$attribute.'</td>';
            }
            $tbody .= $this->actionsColumn($item);
            $tbody .= '</tr>';
        }
        $thead .= '</tr></thead>';
        $tbody .= '</tbody>';
        
        $html .= $thead . $tbody . '</table>';
        return $html;
    }
    
    protected function viewDir() {
        return dirname(__FILE__) . '/../views/';
    }
    public function hasView($r) {
        if (file_exists($this->viewDir() . $r . '.php')) return true;
    }
    
    public function render($r = null) {
        require $this->viewDir() . $r . '.php';
    }
    
    public function url($controller = '', $action = '', $params = array()) {
        return GController::instance()->url($controller, $action, $params);
    }
    public function actionsColumn($item) {
        $pk = $item->pk;
        $modelName = get_class($item);
        return '<td><a href="'.$this->url($modelName, 'view', array('id'=>$item->$pk)).'">Ver</a> - <a href="'.$this->url($modelName, 'edit', array('id'=>$item->$pk)).'">Editar</a> - <a href="'.$this->url($modelName, 'delete', array('id'=>$item->$pk)).'">Excluir</a></td>';
    }
    
    public function view($attributes = '*') {
        if ($attributes == '*') $attributes = $this->model->attributeNames;
        $labels = $this->model->attributeLabels();
        $html = '<table width="100%" class="gview">';
        foreach ($attributes as $attribute) {
            $value = $this->model->$attribute;
            if ($relation = $this->model->getRelationName($attribute)) {
                $value = $this->model->$relation;
                $attr = $value->name;
                $value = $value->$attr;
            }
            $html .= '<tr><td class="gviewlabel">'.$labels[$attribute].'</td><td class="gviewcontent">'.$value.'</td></tr>';
        }
        $html .= '</table>';
        return $html;
    }
    
    public function searchResults($items = array()) {
        if (!$items && $this->model) $items = $this->model->findAll();
        return $this->grid($items);
    }
    
    protected static $_scripts;
    protected static $_jss;
    public static function registerScript($name, $content) {
        if (self::$_scripts == null) self::$_scripts = array();
        if (isset(self::$_scripts[$name])) return;
        $html = '<script type="text/javascript">'.$content.'</script>';
        self::$_scripts[$name] = $content;
        echo $html;
    }
    
    public static function registerJs($name, $src = null) {
        if (self::$_jss == null) self::$_jss = array();
        // Se já está incluído, não inclui de novo na página
        if (isset(self::$_jss[$name])) return;
        
        if (!$src) {
            // Verifica primeiro se o registro já está em assets
            if (isset(self::$_assets[$name])) {
                self::registerJs($name, self::$_assets[$name]);
            } else {
                // Verifica se existe um arquivo PHP com o conteúdo a ser registrado
                $class = 'GJs'.ucfirst($name);
                $filename = $class.'.php';
                if (file_exists($filename)) {
                    require_once $filename;
                    $obj = new $class();
                    $class->register();
                    self::registerJs($name, self::$_assets[$name]);
                } else {
                    throw new Exception('Não há nenhum script com o nome '. $name . '. Informe um nome válido, ou informe o caminho do arquivo');
                }
            }
        } else {
            $html = '<script type="text/javascript" src="assets/'.$src.'"></script>';
            self::$_jss[$name] = $src;
            echo $html;
        }
    }
    
    protected static $_assets;
    public static function createAsset($name, $content, $ext) {
        $filename = md5($name);
        file_put_contents($filename, $content);
        self::$_assets[$name] = $filename;
        self::storeAssets();
    }
    
    protected static function loadAssets() {
        if (self::$_assets) return self::$_assets;
        if (file_exists('a')) {
            self::$_assets = unserialize(base64_decode(file_get_contents('a')));
        } else {
            self::$_assets = array();
        }
    }
    
    protected static function storeAssets() {
        file_put_contents('a', base64_encode(serialize(self::$_assets)));
    }
    
    public static function listData($data, $value, $text) {
        $result = array();
        foreach ($data as $item) {
            $result[$item->$value] = $item->$text;
        }
        return $result;
    }
}