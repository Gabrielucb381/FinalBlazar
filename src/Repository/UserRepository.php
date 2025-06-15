<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use App\Document\User;

class UserRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        parent::__construct($dm, $dm->getUnitOfWork(), $dm->getClassMetadata(User::class));
    }
    public function findAllUsers()
    {
        return $this->findAll();
    }

    public function findUserByEmail(string $email)
    {
        return $this->findOneBy(['email' => $email]);
    }
}