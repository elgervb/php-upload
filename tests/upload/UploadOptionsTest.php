<?php
namespace ns;

use upload\UploadOptions;
/**
 *
 * @author eaboxt
 *        
 */
class UploadOptionsTest extends \PHPUnit_Framework_TestCase
{
    private $options;
    
    protected function setUp()
    {
        parent::setUp();
        $this->options = new UploadOptions();
    }

    
    public function testAllowOverwrite(){
        $this->options->setAllowOverwrite(true);
        
        $this->assertTrue($this->options->getAllowOverwrite());
    }
}