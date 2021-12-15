<?php

class Mailgun_Event_Extractor
{

    private $auth_context = [];
    private $subject = '';
    private $api_url = '';
    private $domain = '';
    private $event = '';

    private $item_count = 0;

    private $addresses = [];
    private $duplicates = [];

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

            $this->process_items($items);
            
            $this->item_count = $this->item_count+count($items);
            $next_page = $response->paging->next;
            if($this->item_count < 500) {
                $this->request($next_page);
            } else {
                $this->report();
            }
        }

    }

    private function start()
    {
        $this->request("https://{$this->api_url}/v3/{$this->domain}/events?subject={$this->subject}&event={$this->event}");
    }

    private function process_items($items)
    {
        foreach ($items as $item) {
            if (!in_array($item->recipient, $this->addresses, true)) {
                $this->addresses[] = $item->recipient;
            } else {
                $this->duplicates[] = $item->recipient;
            }
        }
    }

    private function report()
    {
        error_log('Addresses: ' . count($this->addresses));
        error_log('Duplicates: ' . count($this->duplicates));

        $duplicates_insight = array_filter(
            array_count_values($this->duplicates), function ($v) {
                return $v > 1; 
            }
        );

        error_log(print_r($duplicates_insight, true));

    }
}

$extract = new Mailgun_Event_Extractor();
