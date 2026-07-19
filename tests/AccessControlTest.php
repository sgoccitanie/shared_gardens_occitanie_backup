<?php

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessControlTest extends WebTestCase
{
    /**
     * @dataProvider provideAccess
     */
    public function testAccess(string $url, ?string $role, int $expected): void
    {
        $client = static::createClient();

        if ($role !== null) {
            $client->loginUser($this->getUserWithRole($role));
        }

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame($expected);
    }

    public static function provideAccess(): iterable
    {
        // Profils testés : libellé => rôle (null = visiteur anonyme)
        $roles = [
            'anon'   => null,
            'editor' => 'ROLE_EDITOR',
            'admin'  => 'ROLE_ADMIN',
        ];

        // Niveau d'accès => statut HTTP attendu pour chaque profil
        $tiers = [
            // 302 = redirection vers page login (visiteur anonyme)
            // 403 = accès refusé (visiteur authentifié mais pas autorisé)
            // 200 = accès autorisé
            // 404 = page non trouvée (ex: URL inexistante)
            // 500 = erreur serveur (ex: page non trouvée)
            'public'       => ['anon' => 200, 'editor' => 200, 'admin' => 200],
            'editor_admin' => ['anon' => 302, 'editor' => 200, 'admin' => 200],
            'admin_only'   => ['anon' => 302, 'editor' => 403, 'admin' => 200],
            'auth_pages'   => ['anon' => 200, 'editor' => 302, 'admin' => 302],
        ];

        // Chaque route rangée dans son niveau (une URL représentative par groupe *)
        $routes = [
            '/register'             => 'admin_only',
            '/admin/user'           => 'admin_only',
            '/admin/presentation'   => 'admin_only',
            '/forget-password'      => 'public',

            '/admin'               => 'editor_admin',
            '/admin/profile'       => 'editor_admin',
            '/admin/addresses'     => 'editor_admin',
            '/admin/association'   => 'editor_admin',
            '/admin/categories'    => 'editor_admin',
            '/admin/comment'       => 'editor_admin',
            '/admin/keywords'      => 'editor_admin',
            '/admin/links'         => 'editor_admin',
            '/admin/posts'         => 'editor_admin',
            '/admin/subject-email' => 'editor_admin',
            '/admin/tabs'          => 'editor_admin',

            '/login'                => 'auth_pages',
            '/resources'            => 'public',
        ];

        foreach ($routes as $url => $tier) {
            foreach ($roles as $label => $role) {
                yield sprintf('%s → %s', $label, $url) => [$url, $role, $tiers[$tier][$label]];
            }
        }
    }

    private function getUserWithRole(string $role): User
    {
        $user = new User();
        $user->setEmail('test_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setIsVerified(true);
        $user->setLogin('test_' . uniqid());   // login : unique lui aussi ? → uniqid le gère
        $user->setRoles([$role]);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setToken(null);                  // ← nullable, on ne s'embête pas
        $user->setTokenExpirateAt(new \DateTimeImmutable('+1 day'));
        $user->setUserAsso(null);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
