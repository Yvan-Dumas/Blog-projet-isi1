<?php

require_once __DIR__ . '/../models/Admin.php';
class AdminController
{
    private \Twig\Environment $twig;
    private Admin $adminModel;
    public function __construct(\Twig\Environment $twig)
    {
        $this->twig = $twig;
        $this->adminModel = new Admin();
    }

    public function AdminBoard(): void
    {
        echo $this->twig->render('adminBoard.twig', [
            'titre_doc' => "Blog - AdminBoard",
            'titre_page' => 'AdminBoard',
        ]);
    }
}
