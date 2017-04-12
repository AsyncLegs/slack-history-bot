<?php

namespace Terekhov;

use MongoDB\BSON\Regex;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use React\EventLoop\Factory;
use Slack\RealTimeClient;
use MongoDB\Client;
use Slack\User;

class Application
{
    private $logger;
    private $mongoClient;

    /**
     * Application constructor.
     * @param Logger $logger
     * @param Client $mongoClient
     */
    public function __construct(Logger $logger, Client $mongoClient)
    {
        $this->logger = $logger;
        $this->mongoClient = $mongoClient;

    }


    public function run()
    {
        $collection = $this->mongoClient->slackbot->history;
        try {
            $loop = Factory::create();
            $client = new RealTimeClient($loop);
            $client->setToken(getenv('SLACK_API_TOKEN'));
            $client->connect();
            $this->logger->info('Slack Bot has been started');

            $client->on('message', function ($data) use ($client, $collection) {

                if (stripos($data['text'], '!showme') === 0) {
                    $client->getChannelGroupOrDMByID($data['channel'])->then(function ($channel) use ($client, $data, $collection) {

                        $text = str_ireplace('!showme ', '', $data['text']);

                        $regex = new Regex($text, 's');
                        $where = array('text' => $regex);

                        $cursor = $collection->find($where);


                        if (!empty($cursor)) {
                            foreach ($cursor as $message) {
                                $userName = 'test';
                                $client->getUserById($message->user)->then(function (User $user) use (&$userName) {
                                    $userName = $user->getRealName();
                                });
                                $dateTime = new \DateTime();
                                $dateTime->setTimestamp($message->ts);
                                $text = "{$dateTime->format('d-m-Y h:m:s')} : {$userName} : {$message->text}";
                                $preparedMessage = $client->getMessageBuilder()
                                    ->setText($text)
                                    ->setChannel($channel)
                                    ->create();
                                $client->postMessage($preparedMessage);
                            }
                        }
                    });

                } else {
                    $client->getChannelGroupOrDMByID($data['channel'])->then(function () use ($data, $collection) {
                        $result = $collection->insertOne(
                            [
                                'type' => $data['type'],
                                'channel' => $data['channel'],
                                'user' => $data['user'],
                                'text' => $data['text'],
                                'ts' => $data['ts'],
                                'source_team' => $data['source_team'],
                                'team' => $data['team']
                            ]
                        );
                        $inserted = "Inserted with Object ID '{$result->getInsertedId()}'";
                        $this->logger->info($inserted);
                    });
                }
            });
            $loop->run();
        } catch (\Exception $e) {
            $this->logger->error("A problem has been encountered: {$e->getMessage()}");

        }

    }


}