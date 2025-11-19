<?php
// data.php
function getData()
{
    // tableau associatif des données
    $data = array(
        'titre_doc' => 'Blog',
        'titre_page' => 'Bienvenue sur mon blog',
        'date' => date("d/m/Y"),
        // pour simplifier l'exemple, les données sont définies
// statiquement (généralement elles sont extraites d'une BD)
        'article' => array(
            array('id' => 1, 
                  'description' => 'je suis l\'article 1 ',
                  'titre' => 'Le titre de mon Article 1',
                  'date' => '2024-06-01',
                  'contenu' => 'Ceci est le contenu détaillé de mon article 1.'
                  ),

            array('id' => 2,
                  'description' => 'je suis l\'article 2',
                  'titre' => 'Le titre de mon Article 2',
                  'date' => '2024-06-02',
                  'contenu' => 'Ceci est le contenu détaillé de mon article 2.'
                ),

            array('id' => 3,
                  'description' => 'je suis l\'article 3',
                    'titre' => 'Le titre de mon Article 3',
                    'date' => '2024-06-03',
                    'contenu' => 'Ceci est le contenu détaillé de mon article 3.'
                ),

            array('id' => 4,
                  'description' => 'je suis l\'article 4',
                  'titre' => 'Le titre de mon Article 4',
                  'date' => '2024-06-04',
                  'contenu' => 'Ceci est le contenu détaillé de mon article 4.'
                )
        )
    );
    return $data;
}

function getDataContact()
{
    $data = array(
        'titre_doc' => 'Formulaire Contact',
        'titre_page' => 'Contactez nous'
    );
    return $data;
};
    
        