<?php

namespace Enniel\Ami\Commands;

use Clue\React\Ami\Protocol\Response;
use React\EventLoop\LoopInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Clue\React\Ami\Client;
use Enniel\Ami\Factory;
use Exception;

abstract class AmiAbstract extends Command
{
    protected $loop;

    protected $connector;

    protected $client;

    protected $config;

    protected $events;

    public function __construct(LoopInterface $loop, Factory $connector, array $config = [])
    {
        parent::__construct();
        $this->loop = $loop;
        $this->connector = $connector;
        $events = [];
        foreach (Arr::get($config, 'events', []) as $key => $value) {
            $key = mb_strtolower($key);
            $events[$key] = $value;
        }
        $this->events = $events;
        $this->config = $config;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = $this->connector->create($this->config);
        $client->then([$this, 'client'], [$this, 'writeException']);
        $this->loop->run();
    }

    public function client(Client $client)
    {
        $this->client = $client;
        $this->client->on('error', [$this, 'writeException']);
    }

    public function writeException(Exception $e)
    {
        $this->warn($e->getMessage());
        $this->stop();
    }

    public function writeResponse(Response $response)
    {
        $message = Arr::get($response->getFields(), 'Message', null);
        $this->line($message);
        $this->stop();
    }

    public function request($action, array $options = [])
    {
        return $this->client->request($this->client->createAction($action, $options));
    }

    public function stop()
    {
        $this->loop->stop();

        return false;
    }
}
