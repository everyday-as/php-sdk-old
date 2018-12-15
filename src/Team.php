<?php

namespace kanalumaddela\GmodStoreAPI;

class Team extends Model
{
    public static $endpoint = 'teams';

    public function fixRelations()
    {
        parent::fixRelations();

        if (isset($this->primaryAuthor->user)) {
            $this->primaryAuthor->user = (new User($this->primaryAuthor->user))->setClient($this->client)->with($this->withRelations)->forceExists();
        }

        return $this;
    }

    public function getUsers()
    {
        if (\is_null($users = $this->newRequest()->getUsers())) {
            $users = [];
        }

        if (($length = \count($users)) > 0) {
            for ($i = 0; $i < $length; $i++) {
                $users[$i]->user = (new User($users[$i]->user))->setClient($this->client)->with($this->withRelations)->forceExists()->fixRelations();
            }
        }

        $this->setRelation('users', $users);

        return $users;
    }
}
