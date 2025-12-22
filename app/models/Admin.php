<?php // app/models/Blog.php

require_once __DIR__ . '/../../config/Database.php';

class Admin
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }


}