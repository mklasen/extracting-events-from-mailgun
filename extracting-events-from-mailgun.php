<?php

class Mailgun_Event_Extractor
{

    private $auth_context = [];
    private $subject = '';
    private $api_url = '';
    private $domain = '';
    private $event = '';

    private $item_count = 0;

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

        error_log('Got response, ' . count($items) . ' items');
        error_log('Last item from request: ' . $items[0]->id);

        if (!empty($items)) {
            $this->item_count = $this->item_count+count($items);
            $next_page = $response->paging->next;
            if($this->item_count < 500) {
                $this->request($next_page);
            }
        }

    }

    private function start()
    {
        $this->request("https://{$this->api_url}/v3/{$this->domain}/events?subject={$this->subject}&event={$this->event}");
    }
}

$extract = new Mailgun_Event_Extractor();
