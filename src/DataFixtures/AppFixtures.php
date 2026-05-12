<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Addresses;
use App\Entity\Cities;
use App\Entity\Countries;
use App\Entity\Association;
use App\Entity\User;
use App\Entity\Keywords;
use App\Entity\Pages;
use App\Entity\Tabs;
use App\Entity\Posts;
use App\Entity\Files;
use App\Entity\Postmeta;
use App\Entity\Resources;
use App\Entity\SubjectEmail;
use App\Entity\Links;
use App\Entity\Categories;
use App\Entity\Videos;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer des pays
        $countries = [];
        for ($i = 0; $i < 3; $i++) {
            $country = new Countries();
            $country->setName($faker->country());
            $manager->persist($country);
            $countries[] = $country;
        }

        // Créer des villes
        $cities = [];
        for ($i = 0; $i < 10; $i++) {
            $city = new Cities();
            $city->setName($faker->city());
            $city->setPostalcode($faker->postcode());
            $city->setAreaName($faker->citySuffix());
            $city->setDptName($faker->randomElement(['Hérault', 'Aude', 'Tarn', 'Gard']));
            $city->setCountry($faker->randomElement($countries));
            $manager->persist($city);
            $cities[] = $city;
        }

        // Créer des adresses
        $addresses = [];
        for ($i = 0; $i < 15; $i++) {
            $address = new Addresses();
            $address->setStreet($faker->streetAddress());
            $address->setLongitude($faker->longitude());
            $address->setLatitude($faker->latitude());
            $address->setCity($faker->randomElement($cities));
            $manager->persist($address);
            $addresses[] = $address;
        }

        // Créer des associations
        $associations = [];
        for ($i = 0; $i < 5; $i++) {
            $asso = new Association();
            $asso->setFoundedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 years')));
            $asso->setMantra($faker->catchPhrase());
            $asso->setBanner($faker->imageUrl());
            $asso->setLucrative($faker->boolean(50));
            $asso->setName($faker->company());
            $asso->setAcronyme($faker->lexify('??'));
            $asso->setLogo($faker->imageUrl());
            $asso->setDescription($faker->paragraph());
            $asso->setStatus($faker->randomElement(['active', 'inactive']));
            $asso->setMobile($faker->e164PhoneNumber());
            $asso->setEmail($faker->email());
            $asso->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 years')));
            $asso->setUpdateAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year')));
            $asso->setAddress($faker->randomElement($addresses));
            $manager->persist($asso);
            $associations[] = $asso;
        }

        // Créer des utilisateurs
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setFirstname($faker->firstName());
            $user->setLastname($faker->lastName());
            $user->setEmail($faker->unique()->safeEmail());

            $user->setLogin(strtolower($user->getFirstname() . '.' . $user->getLastname()));
            
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($faker->password(8, 16, true, true, true));
            $user->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-5 years')));
            $user->setLastConnection($faker->dateTimeBetween('-1 month'));
            $manager->persist($user);
            $users[] = $user;
        }

        // Créer des keywords
        $keywords = [];
        for ($i = 0; $i < 10; $i++) {
            $keyword = new Keywords();
            $keyword->setLabel($faker->word());
            $manager->persist($keyword);
            $keywords[] = $keyword;
        }

        // Créer des pages
        $pages = [];
        for ($i = 0; $i < 5; $i++) {
            $page = new Pages();
            $page->setName($faker->sentence(2));
            $page->setSlug($faker->slug());
            $page->setHome($faker->boolean(10));
            $manager->persist($page);
            $pages[] = $page;
        }

        // Créer des tabs
        $tabs = [];
        for ($i = 0; $i < 10; $i++) {
            $tab = new Tabs();
            $tab->setLabel($faker->word());
            $tab->setNewsFeed($faker->boolean(40));
            $tab->setSlug($faker->slug());
            $tab->setPages($faker->randomElement($pages));
            $manager->persist($tab);
            $tabs[] = $tab;
        }

        // Créer des posts
        $posts = [];
        for ($i = 0; $i < 50; $i++) {
            $post = new Posts();
            $post->setTitle($faker->sentence(5));
            $post->setContent($faker->paragraphs(3, true));
            $post->setSlug($faker->slug());

            // Date de publication
            $postedAtMutable = $faker->dateTimeBetween('-1 year');
            $post->setPostedAt(\DateTimeImmutable::createFromMutable($postedAtMutable));

            // Date de modification entre postedAt et maintenant
            $modifiedAtMutable = $faker->dateTimeBetween($postedAtMutable, 'now');
            $post->setModifiedAt(\DateTimeImmutable::createFromMutable($modifiedAtMutable));

            $post->setLikesCounter($faker->numberBetween(0, 500));
            $post->setStatus($faker->boolean(80));
            $post->setCommentCounter($faker->numberBetween(0, 10));
            $post->setTab($faker->randomElement($tabs));
            $post->setUser($faker->randomElement($users));

            // Ajout de keywords
            $numKeywords = $faker->numberBetween(0, 3);
            $postKeywords = $faker->randomElements($keywords, $numKeywords);
            foreach ($postKeywords as $keyword) {
                $post->addKeyword($keyword);
            }

            $manager->persist($post);
            $posts[] = $post;
        }

        // Créer des fichiers
        $files = [];
        for ($i = 0; $i < 10; $i++) {
            $file = new Files();
            $file->setLabel($faker->word());
            $manager->persist($file);
            $files[] = $file;
        }

        // Créer des postmetas
        for ($i = 0; $i < 10; $i++) {
            $postmeta = new Postmeta();
            $postmeta->setOriginalName($faker->word());
            // Ajout aléatoire de posts
            $numPosts = $faker->numberBetween(1, 3);
            $postMetaPosts = $faker->randomElements($posts, $numPosts);
            foreach ($postMetaPosts as $p) {
                $postmeta->addPost($p);
            }
            $manager->persist($postmeta);
        }

        // Créer des ressources
        for ($i = 0; $i < 10; $i++) {
            $res = new Resources();
            $res->setTitle($faker->sentence(4));
            $res->setAuthor($faker->name());
            $res->setDescription($faker->paragraph());
            $res->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 years')));
            $res->setIsbn($faker->isbn13());
            $res->setUrl($faker->url());
            $res->setUser($faker->randomElement($users));
            $manager->persist($res);
        }

        // Créer des  subjectEmails
        for ($i = 0; $i < 5; $i++) {
            $subEmail = new SubjectEmail();
            $subEmail->setLabel($faker->sentence(3));
            $subEmail->setAssociation($faker->randomElement($associations));
            $manager->persist($subEmail);
        }

        // Créer des links
        for ($i = 0; $i < 8; $i++) {
            $link = new Links();
            $link->setName($faker->domainName());
            $link->setUrl($faker->url());
            $link->setAssociation($faker->randomElement($associations));
            $manager->persist($link);
        }

        // Créer des Categories
        $categories = [];
        for ($i = 0; $i < 5; $i++) {
            $cat = new Categories();
            $cat->setName($faker->word());
            $manager->persist($cat);
            $categories[] = $cat;
        }

        // Assigner des categories aux posts
        foreach ($posts as $post) {
            $numCategories = $faker->numberBetween(1, 2);
            $postCategories = $faker->randomElements($categories, $numCategories);
            foreach ($postCategories as $category) {
                $post->addCategory($category);
            }
        }

        // Créer des videos
        for ($i = 0; $i < 10; $i++) {
            $video = new Videos();
            $video->setTitle($faker->sentence(3));
            $video->setDescription($faker->paragraph());
            $video->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year')));
            $video->setUrl($faker->url());
            $video->setOrigin($faker->domainName());
            $video->setUser($faker->randomElement($users));
            $manager->persist($video);
        }

        $manager->flush();
    }

}
