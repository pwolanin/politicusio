<?php

namespace Politicusio\Entity;

class Vote extends DbEntity
{
    protected static $type = 'vote';

    /** 
     * The TYPE_* constants denote if a vote is an upvote or a downvote.
     */
    const TYPE_DOWNVOTE = 0;
    const TYPE_UPVOTE = 1;

    protected function __construct(array $record)
    {
        $this->record['fact_id'] = $record['fact_id'];
        $this->record['type'] = $record['type'];

        parent::__construct($record);
    }

    /**
     * {@inheritdoc}
     */
    protected function validate($record)
    {
        parent::validate($record);

        if (!in_array($record['type'], array(self::TYPE_DOWNVOTE, self::TYPE_UPVOTE))) {
            throw new VoteException("Type is not valid.");
        }
    }

    public function getFactId()
    {
        return $this->record['fact_id'];
    }

    public function getType()
    {
        return $this->record['type'];
    }

    public function asArray()
    {
        return array_merge(parent::asArray(), array(
                'fact_id' => $this->getFactId(),
                'type' => $this->getType(),
            )
        );
    }
}

class VoteException extends DbEntityException {}
