<?php

namespace Politicusio\Entity;

use ORM;

abstract class DbEntity
{
    protected $record = null;
    protected static $type = null;

    /** 
     * The STATUS_* constants represent a given record's processing status.
     *  - STATUS_PENDING: The DbEntity has been created and is awaiting validation/confirmation.
     *  - STATUS_ACTIVE: The DbEntity has been processed and approved.
     *  - STATUS_ACTIVE: The DbEntity has been processed and rejected.
     */
    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_REJECTED = 2;

    const QUERY_LIMIT = 2000;
    const QUERY_OFFSET = 0;

    protected function __construct(array $record)
    {
        if (!static::$type) {
            throw new DbEntityException("Invalid type.");
        }

        $this->validate($record);

        // Save the keys one by one to avoid any kind of
        // polution (invalid keys are just dropped/not used).
        if (!empty($record['id'])) {
            $this->record['id'] = $record['id'];
        }
        $this->record['creator'] = $record['creator'];
        $this->record['created'] = $record['created'];
        $this->record['updated'] = $record['updated'];
        $this->record['status'] = $record['status'];

    }

    /**
     * Creates a new DbEntity Object from a valid record ready to save to the database.
     *
     * @param array $record
     *  A proper record array. Will go through validation.
     * @return \Politicusio\DbEntity
     *  An instantiated \Politicusio\DbEntity object.
     * @throws \Politicusio\DbEntityException
     *  When an id is included in the record, which would denote an existing DbEntity.
     */
    public static function create(array $record)
    {
        if (!empty($record['id'])) {
            throw new DbEntityException('Cannot create DbEntity with ID.');
        }

        return new static($record);
    }

    /**
     * Saves a DbEntity Object to the database.
     *
     * @return bool
     *  True on success and false on failure.
     */
    public function save()
    {
        // Choose an update or insert.
        $dbentity = !empty($this->record['id']) ? ORM::forTable(static::$type)->findOne($this->record['id']) : ORM::forTable(static::$type)->create();

        foreach ($this->record as $field => $value) {
            $dbentity->$field = $value;
        }

        if ($dbentity->save()) {
            $this->record['id'] = $dbentity->id;
            return true;
        }

        return false;
    }

    /**
     * Validates a record before instantiating a DbEntity object.
     * This method is meant to be extended on child classes to handle
     * validation on properties that are not common and handled here.
     *
     * @param array $record
     *  A record array.
     * @return null
     *  This method runs silently if there are no errors.
     * @throws \Politicusio\DbEntityException
     *  - If status is not one of STATUS_PENDING, STATUS_ACTIVE, STATUS_REJECTED.
     *  - If created or updated are future timestamps.
     */
    protected function validate($record)
    {
        if (!in_array($record['status'], array(self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_REJECTED))) {
            throw new DbEntityException("Status isn't valid.");
        }

        foreach (array('created', 'updated') as $time_type) {
            if ($record[$time_type] > time()) {
                throw new DbEntityException(ucfirst($time_type) . " time cannot be greater than now.");
            }
        }
    }

    /**
     * Retrieves a DbEntity by its database id (primary key).
     *
     * @param int $id
     *  The id to search the DbEntity record with.
     * @return \Politicusio\DbEntity
     *  An instantiated \Politicusio\DbEntity object.
     * @throws \Politicusio\DbEntityException
     *  - If the database table doesn't exist.
     *  - If there is no record with such id.
     */
    public static function fromId($id)
    {
        if (!static::$type) {
            throw new DbEntityException("Can't retrieve record without a type.");
        }

        $dbentity = ORM::forTable(static::$type)->findOne($id);
        
        if (!$dbentity) {
            throw new DbEntityException(ucfirst(static::$type) . " id {$id} does not exist.");
        }

        return new static($dbentity->asArray());
    }

    /**
     * Retrieves a set of DbEntity objects by query.
     *
     * @todo inlude usage examples.
     *
     * @param array $query
     *  The query to use to find the records.
     *  - int limit ex: 100
     *  - int offset ex: 50
     *  - array where
     *    - string field ex: 'created'
     *    - string op ex: '<'
     *    - string/int value ex: '1437065692'
     *  - array order_by ex: ('order_by' => array('field' => 'created', 'order' => 'DESC'))
     *    - string field ex: 'created'
     *    - string order ex: 'asc'
     * @return array
     *  An array of \Politicusio\DbEntity->asArray() records.
     * @throws \Politicusio\DbEntityException
     *  - If the database table doesn't exist.
     */
    public static function find($query = array())
    {
        if (!static::$type) {
            throw new DbEntityException("Can't retrieve record without a type.");
        }

        // Enforce a strict limit on records imposed by QUERY_LIMIT.
        $limit = !empty($query['limit']) &&  $query['limit'] <= self::QUERY_LIMIT ? $query['limit'] : self::QUERY_LIMIT;
        $offset = !empty($query['offset']) ? $query['offset'] : self::QUERY_OFFSET;
        $wheres = !empty($query['where']) ? $query['where'] : array();
        $sort_by = !empty($query['order_by']) ? $query['order_by'] : null;

        $results = array();
        $dbentities = ORM::forTable(static::$type)->limit($limit)->offset($offset);

        foreach ($wheres as $where) {
            $where_type = null;
            switch (strtoupper($where['op'])) {
                case '=':
                    $where_type = 'where_equal';
                    break;
                case '!=':
                    $where_type = 'where_not_equal';
                    break;
                case '<':
                    $where_type = 'where_lt';
                    break;
                case '<=':
                    $where_type = 'where_lte';
                    break;
                case '>':
                    $where_type = 'where_gt';
                    break;
                case '>=':
                    $where_type = 'where_gte';
                    break;
                case 'LIKE':
                    $where_type = 'where_like';
                    break;
                case 'NOT LIKE':
                    $where_type = 'where_not_like';
                    break;
            }

            if (!$where_type) {
                throw new DbEntityException("Where operation not supported: {$where['op']}.");
            }

            // Add the 'where' filter.
            $dbentities->$where_type($where['field'], $where['value']);
        }

        try {
            foreach ($dbentities->findMany() as $dbentity) {
                $dbentity = new static($dbentity->asArray());
                $results[] = $dbentity->asArray();
            }
        } catch (\PDOException $e) {
            throw new DbEntityException("Invalid query.");
        }
        
        // Order by in code to improve performance.
        if ($sort_by) {
            usort($results, function($a, $b) use ($sort_by)
                {
                    switch (strtoupper($sort_by['order'])) {
                        case 'DESC':
                            if ($a[$sort_by['field']] > $b[$sort_by['field']]) {
                                return -1;
                            } else if ($a[$sort_by['field']] < $b[$sort_by['field']]) {
                                return 1;
                            } else {
                                return 0;
                            }
                            break;
                        case 'ASC':
                            if ($a[$sort_by['field']] < $b[$sort_by['field']]) {
                                return -1;
                            } else if ($a[$sort_by['field']] > $b[$sort_by['field']]) {
                                return 1;
                            } else {
                                return 0;
                            }
                            break;
                        default:
                            throw new DbEntityException("Order by not supported: {$sort_by['order']}"); 
                    }
                }
            );
        }

        return $results;
    }

    /**
     * Getter method for the record id.
     *
     * @return int
     *  The record id.
     * @throws \Politicusio\DbEntityException
     *  If the object doesn't have an id yet (when is first created before saved to the database).
     */
    public function getId()
    {
        if (empty($this->record['id'])) {
            throw new DbEntityException("DbEntity 'id' is not set.");
        }
        return (int) $this->record['id'];
    }

    /**
     * Getter method for the record creator.
     *
     * @return string
     *  The record creator, either an email address or an email address hash.
     */
    public function getCreator()
    {
        return $this->record['creator'];
    }

    /**
     * Setter method for the record creator.
     *
     * @return string
     *  The record creator, either an email address or an email address hash.
     */
    public function setCreator($creator)
    {
        return $this->record['creator'] = $creator;
    }

    /**
     * Getter method for the record created time timestamp.
     *
     * @return int
     *  The record created time timestamp.
     */
    public function getCreated()
    {
        return (int) $this->record['created'];
    }

    /**
     * Getter method for the record updated time timestamp.
     *
     * @return int
     *  The record updated time timestamp.
     */
    public function getUpdated()
    {
        return $this->record['updated'];
    }

    /**
     * Setter method for the record updated time timestamp.
     *
     * @return int
     *  The record updated time timestamp.
     */
    public function setUpdated()
    {
        return $this->record['updated'] = (int) time();
    }

    /**
     * Getter method for the record status.
     *
     * @return int
     *  The record status.
     */
    public function getStatus()
    {
        return (int) $this->record['status'];
    }

    /**
     * Setter method for the record status.
     *
     * @return int
     *  The record status.
     */
    public function setStatus($status)
    {
        return $this->record['status'] = (int) $status;
    }

    /**
     * Formats the DbEntity onject into an array.
     *
     * @return array
     *  An array with the object's properties.
     */
    public function asArray()
    {
        return array(
            'id' => $this->getId(),
            'created' => $this->getCreated(),
            'updated' => $this->getUpdated(),
            'status' => $this->getStatus(),
        );
    }
}

class DbEntityException extends \Exception {}
