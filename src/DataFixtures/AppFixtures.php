<?php

namespace App\DataFixtures;

use App\Entity\Conseil;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // --- USER --- 
        $user = new User(); 
        $user->setEmail('user@ecogarden.com'); 
        $user->setRoles(['ROLE_USER']); 
        $user->setCity('Paris'); 
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'User123!'); 
        $user->setPassword($hashedPassword); 
        $manager->persist($user);

        // --- ADMIN --- 
        $userAdmin = new User(); 
        $userAdmin->setEmail('admin@ecogarden.com'); 
        $userAdmin->setRoles(['ROLE_ADMIN']); 
        $userAdmin->setCity('Laon'); 
        $hashedPassword = $this->passwordHasher->hashPassword($userAdmin, 'Admin123!'); 
        $userAdmin->setPassword($hashedPassword); 
        $manager->persist($userAdmin);

        // --- CONSEILS --- 
        $conseilsData = [
            ['contenu' => 'Plantez vos bulbes de printemps en terre bien drainée.', 'mois' => [10, 11]],
            ['contenu' => 'Taillez vos rosiers pour favoriser une belle floraison.', 'mois' => [2, 3]],
            ['contenu' => 'Arrosez vos plantes tôt le matin pour limiter l\'évaporation.', 'mois' => [6, 7, 8]],
            ['contenu' => 'Récoltez vos courgettes régulièrement pour stimuler la production.', 'mois' => [6, 7, 8, 9]],
            ['contenu' => 'Protégez vos cultures du gel avec un voile d\'hivernage.', 'mois' => [11, 12, 1, 2]],
            ['contenu' => 'Semez vos tomates en intérieur avant les Saints de Glace.', 'mois' => [3, 4]],
            ['contenu' => 'Retournez votre compost pour l\'aérer.', 'mois' => [3, 9]],
            ['contenu' => 'Cueillez pommes et poires à maturité, stockez au frais.', 'mois' => [9, 10]],
            ['contenu' => 'Paillez le sol pour conserver l’humidité et limiter les mauvaises herbes.', 'mois' => [5, 6, 7, 8]],
            ['contenu' => 'Éclaircissez vos semis pour permettre aux plants de bien se développer.', 'mois' => [4, 5]],
            ['contenu' => 'Installez un récupérateur d’eau de pluie pour arroser durablement.', 'mois' => [3, 4, 5]],
            ['contenu' => 'Surveillez l’apparition des pucerons et utilisez du savon noir si nécessaire.', 'mois' => [4, 5, 6]],
            ['contenu' => 'Aérez la serre pour éviter l’excès d’humidité et les maladies.', 'mois' => [4, 5, 6, 7]],
            ['contenu' => 'Buttez les pommes de terre pour favoriser la production.', 'mois' => [5, 6]],
            ['contenu' => 'Taillez la lavande après la floraison pour la garder compacte.', 'mois' => [8, 9]],
            ['contenu' => 'Nettoyez vos outils de jardin pour éviter la propagation de maladies.', 'mois' => [10, 11, 12]],
            ['contenu' => 'Plantez les arbres fruitiers à racines nues.', 'mois' => [11, 12, 1]],
            ['contenu' => 'Semez les engrais verts pour enrichir naturellement le sol.', 'mois' => [9, 10]],
            ['contenu' => 'Protégez les jeunes plants du soleil avec un voile d’ombrage.', 'mois' => [7, 8]],
            ['contenu' => 'Divisez les vivaces pour les rajeunir et les multiplier.', 'mois' => [3, 4]],
            ['contenu' => 'Apportez du compost mûr pour nourrir le sol avant les plantations.', 'mois' => [2, 3]],
            ['contenu' => 'Installez des tuteurs pour soutenir les plantes hautes.', 'mois' => [4, 5, 6]],
            ['contenu' => 'Ramassez les feuilles mortes pour éviter les maladies cryptogamiques.', 'mois' => [10, 11]],
            ['contenu' => 'Installez des pièges à limaces pour protéger vos jeunes plants.', 'mois' => [4, 5, 6]]

        ];

        foreach ($conseilsData as $data) {
            $conseil = new Conseil();
            $conseil->setContenu($data['contenu']);
            $conseil->setMois($data['mois']);
            $manager->persist($conseil);
        }
        
        $manager->flush();
    }
}
