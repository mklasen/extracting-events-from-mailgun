<?php

class Mailgun_Event_Extractor
{

    private $auth_context = [];
    private $subject = '';
    private $api_url = '';
    private $domain = '';
    private $event = '';

    public function __construct()
    {
        $this->init();
        $this->start();
    }

    private function init()
    {
        include 'vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $this->subject = urlencode($_ENV['SUBJECT']);

        $auth = base64_encode("api:{$_ENV['MAILGUN_API_KEY']}");
        $this->auth_context = stream_context_create(
            [
            "http" => [
            "header" => "Authorization: Basic $auth"
            ]
            ]
        );

        $this->api_url = $_ENV['MAILGUN_API'];
        $this->domain = $_ENV['MAILGUN_DOMAIN'];
        $this->event = $_ENV['MAILGUN_EVENT'];
    }

    private function request($url)
    {
        $response_json = file_get_contents($url, false, $this->auth_context);

        $response = json_decode($response_json);

        $items = $response->items;

        if (!empty($items)) {
            var_dump('items is not empty, item count: ' . count($items));
        }

        // $next_page = $response->paging->next;

        // var_dump($response);
    }

    private function start()
    {
        $this->request("https://{$this->api_url}/v3/{$this->domain}/events?subject={$this->subject}&event={$this->event}");
    }
}

$extract = new Mailgun_Event_Extractor();
