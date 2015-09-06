<?php
namespace upload;

/**
 * Upload Execption, thrown by the upload package
 * 
 * @author Elger van Boxtel
 */
class UploadException extends \Exception
{
	/**
	 * Creates a new UploadException
	 * 
	 * @param string $aMessage
	 * @param int $aCode = 0
	 * @param \Excpetion $aCause
	 */
	public function __construct( $aMessage, $aCode = 0 , $aCause = null )
	{
		parent::__construct( $aMessage, $aCode, $aCause  );
	}
}