<?php

/**
 * php reindex.php                  # Reindex all
 * PORTAL_ID=12345 php reindex.php  # Reindex per portal.
 */

/** @var App $app */

use go1\app\App;
use go1\clients\MqClient;
use go1\util\user\UserHelper;
use Symfony\Component\HttpFoundation\Request;

$appRoot = is_dir(__DIR__ . '/../../../../vendor') ? __DIR__ . '/../../../..' : '';
!$appRoot && die('Can not find application directory/');

$app = require "{$appRoot}/public/index.php";
$app = require "{$appRoot}/public/index.php";

/**
 * Process the messages directly without RabbitMQ.
 */
$app->extend('go1.client.mq', function () use ($app) {
    return new class ($app) extends MqClient
    {
        private $app;

        public function __construct(App $app)
        {
            $this->app = $app;
        }

        public function publish($body, string $routingKey, array $context = [])
        {
            echo "[$routingKey:$routingKey] " . print_r($body, true) . "\n";

            $req = Request::create('/consume?jwt=' . UserHelper::ROOT_JWT, 'POST');
            $req->request->replace(['routingKey' => $routingKey, 'body' => $body]);
            $this->app->handle($req);
        }
    };
});

$portalId = getenv('PORTAL_ID');
$url = '/reindex?jwt=' . UserHelper::ROOT_JWT;
$url .= $portalId ? "&portalId={$portalId}" : '';

$req = Request::create($url, 'POST');
$res = $app->handle($req);

echo "[RESPONSE] {$res->getStatusCode()} {$res->getContent()}\n";
