<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/21
 * Time: 22:44
 */
namespace Yaoli\Sendorder\Model;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ
{
    private static $configs = null;
    private static $instances = [];
    private $_host;
    private $_port;
    private $_user;
    private $_pass;
    private $_path;
    private $_name;
    private $_exchange = 'amq.direct';
    private $_consumer_tag = 'consumer';
    private $_quit = false;
    private $_type = 'direct';

    /**
     * @var AMQPConnection
     */
    private $_connection;

    /**
     * @var AMQPChannel
     */
    private $_channel;

    /**
     * RabbitMQ constructor.
     * @param string $uri
     * @param string $name
     * @param string $exchange
     */
    protected function __construct($uri, $name, $exchange = 'amq.direct', $type = 'direct')
    {
        $this->_name     = $name;
        $this->_exchange = $exchange;
        $this->_type     = $type;
        if (empty($this->_type))
        {
            $this->_type = 'direct';
        }

        $url = parse_url($uri);
        if (!is_array($url))
        {
            throw new \Exception("Unknow RabbitMQ connection uri {$uri}");
        }

        $this->_host = $url['host'];
        if (isset($url['port']))
        {
            $this->_port = $url['port'] < 100 ? 5672 : $url['port'];
        }
        else
        {
            $this->_port = 5672;
        }

        $this->_user = isset($url['user']) ? $url['user'] : 'guest';
        $this->_pass = isset($url['pass']) ? $url['pass'] : 'guest';
        $this->_path = isset($url['path']) ? substr($url['path'], 1) : 'yaoli_oa';

        $this->_connection = new AMQPStreamConnection($this->_host, $this->_port, $this->_user, $this->_pass, $this->_path);

        if (!$this->_connection->isConnected())
        {
            throw new \Exception("Cannot connect to the RabbitMQ {$this->_name}");
        }

        $this->_channel = $this->_connection->channel();

        $this->_channel->queue_declare($this->_name, false, true, false, false);

        $this->_channel->exchange_declare($this->_exchange, $this->_type, false, true, false);

        $this->_channel->queue_bind($this->_name, $exchange);
    }

    /**
     * RabbitMQ destructor
     */
    public function __destruct()
    {
        if ($this->_channel)
        {
            $ch = $this->_channel;
            $this->_channel = null;
            $ch->close();
        }

        if ($this->_connection)
        {
            $con = $this->_connection;
            $this->_connection = null;
            if ($con->isConnected())
            {
                $con->close();
            }
        }
    }

    /**
     * @param string $name
     * @param string $uri
     * @param string $exchange
     * @return static
     * @throws \Exception
     */
    public static function create($name, $uri = '', $exchange = 'amq.direct', $type = 'direct')
    {
        return static::createInstance($name, $uri, $exchange, $type);
    }

    /**
     * @param string $name
     * @param string $uri
     * @param string $exchange
     * @return static
     * @throws \Exception
     */
    public static function createInstance($name, $uri = '', $exchange = 'amq.direct', $type = 'direct')
    {
        if (empty($exchange))
        {
            $exchange = 'amq.direct';
        }

        if (empty($type))
        {
            $type = 'direct';
        }

        if (!isset(self::$instances[$name.'_'.$exchange.'_'.$type]))
        {
            if (!is_string($uri))
            {
                throw new \Exception('Rabbit mq uri must be a string type, but got '.gettype($uri));
            }

            $uri = trim($uri);
            if (empty($uri))
            {
                throw new \Exception('Rabbit mq uri was empty');
            }

            self::$instances[$name.'_'.$exchange.'_'.$type] = new static($uri, $name, $exchange, $type);
        }

        return self::$instances[$name.'_'.$exchange.'_'.$type];
    }

    /**
     * 发送消息
     * @param mixed $message
     * @return $this
     */
    public function publish($message, $delivery_mode = 2, $message_id = null)
    {
        $msg = new AMQPMessage(json_encode($message), ['content_type' => 'text/plain', 'delivery_mode' => $delivery_mode, 'message_id'=>empty($message_id) ? uniqid() : $message_id ]);
        $this->_channel->basic_publish($msg, $this->_exchange);
        return $this;
    }



    private $_runWithConsumer = false;
    private $_callback = null;

    /**
     * @return $this
     */
    public function quit()
    {
        $this->_quit = true;
        return $this;
    }

    /**
     * 以消费者模式运行消息队列
     * @param callback $callback     function (RabbitMQ $sender, $jsonMessage, \PhpAmqpLib\Message\AMQPMessage $msgObj)
     * @param int $timeout           等待超时时间（单位：秒)
     * @return $this
     */
    public function runWithConsumer($callback, $timeout = 2)
    {
        if ($this->_runWithConsumer)
        {
            return $this;
        }

        $this->_runWithConsumer = true;
        $this->_quit = false;
        $this->_callback = $callback;
        $startTime = time();

        $this->_channel->basic_consume($this->_name, $this->_consumer_tag, false, false, false, false, [$this, '__process_message']);

        // Loop as long as the channel has callbacks registered
        while (count($this->_channel->callbacks) > 0 && !$this->_quit)
        {
            $this->_channel->wait(null, true, $timeout < 0 ? 10 : $timeout);
            \usleep(10);

            if ($timeout > 0 && (time() - $startTime > $timeout))
            {
                //break;
            }
            elseif ($timeout < 0)
            {
                break;
            }
        }

        $this->_quit = true;
        $this->_runWithConsumer = false;
        return $this;
    }

    /**
     * @param \PhpAmqpLib\Message\AMQPMessage $msg
     */
    public function __process_message($msg)
    {
        //$tag = $msg->delivery_info['delivery_tag'];
        $json = @json_decode($msg->body, true);
        if (is_callable($this->_callback))
        {
            call_user_func($this->_callback, $this, $json, $msg);
        }
    }
}