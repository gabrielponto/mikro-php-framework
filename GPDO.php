<?php
class GPDO {
    protected $dsn = 'mysql:dbname=testdb;host=127.0.0.1';
    protected $user = 'root';
    protected $password = '';
    
    protected $_pdo;
    
    static protected $_instance = null;
    
    public function __construct() {
        if (!isset(GManager::app()->config->db)) {
            throw new Exception('A configuração do banco não está definida');
        }
        try {
            $dsn = GManager::app()->config->db->connectionString;
            $user = GManager::app()->config->db->user;
            $password = GManager::app()->config->db->password;
            $this->_pdo = new PDO($dsn, $user, $password);
        } catch (PDOException $e) {
            echo 'GPDO - PDOException - Connection failed: ' . $e->getMessage();
        } catch (Exception $e) {
            echo 'GPDO - Exception - Connection failed: '. $e->getMessage();
        }
    }
    public function initTransaction() {
        $this->_pdo->beginTransaction();
    }
    
    public function commit() {
        $this->_pdo->commit();
    }
    
    public function rollBack() {
        $this->_pdo->rollBack();
    }
    
    public static function instance() {
        if (self::$_instance !== null) return self::$_instance;
        self::$_instance = new GPDO();
        return self::$_instance;
    }
    
    public function query($sql) {
        return $this->_pdo->query($sql);
    }
    
    public function exec($sql) {
        return $this->_pdo->exec($sql);
    }
    
    public function id() {
        return $this->_pdo->lastInsertId();
    }
    
    public function columns($table) {
        $q = $this->_pdo->prepare("DESCRIBE `$table`");
        $q->execute();
        return $q->fetchAll(PDO::FETCH_COLUMN);
    }
}