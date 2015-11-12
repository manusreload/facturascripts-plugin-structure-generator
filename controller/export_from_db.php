<?php
class export_from_db extends fs_controller
{

    function __construct()
    {
        parent::__construct(__CLASS__, 'Generar Contenido', 'admin', TRUE, TRUE);
    }
    var $items;
    var $tables;
    var $table;
    var $foreign;
    var $all;
    protected function private_core()
    {
        $this->all = $_GET['all'];
        if(!isset($_GET['table']))
        {
            $this->tables = $this->db->select("SHOW TABLES");
        }
        else{
            $this->table = $_GET['table'];
            $this->items = $this->db->select("DESCRIBE {$_GET['table']}");
            $this->model = $this->parseModel($this->table);
            $this->foreign = $this->db->select("select
    column_name as 'key',
    referenced_table_name as 'table',
    referenced_column_name as 'column',
    concat(referenced_table_name, '_', referenced_column_name) as target
from
    information_schema.key_column_usage
where
    referenced_table_name is not null
    and table_name = '{$this->table}'");
        }
        return false;
    }

    public function debug($item)
    {
        return print_r($item, true);
    }
    public function parseType($type)
    {
        $type = str_replace("varchar", "character varying", $type); // varchar(*) -> character varying(*)
        $type = preg_replace("/^double$/", "double precision", $type); // double -> double precision
        $type = preg_replace("/^int\(\d+\)/", "integer", $type); // int(*) -> integer
        $type = preg_replace("/tinyint\(1\)/", "boolean", $type); // tinyint(1) -> boolean
        $type = preg_replace("/^timestamp$/", "timestamp without time zone", $type); // timestamp -> timestamp without time zone

        return $type;
    }

    public function parseDefault($table, $item)
    {
        if(strtolower($item['Extra']) == "auto_increment")
        {
            return "nextval('{$table}_{$item['Field']}_seq'::regclass)";
        }
        return $item['Default'];
    }

    public function parseModel($model)
    {
        if(substr($model, strlen($model) - 1) == "s")
        {
            $tmp = substr($model, 0, strlen($model) - 1);
            if($this->validModel($tmp)) return $tmp;
            $tmp = substr($model, 0, strlen($model) - 2);
            if($this->validModel($tmp)) return $tmp;
        }
        return $model;
    }

    public function isInternalModel($model)
    {
        $items = array('albaranescli',
            'albaranesprov',
            'almacene',
            'articulostarifa',
            'cargo',
            'certificado',
            'co_asiento',
            'co_codbalances08',
            'co_conceptospar',
            'co_cuenta',
            'co_cuentascb',
            'co_cuentascbba',
            'co_cuentasesp',
            'co_epigrafe',
            'co_gruposepigrafe',
            'co_regiva',
            'co_secuencia',
            'co_subcuenta',
            'co_subcuentascli',
            'co_subcuentasprov',
            'concepto',
            'consumo',
            'contadore',
            'cuentasbanco',
            'cuentasbcocli',
            'cuentasbcopro',
            'dircliente',
            'dirproveedores',
            'facturaciones',
            'facturascli',
            'facturasprov',
            'formaspago',
            'fs_acces',
            'fs_extensions2',
            'gruposcliente',
            'lectura',
            'lineasalbaranescli',
            'lineasalbaranesprov',
            'lineasfacturascli',
            'lineasfacturasprov',
            'lineasivafactcli',
            'lineasregstock',
            'paise',
            'proveedores',
            'sectore',
            'secuenciasejercicio',
            'tecnico');

        return in_array($model, $items);
    }

    public function validModel($model)
    {
        require_model($model . ".php");
        return in_array($model . ".php", $GLOBALS['models']);
    }

    public function evalFunction($type, $text)
    {

        if(strpos($type, "tinyint") !== FALSE)
        {
            return '$this->str2bool($data[\'' . $text . '\'])';
        }

        if(strpos($type, "int") !== FALSE)
        {
            return '$this->intval($data[\'' . $text . '\'])';
        }
        if(strpos($type, "timestamp") !== FALSE)
        {
            return 'Date(\'d-m-Y H:i:s\', strtotime($data[\'' . $text . '\']))';
        }
        if(strpos($type, "double") !== FALSE)
        {
            return 'floatval($data[\'' . $text . '\'])';
        }

        return '$data[\'' . $text . '\']';

    }


}