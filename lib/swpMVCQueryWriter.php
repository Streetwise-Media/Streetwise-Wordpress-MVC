<?php

class swpMVCQueryWriter
{
    private $_operators = array('$lte:' => ' <= ', '$gte:' => ' >= ', '$or:' => ' OR ', '$neq:' => '<>', '$ni:' => ' NOT IN ');
    private $_table;
    
    public function __construct($table=false)
    {
        $this->_table = ($table) ? $table.'.' : '';
    }
    
    public function prefixes()
    {
        return array_keys($this->_operators);
    }
    
    public function querykey($property, $args)
    {
        $r = array();
        foreach($this->prefixes() as $prefix)
        {
            if (in_array($prefix.$property, array_keys($args))) $r[] = $prefix.$property;
        }
        if (in_array($property, array_keys($args))) $r[] = $property;
        return $r;
    }
    
    public function WHEREIN($col, $formats, $op=false)
    {
        $o = ($op) ? $this->_operators[$op] : ' IN ';
        $g = ',';
        $v = '('.join($g, $formats).')';
        return ("{$this->_table}$col $o $v");
    }
    
    public function WHEREEQ($col, $format, $op=false)
    {
        $o = ($op) ? $this->_operators[$op] : ' = ';
        return "{$this->_table}$col $o $format";
    }
    
    public function WHEREREGEXP($col, $formats, $ao=false)
    {
        $o = ' REGEXP ';
        $ret = array();
        foreach($formats as $v)
        {
            $ret[] = "{$this->_table}$col $o $v";
        }
        $g = ($ao) ? $this->_operators[$ao] : ' AND ';
        return "(".join($g, $ret).")";
    }
    
}