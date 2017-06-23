<?php
/**
* Processa requisições baseadas no parâmetro 'r'. Atualmente não fazemos controle com urls amigáveis
* Possui 4 métodos básicos que interagem com um objeto do tipo GModel para realizar alterações baseadas em formulário
*/
class GController {
    public $model;
    public $view;
    protected $r;
    
    public function __construct($model = null) {
        if ($model) $this->model = $model;
        if (!$this->view) $this->view = new GView();
    }
    public function process() {
        // Processa uma requisição
        $r = isset($_GET['r']) ? $_GET['r'] : '';
        $r = trim($r, '/');
        $this->r = $r;
        $this->_route($this->r);
        $this->view->model = $this->model;
    }
    
    protected $action;
    protected $controller;
    public function _route($r) {
        if (!$r) {
            $this->action = 'index';
            $this->controller = 'index';
            $this->_fire('beforeAction');
            $this->indexAction();
            return;
        }
        $r = trim($r, '/');
        if (strpos($r, '/')) {
            $parts = explode('/', $r);
            $this->action = $parts[count($parts)-1];
            $this->controller = $parts[0];
            // Atribui o model ao controller
            $model = ucfirst($parts[0]);
            $this->model = new $model();
            
            $action = $parts[count($parts)-1] . 'Action';
            $this->_fire('beforeAction');
            $this->$action($parts);
        } else {
            $this->controller = '';
            $this->action = $r;
            $action = $r . 'Action';
            $this->_fire('beforeAction');
            $this->$action();
        }
    }
    
    protected function _fire($event) {
        if (method_exists($this->model, $event)) $this->model->$event($this);
    }
    
    /*
    * Pre-defined actions - CRUD actions
    */
    
    public function indexAction() {
        echo 'index';
    }
    
    public function viewAction() {
        $pk = $this->model->pk;
        if (!$this->model->$pk) {
            $this->model = $this->model->find($_REQUEST['id']);
            if ($this->model === null) {
                echo 'Registro não encontrado';
                return;
            }
            $this->view->model = $this->model;
        }
        if ($this->view->hasView($this->r)) {
            $this->view->render($this->r);
        } else {
            echo $this->view->view();
        }
    }
    
    public function addAction() {
        $postKey = get_class($this->model);
        if (count($_POST) > 0 && isset($_POST[$postKey])) {
            // Processa
            $data = $_POST[$postKey];
            if (method_exists($this->model, 'processData')) {
                $data = $this->model->processData($data);
            }
            $this->model->attributes = $data;
            $id = $this->model->save();
            $this->autoRedirect('view', array('id'=>$id));
        } else {
            // Exibe somente o formulário
            echo $this->model->form();
        }
    }
    
    public function editAction() {
    
    }
    
    public function deleteAction() {
    
    }
    
    public function searchAction() {
        $items = $this->model->findAllByAttributes($_REQUEST);
        $this->view->model = $this->model;
        echo $this->view->searchResults($items);
    }
    
    /**
    * Retorna a url atual. O parâmetro $params é opcional, se passado os parâmetros serão adicionados como parâmetros na url
    * Caso controller e $action sejam informados a url retornada é uma nova url com essas informações
    */
    public function url($controller = '', $action = '', $params = array()) {
        $current = $_SERVER['PHP_SELF'];
        if (!$controller) $controller = $this->controller;
        $r = $controller;
        if (!$action) $action = $this->action;
        if ($r) $r .= '/';
        $r .= $action;
        
        $url = $current . '?r='.$r;
        if ($params) {	
            $result = array();
            foreach ($params as $key=>$param) {
                $result[] = "$key=$param";
            }
            $url .= '&' . implode('&', $result);
        }
        return $url;
    }
    /**
    * Redireciona para a mesma página atual mudando somente a action, o array params será enviado como variáveis GET
    * Esse método finaliza a execução do script php com exit();
    */
    public function autoRedirect($action, $params = array()) {
        $redirect = $this->url($this->controller, $action, $params);
        header('Location: '.$redirect);
        exit;
    }
    
    protected static $instance;
    public static function instance($model = null) {
        if (self::$instance) {
            if ($model)
                self::$instance->model = $model;
            return self::$instance;
        }
        self::$instance = new GController($model);
        return self::$instance;
    }
}