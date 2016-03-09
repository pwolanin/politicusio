<?php

namespace Politicusio\Entity;

class Fact extends DbEntity
{
    protected static $type = 'fact';

    /** 
     * The TYPE_* constants denote if a fact is negative or positive.
     */
    const TYPE_NEGATIVE = 0;
    const TYPE_POSITIVE = 1;

    protected function __construct(array $record)
    {
        $this->record['politician_id'] = $record['politician_id'];
        $this->record['title'] = $record['title'];
        $this->record['description'] = $record['description'];
        $this->record['type'] = $record['type'];

        parent::__construct($record);
    }

    /**
     * {@inheritdoc}
     */
    protected function validate($record)
    {
        parent::validate($record);
        
        if (!$this->isValidTitle($record['title'])) {
            throw new FactException("Title is not valid.");
        }

        if (!$this->isValidDescription($record['description'])) {
            throw new FactException("Description is not valid.");
        }

        if (!in_array($record['type'], array(self::TYPE_POSITIVE, self::TYPE_NEGATIVE))) {
            throw new FactException("Type is not valid.");
        }
    }

    public function getPoliticianId()
    {
        return $this->record['politician_id'];
    }

    public function getTitle()
    {
        return $this->record['title'];
    }

    public function getDescription()
    {
        return $this->record['description'];
    }

    public function getType()
    {
        return $this->record['type'];
    }

    public function asArray()
    {
        return array_merge(parent::asArray(), array(
                'politician_id' => $this->getPoliticianId(),
                'title' => $this->getTitle(),
                'description' => $this->getDescription(),
                'type' => $this->getType(),
            )
        );
    }

    /**
     * Check if the title is within the valid lenght.
     *
     * @param string $title
     *  The title to check.
     * @return bool
     */
    private function isValidTitle($title)
    {
        return true;
    }

    /**
     * Check if the description is within the valid lenght.
     *
     * @param string $description
     *  The description to check.
     * @return bool
     */
    private function isValidDescription($description)
    {
        return true;
    }
}

class FactException extends DbEntityException {}
