<?php
namespace TestAnalysis;

class ScannedTestPage extends DatabaseCollection
{
    const SCANNEDTEST_ID = 'ScannedTest_id';
    const TESTCOMPONENT_ID = 'TestComponent_id';
    const PAGE_NUM = 'page_num';
    const ANNOTATIONS = 'annotations';
    const STUDENT_ANNOTATIONS = 'annotations';
    const PAGE_SCORE = 'page_score';
    const SHA = 'sha';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        foreach ([  self::SCANNEDTEST_ID,
                    self::TESTCOMPONENT_ID,
                    self::PAGE_NUM,
                    self::ANNOTATIONS,
                    self::PAGE_SCORE,
                    self::STUDENT_ANNOTATIONS,
                    self::SHA,
                ] as $d) {
            $this->details[$d] = $details[$d] ?? null;
        }
    }
    
    public function get(String $detail) {
        if ($detail == 'imagedata') {
            // XXX deprecated
            return $this->getImageData();
        }
        return $this->details[$detail];
    }
    
    public function getImageData() {
        if (!is_null($this->details[self::SHA])) {
            $filename = Config::scannedTestPagedir . "/{$this->details[self::SHA]}.jpg";
            $f = fopen($filename, 'rb');
            $data = fread($f, filesize($filename));
            fclose($f);
            return $data;
        } else if (self::$db->dosql("SELECT version FROM `db_version`;")->fetch_row()[0] < 26) {
            $img = self::$db->dosql("SELECT `imagedata` FROM `ScannedTestPage` WHERE `id` = {$this->getId()};")->fetch_all()[0][0];
	    return $img;
        }
    }
    
    public static function retrieveByDetails(array $detailType, array $detail, string $orderBy = "") {
        /* We won't query the image data unless specifically requested */
        return parent::_retrieveByDetails($detailType, $detail, $orderBy, [
            self::ID,
            self::SCANNEDTEST_ID,
            self::TESTCOMPONENT_ID,
            self::PAGE_NUM,
            self::ANNOTATIONS,
            self::PAGE_SCORE,
            self::STUDENT_ANNOTATIONS,
            self::SHA
        ]);
    }
    
    public function setImageData($img) {
        $this->details[self::SHA] = hash('sha256', $img);
        
        $filename = Config::scannedTestPagedir . "/{$this->details[self::SHA]}.jpg";
        
        self::lock();
        
        if ($file = @fopen($filename, 'xb')) {
            fwrite($file, $img);
            fclose($file);
        }
        
        if (!file_exists($filename)) {
            // BIG PROBLEM
            throw new \Exception('File creation failed-- does ' . 
                Config::scannedTestPagedir . ' exist and can I write to it?');
        }
        
        // We can't afford to allow the change to not be atomic
        $this->commit();
        self::unlock();
    }
    
    public function setPageScore($score) {
        $this->details[self::PAGE_SCORE] = $score;
    }
    
    public static function garbageCollect(bool $silent = false) {
        self::lock();
        $hashes = self::retrieveUniqueValues(self::SHA);
        $files = scandir(Config::scannedTestPagedir);
        $files = array_diff($files, array('.', '..'));
        foreach ($hashes as $h) {
            $files = array_diff($files, array("$h.jpg"));
        }
        foreach ($files as $f) {
            if (!@unlink(Config::scannedTestPagedir . "/$f")) {
                if (!$silent) {
                    echo "Failed to garbage collect page: error was '" . error_get_last()['message'] . "'";
                }
            }
        }
        self::unlock();
    }
    
    public static function getImageFromHash(String $hash) {
        self::lock(true);
        $filename = Config::scannedTestPagedir . "/{$hash}.jpg";
        $f = fopen($filename, 'rb');
        $data = fread($f, filesize($filename));
        fclose($f);
        self::unlock();
        return $data;
    }
    
    function __destruct()
    {}
}