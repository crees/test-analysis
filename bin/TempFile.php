<?php

namespace TestAnalysis;

// Shamelessly stolen from http://nathanielstory.com/2014/10/01/delete-temp-files-in-php.html
// Thanks Nathan!

class TempFile {
    protected $path;
    
    public function __construct(string $prefix = '') {
        $this->path = tempnam(sys_get_temp_dir(), $prefix);
    }
    
    public function getPath() {
        return $this->path;
    }
    
    public function __destruct() {
        unlink($this->path);
    }
}
