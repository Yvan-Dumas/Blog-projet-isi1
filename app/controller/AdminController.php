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
        $stats = [
            'nbArticles' => $this->adminModel->getArticleCount(),
            'nbCommentaires' => $this->adminModel->getPendingCommentCount(),
            'nbUtilisateurs' => $this->adminModel->getActiveUserCount(),
        ];

        echo $this->twig->render('adminBoard.twig', [
            'titre_doc' => "Blog - AdminBoard",
            'titre_page' => 'Tableau de bord',
            'stats' => $stats
        ]);
    }

    public function activity(): void
    {
        $data = [
            'articles' => $this->adminModel->getLastArticles(),
            'comments' => $this->adminModel->getLastComments(),
            'users' => $this->adminModel->getLastUsers(),
        ];

        echo $this->twig->render('adminActivity.twig', [
            'titre_doc' => "Blog - Activité",
            'titre_page' => "Fil d'activité",
            'activity' => $data
        ]);
    }
}
