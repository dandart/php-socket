# PHP-Socket

OOP interface to PHP sockets with fluent interface.

## Installation

`composer require jolharg/php-socket`

## Examples

### Listening

#### TCP

##### HTTP

```php
<?php
namespace Whatever;

use Jolharg\Socket\Listen\Tcp;
use Jolharg\Socket\Listen\Client;

class MyClass
{
    public function whatever()
    {
        $listener = Tcp::create('0.0.0.0', 80);
        $listener->listen(function(Client $client) use ($listener) {
            $text = [];
            $client->onRead(function($strData) use ($client) {
                $arrHeaders = [
                     'HTTP/1.1 200 Hunky Dory',
                     'Content-Type: text/html',
                     'Connection: close'
                ];
                $strData = '<h1>You sent</h1><p>'.str_replace("\r\n", '<br/>', $strData);
                $client->write(implode("\r\n", $arrHeaders)."\r\n\r\n".$strData."\r\n\r\n");
                $client->disconnect();
                $client->onRead(function($strData) use ($client) {});
            });
        });
    }
}
```

##### Telnet-like

```php
<?php
namespace Whatever;

use Jolharg\Socket\Listen\Tcp;
use Jolharg\Socket\Listen\Client;

class MyClass
{
    public function whatever()
    {
        $listener = Tcp::create('0.0.0.0', 12345);

        $listener->listen(function(Client $client) use ($listener) {
            $listener->broadcast('New client coming online'.PHP_EOL);

            echo 'Client connected'.PHP_EOL;
            ob_flush();
            flush();
            $client->write('Hello!'.PHP_EOL)
            ->onRead(function($strData) use ($client) {
                echo 'Client message: ('.$strData.')'.PHP_EOL;
                ob_flush();
                flush();
                $client->write('Echo: '.$strData.PHP_EOL);
            })
            ->onDisconnect(function() use ($client) {
                echo 'Client disconnected'.PHP_EOL;
                ob_flush();
                flush();
            });
        });
    }
}
```

##### Telnet-like with broadcast support (TODO: make sense of this)

```php
<?php
namespace Whatever;

use Jolharg\Socket\Listen\Tcp;
use Jolharg\Socket\Listen\Client;
use Jolharg\Async;

class Whatever
{
    public function whatever()
    {
        $listener = Tcp::create('0.0.0.0', 12345);

        Client::setOnRead(function($strData, $socket) use ($listener) {
            echo 'Client message: ('.$strData.')'.PHP_EOL;
            if (0 === strpos($strData, 'PONG')) {
                echo 'Received a pong: ('.$strData.')'.PHP_EOL;
                ob_flush();
                flush();
            }
        });

        Client::setOnConnect(function($socket) use ($listener) {
            Async::setTimeout(function() use($socket) {
                echo 'Sending a ping'.PHP_EOL;
                ob_flush();
                flush();
                $socket->write('PING '.time().PHP_EOL);
                sleep(5);
            }, 5000);
        });

        Client::setOnDisconnect(function($socket) use ($listener) {
        });

        $listener->listen();
    }
}
```

#### UDP

```php
<?php
namespace Whatever;

use Jolharg\Socket\Listen\Udp;
use Jolharg\Socket\Listen\Client;

class MyClass
{
    public function whatever()
    {
        $listener = Udp::create('0.0.0.0', 1234);
        $listener->listen(function($strText, Closure $closureSend) {
            echo $strText.PHP_EOL;
            $closureSend('Echo: ('.$strText.')'.PHP_EOL);
            ob_flush();
            flush();
        });
    }
}
```

### Connecting

#### Demo IRC bot

```php
<?php
namespace Whatever;

use Jolharg\Socket\Connect\Tcp;

class Whatever
{
    public function whatever()
    {
        $vhost = 'my_vhost';
        $nick = 'TestBot';
        $id = 'Test';
        $desc = 'Test Bot';
        $host = 'irc.megworld.co.uk';
        $socket = Tcp::create($host, 6667)
            ->bind()
            ->connect()
            ->waitFor('/Found your hostname/')
            ->send('NICK '.$nick)
            ->send('USER '.$nick.' '.$vhost.' '.$id.' :'.$desc)
            ->waitFor('/376/')
            ->send('JOIN #bots')
            ->waitFor('/396/')
            ->waitFor('/Welcome/')
            ->send('PRIVMSG #bots :I am a bot. Tra la la la.')
            ->waitFor('/what now/')
            ->send('PRIVMSG #bots :I am leaving now.')
            ->send('QUIT :I\'m leaving now');
    }
}
```

#### Requesting a web page

```php
<?php
namespace Whatever;

use Jolharg\Socket\Connect\Tcp;

class Whatever
{
    public function whatever()
    {
        $socket = Tcp::create('jolharg.com', 80)
            ->bind()
            ->connect()
            ->send('GET / HTTP/1.1')
            ->send('Host: jolharg.com')
            ->send('');
        do {
            $response = $socket->readText(1024);
            echo $response;
            ob_flush();
        } while ('' !== $response);
        $socket->disconnect();
    }
}
```
