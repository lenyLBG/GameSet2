<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategorieFixtures extends Fixture
{
    // On définit une constante pour appeler cette référence ailleurs facilement
    public const CAT_SENIOR = 'categorie-senior';

    public function load(ObjectManager $manager): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('Senior');
        
        // On persiste l'objet
        $manager->persist($categorie);

        // ON CRÉE LA RÉFÉRENCE POUR L'UTILISER AILLEURS
        $this->addReference(self::CAT_SENIOR, $categorie);

        $manager->flush();
    }
}