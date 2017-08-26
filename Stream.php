<?php

namespace Kapi\Http;

use InvalidArgumentException;

class Stream extends AbstractStream
{
	/**
	 * Stream constructor.
	 *
	 * @param string|resource $stream
	 * @param string $mode Mode with which to open stream
	 * @throws InvalidArgumentException
	 */
	public function __construct($stream, $mode = 'r')
	{
		$this->setStream($stream, $mode);
	}

	/**
	 *	Closes the resource when destruct
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Attach a new stream/resource to the instance.
	 *
	 * @param string|resource $resource
	 * @param string $mode
	 * @throws InvalidArgumentException for stream identifier that cannot be
	 *     cast to a resource
	 * @throws InvalidArgumentException for non-resource stream
	 */
	public function attach($resource, $mode = 'r')
	{
		$this->setStream($resource, $mode);
	}

	/**
	 * Set the internal stream resource.
	 *
	 * @param string|resource $stream String stream target or stream resource.
	 * @param string $mode Resource mode for stream target.
	 * @throws InvalidArgumentException for invalid streams or resources.
	 */
	private function setStream($stream, $mode = 'r')
	{
		$error    = null;
		$resource = $stream;

		if (is_string($stream)) {
			set_error_handler(function ($e) use (&$error) {
				$error = $e;
			}, E_WARNING);
			$resource = fopen($stream, $mode);
			restore_error_handler();
		}

		if ($error) {
			throw new InvalidArgumentException('Invalid stream reference provided');
		}

		if (!is_resource($resource) || 'stream' !== get_resource_type($resource)) {
			throw new InvalidArgumentException(
				'Invalid stream provided; must be a string stream identifier or stream resource'
			);
		}

		$this->resource = $resource;
	}
}