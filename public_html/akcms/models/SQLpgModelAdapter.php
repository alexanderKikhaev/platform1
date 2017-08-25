<?php
const RAWSQL = 'rawsql';
/**
 * Class SQLpgModelAdapter
 */
trait SQLpgModelAdapter {
    private $sqlres = NULL;
    private $sqlpos = 0;
    private $recors = 0;
    private $position = 0;
    private $datapos = -1;
    //protected $data = array();
    protected $result_type = PGSQL_ASSOC;
    /* @var pgdb */
    private $sql;
    /* SELECT SQL statement */

    public $query = '';
    private $query_fields = [];
    private $query_from = [];
    private $query_where = [];
    private $query_order = [];
    private $query_limit = 0;
    private $query_page = 0;
    private $query_pageSize = 50;

    private function genFiledsDBList(){
        if (isset($this->struct['fieldsDB'])) return;
        foreach ($this->filled as $k=>$v) {
            $field = $this->struct['fields'][$k];
            $this->struct['fieldsDB'][$field['COLUMN_NAME']] = &$field;
        }
    }

    /**
     * Set query fields
     *
     * @param array|string $fields
     * @return $this
     */
    public function fields($fields = []) {
        $this->query = '!';
        $this->query_fields = $fields;
        return $this;
    }

    /**
     * Set query from
     *
     * @param array|string $from
     * @return $this
     */
    public function from($from = []) {
        $this->query = '!';
        $this->query_from = $from;
        return $this;
    }

    /**
     * Generate where string
     * @param array $where
     * @return string
     * @throws DBException
     */
    private function _where(array $where = []) {
        if (count($where)==3 && is_string($where[1])) {
            $field = @$this->struct['fields'][$this->struct['fieldsDB'][$where[0]]];
            if ($field!==NULL) {
                $type = is_object($where[2])?get_class($where[2]):gettype($where[2]);
                if ($type==RAWSQL) return $where[0].$where[1].$where[2];
                $FieldClass = 'CMS'.$field['FIELD_CLASS'];
                if (strcasecmp($where[1],'IN')===0) $where[1] = '=ANY';
                /* @var CMSFieldAbstract */
                if (preg_match('/^(\=|\<|\>)ANY$/iu',$where[1])) {
                    if (!is_array($where[2])) $where[2] = [$where[2]];
                    $f = [];
                    foreach ($where[2] as $f_) {$f[] = '('.$FieldClass::quote($this->sql,$f_).')';}
                    return $where[0].$where[1].'(VALUES'.implode(',',$f).')';
                }
                else return $where[0].$where[1].$FieldClass::quote($this->sql,$where[2]);
            } else throw new DBException('Where field not found '.$where[0]);

        }
        if (count($where)==4 && is_string($where[1])) {
            $field = @$this->struct['fields'][$this->struct['fieldsDB'][$where[0]]];
            if ($field!==NULL) {
                $FieldClass = 'CMS'.$field['FIELD_CLASS'];
                /* @var CMSFieldAbstract */
                return $where[0].' BETWEEN '.$FieldClass::quote($this->sql,$where[2]).' AND '.$FieldClass::quote($this->sql,$where[3]);
            } else throw new DBException('Where field not found '.$where[0]);

        }

    }


    /**
     * Set query where
     *
     * @param array|string|int $where id value may be only int. Otherwise go another way
     * @return $this
     */
    public function where($where = []) {
        $this->query = '!';
        if (func_num_args()==3) $where = $this->_where(func_get_args());
        else if (is_array($where)) {
            $f = [];
            foreach ($where as $w) {
                $f[] = $this->_where($w);
            }
            $where = implode(' AND ',$f);
        }
        $this->query_where = $where;
        return $this;
    }

    public function AND_($where = []) {
        $this->query = '!';
        if (func_num_args()===3) $where = $this->_where(func_get_args());
        else if (is_array($where)) {
            $f = [];
            foreach ($where as $w) {
                $f[] = $this->_where($w);
            }
            $where = implode(' AND ',$f);
        }
        $this->query_where .= ' OR ('.$where.')';
        return $this;
    }

    public function OR_($where = []) {
        $this->query = '!';
        if (func_num_args()==3) $where = $this->_where(func_get_args());
        else if (is_array($where)) {
            $f = [];
            foreach ($where as $w) {
                $f[] = $this->_where($w);
            }
            $where = implode(' AND ',$f);
        }
        $this->query_where .= ' OR ('.$where.')';
        return $this;
    }

    /**
     * Set query order
     *
     * @param array $order
     * @return $this
     */
    public function order($order = []) {
        $this->query = '!';
        $this->query_order = $order;
        return $this;
    }

    /**
     * Set query limit
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 0) {
        $this->query = '!';
        $this->query_limit = $limit;
        return $this;
    }

    /**
     * Set query page
     *
     * @param int $page
     * @param null $pageSize
     * @return $this
     */
    public function page($page = 0,$pageSize=null) {
        $this->query = '!';
        $this->query_page = $page;
        $this->pageSize($pageSize);
        return $this;
    }

    /**
     * Set size of query page
     *
     * @param null $pageSize
     * @return $this
     */
    public function pageSize($pageSize=null) {
        if (is_int($pageSize)) $this->query_pageSize = $pageSize;
        return $this;
    }

    /**
     * build query
     */
    private function buildQuery() {
        $query = 'SELECT ';

        if (is_string($this->query_fields)) $query .= $this->query_fields;
        elseif (count($this->query_fields)==0) $query .= '*';
        else $query .= implode(',',$this->query_fields);

        if (is_string($this->query_from)) $query .= ' FROM '.$this->query_from;
        elseif (count($this->query_from)==0) $query .= ' FROM '.static::$tableName;
        else $query .= ' FROM '.implode(' ',$this->query_from);

        //todo Сложные условия, указание сравнения
        if (is_int($this->query_where)) $query .= ' WHERE '.$this->_pr_whereID($this->query_where);
        if (is_object($this->query_where) && is_subclass_of($this->query_where,'modelAbstact')) $query .= ' WHERE '.$this->query_where->_pr_whereEQ();
        if (is_string($this->query_where) && $this->query_where!='') $query .= ' WHERE '.$this->query_where;
        if (is_array($this->query_where) && count($this->query_where)>0) $query .= ' WHERE '.implode(' AND ',$this->query_where);

        //todo указание направления сотрировки
        if (is_array($this->query_order) && count($this->query_order)>0) $query .= ' ORDER BY '.implode(',',$this->query_order);
        if (is_string($this->query_order) && $this->query_order!='') $query .= ' ORDER BY '.$this->query_order;

        if ($this->query_limit>0) $query .= ' LIMIT '.@(int)$this->query_limit;
        else if ($this->query_page>0)
            $query .= ' LIMIT '.$this->query_pageSize.' OFFSET '.($this->query_pageSize*($this->query_page-1));
        $this->query = $query;
    }

    /**
     * Set query
     *
     * @param array $fields
     * @param array $from
     * @param array $where
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param null $pageSize
     * @return $this
     */
    public function select($fields = ['*'],$from = [],$where = [],$order = [],$limit = 0,$page = 0,$pageSize = NULL){
        $this->fields($fields);
        $this->from($from);
        $this->where($where);
        $this->order($order);
        $this->limit($limit);
        $this->page($page,$pageSize);
        return $this;
    }

    /* @property array struct */

    /**
     * SQLpgModelAdapter constructor.
     * @param null|int|string|resource $any  id | SQL запрос | русурс запроса pgsql
     *
     * @throws DBException
     */
    function __construct($any = NULL) {
        $this->sql = $GLOBALS['sql'];
        if (is_int($any)) {
            $primary = $this->struct['primary'];
            if ($primary=='') throw new DBException('No primary for '.__CLASS__);
            $this->__set($primary,$any);
            $any = 'SELECT * FROM '.static::$tableName.' WHERE '.$this->_pr_whereID();
        }
        if (is_string($any) && mb_strlen($any)>0) {
            $this->query = $any;
            $this->get();
        }
        if (is_resource($any)) {
            $this->sqlres=$any;
            $this->recors = pg_num_rows($this->sqlres);
        }
    }

    public function update($where = null) {
        if ($where==null) $where=$this->_pr_whereID();
        $query = $this->pr_u($where);
        return $this->sql->command($query);
    }

    public function insert() {
        $query = $this->pr_i();
        $primary = $this->struct['primary'];
        if ($primary=='') {
            return $this->sql->command($query);
        }
        else {
            $field = $this->struct['fields'][$primary];
            $query .= ' RETURNING '.$field['COLUMN_NAME'];
            $res = $this->sql->query_one($query);
            if ($res!=false) {
                $this->data[$field['COLUMN_NAME']] = $res;
                return 1;
            } else return 0;
        }
    }

    /** Execute query ant return the result
     * @return $this
     * @throws DBException
     */
    public function get() {
        if ($this->query=='') throw new DBException('No query for '.__CLASS__);
        if ($this->query=='!') $this->buildQuery();
        $this->sqlres=$this->sql->query($this->query);
        $this->recors = pg_num_rows($this->sqlres);
        $this->current();
        return $this;
    }

    public function all() {
        return $this->where()->get();
    }

    public function __invoke()
    {
        return $this->get();
    }

    public function explain(){
        if ($this->query=='') throw new DBException('No query for '.__CLASS__);
        if ($this->query=='!') $this->buildQuery();
        $this->sql->query('begin');
        $res = implode("\n",$this->sql->query_all_column('EXPLAIN ANALYZE '.$this->query));
        $this->sql->query('rollback');
        echo $res."\n";
        return $this;
    }

    /** prepare update */
    private function pr_u($where='') {
        $_f = array();
        foreach ($this->filled as $k=>$v) if ($this->struct['primary']!=$k) {
            $field = $this->struct['fields'][$k];
            $FieldClass = 'CMS'.$field['FIELD_CLASS'];
            /* @var CMSFieldAbstract */
            $_f[] = $field['COLUMN_NAME'].'='.$FieldClass::quote($this->sql,$this->data[$field['COLUMN_NAME']]);
        }
        if (count($_f)==0) throw new DBException('No data to update for '.__CLASS__);
        return 'UPDATE '.static::$tableName.' SET '.implode(',',$_f).' WHERE '.$where;
    }

    /** prepare insert */
    private function pr_i() {
        $_f = array(); $_v = array();
        foreach ($this->filled as $k=>$v) if ($this->struct['primary']!=$k) {
            $field = $this->struct['fields'][$k];
            $FloatClass = 'CMS'.$field['FIELD_CLASS'];
            $_f[] = $field['COLUMN_NAME'];
            $_v[] = $FloatClass::quote($this->sql,$this->data[$field['COLUMN_NAME']]);
        }
        return 'INSERT INTO '.static::$tableName.'('.implode(',',$_f).') VALUES ('.implode(',',$_v).')';
    }

    /**
     * Prepare where part by ID
     * @param null $id
     * @return string
     * @throws DBException
     */
    private function _pr_whereID($id = null)
    {
        $primary = $this->struct['primary'];
        $field = $this->struct['fields'][$primary];
        if ($primary=='') throw new DBException('No primary for '.__CLASS__);
        $FieldClass = 'CMS'.$field['FIELD_CLASS'];
        if ($id === null) {
            if (!isset($this->data[$field['COLUMN_NAME']])) throw new DBException('Primary is unset for '.__CLASS__);
            return $field['COLUMN_NAME'].'='.$FieldClass::quote($this->sql,$this->data[$field['COLUMN_NAME']]);
        }
        else return $field['COLUMN_NAME'].'='.$FieldClass::quote($this->sql,$id);
    }

    /**
     * @return string
     * @throws DBException
     */
    public function _pr_whereEQ()
    {
        $_f = array();
        foreach ($this->filled as $k=>$v) if ($this->struct['primary']!=$k) {
            $field = $this->struct['fields'][$k];
            $FieldClass = 'CMS'.$field['FIELD_CLASS'];
            /* @var CMSFieldAbstract */
            $_f[] = $field['COLUMN_NAME'].'='.$FieldClass::quote($this->sql,$this->data[$field['COLUMN_NAME']]);
        }
        if (count($_f)==0 ) throw new DBException('No filled field for '.__CLASS__);
        return implode(' AND ',$_f);
    }

    function __destruct() {
        if ($this->sqlres) pg_free_result($this->sqlres);
    }

    function current() {
        if ($this->datapos !== $this->position)
        {
            if ($this->sqlpos!=$this->position) {
                pg_result_seek($this->sqlres,$this->position);
                $this->sqlpos = $this->position;
            }
            $this->fetch();
            $this->filled = array();
        }
        return $this;
    }
    function count() {return $this->recors;}
    function valid() {return $this->position<$this->recors;}
    function key() {return $this->position;}

    function rewind() {
        if ($this->sqlres) pg_result_seek($this->sqlres,0);
        $this->sqlpos = 0;
        $this->position=0;
    }
    function seek($position) {
        if ($this->sqlres) pg_result_seek($this->sqlres,$position);
        $this->sqlpos = $position;
        $this->position=$position;
    }
    function next() {++$this->position;}

    /* fetches */
    private function fetch_r() {
        $this->datapos = $this->sqlpos;
        $res = pg_fetch_row($this->sqlres);
        if ($res!==false) $this->sqlpos++;
        $this->data = $res;
        return $res;
    }

    private function fetch_a() {
        $this->datapos = $this->sqlpos;
        $res = pg_fetch_assoc($this->sqlres);
        if ($res!==false) $this->sqlpos++;
        $this->data = $res;
        return $res;
    }

    function fetch() {
        $this->datapos = $this->sqlpos;
        $res = pg_fetch_array($this->sqlres,null,$this->result_type);
        if ($res!==false) $this->sqlpos++;
        $this->data = $res;
        return $res;
    }
}

class rawsql{
    private $string = '';
    public function __construct($rawquery){
        $this->string = $rawquery;
    }

    public function __toString(){
        return $this->string;
    }
}