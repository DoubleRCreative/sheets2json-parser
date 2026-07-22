<?php

namespace App\Components\Http;

use App\Components\Document\Document;

class GoogleClient extends Client
{
    protected $baseUrl = 'https://docs.google.com/spreadsheets/d/';
    protected $jsonUrl = '/gviz/tq?tqx=out:json'; // Deprecated
    protected $csvUrl = '/export?format=csv';
    protected $sheetId;
    
    protected function getUrl()
    {
        // Get sheetID from provided url (source)
        $sheetID = $this->getSheetID();
        // If sheetID is not found
        if (empty($sheetID)) {
            throw new \Exception('Unable to find valid Worksheet ID', 400);
        }
        // Build endpoint and query
        $endpoint = $this->baseUrl . $sheetID . ($this->canGetAsCSV() ? $this->csvUrl : $this->jsonUrl);
        $query = $this->setQuery();
        // Return client url
        return $endpoint . $query;
    }

    /**
     * Set additional request query from options
     * 
     * @return string
     */
    protected function setQuery(): string
    {
        // Base query string
        $query = '';
        // GID query (worksheet ID)
        if ($this->hasGID()) {
            $query .= '&gid=' . $this->options[Document::OPTION_TARGET];
        }
        // Sheet query (worksheet name)
        if (!empty($this->options[Document::OPTION_TARGET])) {
            //throw new \Exception('Google sheets target needs to be the tab ID (gid), not the name', 400);
            $query .= '&sheet=' . $this->options[Document::OPTION_TARGET];
        }
        // Return query
        return $query;
    }

    protected function getSheetID(): ?string
    {
        if (empty($this->url) || empty($this->baseUrl)) {
            return null;
        }
        $sheetID = explode($this->baseUrl, $this->url)[1];
        $sheetID = explode('/edit', $sheetID)[0];
        return $sheetID ?? null;
    }

    protected function hasGID(): bool
    {
        if (!empty($this->options[Document::OPTION_TARGET])) {
            return is_numeric($this->options[Document::OPTION_TARGET]);
        }
        return false;
    }

    protected function canGetAsCSV(): bool
    {
        $query = $this->getQuery($this->url);
        if (!empty($query['gid'])) {
            $this->options[Document::OPTION_TARGET] = $query['gid'];
            return true;
        }

        if (empty($this->options[Document::OPTION_TARGET])) {
            return true;
        }

        if ($this->hasGID()) {
            return true;
        }

        return false;
    }
}
