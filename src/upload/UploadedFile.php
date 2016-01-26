<?php
namespace upload;

use upload\UploadException;

/**
 * a single uploaded file
 * 
 * @author Elger van Boxtel
 */
class UploadedFile extends \SplFileInfo
{
	/**
	 * @var int one of the UPLOAD_ERR_** constants
	 */
	private $error;
	
	/**
	 * The size in bytes of the uploaded file
	 * 
	 * @var int
	 */
	private $uploadedSize;
	
	/**
	 * The filename of the original file uploaded by the user
	 *
	 * @var String
	 */
	private $origFileName;
	
	/**
	 * The mime type of the uploaded file
	 *
	 * @var string
	 */
	private $mimetype;
	
	/**
	 * Creates a new UploadedFile form a $_FILES array, this should contain the keys: tmp_name, size, error, type, name
	 *
	 * @param $aFileUploadArray array The $_FILES[] array for each file
	 */
	public function __construct( array $aFileUploadArray )
	{
		assert( 'array_key_exists( "tmp_name", $aFileUploadArray )' );
		assert( 'array_key_exists( "size", $aFileUploadArray )' );
		assert( 'array_key_exists( "error", $aFileUploadArray )' );
		assert( 'array_key_exists( "type", $aFileUploadArray )' );
		assert( 'array_key_exists( "name", $aFileUploadArray )' );
		
		parent::__construct( $aFileUploadArray['tmp_name'] );
		
		$this->setInfoClass(get_class($this));
		
		$this->uploadedSize = $aFileUploadArray['size'];
		$this->error = $aFileUploadArray['error'];
		$this->mimetype = $aFileUploadArray['type'];
		$this->origFileName = $aFileUploadArray['name'];
	}
	
	public static function createFrom( UploadedFile $aOriginal,\SplFileInfo $aFile )
	{
		return new UploadedFile( $aOriginal->getUploadArrayWithNewPath( $aFile->getPathname() ) );
	}
	
	public function getUploadArrayWithNewPath( $aPath )
	{
		return array("tmp_name" => $aPath , "size" => "$this->uploadedSize" , "error" => $this->error , "type" => $this->mimetype , "name" => "$this->origFileName");
	}
	
	/**
	 * Returns the upload error
	 *
	 * @return int
	 */
	public function getError()
	{
		return $this->error;
	}
	
	/**
	 * Returns the file extension of the original filename
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return pathinfo( $this->getOriginalFilename(), PATHINFO_EXTENSION );
	}
	
	/**
	 * Returns the filename without the extension
	 *
	 * @return string the filename of the file without extension
	 */
	public function getFilenameWithoutExtension()
	{
		$ext = $this->getExtension();
		if (empty( $ext ))
		{
			return $this->getFilename();
		}
		
		return str_replace( '.' . $ext, "", $this->getOriginalFilename() );
	
	}
	
	/**
	 * Returns the original filename: the filename the user entered
	 *
	 * @return string
	 */
	public function getOriginalFilename()
	{
		return $this->origFileName;
	}
	
	/**
	 * Returns the size of a file in bytes
	 *
	 * Overridden because the parent always return 0
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->uploadedSize;
	}
	
	/**
	 * Checks if a file with the same original filename (!) already exists in another directory
	 *
	 * @param $obj $aPath
	 *
	 * @return boolean
	 */
	public function existsInDir( \SplFileInfo $aPath )
	{
		assert( '$aPath->isDir()' );
		
		return file_exists( $aPath . DIRECTORY_SEPARATOR . $this->getOriginalFilename() );
	}
	
	/**
	 * Returns the mime type of the uploaded file
	 *
	 * @return string the mime type
	 */
	public function getMimeType()
	{
		return $this->mimetype;
	}
	
	/**
	 * Returns if the uploaded file has an error
	 *
	 * @link http://nl3.php.net/manual/en/features.file-upload.errors.php
	 *      
	 * @return boolean
	 */
	public function hasError()
	{
		return $this->error !== null && $this->error !== UPLOAD_ERR_OK;
	}
	
	/**
	 * Moves or renames a file; If the new path is a directory, then the original filename will be used when moving the file
	 *
	 * @param $aNewPath SplFileInfo The new filename or directory to move the file to
	 * @param $aIsOverwrite boolean [false] Overwrite an existing file
	 *       
	 * @return SplFileInfo The moved file
	 *        
	 * @throws UploadException when file already exists and overwrite is false
	 */
	public function move( \SplFileInfo $aNewPath, $aIsOverwrite = false )
	{
		if ($aNewPath->isDir())
		{
			$newPath = new \SplFileInfo( $aNewPath . "/" . $this->getOriginalFilename() );
		}
		else
		{
			$newPath = $aNewPath;
		}
		
		if ($newPath->isFile())
		{
			if ($aIsOverwrite)
			{
				// remove the new file so we can move the newly uploaded one
				unlink( $newPath->getPathname() );
			}
			else
			{
				throw new UploadException( "File exists " . $aNewPath->getFilename() );
			}
		}
		
		if (! rename( $this->getPathname(), $newPath ))
		{
			throw new UploadException( "Could not move file " . $newPath->getPathname() );
		}
		
		return new \SplFileInfo( $newPath );
	}
}