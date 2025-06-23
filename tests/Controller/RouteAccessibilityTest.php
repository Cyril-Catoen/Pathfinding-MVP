<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RouteAccessibilityTest extends WebTestCase
{
    public function testGuestPagesAreAccessible(): void
    {
        $client = static::createClient();

        $guestRoutes = [
            '/',
            '/login',
            '/register',
            '/contact-form',
            '/about',
        ];

        foreach ($guestRoutes as $route) {
            $client->request('GET', $route);
            $this->assertResponseStatusCodeSame(200, "La page $route devrait retourner un code 200 pour les visiteurs.");
        }
    }

    public function testGuest404PageReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/404');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserPagesRedirectToLoginWhenNotAuthenticated(): void
    {
        $client = static::createClient();

        $userRoutes = [
            '/user/dashboard',
            '/user/profile',
            '/user/discover',
            '/user/404',
        ];

        foreach ($userRoutes as $route) {
            $client->request('GET', $route);
            $this->assertResponseRedirects('/login', null, "La page $route doit rediriger vers /login si non connecté.");
        }
    }

    public function testAdminPagesRedirectToLoginWhenNotAuthenticated(): void
    {
        $client = static::createClient();

        $adminRoutes = [
            '/admin/dashboard',
            '/admin/404',
        ];

        foreach ($adminRoutes as $route) {
            $client->request('GET', $route);
            $this->assertResponseRedirects('/login', null, "La page $route doit rediriger vers /login si non connecté.");
        }
    }
}
