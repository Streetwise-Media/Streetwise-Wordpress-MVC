<?php

class testUtilities
{
    
    public function dump($val=false)
    {
        echo '<pre>'.print_r($val, true).'</pre>';
    }
    
    public function minh($html)
    {
        return (preg_replace('/\s+/', '', htmlentities($html)));
    }
}