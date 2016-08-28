 <?php
namespace Jolharg\Socket\Listen;

class Tcp extends ListenAbstract
{
	const PROTOCOL = SOL_TCP;

	private static $_arrConnections;

	private $_onConnect;

	private $_arrClients = array();

	public static function create($strHost, $intPort)
	{
		$strId = self::_createId($strHost, $intPort);
		if(isset(self::$_arrConnections[$strId])) {
			return self::$_arrConnections[$strId];
		}

		self::$_arrConnections[$strId] = new self($strHost, $intPort);
		return self::$_arrConnections[$strId];
	}

	protected function _getProtocol()
	{
		return self::PROTOCOL;
	}

	public function broadcast($strText)
	{
		if(empty($this->_arrClients)) {
			return;
		}
		foreach($this->_arrClients as $socketClient) {
			$client = new Chaplin_Socket_Listen_Client($socketClient);
			$client->write($strText);
		}
	}

	public function listen(Callable $callback)
	{
		if (!$this->_bBound) {
			$this->bind();
		}
		$this->_bConnected = socket_listen($this->_resourceSocket);
		if (!$this->_bConnected) {
			$this->_exceptionError();
		}
		socket_set_nonblock($this->_resourceSocket);

		while(true) {
			$socketClient = @socket_accept($this->_resourceSocket);
			if (is_resource($socketClient)) {
				$client = new Chaplin_Socket_Listen_Client($socketClient);
				$callback($client);
				$this->_arrClients[] = $client;
			}

			foreach($this->_arrClients as $idxClient => $client) {
				$resClient = $client->getResource();
				if (@socket_recv($resClient, $string, 1024, MSG_DONTWAIT) === 0) {
					$client->invokeDisconnect();
					unset($this->_arrClients[$idxClient]);
					socket_close($resClient);
				} else {
					if (!empty($string)) {
						$string = trim($string);
						$client->invokeRead($string);
					}
				}
			}
		}
		socket_close($this->_resourceSocket);
	}
}
