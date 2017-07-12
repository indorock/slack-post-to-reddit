<?php

class XPath_Query{

    private $doc;
    private $xpath;
    private $file;

    public function __construct($file){
        $this->file = $file;
        $this->doc = new DOMDocument();
        $this->doc->load($file);
        $this->xpath = new DOMXPath($this->doc);
    }

    public function get_nodelist($query, $context = null){
        if($context == null) $context = $this->doc->documentElement;
        return $this->xpath->query($query, $context);
    }

    public function get_node($query, $context = null){
        if($context == null) $context = $this->doc->documentElement;
        $nodes = $this->xpath->query($query, $context);
        if($nodes->length >0) return $nodes->item(0);
        else{
//            echo 'ERROR: query ' . $query . ' failed, no node found.';
            return null;
        }
    }

    public function get_value($query, $context = null){
        if($context == null) $context = $this->doc->documentElement;
        $node = $this->get_node($query, $context);
        if($node) return trim($node->nodeValue);
        else{
//            echo 'ERROR: query ' . $query . ' failed, no node value found.';
            return null;
        }
    }

    public function set_value($node, $value){
        $node->nodeValue = $value;
    }

    public function set_attribute($node, $name, $value){
        $node->setAttribute($name, $value);
    }

    public function saveFile(){
        $this->doc->save($this->file);
    }
}
