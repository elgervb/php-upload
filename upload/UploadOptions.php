<?php
namespace upload;

/**
 * Options to configure the upload
 * 
 * @author Elger van Boxtel
 */
class UploadOptions
{
	/**
	 *
	 * @var \SplFileInfo
	 */
	private $uploadDir;
	
	/**
	 *
	 * @var array
	 */
	private $allowedMimetypes = array();
	
	/**
	 *
	 * @var int
	 */
	private $maxSize;
	
	/**
	 *
	 * @var int
	 */
	private $maxFiles = 1;
	
	/**
	 *
	 * @var int
	 */
	private $maxTotalSize;
	
	/**
	 *
	 * @var boolean
	 */
	private $allowOverwrite = false;
	
	/**
	 * Creates a new UploadOptions
	 */
	public function __construct()
	{
	    //
	}
	
	/**
	 * Add a mimetype to the allowed mimetype list
	 *
	 * @param $aMimetype string
	 */
	public function addMimetype( $aMimetype )
	{
		$this->allowedMimetypes[] = $aMimetype;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getAllowOverwrite()
	{
		return $this->allowOverwrite;
	}
	
	/**
	 *
	 * @return \SplFileInfo
	 */
	public function getUploadDir()
	{
		return $this->uploadDir;
	}
	
	/**
	 *
	 * @return int
	 */
	public function getMaxFiles()
	{
		return $this->maxFiles;
	}
	
	/**
	 *
	 * @return int
	 */
	public function getMaxSize()
	{
	    // replace shorthand  20M = 20000000 bytes
		$systemSize = str_replace('M', '000000', ini_get('upload_max_filesize'));
	    if ($systemSize < $this->maxSize){
	        return $systemSize;
	    }
		return $this->maxSize;
	}
	
	/**
	 *
	 * @return int
	 */
	public function getMaxTotalSize()
	{
		return $this->maxTotalSize;
	}
	
	/**
	 *
	 * @return array with all mimetypes
	 */
	public function getMimetypes()
	{
		return $this->allowedMimetypes;
		
	}
	
	/**
	 *
	 * @param $allowOverwrite boolean
	 * 
	 * @return UploadOptions
	 */
	public function setAllowOverwrite( $allowOverwrite )
	{
		$this->allowOverwrite = $allowOverwrite;
		return $this;
	}
	
	/**
	 *
	 * @param aMaxFiless int
	 * 
	 * @return UploadOptions
	 */
	public function setMaxFiles( $aMaxFiles )
	{
		$this->maxFiles = $aMaxFiles;
		return $this;
	}
	
	/**
	 * Sets the max upload size for this upload batch (all size together)
	 *
	 * @param $maxTotalSize int in bytes
	 * 
	 * @return UploadOptions
	 */
	public function setMaxTotalSize( $maxTotalSize )
	{
		$this->maxTotalSize = $maxTotalSize;
		return $this;
	}
	
	/**
	 * Sets the max file size (in bytes) for each upload
	 *
	 * @param $maxSize int the max size in bytes
	 * 
	 * @return UploadOptions
	 */
	public function setMaxSize( $maxSize )
	{
		$this->maxSize = $maxSize;
		
		// replace shorthand  20M = 20000000 bytes
		$systemSize = str_replace('M', '000000', ini_get('upload_max_filesize'));
		
		if ($systemSize < $maxSize){
		    ini_set('upload_max_filesize', $maxSize);
		    $this->maxSize = str_replace('M', '000000', ini_get('upload_max_filesize'));
		}
		return $this;
	}
	
	/**
	 *
	 * @param $allowedMimetypes string
	 *
	 * @see addMimetype(string $aMimetype)
	 * 
	 * @return UploadOptions
	 */
	public function setMimetype( $aMimetype )
	{
		assert( 'is_string($aMimetype)' );
		
		$this->addMimetype( $aMimetype );
		return $this;
	}
	
	/**
	 *
	 * @param $allowedMimetypes array
	 * 
	 * @return UploadOptions
	 */
	public function setMimetypes( array $aMimetypes )
	{
		if ($this->allowedMimetypes === null)
		{
			$this->allowedMimetypes = $aMimetypes;
		}
		else
		{
			foreach ($aMimetypes as $mimetype)
			{
				$this->addMimetype( $mimetype );
			}
		}
		
		return $this;
	}
	
	
	/**
	 * Set the upload directory
	 *
	 * @param $uploadDir \SplFileInfo
	 * @param $createIfNotExists create the new directory when it does not exists
	 * 
	 * @return UploadOptions
	 */
	public function setUploadDir(\SplFileInfo $uploadDir, $createIfNotExists = false )
	{
	    if ($createIfNotExists && !$uploadDir->isDir()){
	        mkdir($uploadDir, 0755, true);
	    }
	    
		$this->uploadDir = $uploadDir;
		return $this;
	}
}
