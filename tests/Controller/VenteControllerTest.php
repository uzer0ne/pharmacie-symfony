<?php

namespace App\Tests\Controller;

use App\Entity\Vente;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VenteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $venteRepository;
    private string $path = '/vente/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->venteRepository = $this->manager->getRepository(Vente::class);

        foreach ($this->venteRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Vente index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'vente[date_vente]' => 'Testing',
            'vente[montant_total]' => 'Testing',
            'vente[patient]' => 'Testing',
            'vente[ordonnance]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->venteRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Vente();
        $fixture->setDate_vente('My Title');
        $fixture->setMontant_total('My Title');
        $fixture->setPatient('My Title');
        $fixture->setOrdonnance('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Vente');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Vente();
        $fixture->setDate_vente('Value');
        $fixture->setMontant_total('Value');
        $fixture->setPatient('Value');
        $fixture->setOrdonnance('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'vente[date_vente]' => 'Something New',
            'vente[montant_total]' => 'Something New',
            'vente[patient]' => 'Something New',
            'vente[ordonnance]' => 'Something New',
        ]);

        self::assertResponseRedirects('/vente/');

        $fixture = $this->venteRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDate_vente());
        self::assertSame('Something New', $fixture[0]->getMontant_total());
        self::assertSame('Something New', $fixture[0]->getPatient());
        self::assertSame('Something New', $fixture[0]->getOrdonnance());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Vente();
        $fixture->setDate_vente('Value');
        $fixture->setMontant_total('Value');
        $fixture->setPatient('Value');
        $fixture->setOrdonnance('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/vente/');
        self::assertSame(0, $this->venteRepository->count([]));
    }
}
