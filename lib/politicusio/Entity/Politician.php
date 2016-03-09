<?php

namespace Politicusio\Entity;

class Politician extends DbEntity
{
    protected static $type = 'politician';

    protected function __construct(array $record)
    {
        $this->record['name'] = $record['name'];
        $this->record['wikipedia'] = $record['wikipedia'];
        $this->record['country_id'] = $record['country_id'];

        parent::__construct($record);
    }

    /**
     * {@inheritdoc}
     */
    protected function validate($record)
    {
        parent::validate($record);
        
        if (!$this->isValidName($record['name'])) {
            throw new PoliticianException("Name is not valid.");
        }

        if (!$this->isValidWikipediaLink($record['wikipedia'])) {
            throw new PoliticianException("Wikipedia link is not valid.");
        }

        if (!$this->isValidCountryId($record['country_id'])) {
            throw new PoliticianException("Country id is not valid.");
        }
    }

    public function getName()
    {
        return $this->record['name'];
    }

    public function getWikipedia()
    {
        return $this->record['wikipedia'];
    }

    public function getCountryId()
    {
        return $this->record['country_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function asArray()
    {
        return array_merge(parent::asArray(), array(
                'name' => $this->getName(),
                'wikipedia' => $this->getWikipedia(),
                'country_id' => $this->getCountryId(),
            )
        );
    }

    /**
     * Check if the name is within the valid lenght.
     *
     * @param string $name
     *  The name to check.
     * @return bool
     */
    private function isValidName($name)
    {
        // Names need to exist and be 50 characters max.
        $len = strlen($name);
        if ($len <= 0 || $len >= 50) {
            return false;
        }

        return true;
    }

    /**
     * Check if the wikipedia record property is an actual Wikipedia article.
     * First check is on the url format, so garbage gets out of the way before
     * making an http request.
     *
     * @param string $url
     *  The Wikipedia article url to check.
     * @return bool
     */
    private function isValidWikipediaLink($url)
    {
        // Is this a valid url?
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Wikipedia host?
        $parts = parse_url($url);
        $possible_domain = explode('.', $parts['host']);
        
        // $parts[2] should be 'org', while one of the others should be 'wikipedia'.
        if ('wikipedia' != $possible_domain[0] && 'wikipedia' != $possible_domain[1]) {
            return false;
        }

        return true;
    }

    /**
     * Check if the country is valid.
     *
     * @param int $country_id
     *  The country id to check.
     * @return bool
     */
    private function isValidCountryId($country_id)
    {
        // @todo: determine what checks need to be ran to validate country.
        // Check against a table? Check against an array? file?
        return true;
    }
}

class PoliticianException extends DbEntityException {}
