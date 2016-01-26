<?php
namespace upload;

use upload\UploadOptions;

/**
 * Upload manager to upload files to the server
 *
 * @author Elger van Boxtel
 *        
 * @see http://www.php.net/manual/en/features.file-upload.php
 */
class UploadManager
{
	/**
	 *
	 * @var UploadOptions
	 */
	private $options;
	
	/**
	 *
	 * @var \ArrayObject of UploadedFile
	 */
	private $files;
	
	/**
	 * Creates a new UploadManager
	 *
	 * @param $aUploadOption UploadOptions The upload options (optional)
	 */
	public function __construct( UploadOptions $uploadOptions = null )
	{
	    if (!$uploadOptions){
	        $uploadOptions = new UploadOptions();
	    }
		$this->options = $uploadOptions;
		
		$this->collectFiles();
	}
	
	/**
	 * Collects all files from the upload and stores them in a list
	 */
	private function collectFiles()
	{
		$this->files = new \ArrayObject();
		
		foreach ($_FILES as $upload)
		{
		    // handle multiple files  
			if (isset( $upload['name'] ) && is_array( $upload['name'] ))
			{
				// multi upload
				for ($i = 0; $i < count( $upload['name'] ); $i ++)
				{
					// sometimes a user uploads an emtpy input. This results
					// in an empty $_FILES entry.
					if ($upload['tmp_name'][$i] !== "" && $upload['tmp_name'][$i] !== null)
					{
						// Check for empty tmp_name, can occur when user does not
						// fill in the file input and submits the form
						if ( $upload['error'][$i] !== UPLOAD_ERR_NO_FILE )
						{
							$array = array();
							$array['name'] = $upload['namxe'][$i];
							$array['type'] = $upload['type'][$i];
							$array['tmp_name'] = $upload['tmp_name'][$i];
							$array['error'] = $upload['error'][$i];
							$array['size'] = $upload['size'][$i];
							
							$file = new UploadedFile( $array );
							$this->files->append( $file );
						}
					}
				}
			}
			else
			{
				// Check for empty tmp_name, can occur when user does not
				// fill in the file input and submits the form
				if ($upload['error'] !== UPLOAD_ERR_NO_FILE )
				{
					// single upload
					$file = new UploadedFile( $upload );
					$this->files->append( $file );
				}
			}
		}
	}
	
	/**
	 * Returns the number of files uploaded by the user
	 *
	 * @return int
	 */
	public function countFiles()
	{
		return $this->files->count();
	}
	
	/**
	 * Upload functionality
	 *
	 * @return ArrayObject list with uploaded files
	 *        
	 * @throws UploadException when file already exists
	 */
	private function doUpload($fnEach = null)
	{
		$list = new \ArrayObject();
		
		try
		{
			$index = 1;
			/* @var $file UploadedFile */
			foreach ($this->files as $file)
			{
				$uploaded = $this->uploadFile( $file, $index );
				if ($fnEach){
				    assert ('$fnEach instanceof \Closure');
				    
				    $file = $fnEach($file, $file->getOriginalFilename(), $index);
				    assert('$file === null || $file instanceof \SplFileInfo');
				    
				    if ($file){
				        $list->append($file);
				    }
				}
				else{
				    $list->append( $uploaded );
				}
				$index ++;
			}
		}
		catch (\Exception $e)
		{
			// catch whatever exception have occured and wrap it into an upload exception
			throw new UploadException( $e->getMessage(), 0, $e );
		}
		
		return $list;
	}
	
	/**
	 * Format a number of bytes into human readable format
	 *
	 * @param int $bytes
	 *
	 * @return string
	 */
	private function formatSize($bytes)
	{
	    if ($bytes >= 1073741824)
	    {
	        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
	    }
	    elseif ($bytes >= 1048576)
	    {
	        $bytes = number_format($bytes / 1048576, 2) . ' MB';
	    }
	    elseif ($bytes >= 1024)
	    {
	        $bytes = number_format($bytes / 1024, 2) . ' KB';
	    }
	    elseif ($bytes > 1)
	    {
	        $bytes = $bytes . ' bytes';
	    }
	    elseif ($bytes == 1)
	    {
	        $bytes = $bytes . ' byte';
	    }
	    else
	    {
	        $bytes = '0 bytes';
	    }
	
	    return $bytes;
	}
	
	/**
	 * Returns all uploaded files
	 *
	 * @return \ArrayIterator with UploadedFile
	 */
	public function getUploadedFiles()
	{
		return $this->files->getIterator();
	}
	
	/**
	 * Returns the php ini setting of the upload_max_filesize
	 *
	 * @return int Max file size in bytes
	 */
	public function getUploadMaxFileSize()
	{
		return ini_get( 'upload_max_filesize' );
	}
	
	/**
	 * Returns the size of all files together being uploaded
	 *
	 * @return int Size in bytes
	 */
	public function getUploadSize()
	{
		$iter = $this->files->getIterator();
		$result = 0;
		
		/* @var $file SplFileInfo */
		foreach ($this->files as $file)
		{
			$result += $file->getSize();
		}
		
		return $result;
	}
	
	/**
	 * Sets the php ini setting upload_max_filesize
	 *
	 * @param $aBytes int
	 */
	public function setUploadMaxFileSize( $aBytes )
	{
		assert( 'is_int($aBytes)' );
		
		ini_set( 'upload_max_filesize', $aBytes );
		
		if (ini_get( 'post_max_size' ) < $aBytes)
		{
			ini_set( 'post_max_size', $aBytes );
		}
	}
	
	/**
	 * Upload all files which were posted through a form (after validation)
	 * 
	 * @param $fnEach optional function to call for each uploaded file and must return the uploaded file: $fnEach($file, $file->getOriginalFilename(), $index);
	 *
	 * @return \ArrayObject list with uploaded files
	 *        
	 * @throws UploadException on error or on validation
	 */
	public function upload($fnEach = null)
	{
		$this->validateUploads();
		
		return $this->doUpload($fnEach);
	}
	
	/**
	 * Upload a file
	 *
	 * @param $aFile UploadedFile
	 * @param $aIndex int The index of the file being uploaded
	 * 
	 * @return UploadedFile
	 */
	private function uploadFile( UploadedFile $aFile, $aIndex )
	{
		 if ($this->options->getUploadDir()){
		    $filename = $aFile->getOriginalFilename();
		     
    		$file = $aFile->move( new \SplFileInfo( $this->options->getUploadDir() . DIRECTORY_SEPARATOR . $filename ), $this->options->getAllowOverwrite() );
    		return UploadedFile::createFrom($aFile, $file);
		 }
		 
		 return $aFile;
	}
	
	/**
	 * Validates the upload
	 *
	 * @throws UploadException on error
	 */
	private function validateUploads()
	{
		// if there are no files to validate...
		if ($this->files->count() === 0)
		{
			return;
		}
		
		if ($this->options->getMaxTotalSize() !== null && $this->getUploadSize() > $this->options->getMaxTotalSize())
		{
			throw new UploadException( "Total file size exceeds total allowed size " . $this->formatSize( $this->options->getMaxTotalSize() ) . ". " . $this->formatSize( $this->getUploadSize() ) . " with " . $this->files->count() . " files." );
		}
		
		if ($this->options->getMaxFiles() !== null && ($this->countFiles() > $this->options->getMaxFiles()))
		{
			throw new UploadException( "Max file exceeded, only " . $this->options->getMaxFiles() . " file(s) allowed." );
		}
		
		$error = "";
		/* @var $uploadFile UploadedFile */
		foreach ($this->files as $uploadFile)
		{
			try
			{
				$this->validateUpload( $uploadFile );
			}
			catch (UploadException $ex)
			{
				$error .= $ex->getMessage() . "\n";
			}
		}
		
		// if there are errors, throw an exception
		if (! empty( $error ))
		{
			throw new UploadException( $error );
		}
	}
	
	/**
	 * Validates a single uploaded file
	 *
	 * @param $aFile UploadedFile
	 *
	 * @throws UploadException on validation exception
	 */
	private function validateUpload( UploadedFile $aFile )
	{
		$uploadDir = $this->options->getUploadDir();
		if ($uploadDir && ! $uploadDir->isDir())
		{
			throw new UploadException( "Upload dir does not exist" );
		}
		
		
		$maxSize = $this->options->getMaxSize();
		$mimeTypes = $this->options->getMimetypes();
		$errors = array();
		
		// PHP system errors
		if ($aFile->getError()){
		    switch($aFile->getError()){
		    	case UPLOAD_ERR_CANT_WRITE : 
		    	    $errors[] = 'Failed to write file to disk.';
		    	    break;
		    	case UPLOAD_ERR_EXTENSION :
		    	    $errors[] = 'A PHP extension stopped the file upload.';
		    	    break;
		    	case UPLOAD_ERR_FORM_SIZE :
		    	    $errors[] = $aFile->getOriginalFilename() . " exceeds max size. Allowed is " . $this->formatSize( $maxSize );
		    	    break;
	    	    case UPLOAD_ERR_INI_SIZE :
	    	        $errors[] = $aFile->getOriginalFilename() . " exceeds max size. Allowed is " . $this->formatSize( $maxSize );
	    	        break;
    	        case UPLOAD_ERR_NO_FILE :
    	            $errors[] = 'No file was uploaded.';
    	            break;
	            case UPLOAD_ERR_NO_TMP_DIR :
	                throw new UploadException('Missing a temporary folder.');
	                break;
                case UPLOAD_ERR_PARTIAL :
                    $errors[] = 'The uploaded file was only partially uploaded. ';
                    break;
		    }
		}
		// User defined errors
		else {
		
    		if ($maxSize !== null)
    		{
    			if ($aFile->getSize() > $maxSize)
    			{
    				$errors[] = $aFile->getOriginalFilename() . " exceeds max size. Allowed is " . $this->formatSize( $maxSize ) . " but was " . $this->formatSize( $aFile->getSize() );
    			}
    		}
    		
    		if ($mimeTypes !== null && count($mimeTypes) > 0)
    		{
    			if (! in_array( $aFile->getMimeType(), $mimeTypes ))
    			{
    				$errors[] = $aFile->getOriginalFilename() . " is not allowed to be uploaded (" . $aFile->getMimeType() . ")";
    			}
    		}
    		
    		// Upload dir can be null
    		if ($this->options->getAllowOverwrite() === false && $uploadDir && $aFile->existsInDir( $uploadDir ))
    		{
    			$errors[] = "File " . $aFile->getOriginalFilename() . " already exists.";
    		}
		
		}
		
		// if there are errors, throw an exception
		if (count( $errors ) > 0)
		{
			$excString = "";
			foreach ($errors as $error)
			{
				$excString .= $error . "\n";
			}
			
			throw new UploadException( trim( $excString ) );
		}
	}
}