<?php
namespace Jolharg\Socket\Listen;

use Jolharg\SocketAbstract;

class Client extends SocketAbstract
{
	private $_onRead;
	private $_onDisconnect;
	private $_onConnect;

	public function __construct($resourceSocket)
	{
		$this->_bConnected = true;
		$this->_resourceSocket = $resourceSocket;
	}

	public function getResource()
	{
		return $this->_resourceSocket;
	}

	public function onRead(Callable $closure)
	{
		$this->_onRead = $closure;
		return $this;
	}

	public function onDisconnect(Callable $closure)
	{
		$this->_onDisconnect = $closure;
		return $this;
	}

	public function invokeRead($strData)
	{
		echo __METHOD__.PHP_EOL;
		ob_flush();
		flush();
		if(is_null($this->_onRead)) {
			echo 'No read handler'.PHP_EOL; ob_flush();flush();
			return;
		}
		$callback = $this->_onRead;
		$callback($strData);
		return $this;
	}

	public function invokeDisconnect()
	{
		echo __METHOD__.PHP_EOL;
		ob_flush();
		flush();
		if(is_null($this->_onDisconnect)) {
			echo 'No disconnect handler'.PHP_EOL; ob_flush();flush();
			return;
		}
		$callback = $this->_onDisconnect;
		$callback();
		$this->_onRead = null;
		return $this;
	}
}
