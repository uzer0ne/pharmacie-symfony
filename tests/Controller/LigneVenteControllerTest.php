<?php

namespace App\Tests\Controller;

use App\Entity\LigneVente;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LigneVenteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $ligneVenteRepository;
    private string $path = '/ligne/vente/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->ligneVenteRepository = $this->manager->getRepository(LigneVente::class);

        foreach ($this->ligneVenteRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('LigneVente index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'ligne_vente[quantite]' => 'Testing',
            'ligne_vente[prix_unitaire_vente]' => 'Testing',
            'ligne_vente[vente]' => 'Testing',
            'ligne_vente[produit]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->ligneVenteRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new LigneVente();
        $fixture->setQuantite('My Title');
        $fixture->setPrix_unitaire_vente('My Title');
        $fixture->setVente('My Title');
        $fixture->setProduit('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('LigneVente');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new LigneVente();
        $fixture->setQuantite('Value');
        $fixture->setPrix_unitaire_vente('Value');
        $fixture->setVente('Value');
        $fixture->setProduit('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'ligne_vente[quantite]' => 'Something New',
            'ligne_vente[prix_unitaire_vente]' => 'Something New',
            'ligne_vente[vente]' => 'Something New',
            'ligne_vente[produit]' => 'Something New',
        ]);

        self::assertResponseRedirects('/ligne/vente/');

        $fixture = $this->ligneVenteRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getQuantite());
        self::assertSame('Something New', $fixture[0]->getPrix_unitaire_vente());
        self::assertSame('Something New', $fixture[0]->getVente());
        self::assertSame('Something New', $fixture[0]->getProduit());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new LigneVente();
        $fixture->setQuantite('Value');
        $fixture->setPrix_unitaire_vente('Value');
        $fixture->setVente('Value');
        $fixture->setProduit('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/ligne/vente/');
        self::assertSame(0, $this->ligneVenteRepository->count([]));
    }
}
