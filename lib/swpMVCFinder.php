<?php

class swpMVCFinder
{
    public function find($args)
    {
        $vals = array();
        $qstring = array();
        foreach($args as $col => $val)
        {
            $op = new swpMVCFinderOperator($col, $val);
            $qstring[] = $op->qstring();
            $vals = $op->add_values($vals);
        }
        array_unshift($vals, join(' AND ', $qstring));
        return $vals;
    }

}

class swpMVCFinderOperator
{
    private $column;
    private $original_column;
    private $value;
    private $_operators = array(
        '$lte:' => ' <= ', '$gte:' => ' >= ', '$rxor:' => ' OR ', '$rxand:' => ' AND ',
        '$neq:' => ' <> ', '$ni:' => ' NOT IN ');

    public function __construct($col, $val)
    {
        $this->column = addslashes($col);
        $this->original_column = $col;
        $this->value = $val;
    }
    
    public function qstring()
    {
        $q = is_array($this->value) ? '(?)' : '?';
        $o = (is_array($this->value)) ? ' IN ' : ' = ';
        foreach($this->_operators as $k => $v)
            if (substr($this->column, 0, strlen($k)) === $k
                and $m = preg_replace('/[\$\:]/', '', $k)
                    and is_callable(array($this, $m))
                        and $this->column = str_replace($k, '', $this->column))
                        return $this->$m();
        foreach($this->_operators as $k => $v)
            if (substr($this->column, 0, strlen($k)) === $k
                and $this->column = str_replace($k, '', $this->column))
                    $o = $v;
        return $this->column.$o.$q;
    }
    
    public function add_values($vals)
    {
        if (!$this->values_should_be_flattened()) $vals[] = $this->value;
        else foreach($this->value as $val) $vals[] = $val;
        return $vals;
    }
    
    private function values_should_be_flattened()
    {
        $flatteners = array('$rxor:', '$rxand:');
        foreach($flatteners as $k)
            if (substr($this->original_column, 0, strlen($k)) === $k) return true;
        return false;
    }
    
    private function rxop($operator)
    {
        $r = array();
        if (!is_array($this->value)) return '';
        foreach($this->value as $v) $r[] = $this->column.' REGEXP ?';
        return '('.join(' '.$operator.' ', $r).')';
    }
    
    public function rxor()
    {
        return $this->rxop('OR');
    }
    
    public function rxand()
    {
        return $this->rxop('AND');
    }
    
}
