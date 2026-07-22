<?php

namespace App\Components\Http;

use Exception;
use App\Components\Utility\Data;
use Illuminate\Http\Client\Response;

class MicrosoftClient extends Client
{

    public function get(string $url = null): Response
    {
        // Preflight request
        try {
            $preflight = $this->preflight();
        } catch (\Exception $e) {
            throw new \Exception('Error trying to get remote file from source=' . $this->url, 400);
        }
        // Set additional options for next request
        $this->setOptions([
            'cookies' => $preflight['cookies'],
        ]);

        // File url
        $file = Data::searchArrayByKey($preflight['data'], 'download');
        if (empty($file)) {
            $file = Data::searchArrayByKey($preflight['data'], 'FileGetUrl');
        }
        if (empty($file)) {
            $file = Data::searchArrayByKey($preflight['data'], 'DocUrl');
        }
        if (empty($file)) {
            throw new Exception('Unable to get file from source');
        }

        // Return response
        return parent::get($file[0]);
    }

    protected function preflight(): array
    {
        // Set options
        $this->setOptions([
            'debug' => false,
            'allow_redirects' => true,
            'headers' => [
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.104 Safari/537.36',
                'accept' => '*/*',
                'cache-control' => 'no-cache'
            ]
        ]);
        // Make request
        $res = parent::get();
        if ($res->status() !== 200) {
            throw new Exception('Invalid preflight response');
        }
        // Get body contents
        $body = $res->getBody()->getContents();
        // Parse body
        $data = $this->parseBodyContents($body);
        // Return data
        return [
            'data' => $data,
            'cookies' => $res->cookies()
        ];
    }

    protected function parseBodyContents(string $data): array
    {
        // M365 Sharepoint
        if (str_contains($data, 'var _wopiContextJson')) {
            // Split string and known strings
            $data = explode('var _wopiContextJson =', $data)[1];
            $data = explode("var appName = 'Excel'", $data)[0];
            $data = explode("wopiContextFlushTime", $data)[0];
            // Trim string down
            $data = trim($data);
            $data = rtrim($data, ';');
            // Decode expected json text
            $data = json_decode($data, true);
            // Return
            return Data::toArrayRecursive($data);
        }

        // M365 One drive
        if (str_contains($data, 'var WacConfig=')){
            // Split string and known strings
            $data = explode('var WacConfig=', $data)[1];
            $data = explode('var __odsp_culture=', $data)[0];
            // Trim string down
            $data = rtrim($data, ';');
            // Decode expected json text
            $data = json_decode($data, true);
            // Return
            return Data::toArrayRecursive($data);
        }

        // Default
        return [];
    }
}
