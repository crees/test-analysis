<?php
namespace TestAnalysis;

class ScannedTestPage extends DatabaseCollection
{
    const SCANNEDTEST_ID = 'ScannedTest_id';
    const TESTCOMPONENT_ID = 'TestComponent_id';
    const PAGE_NUM = 'page_num';
    const IMAGEDATA = 'imagedata';
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
                    self::IMAGEDATA,
                    self::ANNOTATIONS,
                    self::PAGE_SCORE,
                    self::STUDENT_ANNOTATIONS,
                    self::SHA,
                ] as $d) {
            $this->details[$d] = $details[$d] ?? null;
        }
    }
    
    public function get(String $detail) {
        if ($detail == self::IMAGEDATA) {
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
        }
        if (is_null($this->details[self::IMAGEDATA])) {
            $this->details[self::IMAGEDATA] = parent::_retrieveByDetails([self::ID], [$this->getId()], "", [self::IMAGEDATA])[0]->get(self::IMAGEDATA);
        }
        return $this->details[self::IMAGEDATA];
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
        $oldsha = $this->details[self::SHA] ?? null;
        $newsha = hash('sha256', $img);
        
        $filename = Config::scannedTestPagedir . "/$newsha.jpg";
        
        if ($file = @fopen($filename, 'xb')) {
            fwrite($file, $img);
            fclose($file);
        }
        
        if (!file_exists($filename)) {
            // BIG PROBLEM
            throw new Exception('File creation failed-- does ' . 
                Config::scannedTestPagedir . ' exist and can I write to it?');
        }
        
        $this->details[self::SHA] = $newsha;
        
        // We can't afford to allow the change to not be atomic
        $this->commit([self::IMAGEDATA]);
        
        if (!is_null($oldsha)) {
            self::garbageCollect($oldsha);
        }
    }
    
    public function setPageScore($score) {
        $this->details[self::PAGE_SCORE] = $score;
    }
    
    public static function garbageCollect($sha = null) {
        if (!is_null($sha)) {
            if (self::retrieveByDetail(self::SHA, $sha) == []) {
                // No more references, garbage collect
                unlink(Config::scannedTestPagedir . "/$sha.jpg");
            }
        } else {
            $hashes = self::retrieveUniqueValues(self::SHA);
            $files = scandir(Config::scannedTestPagedir);
            $files = array_diff($files, array('.', '..'));
            foreach ($hashes as $h) {
                $files = array_diff($files, array("$h.jpg"));
            }
            foreach ($files as $f) {
                unlink(Config::scannedTestPagedir . "/$f");
            }
        }
    }
    
    function __destruct()
    {}
}