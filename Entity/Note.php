<?php

namespace NShiell\MastermindNotes\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="note") */
class Note
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $body;
}